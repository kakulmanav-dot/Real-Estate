import { useState } from 'react'

import './App.css'

import Header from './components/Header'
import About from './components/About'
import Projects from './components/Projects'
import Testimonials from './components/Testimonials'
import Contact from './components/Contact'
import { ToastContainer, toast } from "react-toastify";
import Footer from './components/Footer'
function App() {
 

  return (
    <>
      <div className="overflow-hidden w-full">
        <ToastContainer/>
        <Header />
        <About />
        <Projects />
        <Testimonials />
        <Contact />
        <Footer/>
      </div>
    </>
  );
}

export default App
