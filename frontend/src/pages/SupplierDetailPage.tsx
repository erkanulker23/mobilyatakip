import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  BuildingOffice2Icon,
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon,
  DocumentTextIcon,
  BanknotesIcon,
  ShoppingBagIcon,
  CubeIcon,
  ArrowLeftIcon,
  CurrencyDollarIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { suppliersApi } from '../services/api/suppliersApi';
import { Button } from '../components/ui';
import { supplierPaymentsApi } from '../services/api/supplierPaymentsApi';
import { purchasesApi } from '../services/api/purchasesApi';
import toast from 'react-hot-toast';

interface SupplierDetail {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  taxNumber?: string;
  taxOffice?: string;
  isActive?: boolean;
  products?: Array<{ id: string; name: string; sku?: string; unitPrice?: number }>;
}

interface BalanceInfo {
  totalPurchases: number;
  totalPayments: number;
  balance: number;
}

interface PurchaseRow {
  id: string;
  purchaseNumber: string;
  purchaseDate: string;
  grandTotal: number;
  isReturn?: boolean;
  dueDate?: string;
  notes?: string;
}

interface PaymentRow {
  id: string;
  amount: number;
  paymentDate: string;
  paymentType?: string;
  reference?: string;
  notes?: string;
}

function formatPaymentType(type?: string): string {
  if (!type) return '-';
  const map: Record<string, string> = {
    nakit: 'Nakit',
    havale: 'Havale',
    kredi_karti: 'Kredi kartı',
    diger: 'Diğer',
  };
  return map[type] ?? type;
}

export default function SupplierDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [supplier, setSupplier] = useState<SupplierDetail | null>(null);
  const [balance, setBalance] = useState<BalanceInfo | null>(null);
  const [purchases, setPurchases] = useState<PurchaseRow[]>([]);
  const [payments, setPayments] = useState<PaymentRow[]>([]);
  const [salesStats, setSalesStats] = useState<{ salesCount: number; customerCount: number } | null>(null);
  const [loading, setLoading] = useState(true);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    Promise.all([
      suppliersApi.get(id),
      supplierPaymentsApi.balance(id),
      purchasesApi.list({ supplierId: id }),
      supplierPaymentsApi.bySupplier(id),
      suppliersApi.getStats(id),
    ])
      .then(([sup, bal, pur, pay, stats]) => {
        setSupplier((sup as { data: SupplierDetail }).data ?? sup);
        setBalance((bal as { data: BalanceInfo }).data ?? bal);
        const purBody = pur.data as { data?: unknown[] };
        const payBody = pay.data as { data?: unknown[] };
        setPurchases(Array.isArray(purBody?.data) ? (purBody.data as PurchaseRow[]) : []);
        setPayments(Array.isArray(payBody?.data) ? (payBody.data as PaymentRow[]) : []);
        setSalesStats((stats as { data?: { salesCount: number; customerCount: number } })?.data ?? null);
      })
      .catch(() => toast.error('Tedarikçi bilgileri yüklenemedi'))
      .finally(() => setLoading(false));
  }, [id]);

  const handleDelete = () => {
    if (!id || !globalThis.confirm('Bu tedarikçiyi silmek istediğinize emin misiniz? Bu tedarikçiye bağlı tüm ürünler de silinecektir.')) return;
    setDeleting(true);
    suppliersApi
      .delete(id)
      .then(() => {
        toast.success('Tedarikçi silindi');
        navigate(ROUTES.tedarikciler);
      })
      .catch(() => {
        toast.error('Tedarikçi silinemedi');
        setDeleting(false);
      });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }
  if (!supplier) {
    return (
      <div className="flex flex-col items-center justify-center py-16 gap-3">
        <p className="text-zinc-500">Tedarikçi bilgileri yüklenemedi.</p>
        <Link to={ROUTES.tedarikciler} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">Tedarikçiler listesine dön</Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3">
          <Link
            to={ROUTES.tedarikciler}
            className="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 shadow-sm transition hover:bg-zinc-50"
          >
            <ArrowLeftIcon className="h-4 w-4" />
            Tedarikçiler
          </Link>
          <h1 className="text-2xl font-bold text-zinc-900">{supplier.name}</h1>
          {supplier.isActive === false && (
            <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Pasif</span>
          )}
        </div>
        <Button variant="danger" icon={TrashIcon} onClick={handleDelete} disabled={deleting}>
          {deleting ? 'Siliniyor...' : 'Tedarikçi Sil'}
        </Button>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Firma bilgileri */}
        <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
            <BuildingOffice2Icon className="h-5 w-5 text-emerald-600" />
            Firma bilgileri
          </h2>
          <dl className="space-y-3 text-sm">
            {supplier.email && (
              <div className="flex gap-3">
                <EnvelopeIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500">E-posta</dt>
                  <dd className="font-medium text-zinc-900">
                    <a href={`mailto:${supplier.email}`} className="text-emerald-600 hover:underline">
                      {supplier.email}
                    </a>
                  </dd>
                </div>
              </div>
            )}
            {supplier.phone && (
              <div className="flex gap-3">
                <PhoneIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500">Telefon</dt>
                  <dd className="font-medium text-zinc-900">
                    <a href={`tel:${supplier.phone}`} className="text-emerald-600 hover:underline">
                      {supplier.phone}
                    </a>
                  </dd>
                </div>
              </div>
            )}
            {supplier.address && (
              <div className="flex gap-3">
                <MapPinIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500">Adres</dt>
                  <dd className="font-medium text-zinc-900">{supplier.address}</dd>
                </div>
              </div>
            )}
            {(supplier.taxNumber || supplier.taxOffice) && (
              <div className="flex gap-3">
                <DocumentTextIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500">Vergi bilgisi</dt>
                  <dd className="font-medium text-zinc-900">
                    {[supplier.taxNumber, supplier.taxOffice].filter(Boolean).join(' / ') || '-'}
                  </dd>
                </div>
              </div>
            )}
            {!supplier.email && !supplier.phone && !supplier.address && !supplier.taxNumber && !supplier.taxOffice && (
              <p className="text-zinc-500">Ek bilgi girilmemiş.</p>
            )}
          </dl>
        </div>

        {/* Satış özeti */}
        {salesStats != null && ((salesStats?.salesCount ?? 0) > 0 || (salesStats?.customerCount ?? 0) > 0) && (
          <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
              <ShoppingBagIcon className="h-5 w-5 text-emerald-600" />
              Satış özeti
            </h2>
            <div className="grid grid-cols-2 gap-4">
              <div className="rounded-xl bg-zinc-50 p-4">
                <p className="text-xs font-medium uppercase tracking-wider text-zinc-500">Toplam satış adedi</p>
                <p className="mt-1 text-lg font-semibold text-zinc-900">{salesStats?.salesCount ?? 0}</p>
              </div>
              <div className="rounded-xl bg-zinc-50 p-4">
                <p className="text-xs font-medium uppercase tracking-wider text-zinc-500">Kaç müşteriye satış</p>
                <p className="mt-1 text-lg font-semibold text-zinc-900">{salesStats?.customerCount ?? 0}</p>
              </div>
            </div>
          </div>
        )}

        {/* Cari bakiye */}
        <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
            <BanknotesIcon className="h-5 w-5 text-emerald-600" />
            Cari özet
          </h2>
          {balance != null ? (
            <div className="grid grid-cols-3 gap-4">
              <div className="rounded-xl bg-zinc-50 p-4">
                <p className="text-xs font-medium uppercase tracking-wider text-zinc-500">Toplam alış</p>
                <p className="mt-1 text-lg font-semibold text-zinc-900">
                  {Number(balance?.totalPurchases ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </p>
              </div>
              <div className="rounded-xl bg-zinc-50 p-4">
                <p className="text-xs font-medium uppercase tracking-wider text-zinc-500">Toplam ödeme</p>
                <p className="mt-1 text-lg font-semibold text-zinc-900">
                  {Number(balance?.totalPayments ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </p>
              </div>
              <div className={`rounded-xl p-4 ${Number(balance?.balance ?? 0) >= 0 ? 'bg-amber-50' : 'bg-emerald-50'}`}>
                <p className={`text-xs font-medium uppercase tracking-wider ${Number(balance?.balance ?? 0) >= 0 ? 'text-amber-700' : 'text-emerald-700'}`}>
                  Bakiye {Number(balance?.balance ?? 0) >= 0 ? '(borç)' : '(alacak)'}
                </p>
                <p className={`mt-1 text-lg font-semibold ${Number(balance?.balance ?? 0) >= 0 ? 'text-amber-800' : 'text-emerald-800'}`}>
                  {Number(balance?.balance ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </p>
              </div>
            </div>
          ) : (
            <p className="text-sm text-zinc-500">Bakiye bilgisi yüklenemedi.</p>
          )}
          <Link
            to={ROUTES.tedarikciCari}
            className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700"
          >
            <CurrencyDollarIcon className="h-4 w-4" />
            Tüm tedarikçi carileri
          </Link>
        </div>
      </div>

      {/* Ürünler */}
      <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
          <CubeIcon className="h-5 w-5 text-emerald-600" />
          Ürünler ({supplier.products?.length ?? 0})
        </h2>
        {supplier.products && supplier.products.length > 0 ? (
          <div className="overflow-hidden rounded-xl border border-zinc-200">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Ürün</th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">SKU</th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Birim fiyat</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 bg-white">
                {supplier.products.map((p) => (
                  <tr key={p.id} className="hover:bg-zinc-50/50">
                    <td className="px-4 py-3 text-sm font-medium text-zinc-900">
                      <Link to={`${ROUTES.urunler}?supplier=${supplier.id}`} className="text-emerald-600 hover:underline">
                        {p.name}
                      </Link>
                    </td>
                    <td className="px-4 py-3 text-sm text-zinc-500">{p.sku ?? '-'}</td>
                    <td className="px-4 py-3 text-right text-sm text-zinc-900">
                      {Number(p.unitPrice ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p className="text-sm text-zinc-500">Bu tedarikçiye bağlı ürün yok.</p>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Son alışlar */}
        <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
            <ShoppingBagIcon className="h-5 w-5 text-emerald-600" />
            Son alışlar
          </h2>
          {purchases.length > 0 ? (
            <>
              <div className="overflow-x-auto rounded-xl border border-zinc-200">
                <table className="min-w-full divide-y divide-zinc-200">
                  <thead className="bg-zinc-50">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Fatura no</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Tarih</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Vade</th>
                      <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Tutar</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Notlar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 bg-white">
                    {purchases.slice(0, 10).map((p) => (
                      <tr key={p.id} className="hover:bg-zinc-50/50">
                        <td className="px-4 py-3 text-sm font-medium text-zinc-900">
                          <Link to={ROUTES.alis(p.id)} className="text-emerald-600 hover:underline">
                            {p.purchaseNumber}
                          </Link>
                          {p.isReturn && (
                            <span className="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-800">İade</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-sm text-zinc-600">
                          {p.purchaseDate ? new Date(p.purchaseDate).toLocaleDateString('tr-TR') : '-'}
                        </td>
                        <td className="px-4 py-3 text-sm text-zinc-600">
                          {p.dueDate ? new Date(p.dueDate).toLocaleDateString('tr-TR') : '-'}
                        </td>
                        <td className="px-4 py-3 text-right text-sm font-medium text-zinc-900">
                          {Number(p.grandTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                        </td>
                        <td className="max-w-[180px] truncate px-4 py-3 text-sm text-zinc-500" title={p.notes ?? ''}>
                          {p.notes ? p.notes : '-'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="mt-4 flex flex-wrap items-center gap-3">
                <Link
                  to={ROUTES.alisYeni}
                  className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700"
                >
                  Yeni alış ekle
                </Link>
                {id && purchases.length > 10 && (
                  <Link
                    to={`${ROUTES.alislar}?supplier=${id}`}
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 hover:text-zinc-800"
                  >
                    Tümünü gör ({purchases.length})
                  </Link>
                )}
              </div>
            </>
          ) : (
            <>
              <p className="text-sm text-zinc-500">
                {(supplier.products?.length ?? 0) > 0
                  ? 'Bu tedarikçiye ait alış kaydı bulunamadı. Ürünler doğrudan tedarikçiye bağlanmış olabilir.'
                  : 'Henüz alış kaydı yok.'}
              </p>
              <Link
                to={ROUTES.alisYeni}
                className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700"
              >
                Yeni alış ekle
              </Link>
            </>
          )}
        </div>

        {/* Son ödemeler */}
        <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
            <CurrencyDollarIcon className="h-5 w-5 text-emerald-600" />
            Son ödemeler
          </h2>
          {payments.length > 0 ? (
            <>
              <div className="overflow-x-auto rounded-xl border border-zinc-200">
                <table className="min-w-full divide-y divide-zinc-200">
                  <thead className="bg-zinc-50">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Tarih</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Tür</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Referans</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">Notlar</th>
                      <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">Tutar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 bg-white">
                    {payments.slice(0, 10).map((pay) => (
                      <tr key={pay.id} className="hover:bg-zinc-50/50">
                        <td className="px-4 py-3 text-sm text-zinc-600">
                          {pay.paymentDate ? new Date(pay.paymentDate).toLocaleDateString('tr-TR') : '-'}
                        </td>
                        <td className="px-4 py-3 text-sm font-medium text-zinc-900">
                          {formatPaymentType(pay.paymentType)}
                        </td>
                        <td className="px-4 py-3 text-sm text-zinc-600">{pay.reference ?? '-'}</td>
                        <td className="max-w-[160px] truncate px-4 py-3 text-sm text-zinc-500" title={pay.notes ?? ''}>
                          {pay.notes ?? '-'}
                        </td>
                        <td className="px-4 py-3 text-right text-sm font-medium text-emerald-700">
                          {Number(pay.amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {id && payments.length > 10 && (
                <Link
                  to={ROUTES.tedarikciCari}
                  className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 hover:text-zinc-800"
                >
                  Tümünü gör ({payments.length})
                </Link>
              )}
            </>
          ) : (
            <p className="text-sm text-zinc-500">Henüz ödeme kaydı yok.</p>
          )}
        </div>
      </div>
    </div>
  );
}
