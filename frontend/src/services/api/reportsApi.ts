import { api } from './axios';

export interface ReportSummary {
  from: string;
  to: string;
  sales: { total: number; count: number };
  purchases: { total: number; count: number };
  expenses: { total: number; count: number };
}

export interface IncomeExpenseReport {
  from: string;
  to: string;
  income: { sales: number; customerPayments: number; total: number };
  expense: { purchases: number; expenses: number; supplierPayments: number; total: number };
  net: number;
}

export interface ProductSalesReportRow {
  productId: string;
  productName: string;
  quantity: number;
  total: number;
}

export interface CustomerSalesReportRow {
  customerId: string;
  customerName: string;
  count: number;
  total: number;
}

export const reportsApi = {
  summary: (params?: { from?: string; to?: string }) =>
    api.get<ReportSummary>('/reports/summary', { params }),
  incomeExpense: (params?: { from?: string; to?: string }) =>
    api.get<IncomeExpenseReport>('/reports/income-expense', { params }),
  productSales: (params?: { from?: string; to?: string }) =>
    api.get<{ from: string; to: string; rows: ProductSalesReportRow[] }>('/reports/product-sales', { params }),
  customerSales: (params?: { from?: string; to?: string }) =>
    api.get<{ from: string; to: string; rows: CustomerSalesReportRow[] }>('/reports/customer-sales', { params }),
};
