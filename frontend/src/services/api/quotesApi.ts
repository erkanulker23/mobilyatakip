import { api } from './axios';

export const quotesApi = {
  list: (params?: { customerId?: string; status?: string; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/quotes', { params }),
  latest: (limit = 5) => api.get('/quotes', { params: { limit } }),
  get: (id: string) => api.get(`/quotes/${id}`),
  create: (data: Record<string, unknown>) => api.post('/quotes', data),
  updateStatus: (id: string, status: string) =>
    api.put(`/quotes/${id}/status`, { status }),
  newRevision: (id: string, data: Record<string, unknown>) =>
    api.put(`/quotes/${id}/revision`, data),
  convertToSale: (id: string, warehouseId: string) =>
    api.post(`/quotes/${id}/convert-to-sale`, { warehouseId }),
  delete: (id: string) => api.delete(`/quotes/${id}`),
  getPdf: (id: string) =>
    api.get(`/quotes/${id}/pdf`, { responseType: 'blob' }),
};
