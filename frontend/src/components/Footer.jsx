import React from 'react'
import { assets } from '../assets/assets'

function Footer({ settings }) {
  const companyName = settings?.company_name || "Real Estate";
  const socialLinks = [
    { label: "Facebook", url: settings?.facebook_url },
    { label: "Instagram", url: settings?.instagram_url },
    { label: "LinkedIn", url: settings?.linkedin_url },
  ].filter((link) => link.url);

  return (
    <div
      className="pt-10 px-4 md:px-20 lg:px-32 w-full overflow-hidden bg-gray-900"
      id="Footer"
    >
      <div className="container mx-auto flex flex-col md:flex-row justify-between items-start">
        <div className="w-full md:w-1/3 mb-8 md:mb-0">
          <img src={assets.logo_dark} alt="" />
          <p className="text-gray-400 mt-4">
            {companyName} helps you discover properties that fit the way you live, backed by a team dedicated to your satisfaction.
          </p>
          {socialLinks.length > 0 && (
            <div className="flex gap-4 mt-4">
              {socialLinks.map((link) => (
                <a key={link.label} href={link.url} target="_blank" rel="noreferrer" className="text-gray-400 hover:text-white">
                  {link.label}
                </a>
              ))}
            </div>
          )}
        </div>
        <div className="w-full md:w-1/5 mb:8 md:mb-0">
          <h3 className="text-white text-lg font-bold mb-4">Company</h3>
          <ul className="flex flex-col gap-2 text-gray-400">
            <a href="#Header" className="hover:text-white">
              Home
            </a>
            <a href="#About" className="hover:text-white">
              About us
            </a>
            <a href="#Contacts" className="hover:text-white">
              Contact Us
            </a>
            <a href="#" className="hover:text-white">
              Privacy Policy
            </a>
          </ul>
        </div>
        <div className="w-full md:w-1/3">
          <h3 className="text-white text-lg font-bold mb-4">Get in Touch</h3>
          <p className="text-gray-400 mt-4">
            {settings?.company_email || "hello@realestate.test"}
            <br />
            {settings?.company_phone || ""}
          </p>
        </div>
      </div>
      <div className='border-t border-gray-700 py-4 mt-10 text-center text-gray-500'>Copyright {new Date().getFullYear()} © {companyName}. All Rights Reserved</div>
    </div>
  );
}

export default Footer