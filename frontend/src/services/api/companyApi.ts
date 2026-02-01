import { api } from './axios';

export const companyApi = {
  get: () => api.get('/company'),
  update: (data: Record<string, unknown>) => api.put('/company', data),
};
