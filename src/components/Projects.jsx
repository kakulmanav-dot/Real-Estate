import React, { useState, useEffect } from "react";

import { assets, projectsData } from "../assets/assets";
import { motion } from "framer-motion";

function Projects() {
  const [currentIdx, setCurrentIdx] = useState(0);
  const [cardsToShow, setCardToShow] = useState(1);

  useEffect(()=>{
    const updateCardToSow = () =>{
      if (window.innerWidth >= 1024) {
        setCardToShow(projectsData.length);
      } else {
        setCardToShow(1);
      }
    }
    updateCardToSow();
    window.addEventListener('resize' , updateCardToSow);
    window.removeEventListener('resize' , updateCardToSow);

})
  const nextProject = () => {
    setCurrentIdx((prevIdx) => (prevIdx + 1) % projectsData.length);
  };
  const prevProject = () => {
    setCurrentIdx((prevIdx) =>
      prevIdx === 0 ? projectsData.length - 1 : prevIdx - 1,
    );
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
        Project{" "}
        <span className="underline underline-offset-4 decoration-1 under font-light">
          Completed
        </span>
      </h1>
      <p className="text-center text-gray-500 mb-8 max-w-80 mx-auto">
        Crafting Spaces , Building Legacies-Explore Our Portfolio
      </p>

      {/* Slider BUTTON */}
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
      {/* project slider container */}
      <div className="overflow-hidden">
        <div
          className="flex gap-8 transition transform duration-500 ease-in-out"
          style={{
            transform: `translateX(-${(currentIdx * 100) / cardsToShow}%)`,
          }}
        >
          {projectsData.map((project, idx) => (
            <div key={idx} className="relative flex shrink-0  w-full sm:w-1/4">
              <img
                src={project.image}
                alt={project.title}
                className="mb-14 h-auto w-full"
              />
              <div className="absolute left-0 right-0 bottom-5 flex justify-center">
                <div className="inline-block bg-white w-3.5/4 px-3 py-2 shadow-md">
                  <h2 className="text-xl font-semibold text-gray-800">
                    {project.title}
                  </h2>
                  <p className="text-gray text-sm">
                    {project.price} <span className="px-1">|</span>
                    {project.location}
                  </p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </motion.div>
  );
}

export default Projects;
