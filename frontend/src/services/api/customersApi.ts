import { api } from './axios';

export const customersApi = {
  list: (params?: { active?: boolean; search?: string; page?: number; limit?: number; withBalance?: boolean }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/customers', { params }),
  latest: (limit = 5) => api.get('/customers/latest', { params: { limit } }),
  get: (id: string) => api.get(`/customers/${id}`),
  create: (data: Record<string, unknown>) => api.post('/customers', data),
  update: (id: string, data: Record<string, unknown>) =>
    api.put(`/customers/${id}`, data),
  delete: (id: string) => api.delete(`/customers/${id}`),
};
