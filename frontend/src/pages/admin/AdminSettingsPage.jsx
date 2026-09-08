import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import { adminSettingsApi } from "../../api/admin";

const FIELDS = [
  { key: "company_name", label: "Company Name" },
  { key: "company_email", label: "Company Email" },
  { key: "company_phone", label: "Company Phone" },
  { key: "company_address", label: "Company Address" },
  { key: "hero_title", label: "Hero Title" },
  { key: "hero_subtitle", label: "Hero Subtitle" },
  { key: "about_title", label: "About Title" },
  { key: "about_content", label: "About Content", textarea: true },
  { key: "years_of_experience", label: "Years of Experience" },
  { key: "projects_completed", label: "Projects Completed" },
  { key: "area_delivered", label: "Area Delivered (Mn. Sq.)" },
  { key: "ongoing_projects", label: "Ongoing Projects" },
  { key: "facebook_url", label: "Facebook URL" },
  { key: "instagram_url", label: "Instagram URL" },
  { key: "linkedin_url", label: "LinkedIn URL" },
  { key: "seo_title", label: "SEO Title" },
  { key: "seo_description", label: "SEO Description", textarea: true },
];

export default function AdminSettingsPage() {
  const [form, setForm] = useState({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    adminSettingsApi
      .get()
      .then(setForm)
      .catch((err) => toast.error(err.message || "Unable to load settings."))
      .finally(() => setLoading(false));
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const updated = await adminSettingsApi.update(form);
      setForm(updated);
      toast.success("Settings updated successfully.");
    } catch (err) {
      toast.error(err.message || "Unable to update settings.");
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <PageLoader />;

  return (
    <div className="max-w-3xl">
      <h1 className="text-2xl font-bold mb-6">Site Settings</h1>
      <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-4">
        {FIELDS.map((field) => (
          <div key={field.key}>
            <label className="block text-sm text-gray-600 mb-1">{field.label}</label>
            {field.textarea ? (
              <textarea
                className="input h-24 resize-none"
                value={form[field.key] || ""}
                onChange={(e) => setForm({ ...form, [field.key]: e.target.value })}
              />
            ) : (
              <input
                className="input"
                value={form[field.key] || ""}
                onChange={(e) => setForm({ ...form, [field.key]: e.target.value })}
              />
            )}
          </div>
        ))}
        <button type="submit" disabled={saving} className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-60">
          {saving ? "Saving..." : "Save Settings"}
        </button>
      </form>
    </div>
  );
}
