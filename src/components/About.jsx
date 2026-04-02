import React from 'react'
import { assets } from '../assets/assets';
import { motion } from "framer-motion";

function About() {
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
          Our Brand
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
            <div>
              <p className="font-medium text-4xl text-black">10+</p>
              <p>Years of Experience </p>
            </div>
            <div>
              <p className="font-medium text-4xl text-black">12+</p>
              <p>Projects Completed </p>
            </div>{" "}
            <div>
              <p className="font-medium text-4xl text-black">20+</p>
              <p>Mn. Sq. Delivered </p>
            </div>{" "}
            <div>
              <p className="font-medium text-4xl text-black">25+</p>
              <p> Ongoing Projects </p>
            </div>
          </div>
          <p className="my-10 max-w-lg">
            Lorem, ipsum dolor sit amet consectetur adipisicing elit. Cum in
            aliquam nulla, at et sequi, nostrum quos reiciendis ullam illum
            ratione eaque, nobis obcaecati ut vero laboriosam nisi iure debitis!
          </p>
          <button className="bg-blue-600 text-white px-8 py-2 rounded">
            Learn More
          </button>
        </div>
      </div>
    </motion.div>
  );
}

export default About