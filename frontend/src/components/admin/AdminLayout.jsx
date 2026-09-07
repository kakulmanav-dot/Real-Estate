import { useState } from "react";
import { Link, NavLink, Outlet, useNavigate } from "react-router-dom";
import { useAuth } from "../../context/AuthContext";

const NAV_ITEMS = [
  { to: "/admin", label: "Dashboard", end: true },
  { to: "/admin/properties", label: "Properties" },
  { to: "/admin/enquiries", label: "Enquiries" },
  { to: "/admin/testimonials", label: "Testimonials" },
  { to: "/admin/users", label: "Users" },
  { to: "/admin/settings", label: "Settings" },
  { to: "/admin/activity-logs", label: "Activity Logs" },
  { to: "/admin/profile", label: "My Profile" },
];

export default function AdminLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [mobileOpen, setMobileOpen] = useState(false);

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  const linkClass = ({ isActive }) =>
    `block px-4 py-2 rounded transition ${
      isActive ? "bg-blue-600 text-white" : "text-gray-600 hover:bg-gray-100"
    }`;

  return (
    <div className="min-h-screen flex bg-gray-50">
      <aside className="hidden md:flex md:flex-col w-64 bg-white border-r border-gray-200 p-4">
        <Link to="/" className="text-xl font-bold text-blue-600 mb-8 px-2">
          Real Estate Admin
        </Link>
        <nav className="flex-1 space-y-1">
          {NAV_ITEMS.map((item) => (
            <NavLink key={item.to} to={item.to} end={item.end} className={linkClass}>
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="border-t pt-4 mt-4">
          <p className="text-sm text-gray-500 px-2">{user?.name}</p>
          <button onClick={handleLogout} className="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 rounded mt-1">
            Logout
          </button>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0">
        <header className="md:hidden bg-white border-b border-gray-200 p-4 flex justify-between items-center">
          <Link to="/" className="text-lg font-bold text-blue-600">
            Real Estate Admin
          </Link>
          <button onClick={() => setMobileOpen(!mobileOpen)} className="text-gray-600" aria-label="Toggle menu">
            &#9776;
          </button>
        </header>

        {mobileOpen && (
          <nav className="md:hidden bg-white border-b border-gray-200 p-4 space-y-1">
            {NAV_ITEMS.map((item) => (
              <NavLink key={item.to} to={item.to} end={item.end} className={linkClass} onClick={() => setMobileOpen(false)}>
                {item.label}
              </NavLink>
            ))}
            <button onClick={handleLogout} className="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50 rounded">
              Logout
            </button>
          </nav>
        )}

        <main className="flex-1 p-4 md:p-8 overflow-x-hidden">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
