import { useCallback, useEffect, useState } from "react";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import ConfirmDialog from "../../components/ui/ConfirmDialog";
import { adminTestimonialsApi } from "../../api/admin";

const EMPTY_FORM = { name: "", designation: "", text: "", rating: 5 };

export default function AdminTestimonialsPage() {
  const [testimonials, setTestimonials] = useState([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState(EMPTY_FORM);
  const [imageFile, setImageFile] = useState(null);
  const [saving, setSaving] = useState(false);
  const [confirmTarget, setConfirmTarget] = useState(null);

  const load = useCallback(() => {
    setLoading(true);
    adminTestimonialsApi
      .list()
      .then((response) => setTestimonials(response.data))
      .catch((err) => toast.error(err.message || "Unable to load testimonials."))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const handleCreate = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const formData = new FormData();
      Object.entries(form).forEach(([key, value]) => formData.append(key, value));
      if (imageFile) formData.append("image", imageFile);

      await adminTestimonialsApi.create(formData);
      toast.success("Testimonial created.");
      setForm(EMPTY_FORM);
      setImageFile(null);
      load();
    } catch (err) {
      toast.error(err.message || "Unable to create testimonial.");
    } finally {
      setSaving(false);
    }
  };

  const handleToggleApproval = async (testimonial) => {
    try {
      await adminTestimonialsApi.toggleApproval(testimonial.id);
      load();
    } catch (err) {
      toast.error(err.message || "Unable to update testimonial.");
    }
  };

  const handleDelete = async () => {
    try {
      await adminTestimonialsApi.remove(confirmTarget.id);
      toast.success("Testimonial deleted.");
      setConfirmTarget(null);
      load();
    } catch (err) {
      toast.error(err.message || "Unable to delete testimonial.");
    }
  };

  if (loading) return <PageLoader />;

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div className="lg:col-span-1 bg-white rounded-lg shadow p-6 h-fit">
        <h2 className="text-lg font-semibold mb-4">Add Testimonial</h2>
        <form onSubmit={handleCreate} className="space-y-3">
          <input className="input" placeholder="Name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required />
          <input className="input" placeholder="Designation" value={form.designation} onChange={(e) => setForm({ ...form, designation: e.target.value })} />
          <textarea className="input h-24 resize-none" placeholder="Testimonial text" value={form.text} onChange={(e) => setForm({ ...form, text: e.target.value })} required />
          <select className="input" value={form.rating} onChange={(e) => setForm({ ...form, rating: e.target.value })}>
            {[5, 4, 3, 2, 1].map((r) => (
              <option key={r} value={r}>{r} Stars</option>
            ))}
          </select>
          <input type="file" accept="image/*" className="input" onChange={(e) => setImageFile(e.target.files[0])} />
          <button type="submit" disabled={saving} className="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-60">
            {saving ? "Saving..." : "Add Testimonial"}
          </button>
        </form>
      </div>

      <div className="lg:col-span-2 space-y-4">
        <h1 className="text-2xl font-bold">Testimonials</h1>
        {testimonials.map((testimonial) => (
          <div key={testimonial.id} className="bg-white rounded-lg shadow p-4 flex justify-between items-start gap-4">
            <div>
              <p className="font-semibold">{testimonial.name} <span className="text-gray-400 font-normal text-sm">{testimonial.designation}</span></p>
              <p className="text-gray-600 text-sm mt-1">{testimonial.text}</p>
              <span className={`inline-block mt-2 text-xs px-2 py-1 rounded ${testimonial.is_approved ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"}`}>
                {testimonial.is_approved ? "Approved" : "Pending"}
              </span>
            </div>
            <div className="flex flex-col gap-2 text-sm shrink-0">
              <button onClick={() => handleToggleApproval(testimonial)} className="text-blue-600 hover:underline">
                {testimonial.is_approved ? "Unapprove" : "Approve"}
              </button>
              <button onClick={() => setConfirmTarget(testimonial)} className="text-red-600 hover:underline">
                Delete
              </button>
            </div>
          </div>
        ))}
      </div>

      <ConfirmDialog
        open={!!confirmTarget}
        title="Delete testimonial?"
        confirmLabel="Delete"
        danger
        onConfirm={handleDelete}
        onCancel={() => setConfirmTarget(null)}
      />
    </div>
  );
}
