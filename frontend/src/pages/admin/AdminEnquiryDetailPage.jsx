import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { toast } from "react-toastify";
import PageLoader from "../../components/ui/PageLoader";
import ConfirmDialog from "../../components/ui/ConfirmDialog";
import { adminEnquiriesApi } from "../../api/admin";

export default function AdminEnquiryDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [enquiry, setEnquiry] = useState(null);
  const [loading, setLoading] = useState(true);
  const [status, setStatus] = useState("");
  const [notes, setNotes] = useState("");
  const [saving, setSaving] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(false);

  useEffect(() => {
    adminEnquiriesApi
      .show(id)
      .then((data) => {
        setEnquiry(data);
        setStatus(data.status);
        setNotes(data.admin_notes || "");
      })
      .catch((err) => toast.error(err.message || "Unable to load enquiry."))
      .finally(() => setLoading(false));
  }, [id]);

  const handleSave = async () => {
    setSaving(true);
    try {
      const updated = await adminEnquiriesApi.update(id, { status, admin_notes: notes });
      setEnquiry(updated);
      toast.success("Enquiry updated.");
    } catch (err) {
      toast.error(err.message || "Unable to update enquiry.");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async () => {
    try {
      await adminEnquiriesApi.remove(id);
      toast.success("Enquiry deleted.");
      navigate("/admin/enquiries");
    } catch (err) {
      toast.error(err.message || "Unable to delete enquiry.");
    }
  };

  if (loading) return <PageLoader />;
  if (!enquiry) return null;

  return (
    <div className="max-w-2xl">
      <Link to="/admin/enquiries" className="text-blue-600 hover:underline text-sm">
        &larr; Back to enquiries
      </Link>

      <div className="bg-white rounded-lg shadow p-6 mt-4 space-y-4">
        <h1 className="text-xl font-bold">{enquiry.name}</h1>
        <p className="text-gray-500">{enquiry.email} {enquiry.phone && `• ${enquiry.phone}`}</p>
        {enquiry.property && (
          <p className="text-sm">
            Regarding: <Link to={`/properties/${enquiry.property.slug}`} className="text-blue-600 hover:underline">{enquiry.property.title}</Link>
          </p>
        )}
        <div>
          <p className="text-sm text-gray-500 mb-1">Message</p>
          <p className="bg-gray-50 rounded p-3 text-gray-700">{enquiry.message}</p>
        </div>

        <div>
          <label className="block text-sm text-gray-600 mb-1">Status</label>
          <select className="input" value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="new">New</option>
            <option value="contacted">Contacted</option>
            <option value="qualified">Qualified</option>
            <option value="closed">Closed</option>
            <option value="spam">Spam</option>
          </select>
        </div>

        <div>
          <label className="block text-sm text-gray-600 mb-1">Private Notes</label>
          <textarea className="input h-28 resize-none" value={notes} onChange={(e) => setNotes(e.target.value)} />
        </div>

        <div className="flex gap-3">
          <button onClick={handleSave} disabled={saving} className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-60">
            {saving ? "Saving..." : "Save Changes"}
          </button>
          <button onClick={() => setConfirmDelete(true)} className="text-red-600 border border-red-200 px-6 py-2 rounded hover:bg-red-50">
            Delete
          </button>
        </div>
      </div>

      <ConfirmDialog
        open={confirmDelete}
        title="Delete this enquiry?"
        message="This action cannot be undone."
        confirmLabel="Delete"
        danger
        onConfirm={handleDelete}
        onCancel={() => setConfirmDelete(false)}
      />
    </div>
  );
}
