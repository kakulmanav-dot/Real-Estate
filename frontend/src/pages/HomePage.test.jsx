import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import HomePage from "./HomePage";
import { homeApi } from "../api/content";
import { AuthProvider } from "../context/AuthContext";

vi.mock("../api/content", () => ({
  homeApi: { get: vi.fn() },
  enquiriesApi: { create: vi.fn() },
}));

vi.mock("../api/auth", () => ({
  authApi: { me: vi.fn(), login: vi.fn(), logout: vi.fn() },
}));

function renderHome() {
  return render(
    <MemoryRouter>
      <AuthProvider>
        <HomePage />
      </AuthProvider>
    </MemoryRouter>
  );
}

describe("HomePage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  it("renders featured properties and testimonials once the API resolves", async () => {
    homeApi.get.mockResolvedValue({
      settings: { hero_title: "Find Your Dream Home" },
      featured_properties: [
        {
          id: 1,
          slug: "villa",
          title: "Test Villa",
          city: "Austin",
          purpose: "sale",
          price: 400000,
          currency: "USD",
          bedrooms: 3,
          bathrooms: 2,
          area: 1500,
          area_unit: "sqft",
          featured: true,
          cover_image_url: null,
        },
      ],
      testimonials: [
        { id: 1, name: "Happy Client", designation: "CEO", text: "Great service!", rating: 5, image_url: null },
      ],
    });

    renderHome();

    expect(await screen.findByText("Find Your Dream Home")).toBeInTheDocument();
    expect(await screen.findByText("Test Villa")).toBeInTheDocument();
    expect(await screen.findByText("Happy Client")).toBeInTheDocument();
  });

  it("shows fallback hero copy before the API responds", () => {
    homeApi.get.mockReturnValue(new Promise(() => {})); // never resolves during this test

    renderHome();

    expect(screen.getByText("Explore Home that fits your dreams")).toBeInTheDocument();
  });

  it("shows a non-blocking notice when the homepage API call fails", async () => {
    homeApi.get.mockRejectedValue(new Error("Network Error"));

    renderHome();

    expect(await screen.findByText(/couldn't reach our listings service/i)).toBeInTheDocument();
    // The page must still render its static hero rather than crash.
    expect(screen.getByText("Explore Home that fits your dreams")).toBeInTheDocument();
  });
});
