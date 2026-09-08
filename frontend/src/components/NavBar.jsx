import React, { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { assets } from "../assets/assets";
import { useAuth } from "../context/AuthContext";

function NavBar({ solid = false }) {
  const [showMobileMenu, setMobileMenu] = useState(false);
  const { isAuthenticated, isAdmin, user, logout } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    document.body.style.overflow = showMobileMenu ? "hidden" : "auto";
    return () => {
      document.body.style.overflow = "auto";
    };
  }, [showMobileMenu]);

  const handleLogout = async () => {
    await logout();
    setMobileMenu(false);
    navigate("/");
  };

  const navLinks = [
    { label: "Home", href: "/#Header" },
    { label: "About", href: "/#About" },
    { label: "Properties", href: "/properties", isRoute: true },
    { label: "Testimonials", href: "/#Testimonials" },
  ];

  const textColor = solid ? "text-gray-800" : "text-white";
  const hoverColor = solid ? "hover:text-blue-600" : "hover:text-gray-300";
  const buttonClass = solid ? "bg-blue-600 text-white hover:bg-blue-700" : "bg-white text-gray-800";
  const logoSrc = solid ? assets.logo_dark : assets.logo;
  const menuIconClass = solid ? "invert" : "";

  return (
    <div className={`${solid ? "relative bg-white shadow-sm" : "absolute top-0 left-0"} z-10 w-full`}>
      <div className="container mx-auto flex justify-between items-center px-6 py-4 md:px-20 lg:px-32 bg-transparent">
        <Link to="/">
          <img src={logoSrc} alt="Real Estate logo" />
        </Link>
        <ul className={`hidden md:flex gap-7 items-center ${textColor}`}>
          {navLinks.map((link) =>
            link.isRoute ? (
              <Link key={link.label} to={link.href} className={`cursor-pointer ${hoverColor}`}>
                {link.label}
              </Link>
            ) : (
              <a key={link.label} href={link.href} className={`cursor-pointer ${hoverColor}`}>
                {link.label}
              </a>
            )
          )}
        </ul>

        {isAuthenticated ? (
          <div className={`hidden md:flex items-center gap-4 ${textColor}`}>
            {isAdmin && (
              <Link to="/admin" className={hoverColor}>
                Admin
              </Link>
            )}
            <Link to="/saved-properties" className={hoverColor}>
              Saved
            </Link>
            <Link to="/profile" className={hoverColor}>
              {user?.name?.split(" ")[0] || "Profile"}
            </Link>
            <button onClick={handleLogout} className={`px-6 py-2 rounded-full ${buttonClass}`}>
              Logout
            </button>
          </div>
        ) : (
          <Link to="/register" className={`hidden md:block px-8 py-2 rounded-full ${buttonClass}`}>
            Sign Up
          </Link>
        )}

        <img
          onClick={() => setMobileMenu(true)}
          src={assets.menu_icon}
          className={`md:hidden w-7 cursor-pointer ${menuIconClass}`}
          alt=""
        />
      </div>

      {/* ---------mobile menu ----------- */}
      <div
        className={`md:hidden ${
          showMobileMenu ? "fixed top-0 right-0 bottom-0 w-full bg-white z-20" : "h-0 w-0 overflow-hidden"
        } transition-all duration-300`}
      >
        <div className="flex justify-end p-6 cursor-pointer">
          <img onClick={() => setMobileMenu(false)} src={assets.cross_icon} alt="" />
        </div>
        <ul className="flex flex-col items-center px-5 text-lg gap-2 mt-5 font-medium">
          {navLinks.map((link) =>
            link.isRoute ? (
              <Link
                key={link.label}
                to={link.href}
                onClick={() => setMobileMenu(false)}
                className="px-4 py-2 rounded-full inline-block"
              >
                {link.label}
              </Link>
            ) : (
              <a
                key={link.label}
                href={link.href}
                onClick={() => setMobileMenu(false)}
                className="px-4 py-2 rounded-full inline-block"
              >
                {link.label}
              </a>
            )
          )}

          {isAuthenticated ? (
            <>
              {isAdmin && (
                <Link to="/admin" onClick={() => setMobileMenu(false)} className="px-4 py-2 rounded-full inline-block">
                  Admin
                </Link>
              )}
              <Link to="/saved-properties" onClick={() => setMobileMenu(false)} className="px-4 py-2 rounded-full inline-block">
                Saved Properties
              </Link>
              <Link to="/profile" onClick={() => setMobileMenu(false)} className="px-4 py-2 rounded-full inline-block">
                Profile
              </Link>
              <button onClick={handleLogout} className="px-4 py-2 rounded-full inline-block">
                Logout
              </button>
            </>
          ) : (
            <>
              <Link to="/login" onClick={() => setMobileMenu(false)} className="px-4 py-2 rounded-full inline-block">
                Login
              </Link>
              <Link to="/register" onClick={() => setMobileMenu(false)} className="px-4 py-2 rounded-full inline-block">
                Sign Up
              </Link>
            </>
          )}
        </ul>
      </div>
    </div>
  );
}

export default NavBar;
