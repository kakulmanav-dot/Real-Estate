import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { MemoryRouter } from "react-router-dom";
import RegisterPage from "./RegisterPage";
import { AuthProvider } from "../context/AuthContext";
import { authApi } from "../api/auth";

vi.mock("../api/auth", () => ({
  authApi: { register: vi.fn(), me: vi.fn(), logout: vi.fn() },
}));

function renderPage() {
  return render(
    <MemoryRouter>
      <AuthProvider>
        <RegisterPage />
      </AuthProvider>
    </MemoryRouter>
  );
}

describe("RegisterPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("submits the registration form and persists the token", async () => {
    authApi.register.mockResolvedValue({
      user: { id: 2, name: "New User", email: "new@example.com", role: "user" },
      token: "new-token",
    });

    renderPage();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/full name/i), "New User");
    await user.type(screen.getByLabelText(/^email$/i), "new@example.com");
    await user.type(screen.getByLabelText(/^password$/i), "Password1");
    await user.type(screen.getByLabelText(/confirm password/i), "Password1");
    await user.click(screen.getByRole("button", { name: /sign up/i }));

    await waitFor(() => expect(authApi.register).toHaveBeenCalled());
    await waitFor(() => expect(localStorage.getItem("auth_token")).toBe("new-token"));
  });

  it("shows validation errors returned by the API", async () => {
    authApi.register.mockRejectedValue({
      status: 422,
      message: "The given data was invalid.",
      errors: { email: ["The email has already been taken."] },
    });

    renderPage();
    const user = userEvent.setup();

    await user.type(screen.getByLabelText(/full name/i), "New User");
    await user.type(screen.getByLabelText(/^email$/i), "taken@example.com");
    await user.type(screen.getByLabelText(/^password$/i), "Password1");
    await user.type(screen.getByLabelText(/confirm password/i), "Password1");
    await user.click(screen.getByRole("button", { name: /sign up/i }));

    expect(await screen.findByText(/already been taken/i)).toBeInTheDocument();
  });
});
