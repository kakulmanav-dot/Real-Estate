import apiClient from "./client";

export const propertiesApi = {
  list: (params) => apiClient.get("/properties", { params }).then((r) => r.data),
  featured: () => apiClient.get("/properties/featured").then((r) => r.data.data),
  show: (slug) => apiClient.get(`/properties/${slug}`).then((r) => r.data.data),
  similar: (slug) => apiClient.get(`/properties/${slug}/similar`).then((r) => r.data.data),
};

export const favoritesApi = {
  list: (params) => apiClient.get("/favorites", { params }).then((r) => r.data),
  save: (slug) => apiClient.post(`/favorites/${slug}`).then((r) => r.data),
  remove: (slug) => apiClient.delete(`/favorites/${slug}`).then((r) => r.data),
};
