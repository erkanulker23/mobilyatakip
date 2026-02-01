import { useEffect, useState, Fragment } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import {
  ArrowLeftIcon,
  PencilSquareIcon,
  TrashIcon,
  PrinterIcon,
  BanknotesIcon,
  BanknotesIcon as BanknotesIconSolid,
  BuildingLibraryIcon,
  CreditCardIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { salesApi } from '../services/api/salesApi';
import { customerPaymentsApi } from '../services/api/customerPaymentsApi';
import { kasaApi } from '../services/api/kasaApi';
import { companyApi } from '../services/api/companyApi';
import { generateSalePdf, type CompanyInfo } from '../utils/pdfUtils';
import { Button } from '../components/ui';
import toast from 'react-hot-toast';

type PaymentMethod = 'nakit' | 'kredi_karti' | 'havale';

const COMPANY_NAME = 'Mobilya Takip';
const COMPANY_SUBTITLE = 'Mobilya Satış ve Takip Hizmetleri';

interface SaleDetail {
  id: string;
  saleNumber: string;
  saleDate: string;
  dueDate?: string;
  subtotal: number;
  kdvTotal: number;
  grandTotal: number;
  paidAmount: number;
  notes?: string;
  quoteId?: string;
  createdAt?: string;
  updatedAt?: string;
  customer?: {
    id: string;
    name: string;
    phone?: string;
    email?: string;
    address?: string;
  };
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

function paymentStatusLabel(paid: number, total: number) {
  if (total <= 0) return '—';
  if (paid <= 0) return 'Beklemede';
  if (paid >= total) return 'Ödendi';
  return 'Kısmen Ödendi';
}

function paymentStatusBadgeClass(status: string, isPending: boolean): string {
  if (isPending) return 'bg-amber-100 text-amber-800';
  if (status === 'Ödendi') return 'bg-emerald-100 text-emerald-800';
  return 'bg-zinc-100 text-zinc-700';
}

function PaymentStatusBadge({ status, isPending }: Readonly<{ status: string; isPending: boolean }>) {
  return (
    <span
      className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${paymentStatusBadgeClass(status, isPending)}`}
    >
      {status}
    </span>
  );
}

export default function SaleDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [sale, setSale] = useState<SaleDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [pdfLoading, setPdfLoading] = useState<'download' | 'print' | null>(null);
  const [form, setForm] = useState({ dueDate: '', notes: '' });
  const [paymentModalOpen, setPaymentModalOpen] = useState(false);
  const [paymentSubmitting, setPaymentSubmitting] = useState(false);
  const [banks, setBanks] = useState<{ id: string; name: string; type: string; bankName?: string }[]>([]);
  const [paymentForm, setPaymentForm] = useState({
    amount: '',
    paymentMethod: 'nakit' as PaymentMethod,
    paymentDate: new Date().toISOString().slice(0, 10),
    kasaId: '',
    notes: '',
  });

  const load = () => {
    if (!id) return;
    setLoading(true);
    salesApi
      .get(id)
      .then(({ data }) => {
        const s = data as SaleDetail;
        setSale(s);
        setForm({
          dueDate: s.dueDate ? String(s.dueDate).slice(0, 10) : '',
          notes: s.notes ?? '',
        });
      })
      .catch(() => toast.error('Satış yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [id]);

  useEffect(() => {
    if (paymentModalOpen) {
      kasaApi.list().then(({ data }) => {
        const list = Array.isArray(data) ? data : [];
        setBanks(list.filter((k: { type: string }) => k.type === 'banka'));
      }).catch(() => setBanks([]));
    }
  }, [paymentModalOpen]);

  const handleSave = () => {
    if (!id) return;
    setSaving(true);
    salesApi
      .update(id, { dueDate: form.dueDate || undefined, notes: form.notes || undefined })
      .then(({ data }) => {
        setSale(data as SaleDetail);
        setEditing(false);
        toast.success('Satış güncellendi');
      })
      .catch(() => toast.error('Satış güncellenemedi'))
      .finally(() => setSaving(false));
  };

  const handleDelete = () => {
    if (!id || !globalThis.confirm('Bu satışı silmek istediğinize emin misiniz? Stoklar iade edilecektir.')) return;
    setDeleting(true);
    salesApi
      .delete(id)
      .then(() => {
        toast.success('Satış silindi');
        navigate(ROUTES.satislar);
      })
      .catch(() => toast.error('Satış silinemedi'))
      .finally(() => setDeleting(false));
  };

  const openPaymentModal = () => {
    const unpaid = Number(sale?.grandTotal ?? 0) - Number(sale?.paidAmount ?? 0);
    setPaymentForm({
      amount: unpaid > 0 ? String(unpaid.toFixed(2)) : '',
      paymentMethod: 'nakit',
      paymentDate: new Date().toISOString().slice(0, 10),
      kasaId: '',
      notes: '',
    });
    setPaymentModalOpen(true);
  };

  const handlePaymentSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!sale?.id || !sale.customer?.id) {
      toast.error('Müşteri bilgisi eksik.');
      return;
    }
    const amount = Number.parseFloat(paymentForm.amount.replace(',', '.')) || 0;
    if (amount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    if (paymentForm.paymentMethod === 'havale' && !paymentForm.kasaId) {
      toast.error('Havale için banka seçiniz.');
      return;
    }
    setPaymentSubmitting(true);
    const paymentType = paymentForm.paymentMethod === 'havale' ? 'havale' : paymentForm.paymentMethod === 'kredi_karti' ? 'kredi_karti' : 'nakit';
    const notes = paymentForm.paymentMethod === 'havale' && paymentForm.kasaId
      ? `${paymentForm.notes.trim() || ''} Havale - ${banks.find((b) => b.id === paymentForm.kasaId)?.name ?? ''}`.trim() || undefined
      : paymentForm.notes.trim() || undefined;
    customerPaymentsApi
      .create({
        customerId: sale.customer.id,
        saleId: sale.id,
        amount,
        paymentDate: paymentForm.paymentDate,
        paymentType,
        notes,
        ...(paymentForm.kasaId ? { kasaId: paymentForm.kasaId } : {}),
      })
      .then(() => {
        toast.success('Ödeme kaydedildi.');
        setPaymentModalOpen(false);
        load();
      })
      .catch(() => toast.error('Ödeme kaydedilemedi.'))
      .finally(() => setPaymentSubmitting(false));
  };

  const handlePrint = () => {
    if (!id || !sale) return;
    setPdfLoading('print');
    const fallbackPdf = () => {
      companyApi
        .get()
        .then(({ data }) => {
          const company = data as CompanyInfo | null;
          const doc = generateSalePdf(sale, company);
          const blob = doc.output('blob');
          const url = URL.createObjectURL(blob);
          const w = window.open(url, '_blank');
          if (w) {
            w.onload = () => {
              w.print();
              URL.revokeObjectURL(url);
            };
            toast.success('Yazdırma penceresi açıldı (jspdf)');
          } else {
            URL.revokeObjectURL(url);
            toast.error('Yazdırma penceresi açılamadı. Pop-up engelleyicisini kapatıp tekrar deneyin.');
          }
        })
        .catch(() => toast.error('PDF yüklenemedi'));
    };
    salesApi
      .getPdf(id)
      .then(({ data }) => {
        const blob = data as Blob;
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
      .catch(fallbackPdf)
      .finally(() => setPdfLoading(null));
  };

  if (loading || !sale) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }

  const paymentStatus = paymentStatusLabel(Number(sale.paidAmount), Number(sale.grandTotal));
  const isPending = paymentStatus === 'Beklemede';

  return (
    <div className="mx-auto max-w-4xl">
      {/* Üst bar: Geri Dön + Düzenle, Kaydet, Sil, Yazdır */}
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <Link
          to={ROUTES.satislar}
          className="inline-flex items-center gap-2 text-sm font-medium text-zinc-600 hover:text-zinc-900"
        >
          <ArrowLeftIcon className="h-5 w-5" />
          Geri Dön
        </Link>
        <div className="flex flex-wrap items-center gap-2">
          {editing ? (
            <>
              <Button variant="ghost" onClick={() => setEditing(false)}>
                İptal
              </Button>
              <Button onClick={handleSave} disabled={saving}>
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </Button>
            </>
          ) : (
            <Button variant="secondary" icon={PencilSquareIcon} onClick={() => setEditing(true)}>
              Düzenle
            </Button>
          )}
          <Button variant="danger" icon={TrashIcon} onClick={handleDelete} disabled={deleting}>
            {deleting ? 'Siliniyor...' : 'Sil'}
          </Button>
          <Button
            variant="secondary"
            icon={PrinterIcon}
            onClick={handlePrint}
            disabled={!!pdfLoading}
          >
            {pdfLoading === 'print' ? 'Açılıyor...' : 'Yazdır'}
          </Button>
        </div>
      </div>

      {/* Fatura görünümü kartı */}
      <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-8 shadow-sm">
        {/* FATURA başlık satırı */}
        <div className="mb-8 flex flex-wrap items-start justify-between gap-6 border-b border-zinc-200 pb-6">
          <div>
            <h1 className="text-2xl font-bold tracking-tight text-zinc-900">FATURA</h1>
            <p className="mt-1 text-sm text-zinc-600">Fiş No: {sale.saleNumber}</p>
            <p className="text-sm text-zinc-600">Tarih: {formatDate(sale.saleDate)}</p>
          </div>
          <div className="text-right">
            <p className="text-lg font-bold text-zinc-900">{COMPANY_NAME}</p>
            <p className="text-sm text-zinc-500">{COMPANY_SUBTITLE}</p>
          </div>
        </div>

        {/* İki sütun: Müşteri + Satış bilgileri */}
        <div className="mb-8 grid gap-6 sm:grid-cols-2">
          <div className="rounded-xl border border-zinc-100 bg-zinc-50/50 p-5">
            <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">
              Müşteri Bilgileri
            </h2>
            <dl className="space-y-2 text-sm">
              <div>
                <dt className="text-zinc-500">Ad Soyad</dt>
                <dd className="font-medium text-zinc-900">
                  {sale.customer?.id ? (
                    <Link to={ROUTES.musteri(sale.customer.id)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                      {sale.customer.name ?? '—'}
                    </Link>
                  ) : (
                    sale.customer?.name ?? '—'
                  )}
                </dd>
              </div>
              <div>
                <dt className="text-zinc-500">E-posta</dt>
                <dd className="text-zinc-900">{sale.customer?.email ?? '—'}</dd>
              </div>
              <div>
                <dt className="text-zinc-500">Telefon</dt>
                <dd className="text-zinc-900">{sale.customer?.phone ?? '—'}</dd>
              </div>
              <div>
                <dt className="text-zinc-500">Adres</dt>
                <dd className="text-zinc-900">{sale.customer?.address ?? '—'}</dd>
              </div>
              {sale.quoteId && (
                <div className="pt-2 border-t border-zinc-200">
                  <dt className="text-zinc-500">Teklif</dt>
                  <dd>
                    <Link to={ROUTES.teklif(sale.quoteId)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                      Teklif detayına git →
                    </Link>
                  </dd>
                </div>
              )}
            </dl>
          </div>

          <div className="rounded-xl border border-zinc-100 bg-zinc-50/50 p-5">
            <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">
              Satış Bilgileri
            </h2>
            {editing ? (
              <div className="space-y-3">
                <div>
                  <label htmlFor="sale-due-date" className="block text-xs text-zinc-500">Vade tarihi</label>
                  <input
                    id="sale-due-date"
                    type="date"
                    value={form.dueDate}
                    onChange={(e) => setForm((f) => ({ ...f, dueDate: e.target.value }))}
                    className="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label htmlFor="sale-notes" className="block text-xs text-zinc-500">Notlar</label>
                  <textarea
                    id="sale-notes"
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
                  <dt className="text-zinc-500">Tarih</dt>
                  <dd className="font-medium text-zinc-900">{formatDate(sale.saleDate)}</dd>
                </div>
                <div>
                  <dt className="text-zinc-500">Vade</dt>
                  <dd className="text-zinc-900">{formatDate(sale.dueDate)}</dd>
                </div>
                <div className="flex justify-between items-baseline">
                  <dt className="text-zinc-500">Ara toplam</dt>
                  <dd className="font-medium text-zinc-900 text-right">
                    {Number(sale.subtotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                  </dd>
                </div>
                <div className="flex justify-between items-baseline">
                  <dt className="text-zinc-500">KDV</dt>
                  <dd className="font-medium text-zinc-900 text-right">
                    {Number(sale.kdvTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                  </dd>
                </div>
                <div className="flex justify-between items-baseline border-t border-zinc-200 pt-2">
                  <dt className="text-zinc-500 font-medium">Genel Toplam</dt>
                  <dd className="font-semibold text-zinc-900 text-right">
                    {Number(sale.grandTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                  </dd>
                </div>
                <div className="flex justify-between items-baseline">
                  <dt className="text-zinc-500">Ödenen</dt>
                  <dd className="font-medium text-zinc-900 text-right">
                    {Number(sale.paidAmount).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                  </dd>
                </div>
                {sale.notes && (
                  <div>
                    <dt className="text-zinc-500">Notlar</dt>
                    <dd className="text-zinc-900">{sale.notes}</dd>
                  </div>
                )}
                {(sale.createdAt || sale.updatedAt) && (
                  <div className="pt-2 border-t border-zinc-100 space-y-1 text-xs text-zinc-400">
                    {sale.createdAt && <div>Oluşturulma: {formatDate(sale.createdAt)}</div>}
                    {sale.updatedAt && <div>Son güncelleme: {formatDate(sale.updatedAt)}</div>}
                  </div>
                )}
              </dl>
            )}
          </div>
        </div>

        {/* Kalemler / Aylık fiyatlandırma tarzı tablo */}
        <div>
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
              Satış Kalemleri
            </h2>
            {Number(sale.grandTotal) - Number(sale.paidAmount) > 0 && (
              <Button variant="primary" icon={BanknotesIcon} onClick={openPaymentModal}>
                + Ödeme Al
              </Button>
            )}
          </div>
          <div className="overflow-x-auto rounded-xl border border-zinc-200">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    Ürün
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    Fiyat (TL)
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    Adet
                  </th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    KDV %
                  </th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    Satır Toplam
                  </th>
                  <th className="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    Ödeme Durumu
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {(sale.items ?? []).map((item) => (
                  <tr key={item.id} className="hover:bg-zinc-50/80">
                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900">
                      {item.productId ? (
                        <Link to={ROUTES.urun(item.productId)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                          {item.product?.name ?? '—'}{' '}
                          {item.product?.sku ? `(${item.product.sku})` : ''}
                        </Link>
                      ) : (
                        `${item.product?.name ?? '—'} ${item.product?.sku ? `(${item.product.sku})` : ''}`
                      )}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-600">
                      {Number(item.unitPrice).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-600">
                      {item.quantity}
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-sm text-zinc-500">%{Number(item.kdvRate ?? 0)}</td>
                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-zinc-900">
                      {Number(item.lineTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                    </td>
                    <td className="whitespace-nowrap px-4 py-3 text-center">
                      <PaymentStatusBadge status={paymentStatus} isPending={isPending} />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {(!sale.items || sale.items.length === 0) && (
            <div className="rounded-xl border border-zinc-200 bg-zinc-50/50 px-4 py-8 text-center text-sm text-zinc-500">
              Kalem bulunmuyor.
            </div>
          )}
        </div>
      </div>

      {/* Ödeme Al modal */}
      <Transition appear show={paymentModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={() => !paymentSubmitting && setPaymentModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-2xl">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 border-b border-zinc-100 pb-4">Ödeme Al — {sale.saleNumber}</Dialog.Title>
                  <form onSubmit={handlePaymentSubmit} className="mt-5 space-y-4">
                    <p className="text-sm text-zinc-600">Müşteri: {sale.customer?.name ?? '—'}</p>
                    <p className="text-sm text-zinc-600">Kalan borç: {(Number(sale.grandTotal) - Number(sale.paidAmount)).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</p>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 mb-1">Tutar (₺) *</label>
                      <input
                        type="text"
                        inputMode="decimal"
                        required
                        value={paymentForm.amount}
                        onChange={(e) => setPaymentForm((f) => ({ ...f, amount: e.target.value }))}
                        className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="0,00"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 mb-1">Ödeme tarihi *</label>
                      <input
                        type="date"
                        required
                        value={paymentForm.paymentDate}
                        onChange={(e) => setPaymentForm((f) => ({ ...f, paymentDate: e.target.value }))}
                        className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <span className="block text-sm font-medium text-zinc-700 mb-2">Ödeme türü</span>
                      <div className="flex gap-2 flex-wrap">
                        <button type="button" onClick={() => setPaymentForm((f) => ({ ...f, paymentMethod: 'nakit' }))} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${paymentForm.paymentMethod === 'nakit' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600'}`}><BanknotesIconSolid className="h-5 w-5" /> Nakit</button>
                        <button type="button" onClick={() => setPaymentForm((f) => ({ ...f, paymentMethod: 'kredi_karti' }))} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${paymentForm.paymentMethod === 'kredi_karti' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600'}`}><CreditCardIcon className="h-5 w-5" /> Kredi kartı</button>
                        <button type="button" onClick={() => setPaymentForm((f) => ({ ...f, paymentMethod: 'havale' }))} className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${paymentForm.paymentMethod === 'havale' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600'}`}><BuildingLibraryIcon className="h-5 w-5" /> Havale</button>
                      </div>
                    </div>
                    {paymentForm.paymentMethod === 'havale' && (
                      <div>
                        <label className="block text-sm font-medium text-zinc-700 mb-1">Banka *</label>
                        <select required value={paymentForm.kasaId} onChange={(e) => setPaymentForm((f) => ({ ...f, kasaId: e.target.value }))} className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                          <option value="">Banka seçin</option>
                          {banks.map((b) => (<option key={b.id} value={b.id}>{b.name}{b.bankName ? ` (${b.bankName})` : ''}</option>))}
                        </select>
                      </div>
                    )}
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 mb-1">Not</label>
                      <textarea rows={2} value={paymentForm.notes} onChange={(e) => setPaymentForm((f) => ({ ...f, notes: e.target.value }))} className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" placeholder="Opsiyonel" />
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button type="button" onClick={() => setPaymentModalOpen(false)} disabled={paymentSubmitting} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50">İptal</button>
                      <button type="submit" disabled={paymentSubmitting || !paymentForm.amount || (paymentForm.paymentMethod === 'havale' && !paymentForm.kasaId)} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{paymentSubmitting ? 'Kaydediliyor...' : 'Ödemeyi kaydet'}</button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  );
}
