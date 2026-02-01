import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { ArrowTrendingUpIcon, ArrowTrendingDownIcon } from '@heroicons/react/24/outline';
import { reportsApi } from '../services/api/reportsApi';
import type { IncomeExpenseReport } from '../services/api/reportsApi';
import { PageHeader, Card, StatCard } from '../components/ui';
import toast from 'react-hot-toast';

const defaultFrom = () => {
  const d = new Date();
  d.setDate(1);
  return d.toISOString().slice(0, 10);
};
const defaultTo = () => new Date().toISOString().slice(0, 10);

export default function GelirGiderPage() {
  const [from, setFrom] = useState(defaultFrom());
  const [to, setTo] = useState(defaultTo());
  const [data, setData] = useState<IncomeExpenseReport | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    setLoading(true);
    reportsApi
      .incomeExpense({ from, to })
      .then(({ data: res }) => setData(res ?? null))
      .catch(() => {
        toast.error('Gelir-gider verisi yüklenemedi');
        setData(null);
      })
      .finally(() => setLoading(false));
  }, [from, to]);

  return (
    <div className="space-y-8">
      <PageHeader
        title="Gelir - Gider Takibi"
        description="Dönem bazında gelir ve gider özeti"
        icon={ArrowTrendingUpIcon}
        action={
          <div className="flex items-center gap-3">
            <label className="text-sm font-medium text-zinc-700">Tarih aralığı</label>
            <input
              type="date"
              value={from}
              onChange={(e) => setFrom(e.target.value)}
              className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            />
            <span className="text-zinc-500">–</span>
            <input
              type="date"
              value={to}
              onChange={(e) => setTo(e.target.value)}
              className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            />
          </div>
        }
      />

      {loading ? (
        <div className="flex items-center justify-center py-16">
          <p className="text-zinc-500">Yükleniyor...</p>
        </div>
      ) : data ? (
        <>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
              title="Gelir (Satışlar)"
              value={`${(data.income.sales ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
              description="Dönem satış tutarı"
            />
            <StatCard
              title="Gelir (Müşteri ödemeleri)"
              value={`${(data.income.customerPayments ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
              description="Tahsilat"
            />
            <StatCard
              title="Toplam gelir (tahsilat)"
              value={`${(data.income.total ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
              description="Alınan ödemeler"
            />
            <StatCard
              title="Net (Gelir − Gider)"
              value={`${(data.net ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
              description={data.net >= 0 ? 'Fazla' : 'Açık'}
            />
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <Card className="p-6 border-emerald-100 bg-emerald-50/30">
              <h2 className="flex items-center gap-2 text-lg font-semibold text-emerald-800 mb-4">
                <ArrowTrendingUpIcon className="h-5 w-5" />
                Gelir kalemleri
              </h2>
              <dl className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <dt className="text-zinc-600">Satışlar</dt>
                  <dd className="font-medium text-zinc-900">{(data.income.sales ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-zinc-600">Müşteri ödemeleri (tahsilat)</dt>
                  <dd className="font-medium text-zinc-900">{(data.income.customerPayments ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                <div className="flex justify-between border-t border-emerald-200 pt-2 mt-2">
                  <dt className="font-semibold text-emerald-800">Toplam gelir (tahsilat)</dt>
                  <dd className="font-semibold text-emerald-800">{(data.income.total ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                {Math.abs((data.income.total ?? 0) - (data.income.customerPayments ?? 0)) > 0.01 && (
                  <p className="mt-2 text-xs text-amber-600">Uyarı: Toplam gelir ile müşteri ödemeleri tutarı örtüşmüyor.</p>
                )}
              </dl>
            </Card>

            <Card className="p-6 border-red-100 bg-red-50/30">
              <h2 className="flex items-center gap-2 text-lg font-semibold text-red-800 mb-4">
                <ArrowTrendingDownIcon className="h-5 w-5" />
                Gider kalemleri
              </h2>
              <dl className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <dt className="text-zinc-600">Alışlar</dt>
                  <dd className="font-medium text-zinc-900">{(data.expense.purchases ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-zinc-600">Masraflar</dt>
                  <dd className="font-medium text-zinc-900">{(data.expense.expenses ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                <div className="flex justify-between">
                  <dt className="text-zinc-600">Tedarikçi ödemeleri</dt>
                  <dd className="font-medium text-zinc-900">{(data.expense.supplierPayments ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                <div className="flex justify-between border-t border-red-200 pt-2 mt-2">
                  <dt className="font-semibold text-red-800">Toplam gider</dt>
                  <dd className="font-semibold text-red-800">{(data.expense.total ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
                {Math.abs((data.expense.total ?? 0) - ((data.expense.purchases ?? 0) + (data.expense.expenses ?? 0) + (data.expense.supplierPayments ?? 0))) > 0.01 && (
                  <p className="mt-2 text-xs text-amber-600">Uyarı: Toplam gider alış+masraf+tedarikçi ödemesi ile örtüşmüyor.</p>
                )}
              </dl>
            </Card>
          </div>

          <p className="text-sm text-zinc-500">
            <Link to={ROUTES.raporlar} className="text-emerald-600 hover:text-emerald-700">Detaylı raporlar</Link> sayfasından satış, alış ve masraf listelerini inceleyebilirsiniz.
          </p>
        </>
      ) : (
        <Card className="p-6">
          <p className="text-zinc-500">Veri yüklenemedi. Tarih aralığını kontrol edip tekrar deneyin.</p>
        </Card>
      )}
    </div>
  );
}
