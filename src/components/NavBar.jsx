import React, { useEffect, useState } from 'react'
import { assets } from '../assets/assets'

function NavBar() {
    const [showMobileMenu,setMobileMenu] = useState(false);

    useEffect(()=>{
        if(showMobileMenu){
             document.body.style.overflow = 'hidden'
        }
        else{
             document.body.style.overflow = "auto";
         
        }
        return (()=>{
             document.body.style.overflow = "auto";
             
        })
    },[showMobileMenu])
  return (
    <div className="absolute top-0 left-0 z-10 w-full">
      <div className="container mx-auto flex justify-between item-center px-6 py-4 md:px-20 lg:px-32 bg-transparent">
        <img src={assets.logo} alt="" />
        <ul className="hidden md:flex gap-7 text-white">
          <a href="#Header" className="cursor-pointer hover:text-gray-400">
            Home
          </a>
          <a href="#About" className="cursor-pointer hover:text-gray-400">
            About
          </a>
          <a href="#Projects" className="cursor-pointer hover:text-gray-400">
            Projects
          </a>
          <a href="#Testimonials" className="cursor-pointer hover:text-gray-400">
            Testimonials
          </a>
        </ul>
        <button className="hidden md:block bg-white px-8 py-2 rounded-full">
          Sign Up
        </button>
        <img
          onClick={() => {
            setMobileMenu(true);
          }}
          src={assets.menu_icon}
          className="md:hidden w-7 cursor-pointer"
          alt=""
        />
      </div>
      {/* ---------mobile menu ----------- */}
      <div
        className={`md:hidden ${
          showMobileMenu
            ? "fixed top-0 right-0 bottom-0 w-full bg-white"
            : "h-0 w-0 overflow-hidden"
        } transition-all duration-300`}
      >
        <div className=" flex justify-end p-6 cursor-pointer">
          <img
            onClick={() => {
              setMobileMenu(false);
            }}
            src={assets.cross_icon}
            alt=""
          />
        </div>
        <ul className=" flex flex-col items-center px-5 text-lg gap-2 mt-5 font-medium">
          <a
            href="#Home"
            onClick={() => {
              setMobileMenu(false);
            }}
            className="px-4 py-2 rounded-full inline-block"
          >
            Home
          </a>
          <a
            href="#About"
            onClick={() => {
              setMobileMenu(false);
            }}
            className="px-4 py-2 rounded-full inline-block"
          >
            About
          </a>
          <a
            href="#Project"
            onClick={() => {
              setMobileMenu(false);
            }}
            className="px-4 py-2 rounded-full inline-block"
          >
            Projects
          </a>
          <a
            href="#Testimonials"
            onClick={() => {
              setMobileMenu(false);
            }}
            className="px-4 py-2 rounded-full inline-block"
          >
            Testimonials
          </a>
        </ul>
      </div>
    </div>
  );
}

export default NavBar