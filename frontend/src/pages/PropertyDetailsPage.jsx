import { useCallback, useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { toast } from "react-toastify";
import NavBar from "../components/NavBar";
import Footer from "../components/Footer";
import PropertyCard from "../components/PropertyCard";
import PageLoader from "../components/ui/PageLoader";
import ErrorState from "../components/ui/ErrorState";
import { propertiesApi, favoritesApi } from "../api/properties";
import { enquiriesApi } from "../api/content";
import { useAuth } from "../context/AuthContext";

export default function PropertyDetailsPage() {
  const { slug } = useParams();
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();

  const [property, setProperty] = useState(null);
  const [similar, setSimilar] = useState([]);
  const [activeImage, setActiveImage] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [saved, setSaved] = useState(false);
  const [savingFavorite, setSavingFavorite] = useState(false);

  const [enquiryForm, setEnquiryForm] = useState({ name: "", email: "", phone: "", message: "" });
  const [submittingEnquiry, setSubmittingEnquiry] = useState(false);

  const loadProperty = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await propertiesApi.show(slug);
      setProperty(data);
      setActiveImage(0);
      propertiesApi.similar(slug).then(setSimilar).catch(() => {});
    } catch (err) {
      setError(err.message || "Property not found.");
    } finally {
      setLoading(false);
    }
  }, [slug]);

  useEffect(() => {
    loadProperty();
  }, [loadProperty]);

  useEffect(() => {
    if (!isAuthenticated) {
      setSaved(false);
      return;
    }

    favoritesApi
      .list({ per_page: 50 })
      .then((response) => {
        setSaved(response.data.some((item) => item.slug === slug));
      })
      .catch(() => {});
  }, [isAuthenticated, slug]);

  const toggleFavorite = async () => {
    if (!isAuthenticated) {
      navigate("/login");
      return;
    }
    setSavingFavorite(true);
    try {
      if (saved) {
        await favoritesApi.remove(slug);
        setSaved(false);
        toast.info("Removed from saved properties.");
      } else {
        await favoritesApi.save(slug);
        setSaved(true);
        toast.success("Property saved!");
      }
    } catch (err) {
      toast.error(err.message || "Unable to update saved properties.");
    } finally {
      setSavingFavorite(false);
    }
  };

  const handleEnquirySubmit = async (e) => {
    e.preventDefault();
    setSubmittingEnquiry(true);
    try {
      await enquiriesApi.create({ ...enquiryForm, property_id: property.id, source: "property_page" });
      toast.success("Your enquiry has been sent!");
      setEnquiryForm({ name: "", email: "", phone: "", message: "" });
    } catch (err) {
      toast.error(err.message || "Unable to send your enquiry.");
    } finally {
      setSubmittingEnquiry(false);
    }
  };

  if (loading) return <PageLoader label="Loading property..." />;
  if (error || !property) {
    return (
      <div className="min-h-screen">
        <NavBar solid />
        <ErrorState message={error || "Property not found."} onRetry={loadProperty} />
      </div>
    );
  }

  const images = property.images?.length ? property.images : [];

  return (
    <div className="min-h-screen bg-gray-50">
      <NavBar solid />

      <div className="max-w-6xl mx-auto px-6 py-10">
        <Link to="/properties" className="text-blue-600 hover:underline text-sm">
          &larr; Back to properties
        </Link>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4">
          <div>
            <div className="h-96 bg-gray-100 rounded-lg overflow-hidden">
              {images.length > 0 ? (
                <img src={images[activeImage]?.url} alt={property.title} className="w-full h-full object-cover" />
              ) : (
                <div className="w-full h-full flex items-center justify-center text-gray-400">No images available</div>
              )}
            </div>
            {images.length > 1 && (
              <div className="flex gap-2 mt-3 overflow-x-auto">
                {images.map((image, idx) => (
                  <button
                    key={image.id}
                    onClick={() => setActiveImage(idx)}
                    className={`w-20 h-20 rounded overflow-hidden border-2 flex-shrink-0 ${
                      idx === activeImage ? "border-blue-600" : "border-transparent"
                    }`}
                  >
                    <img src={image.url} alt={image.alt_text || property.title} className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            )}
          </div>

          <div>
            <div className="flex justify-between items-start">
              <div>
                <h1 className="text-3xl font-bold">{property.title}</h1>
                <p className="text-gray-500 mt-1">
                  {property.address}, {property.city}, {property.state}
                </p>
              </div>
              <button
                onClick={toggleFavorite}
                disabled={savingFavorite}
                className={`px-4 py-2 rounded-full border ${
                  saved ? "bg-red-50 border-red-300 text-red-600" : "border-gray-300 text-gray-600 hover:bg-gray-50"
                }`}
              >
                {saved ? "♥ Saved" : "♡ Save"}
              </button>
            </div>

            <p className="text-3xl font-bold text-blue-600 mt-4">
              {new Intl.NumberFormat("en-US", { style: "currency", currency: property.currency, maximumFractionDigits: 0 }).format(
                property.price
              )}
              {property.purpose === "rent" && property.price_period ? `/${property.price_period}` : ""}
            </p>

            <div className="grid grid-cols-3 gap-4 mt-6 text-center">
              <div className="bg-white rounded p-3 shadow-sm">
                <p className="text-xl font-semibold">{property.bedrooms}</p>
                <p className="text-xs text-gray-500">Bedrooms</p>
              </div>
              <div className="bg-white rounded p-3 shadow-sm">
                <p className="text-xl font-semibold">{property.bathrooms}</p>
                <p className="text-xs text-gray-500">Bathrooms</p>
              </div>
              <div className="bg-white rounded p-3 shadow-sm">
                <p className="text-xl font-semibold">
                  {property.area} {property.area_unit}
                </p>
                <p className="text-xs text-gray-500">Area</p>
              </div>
            </div>

            <div className="mt-6">
              <h2 className="text-lg font-semibold mb-2">Description</h2>
              <p className="text-gray-600 whitespace-pre-line">{property.description}</p>
            </div>

            {property.amenities?.length > 0 && (
              <div className="mt-6">
                <h2 className="text-lg font-semibold mb-2">Amenities</h2>
                <div className="flex flex-wrap gap-2">
                  {property.amenities.map((amenity) => (
                    <span key={amenity} className="bg-blue-50 text-blue-700 text-sm px-3 py-1 rounded-full">
                      {amenity}
                    </span>
                  ))}
                </div>
              </div>
            )}

            <div className="mt-6 grid grid-cols-2 gap-2 text-sm text-gray-600">
              <p>
                <strong>Reference:</strong> {property.reference_number}
              </p>
              <p>
                <strong>Type:</strong> {property.property_type}
              </p>
              <p>
                <strong>Furnishing:</strong> {property.furnishing_status || "N/A"}
              </p>
              <p>
                <strong>Year Built:</strong> {property.year_built || "N/A"}
              </p>
              <p>
                <strong>Parking:</strong> {property.parking_spaces ?? "N/A"}
              </p>
              <p>
                <strong>Balconies:</strong> {property.balconies ?? "N/A"}
              </p>
            </div>
          </div>
        </div>

        <div className="mt-12 max-w-2xl bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-semibold mb-4">Interested in this property?</h2>
          <form onSubmit={handleEnquirySubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <input
                type="text"
                required
                placeholder="Your Name"
                className="border border-gray-300 rounded py-2 px-3"
                value={enquiryForm.name}
                onChange={(e) => setEnquiryForm({ ...enquiryForm, name: e.target.value })}
              />
              <input
                type="email"
                required
                placeholder="Your Email"
                className="border border-gray-300 rounded py-2 px-3"
                value={enquiryForm.email}
                onChange={(e) => setEnquiryForm({ ...enquiryForm, email: e.target.value })}
              />
            </div>
            <input
              type="tel"
              placeholder="Phone (optional)"
              className="w-full border border-gray-300 rounded py-2 px-3"
              value={enquiryForm.phone}
              onChange={(e) => setEnquiryForm({ ...enquiryForm, phone: e.target.value })}
            />
            <textarea
              required
              placeholder="I'm interested in this property..."
              className="w-full border border-gray-300 rounded py-2 px-3 h-32 resize-none"
              value={enquiryForm.message}
              onChange={(e) => setEnquiryForm({ ...enquiryForm, message: e.target.value })}
            />
            <button
              type="submit"
              disabled={submittingEnquiry}
              className="bg-blue-600 text-white px-8 py-2 rounded hover:bg-blue-700 disabled:opacity-60"
            >
              {submittingEnquiry ? "Sending..." : "Send Enquiry"}
            </button>
          </form>
        </div>

        {similar.length > 0 && (
          <div className="mt-16">
            <h2 className="text-2xl font-bold mb-6">Similar Properties</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {similar.map((item) => (
                <PropertyCard key={item.id} property={item} />
              ))}
            </div>
          </div>
        )}
      </div>

      <Footer />
    </div>
  );
}
