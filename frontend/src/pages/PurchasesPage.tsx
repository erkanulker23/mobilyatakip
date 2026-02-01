import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ShoppingBagIcon, PlusIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { purchasesApi } from '../services/api/purchasesApi';
import { suppliersApi } from '../services/api/suppliersApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

export default function PurchasesPage() {
  const [list, setList] = useState<unknown[]>([]);
  const [suppliers, setSuppliers] = useState<{ id: string; name: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [supplierFilter, setSupplierFilter] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [purchaseNumberSearch, setPurchaseNumberSearch] = useState('');
  const [isReturnFilter, setIsReturnFilter] = useState('');
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);

  const handleDelete = (purchaseId: string, purchaseNumber: string) => {
    if (!globalThis.confirm(`"${purchaseNumber}" alışını silmek istediğinize emin misiniz? Stok hareketleri geri alınacaktır.`)) return;
    purchasesApi
      .delete(purchaseId)
      .then(() => {
        toast.success('Alış silindi');
        load();
      })
      .catch(() => toast.error('Alış silinemedi'));
  };

  const load = () => {
    const params: { supplierId?: string; dateFrom?: string; dateTo?: string; purchaseNumber?: string; isReturn?: boolean; page?: number; limit?: number } = { page, limit };
    if (supplierFilter) params.supplierId = supplierFilter;
    if (dateFrom) params.dateFrom = dateFrom;
    if (dateTo) params.dateTo = dateTo;
    if (purchaseNumberSearch.trim()) params.purchaseNumber = purchaseNumberSearch.trim();
    if (isReturnFilter === 'true' || isReturnFilter === 'false') params.isReturn = isReturnFilter === 'true';
    purchasesApi
      .list(params)
      .then(({ data }) => {
        const res = data as { data?: unknown[]; total?: number; totalPages?: number };
        setList(Array.isArray(res?.data) ? res.data : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Alışlar yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    load();
  }, [supplierFilter, dateFrom, dateTo, purchaseNumberSearch, isReturnFilter, page, limit]);

  useEffect(() => {
    suppliersApi.list({ limit: 500 }).then(({ data }) => {
      const res = data as { data?: { id: string; name: string }[] };
      setSuppliers(Array.isArray(res?.data) ? res.data : []);
    });
  }, []);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Alışlar"
        description="Satın alma faturaları ve tedarikçi cari"
        icon={ShoppingBagIcon}
        action={
          <Link to={ROUTES.alisYeni}>
            <Button icon={PlusIcon}>Yeni Alış</Button>
          </Link>
        }
      />
      <div className="flex flex-wrap gap-3 items-center">
        <select
          value={supplierFilter}
          onChange={(e) => { setSupplierFilter(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 min-w-[180px]"
        >
          <option value="">Tüm tedarikçiler</option>
          {suppliers.map((s) => (
            <option key={s.id} value={s.id}>{s.name}</option>
          ))}
        </select>
        <input
          type="date"
          value={dateFrom}
          onChange={(e) => { setDateFrom(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
          placeholder="Başlangıç"
        />
        <input
          type="date"
          value={dateTo}
          onChange={(e) => { setDateTo(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
          placeholder="Bitiş"
        />
        <input
          type="text"
          placeholder="Alış no ara..."
          value={purchaseNumberSearch}
          onChange={(e) => setPurchaseNumberSearch(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && (setPage(1), load())}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-40"
        />
        <select
          value={isReturnFilter}
          onChange={(e) => { setIsReturnFilter(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tümü</option>
          <option value="false">Normal alış</option>
          <option value="true">İade</option>
        </select>
        <Button variant="primary" onClick={() => { setPage(1); setLoading(true); load(); }}>Filtrele</Button>
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500">Yükleniyor...</p>
          </div>
        ) : list.length === 0 ? (
          <EmptyState
            icon={ShoppingBagIcon}
            title="Alış bulunamadı"
            description="Henüz alış yok. Yeni alış oluşturarak başlayın."
            action={
              <Link to={ROUTES.alisYeni}>
                <Button icon={PlusIcon}>Yeni Alış</Button>
              </Link>
            }
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-zinc-200">
                <thead className="bg-zinc-50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Alış No</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Tedarikçi</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Tarih</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Toplam</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 bg-white">
                  {list.map((p) => {
                    const row = p as Record<string, unknown>;
                    const supplier = row.supplier as { name?: string } | undefined;
                    const purchaseNumber = String(row.purchaseNumber ?? '—');
                    return (
                      <tr key={String(row.id)} className="hover:bg-zinc-50/80 transition-colors">
                        <td className="px-6 py-4 text-sm font-medium text-zinc-900">
                          <Link to={ROUTES.alis(String(row.id))} className="text-emerald-600 hover:text-emerald-700 hover:underline">{purchaseNumber}</Link>
                        </td>
                        <td className="px-6 py-4 text-sm text-zinc-600">{supplier?.name ?? '—'}</td>
                        <td className="px-6 py-4 text-sm text-zinc-600">{String(row.purchaseDate ?? '—')}</td>
                        <td className="px-6 py-4 text-sm text-right font-medium text-zinc-900">{Number(row.grandTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                        <td className="px-6 py-4 text-right">
                          <ActionButtons
                            viewHref={ROUTES.alis(String(row.id))}
                            onDelete={() => handleDelete(String(row.id), purchaseNumber)}
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
