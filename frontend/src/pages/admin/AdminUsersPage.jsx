import { useCallback, useEffect, useState } from "react";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import ErrorState from "../../components/ui/ErrorState";
import Pagination from "../../components/ui/Pagination";
import { adminUsersApi } from "../../api/admin";

export default function AdminUsersPage() {
  const [users, setUsers] = useState([]);
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = { page };
      if (search) params.search = search;
      const response = await adminUsersApi.list(params);
      setUsers(response.data);
      setMeta(response.meta);
    } catch (err) {
      setError(err.message || "Unable to load users.");
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  useEffect(() => {
    load();
  }, [load]);

  const handleRoleChange = async (user, role) => {
    try {
      const updated = await adminUsersApi.update(user.id, { role });
      setUsers((prev) => prev.map((u) => (u.id === user.id ? updated : u)));
      toast.success("User role updated.");
    } catch (err) {
      toast.error(err.message || "Unable to update role.");
    }
  };

  const handleToggleActive = async (user) => {
    try {
      const updated = await adminUsersApi.update(user.id, { is_active: !user.is_active });
      setUsers((prev) => prev.map((u) => (u.id === user.id ? updated : u)));
      toast.success(updated.is_active ? "User activated." : "User deactivated.");
    } catch (err) {
      toast.error(err.message || "Unable to update user.");
    }
  };

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Users</h1>

      <input
        type="text"
        placeholder="Search users..."
        className="input mb-6 max-w-sm"
        value={search}
        onChange={(e) => {
          setSearch(e.target.value);
          setPage(1);
        }}
      />

      {loading && <PageLoader />}
      {!loading && error && <ErrorState message={error} onRetry={load} />}

      {!loading && !error && (
        <div className="bg-white rounded-lg shadow overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-gray-500">
              <tr>
                <th className="p-3">Name</th>
                <th className="p-3">Email</th>
                <th className="p-3">Role</th>
                <th className="p-3">Status</th>
                <th className="p-3">Joined</th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {users.map((user) => (
                <tr key={user.id}>
                  <td className="p-3 font-medium">{user.name}</td>
                  <td className="p-3">{user.email}</td>
                  <td className="p-3">
                    <select
                      className="border border-gray-300 rounded py-1 px-2 text-xs"
                      value={user.role}
                      onChange={(e) => handleRoleChange(user, e.target.value)}
                    >
                      <option value="user">User</option>
                      <option value="admin">Admin</option>
                    </select>
                  </td>
                  <td className="p-3">
                    <button
                      onClick={() => handleToggleActive(user)}
                      className={`text-xs px-2 py-1 rounded ${user.is_active ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"}`}
                    >
                      {user.is_active ? "Active" : "Inactive"}
                    </button>
                  </td>
                  <td className="p-3 text-gray-500">{new Date(user.created_at).toLocaleDateString()}</td>
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
