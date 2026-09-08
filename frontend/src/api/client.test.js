import { describe, it, expect, vi, beforeEach } from "vitest";
import { normalizeError } from "./client";

describe("normalizeError", () => {
  it("extracts message and errors from a validation response", () => {
    const error = {
      response: {
        status: 422,
        data: { message: "The given data was invalid.", errors: { email: ["Required"] } },
      },
    };

    const result = normalizeError(error);

    expect(result.status).toBe(422);
    expect(result.message).toBe("The given data was invalid.");
    expect(result.errors).toEqual({ email: ["Required"] });
    expect(result.isNetworkError).toBe(false);
  });

  it("falls back to a generic message when there is no response", () => {
    const error = { message: "Network Error" };

    const result = normalizeError(error);

    expect(result.status).toBe(0);
    expect(result.isNetworkError).toBe(true);
    expect(result.message).toBe("Network Error");
  });

  it("falls back to a default message when nothing is provided", () => {
    const result = normalizeError({});

    expect(result.message).toBe("Something went wrong. Please try again.");
    expect(result.errors).toEqual({});
  });
});

describe("apiClient base URL", () => {
  beforeEach(() => {
    vi.resetModules();
  });

  it("uses VITE_API_BASE_URL when set", async () => {
    vi.stubEnv("VITE_API_BASE_URL", "https://api.example.com/api/v1");
    const { default: client } = await import("./client");
    expect(client.defaults.baseURL).toBe("https://api.example.com/api/v1");
    vi.unstubAllEnvs();
  });
});
