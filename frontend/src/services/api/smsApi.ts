import { api } from './axios';

export const smsApi = {
  config: () => api.get<{ smsConfigured: boolean }>('/sms/config'),
  test: (data: { phone: string; message?: string }) => api.post<{ ok: boolean; message?: string }>('/sms/test', data),
};
