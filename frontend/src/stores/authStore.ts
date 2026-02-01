import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface User {
  id: string;
  email: string;
  name: string;
  role: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  setAuth: (user: User | null, token: string | null) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      setAuth: (user, token) => {
        if (token) localStorage.setItem('access_token', token);
        else localStorage.removeItem('access_token');
        set({ user, token });
      },
      logout: () => {
        localStorage.removeItem('access_token');
        set({ user: null, token: null });
      },
    }),
    { name: 'auth-storage' }
  )
);
