import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  DocumentTextIcon,
  ShoppingCartIcon,
  ShoppingBagIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/outline';
import { PageHeader, Card, StatCard } from '../components/ui';
import { quotesApi } from '../services/api/quotesApi';
import { salesApi } from '../services/api/salesApi';
import { purchasesApi } from '../services/api/purchasesApi';
import toast from 'react-hot-toast';

const sections = [
  { name: 'Teklifler', description: 'Teklif oluşturma, revizyon ve satışa dönüştürme', href: '/quotes', icon: DocumentTextIcon },
  { name: 'Satışlar', description: 'Satış fişleri, cari takip ve ödeme vadesi', href: '/sales', icon: ShoppingCartIcon },
  { name: 'Alışlar', description: 'Satın alma faturaları ve tedarikçi cari', href: '/purchases', icon: ShoppingBagIcon },
];

export default function SiparisYonetimiPage() {
  const [stats, setStats] = useState<{ quotes: number; sales: number; purchases: number; salesTotal: number; purchasesTotal: number } | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      quotesApi.list().then(({ data }) => Array.isArray(data) ? data.length : 0),
      salesApi.list().then(({ data }) => {
        const arr = Array.isArray(data) ? data : [];
        const total = arr.reduce((s, x) => s + Number((x as { grandTotal?: number }).grandTotal ?? 0), 0);
        return { count: arr.length, total };
      }),
      purchasesApi.list().then(({ data }) => {
        const arr = Array.isArray(data) ? data : [];
        const total = arr.reduce((s, x) => s + Number((x as { grandTotal?: number }).grandTotal ?? 0), 0);
        return { count: arr.length, total };
      }),
    ])
      .then(([quotesCount, salesData, purchasesData]) => {
        setStats({
          quotes: quotesCount,
          sales: salesData.count,
          purchases: purchasesData.count,
          salesTotal: salesData.total,
          purchasesTotal: purchasesData.total,
        });
      })
      .catch(() => toast.error('Özet yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="space-y-8">
      <PageHeader
        title="Sipariş Yönetimi"
        description="Teklif, satış ve alış siparişlerini yönetin"
        icon={DocumentTextIcon}
      />

      {loading ? (
        <div className="flex items-center justify-center py-16">
          <p className="text-zinc-500">Yükleniyor...</p>
        </div>
      ) : stats && (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard title="Toplam teklif" value={String(stats.quotes)} description="adet" />
          <StatCard title="Toplam satış" value={String(stats.sales)} description="adet" />
          <StatCard
            title="Satış tutarı"
            value={`${(stats.salesTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
            description="toplam"
          />
          <StatCard
            title="Alış tutarı"
            value={`${(stats.purchasesTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
            description={`${stats.purchases} adet`}
          />
        </div>
      )}

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {sections.map((item) => (
          <Link key={item.name} to={item.href}>
            <Card className="group flex items-start gap-4 p-6 transition hover:border-emerald-200 hover:shadow-lg">
              <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 transition group-hover:bg-emerald-500/20">
                <item.icon className="h-6 w-6" />
              </span>
              <div className="min-w-0 flex-1">
                <h2 className="text-lg font-semibold text-zinc-900 group-hover:text-emerald-700">{item.name}</h2>
                <p className="mt-0.5 text-sm text-zinc-500">{item.description}</p>
              </div>
              <ArrowRightIcon className="h-5 w-5 shrink-0 text-zinc-300 transition group-hover:text-emerald-500 group-hover:translate-x-0.5" />
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
