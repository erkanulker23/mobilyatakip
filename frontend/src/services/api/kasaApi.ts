import { api } from './axios';

export const kasaApi = {
  list: (params?: { active?: boolean }) => api.get('/kasa', { params }),
  get: (id: string) => api.get(`/kasa/${id}`),
  create: (data: Record<string, unknown>) => api.post('/kasa', data),
  update: (id: string, data: Record<string, unknown>) => api.put(`/kasa/${id}`, data),
  delete: (id: string) => api.delete(`/kasa/${id}`),
};
