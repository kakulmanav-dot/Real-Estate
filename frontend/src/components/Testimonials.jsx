import React from 'react'
import { assets } from '../assets/assets'
import { motion } from "framer-motion";

function Testimonials({ testimonials, loadFailed }) {
  const items = testimonials || [];

  return (
    <motion.div
      initial={{ opacity: 0, x: 100 }}
      transition={{ duration: 1.5 }}
      whileInView={{ opacity: 1, x: 0 }}
      viewport={{ once: true }}
      className="container mx-auto py-10 lg:py-32 w-full overflow-hidden"
      id="Testimonials"
    >
      <h1 className="text-2xl sm:text-4xl font-bold mb-2 text-center">
        Customers{" "}
        <span className="underline underline-offset-4 decoration-1 under font-light">
          Testimonials
        </span>
      </h1>
      <p className="text-center text-gray-500 mb-12 max-w-80 mx-auto">
        Real Stories from those who found Home with us
      </p>

      {loadFailed && (
        <p className="text-center text-amber-600 text-sm mb-6">
          We couldn't load testimonials right now. Please check back shortly.
        </p>
      )}

      <div className="flex flex-wrap justify-center gap-8">
        {items.map((testimonial) => (
          <div
            key={testimonial.id}
            className="max-w-[340px] border shadow-lg rounded px-8 py-12 text-center"
          >
            {testimonial.image_url ? (
              <img
                className="w-20 h-20 rounded-full mx-auto mb-4 object-cover"
                src={testimonial.image_url}
                alt={testimonial.name}
              />
            ) : (
              <div className="w-20 h-20 rounded-full mx-auto mb-4 bg-gray-200 flex items-center justify-center text-gray-500 text-xl font-semibold">
                {testimonial.name?.[0]}
              </div>
            )}
            <h2 className="text-xl text-gray-700 font-medium">
              {testimonial.name}
            </h2>
            <p className="text-gray-500 mb-4 text-sm">{testimonial.designation}</p>
            <div className="flex justify-center gap-1 text-red-500 mb-4">
              {Array.from({ length: testimonial.rating }, (_, idx) => (
                <img key={idx} src={assets.star_icon} alt="" />
              ))}
            </div>
            <p className="text-gray-600">{testimonial.text}</p>
          </div>
        ))}
      </div>
    </motion.div>
  );
}

export default Testimonials