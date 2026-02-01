import { api } from './axios';

export const customerPaymentsApi = {
  latest: (limit = 5) => api.get('/customer-payments/latest', { params: { limit } }),
  byCustomer: (customerId: string) => api.get(`/customer-payments/customer/${customerId}`),
  balance: (customerId: string) =>
    api.get<{ totalSales: number; totalPayments: number; balance: number; overdueAmount: number }>(
      `/customer-payments/customer/${customerId}/balance`
    ),
  withDebt: () =>
    api.get<Array<{ customerId: string; balance: number; totalSales: number; totalPayments: number }>>(
      '/customer-payments/with-debt'
    ),
  create: (data: Record<string, unknown>) => api.post('/customer-payments', data),
};
