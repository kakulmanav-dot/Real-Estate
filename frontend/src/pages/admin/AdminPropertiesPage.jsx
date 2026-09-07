import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import EmptyState from "../../components/ui/EmptyState";
import ErrorState from "../../components/ui/ErrorState";
import Pagination from "../../components/ui/Pagination";
import ConfirmDialog from "../../components/ui/ConfirmDialog";
import { adminPropertiesApi } from "../../api/admin";

const STATUS_COLORS = {
  draft: "bg-gray-100 text-gray-600",
  published: "bg-green-100 text-green-700",
  sold: "bg-purple-100 text-purple-700",
  rented: "bg-blue-100 text-blue-700",
  archived: "bg-red-100 text-red-700",
};

export default function AdminPropertiesPage() {
  const [properties, setProperties] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);
  const [confirmTarget, setConfirmTarget] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page };
      if (search) params.search = search;
      if (status) params.status = status;
      const response = await adminPropertiesApi.list(params);
      setProperties(response.data);
      setMeta(response.meta);
    } catch (err) {
      setError(err.message || "Unable to load properties.");
    } finally {
      setLoading(false);
    }
  }, [page, search, status]);

  useEffect(() => {
    load();
  }, [load]);

  const handleDelete = async () => {
    try {
      await adminPropertiesApi.remove(confirmTarget.id);
      toast.success("Property deleted.");
      setConfirmTarget(null);
      load();
    } catch (err) {
      toast.error(err.message || "Unable to delete property.");
    }
  };

  const handleRestore = async (property) => {
    try {
      await adminPropertiesApi.restore(property.id);
      toast.success("Property restored.");
      load();
    } catch (err) {
      toast.error(err.message || "Unable to restore property.");
    }
  };

  return (
    <div>
      <div className="flex flex-wrap justify-between items-center gap-4 mb-6">
        <h1 className="text-2xl font-bold">Properties</h1>
        <Link to="/admin/properties/new" className="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700">
          + New Property
        </Link>
      </div>

      <div className="flex flex-wrap gap-3 mb-6">
        <input
          type="text"
          placeholder="Search properties..."
          className="border border-gray-300 rounded py-2 px-3 flex-1 min-w-[200px]"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
        />
        <select
          className="border border-gray-300 rounded py-2 px-3"
          value={status}
          onChange={(e) => {
            setStatus(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All statuses</option>
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="sold">Sold</option>
          <option value="rented">Rented</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      {loading && <PageLoader />}
      {!loading && error && <ErrorState message={error} onRetry={load} />}
      {!loading && !error && properties.length === 0 && <EmptyState title="No properties found" />}

      {!loading && !error && properties.length > 0 && (
        <div className="bg-white rounded-lg shadow overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-gray-500">
              <tr>
                <th className="p-3">Title</th>
                <th className="p-3">City</th>
                <th className="p-3">Price</th>
                <th className="p-3">Status</th>
                <th className="p-3">Featured</th>
                <th className="p-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {properties.map((property) => (
                <tr key={property.id} className={property.deleted_at ? "opacity-50" : ""}>
                  <td className="p-3 font-medium">{property.title}</td>
                  <td className="p-3">{property.city}</td>
                  <td className="p-3">
                    {new Intl.NumberFormat("en-US", { style: "currency", currency: property.currency, maximumFractionDigits: 0 }).format(property.price)}
                  </td>
                  <td className="p-3">
                    <span className={`px-2 py-1 rounded text-xs capitalize ${STATUS_COLORS[property.status] || ""}`}>
                      {property.status}
                    </span>
                  </td>
                  <td className="p-3">{property.featured ? "⭐" : "—"}</td>
                  <td className="p-3 text-right space-x-3">
                    {property.deleted_at ? (
                      <button onClick={() => handleRestore(property)} className="text-green-600 hover:underline">
                        Restore
                      </button>
                    ) : (
                      <>
                        <Link to={`/admin/properties/${property.id}/edit`} className="text-blue-600 hover:underline">
                          Edit
                        </Link>
                        <button onClick={() => setConfirmTarget(property)} className="text-red-600 hover:underline">
                          Delete
                        </button>
                      </>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination currentPage={meta.current_page} lastPage={meta.last_page} onPageChange={setPage} />

      <ConfirmDialog
        open={!!confirmTarget}
        title="Delete property?"
        message={`This will move "${confirmTarget?.title}" to trash. You can restore it later.`}
        confirmLabel="Delete"
        danger
        onConfirm={handleDelete}
        onCancel={() => setConfirmTarget(null)}
      />
    </div>
  );
}
