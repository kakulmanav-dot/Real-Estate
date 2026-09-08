import apiClient from "./client";

export const homeApi = {
  get: () => apiClient.get("/home").then((r) => r.data.data),
};

export const settingsApi = {
  get: () => apiClient.get("/settings").then((r) => r.data.data),
};

export const testimonialsApi = {
  list: () => apiClient.get("/testimonials").then((r) => r.data.data),
};

export const enquiriesApi = {
  create: (payload) => apiClient.post("/enquiries", payload).then((r) => r.data),
};
