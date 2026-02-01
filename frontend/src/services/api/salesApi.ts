import { api } from './axios';

export interface CreateSalePayload {
  customerId: string;
  warehouseId: string;
  dueDate?: string;
  notes?: string;
  items: Array<{ productId: string; quantity: number; unitPrice?: number; kdvRate?: number }>;
}

export const salesApi = {
  list: (params?: { customerId?: string; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/sales', { params }),
  latest: (limit = 5) => api.get('/sales', { params: { limit } }),
  get: (id: string) => api.get(`/sales/${id}`),
  getPdf: (id: string) => api.get(`/sales/${id}/pdf`, { responseType: 'blob' }),
  create: (payload: CreateSalePayload) => api.post('/sales', payload),
  update: (id: string, data: { dueDate?: string; notes?: string }) => api.put(`/sales/${id}`, data),
  delete: (id: string) => api.delete(`/sales/${id}`),
  fromQuote: (quoteId: string, warehouseId: string) =>
    api.post('/sales/from-quote', { quoteId, warehouseId }),
};
