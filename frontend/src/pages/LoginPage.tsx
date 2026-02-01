import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { authApi } from '../services/api/authApi';
import { useAuthStore } from '../stores/authStore';
import ThemeToggle from '../components/ThemeToggle';
import toast from 'react-hot-toast';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const { setAuth, token } = useAuthStore();
  const navigate = useNavigate();

  useEffect(() => {
    if (token) navigate(ROUTES.home, { replace: true });
  }, [token, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const { data } = await authApi.login({ email, password });
      setAuth(data.user, data.access_token);
      toast.success('Giriş başarılı');
      navigate(ROUTES.home);
    } catch (err: unknown) {
      const data = (err as { response?: { data?: { message?: string | string[] } } })?.response?.data;
      const raw = data?.message;
      let msg = 'E-posta veya şifre hatalı.';
      if (Array.isArray(raw) && raw[0]) msg = raw[0];
      else if (typeof raw === 'string') msg = raw;
      toast.error(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-slate-50 via-white to-emerald-50/30 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950">
      {/* Tema geçişi */}
      <div className="fixed top-4 right-4 z-10">
        <ThemeToggle />
      </div>
      {/* Decorative background */}
      <div className="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden>
        <div className="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-emerald-200/20 dark:bg-emerald-900/20 blur-3xl" />
        <div className="absolute top-1/2 -left-32 w-64 h-64 rounded-full bg-slate-200/30 dark:bg-zinc-800/30 blur-3xl" />
        <div className="absolute bottom-20 right-1/4 w-48 h-48 rounded-full bg-amber-100/40 dark:bg-amber-900/20 blur-2xl" />
      </div>

      <div className="relative flex flex-1 flex-col items-center justify-center px-4 py-12 sm:py-16">
        {/* Logo & title */}
        <div className="mb-8 text-center">
          <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/25 mb-4">
            <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
          </div>
          <h1 className="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-white tracking-tight">
            Mobilya Takip
          </h1>
          <p className="mt-1.5 text-slate-500 dark:text-zinc-400 text-sm">
            Yönetim paneline giriş yapın
          </p>
        </div>

        {/* Login card */}
        <div className="w-full max-w-[400px]">
          <div className="rounded-2xl border border-slate-200/80 dark:border-zinc-700/80 bg-white/90 dark:bg-zinc-800/90 backdrop-blur-sm shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
            <form onSubmit={handleSubmit} className="space-y-5">
              <div>
                <label htmlFor="login-email" className="block text-sm font-medium text-slate-700 dark:text-zinc-200 mb-1.5">
                  E-posta
                </label>
                <input
                  id="login-email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  autoComplete="email"
                  placeholder="ornek@sirket.com"
                  className="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-zinc-600 bg-slate-50/50 dark:bg-zinc-700/50 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-white dark:focus:bg-zinc-700 transition-colors"
                />
              </div>
              <div>
                <label htmlFor="login-password" className="block text-sm font-medium text-slate-700 dark:text-zinc-200 mb-1.5">
                  Şifre
                </label>
                <div className="relative">
                  <input
                    id="login-password"
                    type={showPassword ? 'text' : 'password'}
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                    autoComplete="current-password"
                    placeholder="••••••••"
                    className="w-full px-4 py-3 pr-12 rounded-xl border border-slate-200 dark:border-zinc-600 bg-slate-50/50 dark:bg-zinc-700/50 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 focus:bg-white dark:focus:bg-zinc-700 transition-colors"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-600 transition-colors"
                    title={showPassword ? 'Şifreyi gizle' : 'Şifreyi göster'}
                    aria-label={showPassword ? 'Şifreyi gizle' : 'Şifreyi göster'}
                  >
                    {showPassword ? (
                      <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                    ) : (
                      <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    )}
                  </button>
                </div>
              </div>
              <button
                type="submit"
                disabled={loading}
                className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 text-white font-semibold shadow-lg shadow-emerald-600/25 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed transition-all duration-200"
              >
                {loading ? (
                  <span className="inline-flex items-center gap-2">
                    <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden>
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    Giriş yapılıyor...
                  </span>
                ) : (
                  'Giriş yap'
                )}
              </button>
            </form>
          </div>

          {/* App description */}
          <div className="mt-8 max-w-md mx-auto text-center">
            <p className="text-slate-600 dark:text-zinc-400 text-sm leading-relaxed">
              <span className="font-medium text-slate-700 dark:text-zinc-200">Mobilya Takip</span>, stok, satış, teklif, tedarikçi ve müşteri cari, satış sonrası hizmet (SSH), bildirim ve log sistemini tek panelde yönetmenizi sağlayan ticari yazılımdır.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
