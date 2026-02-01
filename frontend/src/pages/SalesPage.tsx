import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { ShoppingCartIcon, PlusIcon } from '@heroicons/react/24/outline';
import { salesApi } from '../services/api/salesApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

export default function SalesPage() {
  const [sales, setSales] = useState<unknown[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);

  const load = () => {
    salesApi
      .list({ page, limit })
      .then(({ data }) => {
        const res = data as { data?: unknown[]; total?: number; totalPages?: number };
        setSales(Array.isArray(res?.data) ? res.data : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Satışlar yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    load();
  }, [page, limit]);

  const handleDelete = (saleId: string, saleNumber: string) => {
    if (!globalThis.confirm(`"${saleNumber}" satışını silmek istediğinize emin misiniz? Stoklar iade edilecektir.`)) return;
    salesApi
      .delete(saleId)
      .then(() => {
        toast.success('Satış silindi');
        load();
      })
      .catch(() => toast.error('Satış silinemedi'));
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Satışlar"
        description="Satış fişleri ve cari takip"
        icon={ShoppingCartIcon}
        action={
          <Link to={ROUTES.satisYeni}>
            <Button icon={PlusIcon}>Satış Oluştur</Button>
          </Link>
        }
      />
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
          </div>
        ) : sales.length === 0 ? (
          <EmptyState
            icon={ShoppingCartIcon}
            title="Satış bulunamadı"
            description="Henüz satış yok. Yeni satış oluşturarak başlayın."
            action={
              <Link to={ROUTES.satisYeni}>
                <Button icon={PlusIcon}>Satış Oluştur</Button>
              </Link>
            }
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Satış No</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tarih</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Toplam</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                  {sales.map((s) => {
                    const row = s as Record<string, unknown>;
                    const saleNumber = String(row.saleNumber ?? '—');
                    return (
                      <tr key={String(row.id)} className="hover:bg-zinc-50/80 dark:hover:bg-zinc-700/80 transition-colors">
                        <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                          <Link to={ROUTES.satis(String(row.id))} className="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:underline">{saleNumber}</Link>
                        </td>
                        <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{String(row.saleDate ?? '—')}</td>
                        <td className="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">{Number(row.grandTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                        <td className="px-6 py-4 text-right">
                          <ActionButtons
                            viewHref={ROUTES.satis(String(row.id))}
                            onDelete={() => handleDelete(String(row.id), saleNumber)}
                          />
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <Pagination
              page={page}
              limit={limit}
              total={total}
              totalPages={totalPages}
              onPageChange={setPage}
              onLimitChange={(l) => { setLimit(l); setPage(1); }}
              limitOptions={[10, 20, 50]}
            />
          </>
        )}
      </Card>
    </div>
  );
}
