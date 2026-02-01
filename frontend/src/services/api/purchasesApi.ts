import { api } from './axios';

export interface CreatePurchasePayload {
  supplierId: string;
  warehouseId: string;
  purchaseDate: string;
  dueDate?: string;
  isReturn?: boolean;
  notes?: string;
  /** Nakit alışta kasadan çıkış için */
  paymentType?: 'nakit' | 'havale' | 'kredi_karti' | 'diger';
  kasaId?: string;
  items: Array<{ productId: string; quantity: number; unitPrice?: number; kdvRate?: number }>;
}

export const purchasesApi = {
  list: (params?: { supplierId?: string; dateFrom?: string; dateTo?: string; purchaseNumber?: string; isReturn?: boolean; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/purchases', { params }),
  get: (id: string) => api.get(`/purchases/${id}`),
  create: (payload: CreatePurchasePayload) => api.post('/purchases', payload),
  update: (id: string, data: { purchaseDate?: string; dueDate?: string; notes?: string; paidAmount?: number }) =>
    api.put(`/purchases/${id}`, data),
  delete: (id: string) => api.delete(`/purchases/${id}`),
};
