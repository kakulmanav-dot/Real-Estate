import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import PageLoader from "../../components/ui/PageLoader";
import ErrorState from "../../components/ui/ErrorState";
import { adminDashboardApi } from "../../api/admin";

const STAT_LABELS = {
  total_properties: "Total Properties",
  published_properties: "Published",
  draft_properties: "Drafts",
  featured_properties: "Featured",
  sold_properties: "Sold",
  rented_properties: "Rented",
  total_enquiries: "Total Enquiries",
  new_enquiries: "New Enquiries",
  total_users: "Total Users",
};

export default function AdminDashboardPage() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(() => {
    setLoading(true);
    setError(null);
    adminDashboardApi
      .get()
      .then(setData)
      .catch((err) => setError(err.message || "Unable to load dashboard."))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  if (loading) return <PageLoader />;
  if (error) return <ErrorState message={error} onRetry={load} />;

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Dashboard</h1>

      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-10">
        {Object.entries(data.stats).map(([key, value]) => (
          <div key={key} className="bg-white rounded-lg shadow p-4">
            <p className="text-2xl font-bold text-blue-600">{value}</p>
            <p className="text-xs text-gray-500 mt-1">{STAT_LABELS[key] || key}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex justify-between items-center mb-3">
            <h2 className="font-semibold">Recent Enquiries</h2>
            <Link to="/admin/enquiries" className="text-blue-600 text-sm hover:underline">
              View all
            </Link>
          </div>
          {data.recent_enquiries.length === 0 ? (
            <p className="text-gray-400 text-sm">No enquiries yet.</p>
          ) : (
            <ul className="divide-y">
              {data.recent_enquiries.map((enquiry) => (
                <li key={enquiry.id} className="py-2">
                  <p className="font-medium text-sm">{enquiry.name}</p>
                  <p className="text-xs text-gray-500">{enquiry.email} &bull; {enquiry.status}</p>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="bg-white rounded-lg shadow p-4">
          <div className="flex justify-between items-center mb-3">
            <h2 className="font-semibold">Recent Properties</h2>
            <Link to="/admin/properties" className="text-blue-600 text-sm hover:underline">
              View all
            </Link>
          </div>
          {data.recent_properties.length === 0 ? (
            <p className="text-gray-400 text-sm">No properties yet.</p>
          ) : (
            <ul className="divide-y">
              {data.recent_properties.map((property) => (
                <li key={property.id} className="py-2">
                  <p className="font-medium text-sm">{property.title}</p>
                  <p className="text-xs text-gray-500">{property.city} &bull; {property.status}</p>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}
