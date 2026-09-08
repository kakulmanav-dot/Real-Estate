import React from 'react'
import { toast } from 'react-toastify';
import { motion } from "framer-motion";
import { enquiriesApi } from '../api/content';

function Contact() {
  const [submitting, setSubmitting] = React.useState(false);

  const onSubmit = async (event) => {
    event.preventDefault();
    setSubmitting(true);

    const formData = new FormData(event.target);
    const payload = {
      name: formData.get('name'),
      email: formData.get('email'),
      message: formData.get('message'),
      source: 'homepage_contact_form',
    };

    try {
      await enquiriesApi.create(payload);
      toast.success('Message sent successfully. We will be in touch soon!');
      event.target.reset();
    } catch (err) {
      toast.error(err.message || 'Unable to send your message. Please try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 100 }}
      transition={{ duration: 1.5 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      className="text-center p-6 py-20 lg:px-32 w-full overflow-hidden"
      id="Contacts"
    >
      <h1 className="text-2xl sm:text-4xl font-bold mb-2 text-center">
        Contact {"  "}
        <span className="underline underline-offset-4 decoration-1 under font-light">
          With us
        </span>
      </h1>
      <p className="text-center text-gray-500 mb-12 max-w-80 mx-auto">
        Ready to Make a Move? Lets Build Your Future Together
      </p>
      <form
        className="max-w-2xl mx-auto text-gray-600 pt-8 "
        onSubmit={onSubmit}
      >
        <div className="flex flex-wrap">
          <div className="w-full md:w-1/2 text-left">
            Your Name
            <input
              className="w-full border border-gray-300 rounded py-3 px-4 mt-2"
              name="name"
              type="text"
              placeholder="Your Name"
              required
            />
          </div>
          <div className="w-full md:w-1/2 text-left md:pl-4">
            Your Email
            <input
              className="w-full border border-gray-300 rounded py-3 px-4 mt-2"
              name="email"
              type="email"
              placeholder="Your Email"
              required
            />
          </div>
        </div>
        <div className="text-left my-6">
          Message
          <textarea
            className="w-full border border-gray-400 rounded px-4 py-3 mt-2 h-48 resize-none"
            name="message"
            placeholder="Write Message"
            required
          ></textarea>
        </div>
        <button disabled={submitting} className="bg-blue-600 text-white py-2 px-10 mb-10 rounded disabled:opacity-60">
          {submitting ? "Sending..." : "Send Message"}
        </button>
      </form>
    </motion.div>
  );
}

export default Contact