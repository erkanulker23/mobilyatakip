import { api } from './axios';

export interface LoginDto {
  email: string;
  password: string;
}

export interface RegisterDto {
  email: string;
  password: string;
  name: string;
  role?: string;
}

export interface AuthResponse {
  access_token: string;
  user: { id: string; email: string; name: string; role: string };
}

export const authApi = {
  login: (data: LoginDto) => api.post<AuthResponse>('/auth/login', data),
  register: (data: RegisterDto) => api.post<AuthResponse>('/auth/register', data),
  me: () => api.get('/auth/me'),
  users: () => api.get<Array<{ id: string; email: string; name: string; role: string }>>('/auth/users'),
  assignableUsers: () => api.get<Array<{ id: string; name: string }>>('/auth/assignable-users'),
  createUser: (data: { email: string; name: string; password: string; role?: string }) =>
    api.post<AuthResponse>('/auth/users', data),
  updateUserRole: (id: string, role: string) =>
    api.put(`/auth/users/${id}/role`, { role }),
};
