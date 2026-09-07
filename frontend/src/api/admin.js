import apiClient from "./client";

function withMethodSpoof(formData, method) {
  formData.append("_method", method);
  return formData;
}

export const adminDashboardApi = {
  get: () => apiClient.get("/admin/dashboard").then((r) => r.data.data),
};

export const adminPropertiesApi = {
  list: (params) => apiClient.get("/admin/properties", { params }).then((r) => r.data),
  show: (id) => apiClient.get(`/admin/properties/${id}`).then((r) => r.data.data),
  create: (formData) => apiClient.post("/admin/properties", formData, { headers: { "Content-Type": "multipart/form-data" } }).then((r) => r.data.data),
  update: (id, payload) => apiClient.put(`/admin/properties/${id}`, payload).then((r) => r.data.data),
  remove: (id) => apiClient.delete(`/admin/properties/${id}`).then((r) => r.data),
  restore: (id) => apiClient.post(`/admin/properties/${id}/restore`).then((r) => r.data.data),
  forceDelete: (id) => apiClient.delete(`/admin/properties/${id}/force`).then((r) => r.data),
  updateStatus: (id, status) => apiClient.patch(`/admin/properties/${id}/status`, { status }).then((r) => r.data.data),
  updateFeatured: (id, featured) => apiClient.patch(`/admin/properties/${id}/featured`, { featured }).then((r) => r.data.data),

  uploadImages: (id, formData) => apiClient.post(`/admin/properties/${id}/images`, formData, { headers: { "Content-Type": "multipart/form-data" } }).then((r) => r.data.data),
  updateImage: (propertyId, imageId, payload) => apiClient.put(`/admin/properties/${propertyId}/images/${imageId}`, payload).then((r) => r.data.data),
  setCoverImage: (propertyId, imageId) => apiClient.patch(`/admin/properties/${propertyId}/images/${imageId}/cover`).then((r) => r.data.data),
  reorderImages: (propertyId, order) => apiClient.post(`/admin/properties/${propertyId}/images/reorder`, { order }).then((r) => r.data.data),
  deleteImage: (propertyId, imageId) => apiClient.delete(`/admin/properties/${propertyId}/images/${imageId}`).then((r) => r.data),
};

export const adminEnquiriesApi = {
  list: (params) => apiClient.get("/admin/enquiries", { params }).then((r) => r.data),
  show: (id) => apiClient.get(`/admin/enquiries/${id}`).then((r) => r.data.data),
  update: (id, payload) => apiClient.patch(`/admin/enquiries/${id}`, payload).then((r) => r.data.data),
  remove: (id) => apiClient.delete(`/admin/enquiries/${id}`).then((r) => r.data),
  export: async (params) => {
    const response = await apiClient.get("/admin/enquiries/export", { params, responseType: "blob" });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `enquiries-${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },
};

export const adminTestimonialsApi = {
  list: (params) => apiClient.get("/admin/testimonials", { params }).then((r) => r.data),
  create: (formData) => apiClient.post("/admin/testimonials", formData, { headers: { "Content-Type": "multipart/form-data" } }).then((r) => r.data.data),
  update: (id, formData) => apiClient.post(`/admin/testimonials/${id}`, withMethodSpoof(formData, "PUT"), { headers: { "Content-Type": "multipart/form-data" } }).then((r) => r.data.data),
  remove: (id) => apiClient.delete(`/admin/testimonials/${id}`).then((r) => r.data),
  toggleApproval: (id) => apiClient.patch(`/admin/testimonials/${id}/approval`).then((r) => r.data.data),
  reorder: (order) => apiClient.post("/admin/testimonials/reorder", { order }).then((r) => r.data),
};

export const adminUsersApi = {
  list: (params) => apiClient.get("/admin/users", { params }).then((r) => r.data),
  show: (id) => apiClient.get(`/admin/users/${id}`).then((r) => r.data.data),
  update: (id, payload) => apiClient.patch(`/admin/users/${id}`, payload).then((r) => r.data.data),
};

export const adminSettingsApi = {
  get: () => apiClient.get("/admin/settings").then((r) => r.data.data),
  update: (payload) => apiClient.put("/admin/settings", payload).then((r) => r.data.data),
};

export const adminActivityLogApi = {
  list: (params) => apiClient.get("/admin/activity-logs", { params }).then((r) => r.data),
};
