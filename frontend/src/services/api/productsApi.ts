import { api } from './axios';

export const productsApi = {
  list: (params?: { search?: string; supplierId?: string; active?: boolean; page?: number; limit?: number }) =>
    api.get<{ data: unknown[]; total: number; page: number; limit: number; totalPages: number }>('/products', { params }),
  get: (id: string) => api.get(`/products/${id}`),
  create: (data: Record<string, unknown>) => api.post('/products', data),
  update: (id: string, data: Record<string, unknown>) =>
    api.put(`/products/${id}`, data),
  delete: (id: string) => api.delete(`/products/${id}`),
  bulkDelete: (ids: string[]) =>
    api.post<{ deleted: number }>('/products/bulk-delete', { ids }),
  import: (file: File, supplierId?: string) => {
    const form = new FormData();
    form.append('file', file);
    if (supplierId) form.append('supplierId', supplierId);
    return api.post<{ created: number; errors: string[] }>('/products/import', form);
  },
  /** Excel dışa aktar; includeExisting true ise mevcut ürünler (filtreye göre) dahil edilir. */
  export: (params?: { includeExisting?: boolean; search?: string; supplierId?: string; active?: boolean }) =>
    api.get<Blob>('/products/export', {
      params: {
        includeExisting: params?.includeExisting !== false ? 'true' : 'false',
        search: params?.search,
        supplierId: params?.supplierId,
        active: params?.active,
      },
      responseType: 'blob',
    }),
  /** XML feed URL'den (RSS / Google Shopping g:) ürünleri çeker. Resimler ve tedarikçi dahil. */
  importFromFeed: (params: { feedUrl: string; supplierId?: string }) =>
    api.post<{ created: number; updated: number; errors: string[] }>('/products/import-from-feed', params),
};
