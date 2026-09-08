import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import EmptyState from "../../components/ui/EmptyState";
import ErrorState from "../../components/ui/ErrorState";
import Pagination from "../../components/ui/Pagination";
import { adminEnquiriesApi } from "../../api/admin";

const STATUS_COLORS = {
  new: "bg-blue-100 text-blue-700",
  contacted: "bg-yellow-100 text-yellow-700",
  qualified: "bg-green-100 text-green-700",
  closed: "bg-gray-100 text-gray-600",
  spam: "bg-red-100 text-red-700",
};

export default function AdminEnquiriesPage() {
  const [enquiries, setEnquiries] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page };
      if (search) params.search = search;
      if (status) params.status = status;
      const response = await adminEnquiriesApi.list(params);
      setEnquiries(response.data);
      setMeta(response.meta);
    } catch (err) {
      setError(err.message || "Unable to load enquiries.");
    } finally {
      setLoading(false);
    }
  }, [page, search, status]);

  useEffect(() => {
    load();
  }, [load]);

  const handleExport = async () => {
    const params = {};
    if (search) params.search = search;
    if (status) params.status = status;

    try {
      await adminEnquiriesApi.export(params);
    } catch (err) {
      toast.error(err.message || "Unable to export enquiries.");
    }
  };

  return (
    <div>
      <div className="flex flex-wrap justify-between items-center gap-4 mb-6">
        <h1 className="text-2xl font-bold">Enquiries</h1>
        <button onClick={handleExport} className="border border-gray-300 px-5 py-2 rounded hover:bg-gray-50">
          Export CSV
        </button>
      </div>

      <div className="flex flex-wrap gap-3 mb-6">
        <input
          type="text"
          placeholder="Search enquiries..."
          className="input flex-1 min-w-[200px]"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
        />
        <select
          className="input w-48"
          value={status}
          onChange={(e) => {
            setStatus(e.target.value);
            setPage(1);
          }}
        >
          <option value="">All statuses</option>
          <option value="new">New</option>
          <option value="contacted">Contacted</option>
          <option value="qualified">Qualified</option>
          <option value="closed">Closed</option>
          <option value="spam">Spam</option>
        </select>
      </div>

      {loading && <PageLoader />}
      {!loading && error && <ErrorState message={error} onRetry={load} />}
      {!loading && !error && enquiries.length === 0 && <EmptyState title="No enquiries found" />}

      {!loading && !error && enquiries.length > 0 && (
        <div className="bg-white rounded-lg shadow overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-gray-500">
              <tr>
                <th className="p-3">Name</th>
                <th className="p-3">Email</th>
                <th className="p-3">Property</th>
                <th className="p-3">Status</th>
                <th className="p-3">Date</th>
                <th className="p-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {enquiries.map((enquiry) => (
                <tr key={enquiry.id}>
                  <td className="p-3 font-medium">{enquiry.name}</td>
                  <td className="p-3">{enquiry.email}</td>
                  <td className="p-3">{enquiry.property?.title || "—"}</td>
                  <td className="p-3">
                    <span className={`px-2 py-1 rounded text-xs capitalize ${STATUS_COLORS[enquiry.status] || ""}`}>
                      {enquiry.status}
                    </span>
                  </td>
                  <td className="p-3 text-gray-500">{new Date(enquiry.created_at).toLocaleDateString()}</td>
                  <td className="p-3 text-right">
                    <Link to={`/admin/enquiries/${enquiry.id}`} className="text-blue-600 hover:underline">
                      View
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <Pagination currentPage={meta.current_page} lastPage={meta.last_page} onPageChange={setPage} />
    </div>
  );
}
