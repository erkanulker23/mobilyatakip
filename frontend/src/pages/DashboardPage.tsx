import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { useAuth } from '../contexts/AuthContext';
import { PageHeader } from '../components/ui';
import {
  CubeIcon,
  DocumentTextIcon,
  ShoppingCartIcon,
  UserGroupIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
  ChartBarIcon,
  BanknotesIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/outline';
import { customersApi } from '../services/api/customersApi';
import { customerPaymentsApi } from '../services/api/customerPaymentsApi';
import { quotesApi } from '../services/api/quotesApi';
import { salesApi } from '../services/api/salesApi';

const quickLinks = [
  { name: 'Ürünler', href: ROUTES.urunler, icon: CubeIcon, desc: 'Stok ve ürün yönetimi' },
  { name: 'Teklifler', href: '/quotes', icon: DocumentTextIcon, desc: 'Teklif oluşturma ve takip' },
  { name: 'Satışlar', href: '/sales', icon: ShoppingCartIcon, desc: 'Satış fişleri ve cari' },
  { name: 'Müşteriler', href: '/customers', icon: UserGroupIcon, desc: 'Müşteri kayıtları' },
  { name: 'Tedarikçiler', href: '/suppliers', icon: TruckIcon, desc: 'Tedarikçi ve alış yönetimi' },
  { name: 'Servis (SSH)', href: '/service-tickets', icon: WrenchScrewdriverIcon, desc: 'Satış sonrası hizmet' },
  { name: 'Raporlar', href: '/reports', icon: ChartBarIcon, desc: 'Satış, alış ve masraf raporları' },
  { name: 'Muhasebe', href: '/accounting', icon: BanknotesIcon, desc: 'Kasa, cari ve mutabakat' },
];

const LIMIT = 5;

function formatDate(value: string | Date | null | undefined): string {
  if (!value) return '—';
  const d = typeof value === 'string' ? new Date(value) : value;
  return d.toLocaleDateString('tr-TR');
}

function formatMoney(value: number | string | null | undefined): string {
  return Number(value ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' ₺';
}

export default function DashboardPage() {
  const { user } = useAuth();
  const [latestCustomers, setLatestCustomers] = useState<Array<{ id: string; name: string; createdAt?: string }>>([]);
  const [latestPayments, setLatestPayments] = useState<Array<{
    id: string;
    amount: number;
    paymentDate: string;
    customer?: { id: string; name: string };
  }>>([]);
  const [latestQuotes, setLatestQuotes] = useState<Array<{
    id: string;
    quoteNumber: string;
    grandTotal: number;
    createdAt?: string;
    customer?: { id: string; name: string };
  }>>([]);
  const [latestSales, setLatestSales] = useState<Array<{
    id: string;
    saleNumber: string;
    saleDate: string;
    grandTotal: number;
    customer?: { id: string; name: string };
  }>>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let done = 0;
    const check = () => {
      done += 1;
      if (done >= 4) setLoading(false);
    };
    customersApi.latest(LIMIT).then(({ data }) => setLatestCustomers(Array.isArray(data) ? data : [])).catch(() => setLatestCustomers([])).finally(check);
    customerPaymentsApi.latest(LIMIT).then(({ data }) => setLatestPayments(Array.isArray(data) ? data : [])).catch(() => setLatestPayments([])).finally(check);
    quotesApi.latest(LIMIT).then(({ data }) => setLatestQuotes(Array.isArray(data) ? data : [])).catch(() => setLatestQuotes([])).finally(check);
    salesApi.latest(LIMIT).then(({ data }) => setLatestSales(Array.isArray(data) ? data : [])).catch(() => setLatestSales([])).finally(check);
  }, []);

  return (
    <div className="space-y-8">
      <PageHeader
        title="Dashboard"
        description={`Hoş geldiniz, ${user?.name ?? 'Kullanıcı'}. Mobilya Takip paneline giriş yaptınız.`}
        icon={ChartBarIcon}
      />

      {/* Son kayıtlar: 4 bölüm */}
      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Son Müşteriler */}
        <section className="rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 dark:shadow-black/20">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">Son Müşteriler</h2>
            <Link
              to={ROUTES.musteriler}
              className="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"
            >
              Tümünü gör
            </Link>
          </div>
          {loading && <p className="text-sm text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>}
          {!loading && latestCustomers.length === 0 && <p className="text-sm text-zinc-500 dark:text-zinc-400">Henüz müşteri yok.</p>}
          {!loading && latestCustomers.length > 0 && (
            <ul className="space-y-2">
              {latestCustomers.map((c) => (
                <li key={c.id}>
                  <Link
                    to={ROUTES.musteri(c.id)}
                    className="flex items-center justify-between rounded-lg py-2 px-2 -mx-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-emerald-700 dark:hover:text-emerald-400"
                  >
                    <span className="font-medium truncate">{c.name}</span>
                    <span className="text-zinc-400 dark:text-zinc-500 text-xs shrink-0 ml-2">{c.createdAt ? formatDate(c.createdAt) : ''}</span>
                  </Link>
                </li>
              ))}
            </ul>
          )}
        </section>

        {/* Son Ödemeler */}
        <section className="rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 dark:shadow-black/20">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">Son Ödemeler</h2>
            <Link
              to={ROUTES.musteriCari}
              className="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"
            >
              Tümünü gör
            </Link>
          </div>
          {loading && <p className="text-sm text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>}
          {!loading && latestPayments.length === 0 && <p className="text-sm text-zinc-500 dark:text-zinc-400">Henüz ödeme yok.</p>}
          {!loading && latestPayments.length > 0 && (
            <ul className="space-y-2">
              {latestPayments.map((p) => (
                <li key={p.id}>
                  <Link
                    to={ROUTES.musteriCari}
                    className="flex items-center justify-between rounded-lg py-2 px-2 -mx-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-emerald-700 dark:hover:text-emerald-400"
                  >
                    <span className="truncate">{p.customer?.name ?? '—'}</span>
                    <span className="font-medium text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">{formatMoney(p.amount)}</span>
                  </Link>
                  <p className="text-xs text-zinc-400 dark:text-zinc-500 pl-2">{formatDate(p.paymentDate)}</p>
                </li>
              ))}
            </ul>
          )}
        </section>

        {/* Son Teklifler */}
        <section className="rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 dark:shadow-black/20">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">Son Teklifler</h2>
            <Link
              to={ROUTES.teklifler}
              className="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"
            >
              Tümünü gör
            </Link>
          </div>
          {loading && <p className="text-sm text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>}
          {!loading && latestQuotes.length === 0 && <p className="text-sm text-zinc-500 dark:text-zinc-400">Henüz teklif yok.</p>}
          {!loading && latestQuotes.length > 0 && (
            <ul className="space-y-2">
              {latestQuotes.map((q) => (
                <li key={q.id}>
                  <Link
                    to={ROUTES.teklif(q.id)}
                    className="flex items-center justify-between rounded-lg py-2 px-2 -mx-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-emerald-700 dark:hover:text-emerald-400"
                  >
                    <span className="font-medium">{q.quoteNumber}</span>
                    <span className="text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">{formatMoney(q.grandTotal)}</span>
                  </Link>
                  <p className="text-xs text-zinc-400 dark:text-zinc-500 pl-2 truncate">{q.customer?.name ?? '—'}</p>
                </li>
              ))}
            </ul>
          )}
        </section>

        {/* Son Satışlar */}
        <section className="rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 dark:shadow-black/20">
          <div className="mb-4 flex items-center justify-between">
            <h2 className="text-lg font-semibold text-zinc-900 dark:text-white">Son Satışlar</h2>
            <Link
              to={ROUTES.satislar}
              className="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"
            >
              Tümünü gör
            </Link>
          </div>
          {loading && <p className="text-sm text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>}
          {!loading && latestSales.length === 0 && <p className="text-sm text-zinc-500 dark:text-zinc-400">Henüz satış yok.</p>}
          {!loading && latestSales.length > 0 && (
            <ul className="space-y-2">
              {latestSales.map((s) => (
                <li key={s.id}>
                  <Link
                    to={ROUTES.satis(s.id)}
                    className="flex items-center justify-between rounded-lg py-2 px-2 -mx-2 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:text-emerald-700 dark:hover:text-emerald-400"
                  >
                    <span className="font-medium">{s.saleNumber}</span>
                    <span className="text-emerald-600 dark:text-emerald-400 shrink-0 ml-2">{formatMoney(s.grandTotal)}</span>
                  </Link>
                  <p className="text-xs text-zinc-400 dark:text-zinc-500 pl-2">{s.customer?.name ?? '—'} · {formatDate(s.saleDate)}</p>
                </li>
              ))}
            </ul>
          )}
        </section>
      </div>

      {/* Hızlı linkler */}
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {quickLinks.map((item) => (
          <Link
            key={item.name}
            to={item.href}
            className="group flex items-start gap-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 dark:shadow-black/20 transition hover:border-emerald-200 dark:hover:border-emerald-800 hover:shadow-xl hover:shadow-emerald-100/30 dark:hover:shadow-emerald-900/20"
          >
            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 transition group-hover:bg-emerald-500/20 dark:group-hover:bg-emerald-500/30">
              <item.icon className="h-6 w-6" />
            </span>
            <div className="min-w-0 flex-1">
              <h3 className="font-semibold text-zinc-900 dark:text-white group-hover:text-emerald-700 dark:group-hover:text-emerald-400">{item.name}</h3>
              <p className="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{item.desc}</p>
            </div>
            <ArrowRightIcon className="h-5 w-5 shrink-0 text-zinc-300 dark:text-zinc-500 transition group-hover:text-emerald-500 group-hover:translate-x-0.5" />
          </Link>
        ))}
      </div>
    </div>
  );
}
