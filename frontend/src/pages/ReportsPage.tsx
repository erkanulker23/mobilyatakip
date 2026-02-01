import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ChartBarIcon, PrinterIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { reportsApi } from '../services/api/reportsApi';
import type { ProductSalesReportRow, CustomerSalesReportRow } from '../services/api/reportsApi';
import { salesApi } from '../services/api/salesApi';
import { purchasesApi } from '../services/api/purchasesApi';
import { expensesApi } from '../services/api/expensesApi';
import { PageHeader, StatCard } from '../components/ui';
import toast from 'react-hot-toast';

const defaultFrom = () => {
  const d = new Date();
  d.setDate(1);
  return d.toISOString().slice(0, 10);
};
const defaultTo = () => new Date().toISOString().slice(0, 10);

export default function ReportsPage() {
  const navigate = useNavigate();
  const [from, setFrom] = useState(defaultFrom());
  const [to, setTo] = useState(defaultTo());
  const [summary, setSummary] = useState<{
    sales: { total: number; count: number };
    purchases: { total: number; count: number };
    expenses: { total: number; count: number };
  } | null>(null);
  const [recentSales, setRecentSales] = useState<unknown[]>([]);
  const [recentPurchases, setRecentPurchases] = useState<unknown[]>([]);
  const [recentExpenses, setRecentExpenses] = useState<unknown[]>([]);
  const [productReport, setProductReport] = useState<ProductSalesReportRow[]>([]);
  const [customerReport, setCustomerReport] = useState<CustomerSalesReportRow[]>([]);
  const [loading, setLoading] = useState(true);

  const load = () => {
    setLoading(true);
    setSummary(null);
    Promise.all([
      reportsApi.summary({ from, to }).then(({ data }) => setSummary(data ?? null)).catch(() => setSummary(null)),
      salesApi.list().then(({ data }) => {
        const res = data as { data?: unknown[] };
        setRecentSales(Array.isArray(res?.data) ? res.data.slice(0, 10) : []);
      }).catch(() => setRecentSales([])),
      purchasesApi.list().then(({ data }) => {
        const res = data as { data?: unknown[] };
        setRecentPurchases(Array.isArray(res?.data) ? res.data.slice(0, 10) : []);
      }).catch(() => setRecentPurchases([])),
      expensesApi.list({ from, to }).then(({ data }) => setRecentExpenses(Array.isArray(data) ? data.slice(0, 10) : [])).catch(() => setRecentExpenses([])),
      reportsApi.productSales({ from, to }).then(({ data }) => setProductReport(data?.rows ?? [])).catch(() => setProductReport([])),
      reportsApi.customerSales({ from, to }).then(({ data }) => setCustomerReport(data?.rows ?? [])).catch(() => setCustomerReport([])),
    ])
      .catch(() => toast.error('Raporlar yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [from, to]);

  return (
    <div className="space-y-8">
      <PageHeader
        title="Raporlar"
        description="Satış, alış ve masraf özeti"
        icon={ChartBarIcon}
        action={
          <div className="flex items-center gap-3 flex-wrap">
            <label className="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tarih aralığı</label>
            <input
              type="date"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
              className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            />
            <span className="text-zinc-500 dark:text-zinc-400">–</span>
            <input
              type="date"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            />
            <button
              type="button"
              onClick={() => window.print()}
              className="inline-flex items-center gap-2 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            >
              <PrinterIcon className="w-5 h-5" />
              Yazdır / PDF
            </button>
          </div>
        }
      />

      {loading ? (
        <div className="flex items-center justify-center py-16">
          <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
        </div>
      ) : (
        <>
          <section className="space-y-6">
            <h2 className="text-lg font-semibold text-zinc-800 dark:text-white">Dönem özeti</h2>
            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
              <StatCard
                title="Satışlar"
                value={`${(summary?.sales?.total ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
                description={`${summary?.sales?.count ?? 0} adet`}
              />
              <StatCard
                title="Alışlar"
                value={`${(summary?.purchases?.total ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
                description={`${summary?.purchases?.count ?? 0} adet`}
              />
              <StatCard
                title="Masraflar"
                value={`${(summary?.expenses?.total ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
                description={`${summary?.expenses?.count ?? 0} adet`}
              />
              <StatCard
                title="Dönem brüt kar"
                value={`${((summary?.sales?.total ?? 0) - (summary?.purchases?.total ?? 0)).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
                description="Satış − Alış"
              />
            </div>
          </section>

          <section className="grid gap-6 lg:grid-cols-2">
            <div className="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
              <h2 className="px-6 py-4 text-lg font-semibold text-zinc-800 dark:text-white border-b border-zinc-100 dark:border-zinc-700">Son satışlar</h2>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                  <thead className="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">No</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tarih</th>
                      <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Toplam</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                    {recentSales.map((s) => {
                      const row = s as Record<string, unknown>;
                      const saleId = String(row.id ?? '');
                      return (
                        <tr
                          key={saleId}
                          onClick={() => saleId && navigate(ROUTES.satis(saleId))}
                          className="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                        >
                          <td className="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-white">{String(row.saleNumber ?? '-')}</td>
                          <td className="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">{String(row.saleDate ?? '-')}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-900 dark:text-white">{Number(row.grandTotal ?? 0).toFixed(2)} ₺</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              {recentSales.length === 0 && <p className="p-4 text-center text-zinc-500 dark:text-zinc-400 text-sm">Kayıt yok</p>}
            </div>

            <div className="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
              <h2 className="px-6 py-4 text-lg font-semibold text-zinc-800 dark:text-white border-b border-zinc-100 dark:border-zinc-700">Son alışlar</h2>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                  <thead className="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">No</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tarih</th>
                      <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Toplam</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                    {recentPurchases.map((p) => {
                      const row = p as Record<string, unknown>;
                      const purchaseId = String(row.id ?? '');
                      return (
                        <tr
                          key={purchaseId}
                          onClick={() => purchaseId && navigate(ROUTES.alis(purchaseId))}
                          className="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                        >
                          <td className="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-white">{String(row.purchaseNumber ?? '-')}</td>
                          <td className="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">{String(row.purchaseDate ?? '-')}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-900 dark:text-white">{Number(row.grandTotal ?? 0).toFixed(2)} ₺</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              {recentPurchases.length === 0 && <p className="p-4 text-center text-zinc-500 dark:text-zinc-400 text-sm">Kayıt yok</p>}
            </div>
          </section>

          <section className="space-y-6">
            <h2 className="text-lg font-semibold text-zinc-800 dark:text-white">Detaylı raporlama</h2>
            <div className="grid gap-6 lg:grid-cols-2">
              <div className="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <h3 className="px-6 py-4 text-base font-semibold text-zinc-800 dark:text-white border-b border-zinc-100 dark:border-zinc-700">Ürün bazlı satış (dönem)</h3>
                <div className="overflow-x-auto max-h-80 overflow-y-auto">
                  <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead className="bg-zinc-50 dark:bg-zinc-800 sticky top-0">
                      <tr>
                        <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Ürün</th>
                        <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Adet</th>
                        <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tutar</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                      {productReport.map((r) => (
                        <tr
                          key={r.productId}
                          onClick={() => navigate(ROUTES.urun(r.productId))}
                          className="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                        >
                          <td className="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-white">{r.productName}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-600">{r.quantity}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-900 dark:text-white">{r.total.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {productReport.length === 0 && <p className="p-4 text-center text-zinc-500 dark:text-zinc-400 text-sm">Kayıt yok</p>}
              </div>
              <div className="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
                <h3 className="px-6 py-4 text-base font-semibold text-zinc-800 dark:text-white border-b border-zinc-100 dark:border-zinc-700">Müşteri bazlı satış (dönem)</h3>
                <div className="overflow-x-auto max-h-80 overflow-y-auto">
                  <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead className="bg-zinc-50 dark:bg-zinc-800 sticky top-0">
                      <tr>
                        <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Müşteri</th>
                        <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Adet</th>
                        <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tutar</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                      {customerReport.map((r) => (
                        <tr
                          key={r.customerId}
                          onClick={() => navigate(ROUTES.musteri(r.customerId))}
                          className="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                        >
                          <td className="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-white">{r.customerName}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-600">{r.count}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-900 dark:text-white">{r.total.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
                {customerReport.length === 0 && <p className="p-4 text-center text-zinc-500 dark:text-zinc-400 text-sm">Kayıt yok</p>}
              </div>
            </div>
          </section>

          <section>
            <div className="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
              <h2 className="px-6 py-4 text-lg font-semibold text-zinc-800 dark:text-white border-b border-zinc-100 dark:border-zinc-700">Son masraflar (dönem)</h2>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                  <thead className="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tarih</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Açıklama</th>
                      <th className="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Kategori</th>
                      <th className="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Tutar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                    {recentExpenses.map((e) => {
                      const row = e as Record<string, unknown>;
                      return (
                        <tr key={String(row.id)} className="hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                          <td className="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">{row.expenseDate ? new Date(String(row.expenseDate)).toLocaleDateString('tr-TR') : '-'}</td>
                          <td className="px-4 py-2 text-sm font-medium text-zinc-900 dark:text-white">{String(row.description ?? '-')}</td>
                          <td className="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">{String(row.category ?? '-')}</td>
                          <td className="px-4 py-2 text-sm text-right text-zinc-900 dark:text-white">{Number(row.amount ?? 0).toFixed(2)} ₺</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              {recentExpenses.length === 0 && <p className="p-4 text-center text-zinc-500 dark:text-zinc-400 text-sm">Kayıt yok</p>}
            </div>
          </section>
        </>
      )}
    </div>
  );
}
