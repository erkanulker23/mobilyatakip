import { BrowserRouter } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider } from './contexts/AuthContext';
import AppRoutes from './routes/AppRoutes';
import { useThemeStore } from './stores/themeStore';

/** Tema store’unun (persist) rehydrate olması için abone olur; index.html script + store tema sınıfını uygular. */
function ThemeInit() {
  useThemeStore((s) => s.theme);
  return null;
}

/** Toaster'ı tema ile uyumlu gösterir (koyu modda koyu arka plan). */
function ThemedToaster() {
  const theme = useThemeStore((s) => s.theme);
  const isDark = theme === 'dark';
  return (
    <Toaster
      position="top-right"
      toastOptions={{
        className: isDark ? '!bg-zinc-800 !text-zinc-100 !border-zinc-700' : '',
        style: isDark
          ? { background: 'rgb(39 39 42)', color: 'rgb(243 244 246)', border: '1px solid rgb(63 63 70)' }
          : undefined,
      }}
    />
  );
}

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <ThemeInit />
        <AppRoutes />
        <ThemedToaster />
      </AuthProvider>
    </BrowserRouter>
  );
}
