import { Link } from "react-router-dom";

function formatPrice(price, currency, purpose, pricePeriod) {
  const formatted = new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency || "USD",
    maximumFractionDigits: 0,
  }).format(price);

  if (purpose === "rent" && pricePeriod) {
    return `${formatted}/${pricePeriod}`;
  }

  return formatted;
}

export default function PropertyCard({ property }) {
  return (
    <Link
      to={`/properties/${property.slug}`}
      className="block bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden group"
    >
      <div className="relative h-56 overflow-hidden bg-gray-100">
        {property.cover_image_url ? (
          <img
            src={property.cover_image_url}
            alt={property.title}
            className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
        )}
        {property.featured && (
          <span className="absolute top-3 left-3 bg-blue-600 text-white text-xs px-3 py-1 rounded-full">
            Featured
          </span>
        )}
        <span className="absolute top-3 right-3 bg-white/90 text-gray-800 text-xs px-3 py-1 rounded-full capitalize">
          For {property.purpose}
        </span>
      </div>
      <div className="p-4">
        <h3 className="text-lg font-semibold text-gray-800 truncate">{property.title}</h3>
        <p className="text-gray-500 text-sm mt-1">
          {property.city}
          {property.state ? `, ${property.state}` : ""}
        </p>
        <div className="flex items-center justify-between mt-3">
          <span className="text-blue-600 font-bold">
            {formatPrice(property.price, property.currency, property.purpose, property.price_period)}
          </span>
          <span className="text-xs text-gray-500">
            {property.bedrooms} bd &bull; {property.bathrooms} ba &bull; {property.area} {property.area_unit}
          </span>
        </div>
      </div>
    </Link>
  );
}
