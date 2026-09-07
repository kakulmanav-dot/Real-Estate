import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import ConfirmDialog from "../../components/ui/ConfirmDialog";
import { adminPropertiesApi } from "../../api/admin";

const EMPTY_FORM = {
  title: "",
  short_description: "",
  description: "",
  purpose: "sale",
  property_type: "Apartment",
  status: "draft",
  price: "",
  currency: "USD",
  price_period: "",
  address: "",
  city: "",
  state: "",
  country: "United States",
  postal_code: "",
  bedrooms: 1,
  bathrooms: 1,
  balconies: "",
  parking_spaces: "",
  area: "",
  area_unit: "sqft",
  furnishing_status: "",
  year_built: "",
  featured: false,
  amenities: "",
};

export default function AdminPropertyFormPage() {
  const { id } = useParams();
  const isEditing = !!id;
  const navigate = useNavigate();

  const [form, setForm] = useState(EMPTY_FORM);
  const [images, setImages] = useState([]);
  const [newFiles, setNewFiles] = useState([]);
  const [loading, setLoading] = useState(isEditing);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});
  const propertyId = id ? Number(id) : null;
  const [confirmImage, setConfirmImage] = useState(null);

  useEffect(() => {
    if (!isEditing) return;

    adminPropertiesApi
      .show(id)
      .then((property) => {
        setForm({
          ...EMPTY_FORM,
          ...property,
          amenities: (property.amenities || []).join(", "),
        });
        setImages(property.images || []);
      })
      .catch((err) => toast.error(err.message || "Unable to load property."))
      .finally(() => setLoading(false));
  }, [id, isEditing]);

  const handleChange = (field, value) => setForm((prev) => ({ ...prev, [field]: value }));

  const buildPayload = () => ({
    ...form,
    amenities: form.amenities
      .split(",")
      .map((a) => a.trim())
      .filter(Boolean),
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});

    try {
      if (isEditing) {
        await adminPropertiesApi.update(id, buildPayload());
        toast.success("Property updated successfully.");
      } else {
        const formData = new FormData();
        Object.entries(buildPayload()).forEach(([key, value]) => {
          if (key === "amenities") {
            value.forEach((amenity) => formData.append("amenities[]", amenity));
          } else if (value !== null && value !== undefined && value !== "") {
            formData.append(key, value);
          }
        });
        newFiles.forEach((file) => formData.append("images[]", file));

        const property = await adminPropertiesApi.create(formData);
        toast.success("Property created successfully.");
        navigate(`/admin/properties/${property.id}/edit`, { replace: true });
        return;
      }
    } catch (err) {
      setErrors(err.errors || {});
      toast.error(err.message || "Unable to save property.");
    } finally {
      setSaving(false);
    }
  };

  const handleUploadMoreImages = async () => {
    if (newFiles.length === 0) return;
    setSaving(true);
    try {
      const formData = new FormData();
      newFiles.forEach((file) => formData.append("images[]", file));
      const updatedImages = await adminPropertiesApi.uploadImages(propertyId, formData);
      setImages(updatedImages);
      setNewFiles([]);
      toast.success("Images uploaded successfully.");
    } catch (err) {
      toast.error(err.message || "Unable to upload images.");
    } finally {
      setSaving(false);
    }
  };

  const handleSetCover = async (imageId) => {
    try {
      const updatedImages = await adminPropertiesApi.setCoverImage(propertyId, imageId);
      setImages(updatedImages);
    } catch (err) {
      toast.error(err.message || "Unable to update cover image.");
    }
  };

  const handleDeleteImage = async () => {
    try {
      await adminPropertiesApi.deleteImage(propertyId, confirmImage.id);
      setImages((prev) => prev.filter((img) => img.id !== confirmImage.id));
      setConfirmImage(null);
      toast.success("Image deleted.");
    } catch (err) {
      toast.error(err.message || "Unable to delete image.");
    }
  };

  if (loading) return <PageLoader />;

  return (
    <div className="max-w-4xl">
      <h1 className="text-2xl font-bold mb-6">{isEditing ? "Edit Property" : "New Property"}</h1>

      <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Field label="Title" error={errors.title}>
            <input className="input" value={form.title} onChange={(e) => handleChange("title", e.target.value)} required />
          </Field>
          <Field label="Reference Number">
            <input className="input bg-gray-50" value={form.reference_number || "Auto-generated"} disabled />
          </Field>
        </div>

        <Field label="Short Description" error={errors.short_description}>
          <input className="input" value={form.short_description || ""} onChange={(e) => handleChange("short_description", e.target.value)} />
        </Field>

        <Field label="Description" error={errors.description}>
          <textarea className="input h-32 resize-none" value={form.description} onChange={(e) => handleChange("description", e.target.value)} required />
        </Field>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Field label="Purpose">
            <select className="input" value={form.purpose} onChange={(e) => handleChange("purpose", e.target.value)}>
              <option value="sale">Sale</option>
              <option value="rent">Rent</option>
            </select>
          </Field>
          <Field label="Property Type">
            <input className="input" value={form.property_type} onChange={(e) => handleChange("property_type", e.target.value)} required />
          </Field>
          <Field label="Status">
            <select className="input" value={form.status} onChange={(e) => handleChange("status", e.target.value)}>
              <option value="draft">Draft</option>
              <option value="published">Published</option>
              <option value="sold">Sold</option>
              <option value="rented">Rented</option>
              <option value="archived">Archived</option>
            </select>
          </Field>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Field label="Price" error={errors.price}>
            <input type="number" className="input" value={form.price} onChange={(e) => handleChange("price", e.target.value)} required />
          </Field>
          <Field label="Currency">
            <input className="input" value={form.currency} onChange={(e) => handleChange("currency", e.target.value)} maxLength={3} />
          </Field>
          <Field label="Price Period (for rent)">
            <input className="input" placeholder="month" value={form.price_period || ""} onChange={(e) => handleChange("price_period", e.target.value)} />
          </Field>
        </div>

        <Field label="Address" error={errors.address}>
          <input className="input" value={form.address} onChange={(e) => handleChange("address", e.target.value)} required />
        </Field>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Field label="City" error={errors.city}>
            <input className="input" value={form.city} onChange={(e) => handleChange("city", e.target.value)} required />
          </Field>
          <Field label="State">
            <input className="input" value={form.state || ""} onChange={(e) => handleChange("state", e.target.value)} />
          </Field>
          <Field label="Postal Code">
            <input className="input" value={form.postal_code || ""} onChange={(e) => handleChange("postal_code", e.target.value)} />
          </Field>
        </div>

        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Field label="Bedrooms">
            <input type="number" className="input" value={form.bedrooms} onChange={(e) => handleChange("bedrooms", e.target.value)} required />
          </Field>
          <Field label="Bathrooms">
            <input type="number" className="input" value={form.bathrooms} onChange={(e) => handleChange("bathrooms", e.target.value)} required />
          </Field>
          <Field label="Area">
            <input type="number" className="input" value={form.area} onChange={(e) => handleChange("area", e.target.value)} required />
          </Field>
          <Field label="Area Unit">
            <input className="input" value={form.area_unit} onChange={(e) => handleChange("area_unit", e.target.value)} />
          </Field>
        </div>

        <Field label="Amenities (comma separated)">
          <input className="input" value={form.amenities} onChange={(e) => handleChange("amenities", e.target.value)} />
        </Field>

        <label className="flex items-center gap-2">
          <input type="checkbox" checked={!!form.featured} onChange={(e) => handleChange("featured", e.target.checked)} />
          <span className="text-sm text-gray-700">Featured Property</span>
        </label>

        {!isEditing && (
          <Field label="Property Images">
            <input
              type="file"
              multiple
              accept="image/*"
              onChange={(e) => setNewFiles(Array.from(e.target.files))}
              className="input"
            />
          </Field>
        )}

        <div className="flex gap-3 pt-2">
          <button type="submit" disabled={saving} className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-60">
            {saving ? "Saving..." : isEditing ? "Save Changes" : "Create Property"}
          </button>
          <button type="button" onClick={() => navigate("/admin/properties")} className="border border-gray-300 px-6 py-2 rounded hover:bg-gray-50">
            Cancel
          </button>
        </div>
      </form>

      {isEditing && (
        <div className="bg-white rounded-lg shadow p-6 mt-6">
          <h2 className="text-lg font-semibold mb-4">Images</h2>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            {images.map((image) => (
              <div key={image.id} className="relative border rounded overflow-hidden">
                <img src={image.url} alt={image.alt_text || ""} className="w-full h-32 object-cover" />
                {image.is_cover && (
                  <span className="absolute top-1 left-1 bg-blue-600 text-white text-xs px-2 py-0.5 rounded">Cover</span>
                )}
                <div className="p-2 flex justify-between text-xs">
                  {!image.is_cover && (
                    <button onClick={() => handleSetCover(image.id)} className="text-blue-600 hover:underline">
                      Set Cover
                    </button>
                  )}
                  <button onClick={() => setConfirmImage(image)} className="text-red-600 hover:underline ml-auto">
                    Delete
                  </button>
                </div>
              </div>
            ))}
          </div>

          <div className="flex gap-3 items-center">
            <input
              type="file"
              multiple
              accept="image/*"
              onChange={(e) => setNewFiles(Array.from(e.target.files))}
              className="input flex-1"
            />
            <button onClick={handleUploadMoreImages} disabled={saving || newFiles.length === 0} className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-60">
              Upload
            </button>
          </div>
        </div>
      )}

      <ConfirmDialog
        open={!!confirmImage}
        title="Delete image?"
        message="This action cannot be undone."
        confirmLabel="Delete"
        danger
        onConfirm={handleDeleteImage}
        onCancel={() => setConfirmImage(null)}
      />
    </div>
  );
}

function Field({ label, error, children }) {
  return (
    <div>
      <label className="block text-sm text-gray-600 mb-1">{label}</label>
      {children}
      {error && <p className="text-red-500 text-xs mt-1">{error[0]}</p>}
    </div>
  );
}
