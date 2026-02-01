import { api } from './axios';

export interface SupplierBalanceRow {
  supplierId: string;
  supplierName: string;
  totalPurchases: number;
  totalPayments: number;
  balance: number;
}

export const supplierPaymentsApi = {
  bySupplier: (supplierId: string) =>
    api.get(`/supplier-payments/supplier/${supplierId}`),
  balance: (supplierId: string) =>
    api.get<{ totalPurchases: number; totalPayments: number; balance: number }>(
      `/supplier-payments/supplier/${supplierId}/balance`
    ),
  balances: () =>
    api.get<SupplierBalanceRow[]>('/supplier-payments/balances'),
  create: (data: {
    supplierId: string;
    amount: number;
    paymentDate: string;
    paymentType?: string;
    reference?: string;
    notes?: string;
    purchaseId?: string;
    kasaId?: string;
  }) => api.post('/supplier-payments', data),
};
