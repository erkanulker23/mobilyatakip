import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../../config/routes';
import { UserGroupIcon } from '@heroicons/react/24/outline';
import { customersApi } from '../../services/api/customersApi';
import { customerPaymentsApi } from '../../services/api/customerPaymentsApi';
import { PageHeader, Card, EmptyState } from '../../components/ui';
import toast from 'react-hot-toast';

interface CustomerRow {
  id: string;
  name: string;
  email?: string;
  phone?: string;
}

interface DebtRow {
  customerId: string;
  balance: number;
  totalSales: number;
  totalPayments: number;
}

export default function CustomerLedgerPage() {
  const [customers, setCustomers] = useState<CustomerRow[]>([]);
  const [withDebt, setWithDebt] = useState<DebtRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [debtOnly, setDebtOnly] = useState(false);

  useEffect(() => {
    customersApi
      .list({ limit: 500 })
      .then(({ data }) => {
        const res = data as { data?: unknown[] };
        setCustomers(Array.isArray(res?.data) ? (res.data as CustomerRow[]) : []);
      })
      .catch(() => toast.error('Müşteriler yüklenemedi'));
  }, []);

  useEffect(() => {
    setLoading(true);
    customerPaymentsApi
      .withDebt()
      .then(({ data }) => setWithDebt(Array.isArray(data) ? data : []))
      .catch(() => setWithDebt([]))
      .finally(() => setLoading(false));
  }, []);

  const nameById = Object.fromEntries((customers ?? []).map((c) => [c?.id ?? '', c?.name ?? '']));
  const debtListWithNames = (withDebt ?? []).map((d) => ({ ...d, name: nameById[d?.customerId ?? ''] ?? d?.customerId ?? '' }));

  const filteredCustomers = (customers ?? []).filter((c) => {
    if (search.trim() && !(c?.name ?? '').toLowerCase().includes(search.toLowerCase())) return false;
    if (debtOnly && !(withDebt ?? []).some((d) => d?.customerId === c?.id)) return false;
    return true;
  });

  return (
    <div className="space-y-6">
      <PageHeader
        title="Müşteri Cari"
        description="Müşteri alacak/borç durumu. Borçlu müşteriler ödeme yapmadığında bildirimde listelenir."
        icon={UserGroupIcon}
        action={<Link to={ROUTES.musteriler} className="text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300">Müşteriler →</Link>}
      />
      {debtListWithNames.length > 0 && (
        <Card className="border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/20">
          <h2 className="font-semibold text-amber-800 dark:text-amber-200 mb-2">Borçlu Müşteriler ({debtListWithNames.length})</h2>
          <ul className="space-y-1 text-sm">
            {debtListWithNames.map((d) => (
              <li key={d.customerId} className="flex justify-between text-zinc-700 dark:text-zinc-200">
                <span>{d.name ?? d.customerId}</span>
                <span className="font-medium text-amber-800 dark:text-amber-200">{Number(d.balance).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺ borç</span>
              </li>
            ))}
          </ul>
        </Card>
      )}
      <div className="flex flex-wrap gap-3">
        <input
          type="text"
          placeholder="Müşteri ara..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-56"
        />
        <label className="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
          <input type="checkbox" checked={debtOnly} onChange={(e) => setDebtOnly(e.target.checked)} className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500" />
          Sadece borçlular
        </label>
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
          </div>
        ) : filteredCustomers.length === 0 ? (
          <EmptyState
            icon={UserGroupIcon}
            title="Kayıt bulunamadı"
            description="Müşteri veya filtreye uygun kayıt yok."
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Müşteri</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Toplam Satış</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Toplam Ödeme</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Bakiye (Borç)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {filteredCustomers.map((c) => {
                  const debtRow = (withDebt ?? []).find((d) => d?.customerId === c?.id);
                  const totalSales = debtRow?.totalSales ?? 0;
                  const totalPayments = debtRow?.totalPayments ?? 0;
                  const balance = debtRow?.balance ?? 0;
                  return (
                    <tr key={c?.id ?? ''} className="hover:bg-zinc-50/80 dark:hover:bg-zinc-700/80 transition-colors">
                      <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{c?.name ?? '—'}</td>
                      <td className="px-6 py-4 text-sm text-right text-zinc-600 dark:text-zinc-400">{Number(totalSales).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                      <td className="px-6 py-4 text-sm text-right text-zinc-600 dark:text-zinc-400">{Number(totalPayments).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                      <td className={`px-6 py-4 text-sm text-right font-medium ${balance > 0 ? 'text-red-600' : 'text-zinc-900 dark:text-white'}`}>
                        {Number(balance).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺ {balance > 0 ? '(borç)' : ''}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}
