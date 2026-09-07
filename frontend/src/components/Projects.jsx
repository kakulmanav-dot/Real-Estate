import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { assets } from "../assets/assets";
import { motion } from "framer-motion";

function formatPrice(property) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: property.currency || "USD",
    maximumFractionDigits: 0,
  }).format(property.price);
}

function Projects({ properties, loadFailed }) {
  const [currentIdx, setCurrentIdx] = useState(0);
  const [cardsToShow, setCardToShow] = useState(1);

  const items = properties || [];

  useEffect(() => {
    const updateCardsToShow = () => {
      setCardToShow(window.innerWidth >= 1024 ? Math.max(items.length, 1) : 1);
    };

    updateCardsToShow();
    window.addEventListener("resize", updateCardsToShow);

    return () => window.removeEventListener("resize", updateCardsToShow);
  }, [items.length]);

  const nextProject = () => {
    if (items.length === 0) return;
    setCurrentIdx((prevIdx) => (prevIdx + 1) % items.length);
  };
  const prevProject = () => {
    if (items.length === 0) return;
    setCurrentIdx((prevIdx) => (prevIdx === 0 ? items.length - 1 : prevIdx - 1));
  };

  return (
    <motion.div
      initial={{ opacity: 0, x: -200 }}
      transition={{ duration: 1.5 }}
      whileInView={{ opacity: 1, x: 0 }}
      viewport={{ once: true }}
      className="container mx-auto py-4 pt-20 md:px-20 px-6 lg:px-32 my-20 w-full overflow-hidden "
      id="Projects"
    >
      <h1 className="text-2xl sm:text-4xl font-bold mb-2 text-center">
        Featured{" "}
        <span className="underline underline-offset-4 decoration-1 under font-light">
          Properties
        </span>
      </h1>
      <p className="text-center text-gray-500 mb-8 max-w-80 mx-auto">
        Crafting Spaces , Building Legacies-Explore Our Portfolio
      </p>

      {loadFailed && (
        <p className="text-center text-amber-600 text-sm mb-6">
          We couldn't reach our listings service right now. Please check back shortly.
        </p>
      )}

      {!loadFailed && items.length === 0 && (
        <p className="text-center text-gray-400 mb-6">Loading featured properties...</p>
      )}

      {items.length > 0 && (
        <>
          <div className="flex justify-end items-center mb-8">
            <button
              onClick={prevProject}
              className="p-3 bg-gray-200 rounded mr-2"
              aria-label="Previous Project"
            >
              <img src={assets.left_arrow} alt="prev" />
            </button>
            <button
              onClick={nextProject}
              className="p-3 bg-gray-200 rounded mr-2"
              aria-label="Next Project"
            >
              <img src={assets.right_arrow} alt="Next" />
            </button>
          </div>
          <div className="overflow-hidden">
            <div
              className="flex gap-8 transition transform duration-500 ease-in-out"
              style={{
                transform: `translateX(-${(currentIdx * 100) / cardsToShow}%)`,
              }}
            >
              {items.map((property) => (
                <Link
                  to={`/properties/${property.slug}`}
                  key={property.id}
                  className="relative flex shrink-0 w-full sm:w-1/4"
                >
                  {property.cover_image_url ? (
                    <img
                      src={property.cover_image_url}
                      alt={property.title}
                      className="mb-14 h-auto w-full object-cover"
                    />
                  ) : (
                    <div className="mb-14 w-full h-64 bg-gray-100 flex items-center justify-center text-gray-400">
                      No Image
                    </div>
                  )}
                  <div className="absolute left-0 right-0 bottom-5 flex justify-center">
                    <div className="inline-block bg-white w-3.5/4 px-3 py-2 shadow-md">
                      <h2 className="text-xl font-semibold text-gray-800">
                        {property.title}
                      </h2>
                      <p className="text-gray text-sm">
                        {formatPrice(property)} <span className="px-1">|</span>
                        {property.city}
                      </p>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        </>
      )}

      <div className="text-center mt-10">
        <Link
          to="/properties"
          className="inline-block border border-gray-300 px-8 py-3 rounded hover:bg-gray-50"
        >
          View All Properties
        </Link>
      </div>
    </motion.div>
  );
}

export default Projects;
