import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import { MemoryRouter, Routes, Route } from "react-router-dom";
import ProtectedRoute from "./ProtectedRoute";
import AdminRoute from "./AdminRoute";
import { AuthProvider } from "../context/AuthContext";
import { authApi } from "../api/auth";

vi.mock("../api/auth", () => ({
  authApi: { login: vi.fn(), me: vi.fn(), logout: vi.fn() },
}));

function renderWithRoute({ initialEntries, guarded }) {
  return render(
    <MemoryRouter initialEntries={initialEntries}>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<div>Login Page</div>} />
          <Route path="/" element={<div>Home Page</div>} />
          <Route element={guarded}>
            <Route path="/protected" element={<div>Protected Content</div>} />
          </Route>
        </Routes>
      </AuthProvider>
    </MemoryRouter>
  );
}

describe("ProtectedRoute", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("redirects guests to /login", async () => {
    renderWithRoute({ initialEntries: ["/protected"], guarded: <ProtectedRoute /> });

    await waitFor(() => expect(screen.getByText("Login Page")).toBeInTheDocument());
  });

  it("renders the protected content for an authenticated user", async () => {
    localStorage.setItem("auth_token", "token");
    authApi.me.mockResolvedValue({ id: 1, name: "User", role: "user" });

    renderWithRoute({ initialEntries: ["/protected"], guarded: <ProtectedRoute /> });

    await waitFor(() => expect(screen.getByText("Protected Content")).toBeInTheDocument());
  });
});

describe("AdminRoute", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("redirects guests to /login", async () => {
    renderWithRoute({ initialEntries: ["/protected"], guarded: <AdminRoute /> });

    await waitFor(() => expect(screen.getByText("Login Page")).toBeInTheDocument());
  });

  it("redirects a non-admin authenticated user to home", async () => {
    localStorage.setItem("auth_token", "token");
    authApi.me.mockResolvedValue({ id: 1, name: "Regular User", role: "user" });

    renderWithRoute({ initialEntries: ["/protected"], guarded: <AdminRoute /> });

    await waitFor(() => expect(screen.getByText("Home Page")).toBeInTheDocument());
  });

  it("renders admin content for an admin user", async () => {
    localStorage.setItem("auth_token", "token");
    authApi.me.mockResolvedValue({ id: 1, name: "Admin User", role: "admin" });

    renderWithRoute({ initialEntries: ["/protected"], guarded: <AdminRoute /> });

    await waitFor(() => expect(screen.getByText("Protected Content")).toBeInTheDocument());
  });
});
