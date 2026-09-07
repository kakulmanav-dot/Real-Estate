import apiClient from "./client";

export const authApi = {
  register: (payload) => apiClient.post("/auth/register", payload).then((r) => r.data.data),
  login: (payload) => apiClient.post("/auth/login", payload).then((r) => r.data.data),
  logout: () => apiClient.post("/auth/logout").then((r) => r.data),
  me: () => apiClient.get("/auth/me").then((r) => r.data.data),
  forgotPassword: (payload) => apiClient.post("/auth/forgot-password", payload).then((r) => r.data),
  resetPassword: (payload) => apiClient.post("/auth/reset-password", payload).then((r) => r.data),
  updateProfile: (payload) => {
    const isFormData = payload instanceof FormData;

    if (isFormData) {
      // PHP cannot parse multipart bodies on PUT requests, so we spoof the method over POST.
      payload.append("_method", "PUT");
      return apiClient
        .post("/profile", payload, { headers: { "Content-Type": "multipart/form-data" } })
        .then((r) => r.data.data);
    }

    return apiClient.put("/profile", payload).then((r) => r.data.data);
  },
  changePassword: (payload) => apiClient.put("/profile/password", payload).then((r) => r.data),
};
