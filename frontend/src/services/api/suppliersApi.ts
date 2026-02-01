import { api } from './axios';

export const suppliersApi = {
  list: (params?: { active?: boolean; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/suppliers', { params }),
  get: (id: string) => api.get(`/suppliers/${id}`),
  getStats: (id: string) =>
    api.get<{ salesCount: number; customerCount: number }>(`/suppliers/${id}/stats`),
  create: (data: Record<string, unknown>) => api.post('/suppliers', data),
  update: (id: string, data: Record<string, unknown>) =>
    api.put(`/suppliers/${id}`, data),
  delete: (id: string) => api.delete(`/suppliers/${id}`),
};
