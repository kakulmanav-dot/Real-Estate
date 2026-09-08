import React from 'react'
import { assets } from '../assets/assets';
import { motion } from "framer-motion";

function About({ settings }) {
  const aboutTitle = settings?.about_title || "Our Brand";
  const aboutContent =
    settings?.about_content ||
    "Passionate about properties, dedicated to your vision. We help you find the perfect place to call home.";

  const stats = [
    { label: "Years of Experience", value: settings?.years_of_experience || "10" },
    { label: "Projects Completed", value: settings?.projects_completed || "12" },
    { label: "Mn. Sq. Delivered", value: settings?.area_delivered || "20" },
    { label: "Ongoing Projects", value: settings?.ongoing_projects || "25" },
  ];

  return (
    <motion.div
      initial={{ opacity: 0, x: 200 }}
      transition={{ duration: 1 }}
      whileInView={{ opacity: 1, x: 0 }}
      viewport={{ once: true }}
      className="flex flex-col items-center justify-center container mx-auto p-14 md:px-14 lg:px-32 overflow-auto"
      id="About"
    >
      <h1 className="text-2xl sm:text-4xl font-bold mb-2">
        About
        <span className="underline underline-offset-4 decoration-1 font-light">
          {" "}{aboutTitle}
        </span>
      </h1>
      <p className="text-gray-500 max-w-80 text-center mb-8">
        Passionate About Properties , Dedicated to Your Vision
      </p>
      <div className="flex flex-col md:flex-row items-center md:items-start">
        <img
          src={assets.brand_img}
          className="w-full sm:w-1/2 max-w-lg"
          alt=""
        />
        <div className="flex flex-col items-center md:items-start mt-10 ml-20  text-gray-500">
          <div className="grid grid-cols-2 gap-6 md:gap-10 w-full 2xl:pr-28">
            {stats.map((stat) => (
              <div key={stat.label}>
                <p className="font-medium text-4xl text-black">{stat.value}+</p>
                <p>{stat.label}</p>
              </div>
            ))}
          </div>
          <p className="my-10 max-w-lg">{aboutContent}</p>
          <a href="#Contacts" className="bg-blue-600 text-white px-8 py-2 rounded inline-block">
            Learn More
          </a>
        </div>
      </div>
    </motion.div>
  );
}

export default About