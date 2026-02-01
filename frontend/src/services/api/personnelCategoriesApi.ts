import { api } from './axios';

export const personnelCategoriesApi = {
  list: () => api.get<Array<{ id: string; name: string }>>('/personnel-categories'),
  create: (data: { name: string }) => api.post<{ id: string; name: string }>('/personnel-categories', data),
};
