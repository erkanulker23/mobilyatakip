import { api } from './axios';

export interface XmlFeedItem {
  id: string;
  name: string;
  url: string;
  supplierId: string | null;
  supplier?: { id: string; name: string } | null;
  createdAt: string;
}

export const xmlFeedsApi = {
  list: () =>
    api.get<XmlFeedItem[]>('/xml-feeds'),
  create: (data: { name: string; url: string; supplierId?: string | null }) =>
    api.post<XmlFeedItem>('/xml-feeds', data),
  delete: (id: string, deleteProducts?: boolean) =>
    api.delete<{ productsDeleted: number }>(`/xml-feeds/${id}${deleteProducts === true ? '?deleteProducts=true' : ''}`),
};
