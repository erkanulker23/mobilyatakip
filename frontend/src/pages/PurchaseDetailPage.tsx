import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  ArrowLeftIcon,
  PencilSquareIcon,
  TrashIcon,
  PrinterIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { purchasesApi } from '../services/api/purchasesApi';
import { companyApi } from '../services/api/companyApi';
import { generatePurchasePdf, type CompanyInfo } from '../utils/pdfUtils';
import { Button } from '../components/ui';
import toast from 'react-hot-toast';

interface CompanyInfoState {
  name?: string;
  address?: string;
  phone?: string;
  email?: string;
}

interface PurchaseDetail {
  id: string;
  purchaseNumber: string;
  purchaseDate: string;
  dueDate?: string;
  subtotal: number;
  kdvTotal: number;
  grandTotal: number;
  paidAmount?: number;
  isReturn?: boolean;
  notes?: string;
  createdAt?: string;
  updatedAt?: string;
  supplier?: { id: string; name: string };
  items?: Array<{
    id: string;
    productId: string;
    product?: { name: string; sku?: string };
    unitPrice: number;
    quantity: number;
    kdvRate: number;
    lineTotal: number;
  }>;
}

function formatDate(s: string | undefined) {
  if (!s) return '—';
  try {
    return new Date(s).toLocaleDateString('tr-TR');
  } catch {
    return '—';
  }
}

export default function PurchaseDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [purchase, setPurchase] = useState<PurchaseDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [form, setForm] = useState({ purchaseDate: '', dueDate: '', notes: '' });
  const [updatingPaid, setUpdatingPaid] = useState(false);
  const [pdfLoading, setPdfLoading] = useState(false);
  const [company, setCompany] = useState<CompanyInfoState | null>(null);

  const load = () => {
    if (!id) return;
    setLoading(true);
    purchasesApi
      .get(id)
      .then(({ data }) => {
        const p = data as PurchaseDetail;
        setPurchase(p);
        setForm({
          purchaseDate: p.purchaseDate ? String(p.purchaseDate).slice(0, 10) : '',
          dueDate: p.dueDate ? String(p.dueDate).slice(0, 10) : '',
          notes: p.notes ?? '',
        });
      })
      .catch(() => toast.error('Alış yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [id]);

  useEffect(() => {
    companyApi.get().then(({ data }) => setCompany((data as CompanyInfoState) ?? null)).catch(() => setCompany(null));
  }, []);

  const handleSave = () => {
    if (!id) return;
    setSaving(true);
    purchasesApi
      .update(id, {
        purchaseDate: form.purchaseDate || undefined,
        dueDate: form.dueDate || undefined,
        notes: form.notes || undefined,
      })
      .then(({ data }) => {
        setPurchase(data as PurchaseDetail);
        setEditing(false);
        toast.success('Alış güncellendi');
      })
      .catch(() => toast.error('Alış güncellenemedi'))
      .finally(() => setSaving(false));
  };

  const setPaidStatus = (paid: boolean) => {
    if (!id || !purchase) return;
    setUpdatingPaid(true);
    const paidAmount = paid ? Number(purchase.grandTotal) : 0;
    purchasesApi
      .update(id, { paidAmount })
      .then(({ data }) => {
        setPurchase(data as PurchaseDetail);
        toast.success(paid ? 'Alış ödendi olarak işaretlendi' : 'Alış ödenmedi olarak işaretlendi');
      })
      .catch(() => toast.error('Güncellenemedi'))
      .finally(() => setUpdatingPaid(false));
  };

  const handleDelete = () => {
    if (!id || !globalThis.confirm('Bu alışı silmek istediğinize emin misiniz? Stok hareketleri geri alınacaktır.')) return;
    setDeleting(true);
    purchasesApi
      .delete(id)
      .then(() => {
        toast.success('Alış silindi');
        navigate(ROUTES.alislar);
      })
      .catch(() => toast.error('Alış silinemedi'))
      .finally(() => setDeleting(false));
  };

  const handlePrint = () => {
    if (!purchase) return;
    setPdfLoading(true);
    companyApi
      .get()
      .then(({ data }) => {
        const company = data as CompanyInfo | null;
        const doc = generatePurchasePdf(purchase, company);
        const blob = doc.output('blob');
        const url = URL.createObjectURL(blob);
        const w = window.open(url, '_blank');
        if (w) {
          w.onload = () => {
            w.print();
            URL.revokeObjectURL(url);
          };
          toast.success('Yazdırma penceresi açıldı');
        } else {
          URL.revokeObjectURL(url);
          toast.error('Yazdırma penceresi açılamadı. Pop-up engelleyicisini kapatıp tekrar deneyin.');
        }
      })
      .catch(() => toast.error('PDF oluşturulamadı'))
      .finally(() => setPdfLoading(false));
  };

  if (loading || !purchase) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-4xl">
      {/* Üst bar: Geri + Düzenle, Kaydet, Sil, Yazdır — yazdırmada gizlenir */}
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <Link
          to={ROUTES.alislar}
          className="inline-flex items-center gap-2 text-sm font-medium text-zinc-600 hover:text-zinc-900"
        >
          <ArrowLeftIcon className="h-5 w-5" />
          Alışlar
        </Link>
        <div className="flex flex-wrap items-center gap-2">
          {editing ? (
            <>
              <Button variant="ghost" onClick={() => setEditing(false)}>İptal</Button>
              <Button onClick={handleSave} disabled={saving}>{saving ? 'Kaydediliyor...' : 'Kaydet'}</Button>
            </>
          ) : (
            <Button variant="secondary" icon={PencilSquareIcon} onClick={() => setEditing(true)}>Düzenle</Button>
          )}
          <Button variant="danger" icon={TrashIcon} onClick={handleDelete} disabled={deleting}>
            {deleting ? 'Siliniyor...' : 'Sil'}
          </Button>
          <Button variant="secondary" icon={PrinterIcon} onClick={handlePrint} disabled={!!pdfLoading}>
            {pdfLoading ? 'Açılıyor...' : 'Yazdır'}
          </Button>
        </div>
      </div>

      {purchase.isReturn && (
        <div className="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 print:hidden">
          Bu kayıt bir iade alışıdır.
        </div>
      )}

      {/* Ödeme durumu — yazdırmada gizlenir */}
      {(() => {
        const paid = Number(purchase.paidAmount ?? 0);
        const total = Number(purchase.grandTotal ?? 0);
        const isPaid = total <= 0 || paid >= total;
        const isPartial = paid > 0 && paid < total;
        const statusLabel = isPaid ? 'Ödendi' : isPartial ? 'Kısmen ödendi' : 'Ödenmedi';
        const statusClass = isPaid ? 'bg-emerald-100 text-emerald-800' : isPartial ? 'bg-amber-100 text-amber-800' : 'bg-zinc-100 text-zinc-700';
        return (
          <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 print:hidden">
            <div className="flex items-center gap-3">
              <span className={`rounded-full px-3 py-1 text-sm font-medium ${statusClass}`}>{statusLabel}</span>
              {total > 0 && (
                <span className="text-sm text-zinc-600">
                  Ödenen: {Number(purchase.paidAmount ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺ / {total.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </span>
              )}
            </div>
            <div className="flex gap-2">
              <button
                type="button"
                disabled={updatingPaid || isPaid}
                onClick={() => setPaidStatus(true)}
                className="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
              >
                Ödendi yap
              </button>
              <button
                type="button"
                disabled={updatingPaid || paid === 0}
                onClick={() => setPaidStatus(false)}
                className="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-1.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50"
              >
                Ödenmedi yap
              </button>
            </div>
          </div>
        );
      })()}

      {/* Fatura benzeri kart — normal fatura düzeni: firma üstte, toplamlar altta */}
      <div className="invoice-print rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 shadow-sm">
        {/* 1. Üst: Firma bilgileri */}
        <div className="mb-6 border-b border-zinc-200 dark:border-zinc-600 pb-6">
          <h2 className="text-xl font-bold text-zinc-900 dark:text-white">
            {company?.name ?? 'Mobilya Takip'}
          </h2>
          <div className="mt-1 flex flex-wrap gap-x-4 gap-y-0 text-sm text-zinc-600 dark:text-zinc-400">
            {company?.address && <span>{company.address}</span>}
            {company?.phone && <span>Tel: {company.phone}</span>}
            {company?.email && <span>{company.email}</span>}
          </div>
        </div>

        {/* 2. Fatura başlık + fatura no + tarih */}
        <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">ALIŞ / FATURA</h1>
            <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Fatura No: {purchase.purchaseNumber}</p>
            <p className="text-sm text-zinc-600 dark:text-zinc-400">Tarih: {formatDate(purchase.purchaseDate)}</p>
          </div>
        </div>

        {/* 3. İki sütun: Tedarikçi + Tarih/Vade/Notlar (toplamlar yok) */}
        <div className="mb-6 grid gap-6 sm:grid-cols-2">
          <div className="rounded-xl border border-zinc-100 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 p-5">
            <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">Tedarikçi</h2>
            <dl className="space-y-2 text-sm">
              <div>
                <dt className="text-zinc-500">Firma</dt>
                <dd className="font-medium text-zinc-900 dark:text-white">
                  <Link to={purchase.supplier?.id ? ROUTES.tedarikci(purchase.supplier.id) : '#'} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                    {purchase.supplier?.name ?? '—'}
                  </Link>
                </dd>
              </div>
              {purchase.supplier?.id && (
                <div className="pt-2 border-t border-zinc-200 dark:border-zinc-600">
                  <Link to={`${ROUTES.alislar}?supplier=${purchase.supplier.id}`} className="text-sm text-emerald-600 hover:text-emerald-700 hover:underline">
                    Bu tedarikçinin tüm alışları →
                  </Link>
                </div>
              )}
            </dl>
          </div>

          <div className="rounded-xl border border-zinc-100 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 p-5">
            <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">Tarih & Notlar</h2>
            {editing ? (
              <div className="space-y-3">
                <div>
                  <label className="block text-xs text-zinc-500">Alış tarihi</label>
                  <input
                    type="date"
                    value={form.purchaseDate}
                    onChange={(e) => setForm((f) => ({ ...f, purchaseDate: e.target.value }))}
                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs text-zinc-500">Vade tarihi</label>
                  <input
                    type="date"
                    value={form.dueDate}
                    onChange={(e) => setForm((f) => ({ ...f, dueDate: e.target.value }))}
                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs text-zinc-500">Notlar</label>
                  <textarea
                    rows={2}
                    value={form.notes}
                    onChange={(e) => setForm((f) => ({ ...f, notes: e.target.value }))}
                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                  />
                </div>
              </div>
            ) : (
              <dl className="space-y-2 text-sm">
                <div>
                  <dt className="text-zinc-500">Alış tarihi</dt>
                  <dd className="font-medium text-zinc-900 dark:text-white">{formatDate(purchase.purchaseDate)}</dd>
                </div>
                <div>
                  <dt className="text-zinc-500">Vade</dt>
                  <dd className="text-zinc-900 dark:text-zinc-200">{formatDate(purchase.dueDate)}</dd>
                </div>
                {purchase.notes && (
                  <div>
                    <dt className="text-zinc-500">Notlar</dt>
                    <dd className="text-zinc-900 dark:text-zinc-200">{purchase.notes}</dd>
                  </div>
                )}
                {(purchase.createdAt || purchase.updatedAt) && (
                  <div className="pt-2 border-t border-zinc-100 dark:border-zinc-600 space-y-1 text-xs text-zinc-400">
                    {purchase.createdAt && <div>Oluşturulma: {formatDate(purchase.createdAt)}</div>}
                    {purchase.updatedAt && <div>Son güncelleme: {formatDate(purchase.updatedAt)}</div>}
                  </div>
                )}
              </dl>
            )}
          </div>
        </div>

        {/* 4. Kalemler tablosu */}
        <div>
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">Kalemler ({purchase.items?.length ?? 0})</h2>
          <div className="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-600">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Ürün</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Birim fiyat</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Adet</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">KDV %</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Satır toplam</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {(purchase.items ?? []).map((item) => (
                  <tr key={item.id} className="hover:bg-zinc-50/80 dark:hover:bg-zinc-700/50">
                    <td className="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                      {item.product?.name ?? '—'} {item.product?.sku ? <span className="text-zinc-400 font-normal">({item.product.sku})</span> : ''}
                    </td>
                    <td className="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-300">{Number(item.unitPrice).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                    <td className="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-300">{item.quantity}</td>
                    <td className="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">%{Number(item.kdvRate ?? 0)}</td>
                    <td className="px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-white">{Number(item.lineTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {(!purchase.items || purchase.items.length === 0) && (
            <div className="rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 px-4 py-8 text-center text-sm text-zinc-500">
              Kalem bulunmuyor.
            </div>
          )}
        </div>

        {/* 5. Altta: Toplamlar — normal fatura mantığı */}
        <div className="mt-8 flex justify-end">
          <div className="w-full max-w-xs rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50/80 dark:bg-zinc-800/80 p-5">
            <dl className="space-y-2 text-sm">
              <div className="flex justify-between">
                <dt className="text-zinc-500">Ara toplam</dt>
                <dd className="font-medium text-zinc-900 dark:text-white">
                  {Number(purchase.subtotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-zinc-500">KDV</dt>
                <dd className="font-medium text-zinc-900 dark:text-white">
                  {Number(purchase.kdvTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </dd>
              </div>
              <div className="flex justify-between border-t border-zinc-200 dark:border-zinc-600 pt-3">
                <dt className="font-semibold text-zinc-700 dark:text-zinc-300">Genel toplam</dt>
                <dd className="font-bold text-zinc-900 dark:text-white">
                  {Number(purchase.grandTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-zinc-500">Ödenen</dt>
                <dd className="font-medium text-zinc-900 dark:text-white">
                  {Number(purchase.paidAmount ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  );
}
