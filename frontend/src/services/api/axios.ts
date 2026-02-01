import axios from 'axios';

const API_BASE = import.meta.env.VITE_API_URL ?? '/api';

export const api = axios.create({
  baseURL: API_BASE,
  headers: { 'Content-Type': 'application/json' },
  withCredentials: true,
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (res) => {
    if (res.config.responseType === 'blob' && res.data instanceof Blob) {
      const ct = res.headers['content-type'] || '';
      if (ct.includes('application/json')) {
        return new Promise((_, reject) => {
          const reader = new FileReader();
          reader.onload = () => {
            try {
              const body = JSON.parse(reader.result as string);
              reject(Object.assign(new Error(body?.message || body?.error || 'İstek başarısız'), { response: res, parsed: body }));
            } catch {
              reject(Object.assign(new Error('Beklenmeyen yanıt'), { response: res }));
            }
          };
          reader.readAsText(res.data as Blob);
        });
      }
    }
    return res;
  },
  (err) => {
    if (err.response?.status === 401) {
      const isLoginRequest = err.config?.url?.includes('/auth/login');
      if (!isLoginRequest) {
        localStorage.removeItem('access_token');
        window.location.href = '/giris';
      }
    }
    return Promise.reject(err);
  }
);
