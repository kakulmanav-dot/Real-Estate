import { useCallback, useEffect, useState } from "react";
import NavBar from "../components/NavBar";
import Footer from "../components/Footer";
import PropertyCard from "../components/PropertyCard";
import PageLoader from "../components/ui/PageLoader";
import EmptyState from "../components/ui/EmptyState";
import ErrorState from "../components/ui/ErrorState";
import Pagination from "../components/ui/Pagination";
import { favoritesApi } from "../api/properties";
import { Link } from "react-router-dom";

export default function SavedPropertiesPage() {
  const [properties, setProperties] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async (page = 1) => {
    setLoading(true);
    setError(null);
    try {
      const response = await favoritesApi.list({ page });
      setProperties(response.data);
      setMeta(response.meta);
    } catch (err) {
      setError(err.message || "Unable to load saved properties.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div className="min-h-screen bg-gray-50">
      <NavBar solid />
      <div className="max-w-7xl mx-auto px-6 py-10">
        <h1 className="text-3xl font-bold mb-6">Saved Properties</h1>

        {loading && <PageLoader />}
        {!loading && error && <ErrorState message={error} onRetry={() => load(meta.current_page)} />}
        {!loading && !error && properties.length === 0 && (
          <EmptyState
            title="No saved properties yet"
            message="Browse our listings and save the ones you like."
            action={
              <Link to="/properties" className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Browse Properties
              </Link>
            }
          />
        )}
        {!loading && !error && properties.length > 0 && (
          <>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {properties.map((property) => (
                <PropertyCard key={property.id} property={property} />
              ))}
            </div>
            <Pagination currentPage={meta.current_page} lastPage={meta.last_page} onPageChange={load} />
          </>
        )}
      </div>
      <Footer />
    </div>
  );
}
