import { useCallback, useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import NavBar from "../components/NavBar";
import Footer from "../components/Footer";
import PropertyCard from "../components/PropertyCard";
import PageLoader from "../components/ui/PageLoader";
import EmptyState from "../components/ui/EmptyState";
import ErrorState from "../components/ui/ErrorState";
import Pagination from "../components/ui/Pagination";
import { propertiesApi } from "../api/properties";

const PROPERTY_TYPES = ["Apartment", "Villa", "Townhouse", "Penthouse", "Studio"];

export default function PropertiesPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [properties, setProperties] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [filters, setFilters] = useState({
    search: searchParams.get("search") || "",
    purpose: searchParams.get("purpose") || "",
    property_type: searchParams.get("property_type") || "",
    city: searchParams.get("city") || "",
    bedrooms: searchParams.get("bedrooms") || "",
    min_price: searchParams.get("min_price") || "",
    max_price: searchParams.get("max_price") || "",
    sort: searchParams.get("sort") || "newest",
  });

  const page = Number(searchParams.get("page") || 1);

  const fetchProperties = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ""));
      params.page = page;
      const response = await propertiesApi.list(params);
      setProperties(response.data);
      setMeta(response.meta);
    } catch (err) {
      setError(err.message || "Unable to load properties.");
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams]);

  useEffect(() => {
    fetchProperties();
  }, [fetchProperties]);

  const applyFilters = (e) => {
    e?.preventDefault();
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ""));
    setSearchParams(params);
  };

  const resetFilters = () => {
    setFilters({
      search: "",
      purpose: "",
      property_type: "",
      city: "",
      bedrooms: "",
      min_price: "",
      max_price: "",
      sort: "newest",
    });
    setSearchParams({});
  };

  const goToPage = (newPage) => {
    const params = Object.fromEntries(searchParams);
    setSearchParams({ ...params, page: newPage });
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <NavBar solid />

      <div className="max-w-7xl mx-auto px-6 py-10">
        <h1 className="text-3xl font-bold mb-6">Explore Properties</h1>

        <form onSubmit={applyFilters} className="bg-white rounded-lg shadow p-4 mb-8 grid grid-cols-1 md:grid-cols-4 gap-4">
          <input
            type="text"
            placeholder="Search by title, city, or reference..."
            className="border border-gray-300 rounded py-2 px-3 md:col-span-2"
            value={filters.search}
            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
          />
          <select
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.purpose}
            onChange={(e) => setFilters({ ...filters, purpose: e.target.value })}
          >
            <option value="">Any purpose</option>
            <option value="sale">For Sale</option>
            <option value="rent">For Rent</option>
          </select>
          <select
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.property_type}
            onChange={(e) => setFilters({ ...filters, property_type: e.target.value })}
          >
            <option value="">Any type</option>
            {PROPERTY_TYPES.map((type) => (
              <option key={type} value={type}>
                {type}
              </option>
            ))}
          </select>
          <input
            type="text"
            placeholder="City"
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.city}
            onChange={(e) => setFilters({ ...filters, city: e.target.value })}
          />
          <select
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.bedrooms}
            onChange={(e) => setFilters({ ...filters, bedrooms: e.target.value })}
          >
            <option value="">Any bedrooms</option>
            {[1, 2, 3, 4, 5].map((n) => (
              <option key={n} value={n}>
                {n}+ bed
              </option>
            ))}
          </select>
          <input
            type="number"
            placeholder="Min price"
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.min_price}
            onChange={(e) => setFilters({ ...filters, min_price: e.target.value })}
          />
          <input
            type="number"
            placeholder="Max price"
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.max_price}
            onChange={(e) => setFilters({ ...filters, max_price: e.target.value })}
          />
          <select
            className="border border-gray-300 rounded py-2 px-3"
            value={filters.sort}
            onChange={(e) => setFilters({ ...filters, sort: e.target.value })}
          >
            <option value="newest">Newest</option>
            <option value="price_asc">Price: Low to High</option>
            <option value="price_desc">Price: High to Low</option>
          </select>

          <div className="flex gap-3 md:col-span-4">
            <button type="submit" className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
              Apply Filters
            </button>
            <button
              type="button"
              onClick={resetFilters}
              className="border border-gray-300 text-gray-600 px-6 py-2 rounded hover:bg-gray-50"
            >
              Reset
            </button>
          </div>
        </form>

        {loading && <PageLoader label="Loading properties..." />}
        {!loading && error && <ErrorState message={error} onRetry={fetchProperties} />}
        {!loading && !error && properties.length === 0 && (
          <EmptyState title="No properties found" message="Try adjusting your search filters." />
        )}
        {!loading && !error && properties.length > 0 && (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {properties.map((property) => (
                <PropertyCard key={property.id} property={property} />
              ))}
            </div>
            <Pagination currentPage={meta.current_page} lastPage={meta.last_page} onPageChange={goToPage} />
          </>
        )}
      </div>

      <Footer />
    </div>
  );
}
