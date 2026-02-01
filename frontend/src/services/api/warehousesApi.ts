import { api } from './axios';

export const warehousesApi = {
  list: (params?: { active?: boolean }) => api.get('/warehouses', { params }),
  get: (id: string) => api.get(`/warehouses/${id}`),
  create: (data: Record<string, unknown>) => api.post('/warehouses', data),
  update: (id: string, data: Record<string, unknown>) =>
    api.put(`/warehouses/${id}`, data),
};
