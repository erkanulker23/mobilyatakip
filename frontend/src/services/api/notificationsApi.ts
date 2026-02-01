import { api } from './axios';

export const notificationsApi = {
  byUser: (userId: string, unreadOnly?: boolean) =>
    api.get(`/notifications/user/${userId}`, { params: unreadOnly ? { unreadOnly: true } : undefined }),
  markRead: (id: string) => api.post(`/notifications/${id}/read`),
  markAllRead: (userId: string) => api.post(`/notifications/user/${userId}/read-all`),
};
