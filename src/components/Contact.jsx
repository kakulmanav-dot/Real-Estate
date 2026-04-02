import React from 'react'
import { toast } from 'react-toastify';
import { motion } from "framer-motion";

function Contact() {
     const [result, setResult] = React.useState("");

     const onSubmit = async (event) => {
       event.preventDefault();
       setResult("Sending....");
       const formData = new FormData(event.target);

       formData.append("access_key", "c0d543a7-af1b-4e2a-9303-2f9f96c0a496");

       const response = await fetch("https://api.web3forms.com/submit", {
         method: "POST",
         body: formData,
       });

       const data = await response.json();

       if (data.success) {
       toast.success("Form Submitted Successfully");
         setResult("");
         event.target.reset();
       } else {
         console.log("Error", data);
         toast.error(data.message)
         setResult('');
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
              name="Name"
              type="text"
              placeholder="Your Name"
              required
            />
          </div>
          <div className="w-full md:w-1/2 text-left md:pl-4">
            Your Email
            <input
              className="w-full border border-gray-300 rounded py-3 px-4 mt-2"
              name="Email"
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
            name="Message"
            placeholder="Write Message"
            required
          ></textarea>
        </div>
        <button className="bg-blue-600 text-white py-2 px-10 mb-10 rounded">
          {result ? result : "Send Message"}
        </button>
      </form>
    </motion.div>
  );
}

export default Contact