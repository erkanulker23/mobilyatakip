import { api } from './axios';

export const serviceTicketsApi = {
  list: (params?: { customerId?: string; saleId?: string; status?: string; openedAtFrom?: string; openedAtTo?: string; search?: string; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/service-tickets', { params }),
  get: (id: string) => api.get(`/service-tickets/${id}`),
  getPdf: (id: string) => api.get(`/service-tickets/${id}/pdf`, { responseType: 'blob' }),
  create: (data: {
    saleId: string;
    customerId: string;
    underWarranty: boolean;
    issueType: string;
    description?: string;
    assignedUserId?: string;
    assignedVehiclePlate?: string;
    assignedDriverName?: string;
    assignedDriverPhone?: string;
    notes?: string;
    images?: string[];
    serviceChargeAmount?: number | null;
  }) => api.post('/service-tickets', data),
  updateStatus: (id: string, status: string) =>
    api.put(`/service-tickets/${id}/status`, { status }),
  addDetail: (
    id: string,
    data: { userId: string; action: string; notes?: string; parts?: { productId: string; quantity: number }[]; warehouseId?: string; images?: string[] }
  ) => api.post(`/service-tickets/${id}/details`, data),
  openCount: () => api.get<number>('/service-tickets/stats/open-count'),
};
