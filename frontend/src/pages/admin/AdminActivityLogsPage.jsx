import { useCallback, useEffect, useState } from "react";
import PageLoader from "../../components/ui/PageLoader";
import EmptyState from "../../components/ui/EmptyState";
import ErrorState from "../../components/ui/ErrorState";
import Pagination from "../../components/ui/Pagination";
import { adminActivityLogApi } from "../../api/admin";

export default function AdminActivityLogsPage() {
  const [logs, setLogs] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await adminActivityLogApi.list({ page });
      setLogs(response.data);
      setMeta(response.meta);
    } catch (err) {
      setError(err.message || "Unable to load activity logs.");
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Activity Logs</h1>

      {loading && <PageLoader />}
      {!loading && error && <ErrorState message={error} onRetry={load} />}
      {!loading && !error && logs.length === 0 && <EmptyState title="No activity recorded yet" />}

      {!loading && !error && logs.length > 0 && (
        <div className="bg-white rounded-lg shadow overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-gray-500">
              <tr>
                <th className="p-3">Actor</th>
                <th className="p-3">Action</th>
                <th className="p-3">Entity</th>
                <th className="p-3">IP Address</th>
                <th className="p-3">When</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {logs.map((log) => (
                <tr key={log.id}>
                  <td className="p-3 font-medium">{log.actor}</td>
                  <td className="p-3">{log.action}</td>
                  <td className="p-3 text-gray-500">
                    {log.entity_type} {log.entity_id ? `#${log.entity_id}` : ""}
                  </td>
                  <td className="p-3 text-gray-400">{log.ip_address}</td>
                  <td className="p-3 text-gray-500">{new Date(log.created_at).toLocaleString()}</td>
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
