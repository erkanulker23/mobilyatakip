import { api } from './axios';

export const personnelApi = {
  list: (params?: { active?: boolean; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/personnel', { params }),
  get: (id: string) => api.get(`/personnel/${id}`),
  create: (data: Record<string, unknown>) => api.post('/personnel', data),
  update: (id: string, data: Record<string, unknown>) => api.put(`/personnel/${id}`, data),
  delete: (id: string) => api.delete(`/personnel/${id}`),
};
