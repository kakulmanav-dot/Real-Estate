import { useEffect, useState } from "react";
import { ToastContainer } from "react-toastify";
import Header from "../components/Header";
import About from "../components/About";
import Projects from "../components/Projects";
import Testimonials from "../components/Testimonials";
import Contact from "../components/Contact";
import Footer from "../components/Footer";
import { homeApi } from "../api/content";

export default function HomePage() {
  const [homeData, setHomeData] = useState(null);
  const [loadFailed, setLoadFailed] = useState(false);

  useEffect(() => {
    let mounted = true;

    homeApi
      .get()
      .then((data) => {
        if (mounted) setHomeData(data);
      })
      .catch(() => {
        if (mounted) setLoadFailed(true);
      });

    return () => {
      mounted = false;
    };
  }, []);

  return (
    <div className="overflow-hidden w-full">
      <ToastContainer />
      <Header settings={homeData?.settings} />
      <About settings={homeData?.settings} />
      <Projects properties={homeData?.featured_properties} loadFailed={loadFailed} />
      <Testimonials testimonials={homeData?.testimonials} loadFailed={loadFailed} />
      <Contact />
      <Footer settings={homeData?.settings} />
    </div>
  );
}
