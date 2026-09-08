import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import { AuthProvider, useAuth } from "./AuthContext";
import { authApi } from "../api/auth";

vi.mock("../api/auth", () => ({
  authApi: { login: vi.fn(), me: vi.fn(), logout: vi.fn() },
}));

function Probe() {
  const { user, isAuthenticated, isAdmin, loading } = useAuth();
  if (loading) return <div>loading</div>;
  return (
    <div>
      <span data-testid="authenticated">{String(isAuthenticated)}</span>
      <span data-testid="admin">{String(isAdmin)}</span>
      <span data-testid="name">{user?.name ?? "none"}</span>
    </div>
  );
}

describe("AuthProvider", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("starts unauthenticated when there is no stored token", async () => {
    render(
      <AuthProvider>
        <Probe />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId("authenticated").textContent).toBe("false"));
    expect(authApi.me).not.toHaveBeenCalled();
  });

  it("restores the session from a stored token on mount", async () => {
    localStorage.setItem("auth_token", "existing-token");
    authApi.me.mockResolvedValue({ id: 1, name: "Restored User", role: "admin" });

    render(
      <AuthProvider>
        <Probe />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId("name").textContent).toBe("Restored User"));
    expect(screen.getByTestId("admin").textContent).toBe("true");
  });

  it("clears the session when the stored token is invalid", async () => {
    localStorage.setItem("auth_token", "stale-token");
    authApi.me.mockRejectedValue({ status: 401 });

    render(
      <AuthProvider>
        <Probe />
      </AuthProvider>
    );

    await waitFor(() => expect(screen.getByTestId("authenticated").textContent).toBe("false"));
    expect(localStorage.getItem("auth_token")).toBeNull();
  });
});
