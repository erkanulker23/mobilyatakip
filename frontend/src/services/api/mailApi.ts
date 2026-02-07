import { api } from './axios';

export const mailApi = {
  test: (data: { to: string }) =>
    api.post<{ ok: boolean; message?: string }>('/mail/test', data),
};
