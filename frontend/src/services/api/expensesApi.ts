import { api } from './axios';

export const expensesApi = {
  list: (params?: { kasaId?: string; from?: string; to?: string }) =>
    api.get('/expenses', { params }),
  get: (id: string) => api.get(`/expenses/${id}`),
  create: (data: Record<string, unknown>) => api.post('/expenses', data),
  update: (id: string, data: Record<string, unknown>) => api.put(`/expenses/${id}`, data),
  delete: (id: string) => api.delete(`/expenses/${id}`),
};
