import { api } from './axios';

export const paymentsApi = {
  paytrConfig: () => api.get<{ paytrActive: boolean }>('/payments/paytr-config'),
  paytrToken: (data: {
    merchantOid: string;
    amountKurus: number;
    customerEmail: string;
    customerName: string;
    successUrl?: string;
    failUrl?: string;
    callbackUrl?: string;
  }) => api.post<{ token: string; iframeUrl: string }>('/payments/paytr-token', data),
};
