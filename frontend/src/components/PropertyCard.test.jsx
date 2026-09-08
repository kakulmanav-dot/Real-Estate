import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router-dom";
import PropertyCard from "./PropertyCard";

const baseProperty = {
  id: 1,
  slug: "ocean-view-villa",
  title: "Ocean View Villa",
  city: "Miami",
  state: "FL",
  purpose: "sale",
  price: 500000,
  currency: "USD",
  bedrooms: 3,
  bathrooms: 2,
  area: 1800,
  area_unit: "sqft",
  featured: true,
  cover_image_url: "https://example.com/cover.jpg",
};

function renderCard(overrides = {}) {
  return render(
    <MemoryRouter>
      <PropertyCard property={{ ...baseProperty, ...overrides }} />
    </MemoryRouter>
  );
}

describe("PropertyCard", () => {
  it("renders the title, city, and formatted price", () => {
    renderCard();

    expect(screen.getByText("Ocean View Villa")).toBeInTheDocument();
    expect(screen.getByText(/Miami, FL/)).toBeInTheDocument();
    expect(screen.getByText("$500,000")).toBeInTheDocument();
  });

  it("shows a featured badge when featured", () => {
    renderCard({ featured: true });

    expect(screen.getByText("Featured")).toBeInTheDocument();
  });

  it("does not show a featured badge when not featured", () => {
    renderCard({ featured: false });

    expect(screen.queryByText("Featured")).not.toBeInTheDocument();
  });

  it("appends the price period for rentals", () => {
    renderCard({ purpose: "rent", price_period: "month" });

    expect(screen.getByText("$500,000/month")).toBeInTheDocument();
  });

  it("links to the property details page by slug", () => {
    renderCard();

    expect(screen.getByRole("link")).toHaveAttribute("href", "/properties/ocean-view-villa");
  });

  it("shows a placeholder when there is no cover image", () => {
    renderCard({ cover_image_url: null });

    expect(screen.getByText("No Image")).toBeInTheDocument();
  });
});
