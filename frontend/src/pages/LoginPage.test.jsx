import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { MemoryRouter } from "react-router-dom";
import LoginPage from "./LoginPage";
import { AuthProvider } from "../context/AuthContext";
import { authApi } from "../api/auth";

vi.mock("../api/auth", () => ({
  authApi: {
    login: vi.fn(),
    me: vi.fn(),
    logout: vi.fn(),
  },
}));

function renderLoginPage() {
  return render(
    <MemoryRouter>
      <AuthProvider>
        <LoginPage />
      </AuthProvider>
    </MemoryRouter>
  );
}

describe("LoginPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("renders email and password fields", () => {
    renderLoginPage();

    expect(screen.getByText(/sign in to your account/i)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /sign in/i })).toBeInTheDocument();
  });

  it("submits credentials and stores the token on success", async () => {
    authApi.login.mockResolvedValue({
      user: { id: 1, name: "Jane", email: "jane@example.com", role: "user" },
      token: "fake-token",
    });

    renderLoginPage();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/email/i), "jane@example.com");
    await user.type(screen.getByLabelText(/password/i), "Password1");
    await user.click(screen.getByRole("button", { name: /sign in/i }));

    await waitFor(() => {
      expect(authApi.login).toHaveBeenCalledWith({ email: "jane@example.com", password: "Password1" });
    });
    await waitFor(() => {
      expect(localStorage.getItem("auth_token")).toBe("fake-token");
    });
  });

  it("shows a validation error message when login fails", async () => {
    authApi.login.mockRejectedValue({
      status: 422,
      message: "The provided credentials are incorrect.",
      errors: { email: ["The provided credentials are incorrect."] },
    });

    renderLoginPage();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/email/i), "jane@example.com");
    await user.type(screen.getByLabelText(/password/i), "WrongPassword");
    await user.click(screen.getByRole("button", { name: /sign in/i }));

    expect(await screen.findByText(/the provided credentials are incorrect/i)).toBeInTheDocument();
    expect(localStorage.getItem("auth_token")).toBeNull();
  });
});
