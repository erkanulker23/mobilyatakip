import { createContext, useContext, useEffect } from 'react';
import type { ReactNode } from 'react';
import { useAuthStore } from '../stores/authStore';
import type { User } from '../stores/authStore';
import { authApi } from '../services/api/authApi';

interface AuthContextValue {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  setAuth: (user: User | null, token: string | null) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const { user, token, setAuth, logout } = useAuthStore();

  useEffect(() => {
    if (!token || user) return;
    authApi
      .me()
      .then(({ data }) => setAuth(data as User, token))
      .catch(() => logout());
  }, [token, user, setAuth, logout]);

  const value: AuthContextValue = {
    user,
    token,
    isAuthenticated: !!token,
    setAuth,
    logout,
  };
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
