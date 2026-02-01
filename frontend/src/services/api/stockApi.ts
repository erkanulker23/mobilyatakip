import { api } from './axios';

export interface StockRow {
  id: string;
  productId: string;
  warehouseId: string;
  quantity: number;
  reservedQuantity?: number;
  product?: { id: string; name: string; sku?: string };
  warehouse?: { id: string; name: string };
}

export const stockApi = {
  byWarehouse: (warehouseId: string) =>
    api.get<StockRow[]>(`/stock/warehouse/${warehouseId}`),
};
