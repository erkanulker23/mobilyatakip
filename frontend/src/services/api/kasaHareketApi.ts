import { api } from './axios';

export const kasaHareketApi = {
  list: (params: { kasaId: string; from?: string; to?: string }) =>
    api.get('/kasa-hareket', { params }),
  get: (id: string) => api.get(`/kasa-hareket/${id}`),
  giris: (data: { kasaId: string; amount: number; movementDate: string; description?: string }) =>
    api.post('/kasa-hareket/giris', data),
  virman: (data: {
    fromKasaId: string;
    toKasaId: string;
    amount: number;
    movementDate: string;
    description?: string;
  }) => api.post('/kasa-hareket/virman', data),
  delete: (id: string) => api.delete(`/kasa-hareket/${id}`),
};
