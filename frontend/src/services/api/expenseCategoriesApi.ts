import { api } from './axios';

export const expenseCategoriesApi = {
  list: () => api.get('/expense-categories'),
  create: (data: { name: string; sortOrder?: number }) =>
    api.post('/expense-categories', data),
  update: (id: string, data: { name?: string; sortOrder?: number }) =>
    api.put(`/expense-categories/${id}`, data),
  delete: (id: string) => api.delete(`/expense-categories/${id}`),
};
