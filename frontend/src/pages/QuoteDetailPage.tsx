import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import {
  ArrowLeftIcon,
  PencilSquareIcon,
  PrinterIcon,
  ArrowDownTrayIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  XCircleIcon,
  PaperAirplaneIcon,
  DocumentTextIcon,
  ShoppingBagIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { quotesApi } from '../services/api/quotesApi';
import { companyApi } from '../services/api/companyApi';
import { generateQuotePdf, type CompanyInfo } from '../utils/pdfUtils';
import { Button } from '../components/ui';
import toast from 'react-hot-toast';

const STATUS_LABELS: Record<string, string> = {
  taslak: 'Taslak',
  gonderildi: 'Gönderildi',
  onaylandi: 'Onaylandı',
  reddedildi: 'Reddedildi',
  satisa_donustu: 'Satışa dönüştürüldü',
};

const STATUS_STYLES: Record<string, { bg: string; text: string; border: string; icon: React.ElementType }> = {
  taslak: { bg: 'bg-zinc-100', text: 'text-zinc-700', border: 'border-zinc-200', icon: DocumentTextIcon },
  gonderildi: { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-200', icon: PaperAirplaneIcon },
  onaylandi: { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border-emerald-200', icon: CheckCircleIcon },
  reddedildi: { bg: 'bg-red-100', text: 'text-red-800', border: 'border-red-200', icon: XCircleIcon },
  satisa_donustu: { bg: 'bg-emerald-200', text: 'text-emerald-900', border: 'border-emerald-300', icon: ShoppingBagIcon },
};

interface QuoteDetail {
  id: string;
  quoteNumber: string;
  status: string;
  revision: number;
  subtotal: number;
  kdvTotal: number;
  grandTotal: number;
  generalDiscountPercent?: number;
  generalDiscountAmount?: number;
  validUntil?: string;
  notes?: string;
  convertedSaleId?: string;
  createdAt?: string;
  updatedAt?: string;
  customer?: { id: string; name: string; phone?: string; email?: string };
  personnel?: { id: string; name: string };
  items?: Array<{
    id: string;
    productId: string;
    product?: { name: string; sku?: string };
    unitPrice: number;
    quantity: number;
    kdvRate?: number;
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

export default function QuoteDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [quote, setQuote] = useState<QuoteDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [pdfLoading, setPdfLoading] = useState<'download' | 'print' | null>(null);
  const [statusModalOpen, setStatusModalOpen] = useState(false);
  const [statusSelect, setStatusSelect] = useState('');
  const [updatingStatus, setUpdatingStatus] = useState(false);
  const [company, setCompany] = useState<{ name?: string; address?: string; phone?: string; email?: string } | null>(null);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    quotesApi
      .get(id)
      .then(({ data }) => setQuote(data as QuoteDetail))
      .catch(() => toast.error('Teklif yüklenemedi'))
      .finally(() => setLoading(false));
  }, [id]);

  useEffect(() => {
    companyApi.get().then(({ data }) => setCompany((data as { name?: string; address?: string; phone?: string; email?: string }) ?? null)).catch(() => setCompany(null));
  }, []);

  const openStatusModal = () => {
    if (quote) {
      setStatusSelect(quote.status);
      setStatusModalOpen(true);
    }
  };

  const handleStatusUpdate = () => {
    if (!id || !statusSelect || statusSelect === quote?.status) {
      setStatusModalOpen(false);
      return;
    }
    setUpdatingStatus(true);
    quotesApi
      .updateStatus(id, statusSelect)
      .then(({ data }) => {
        setQuote(data as QuoteDetail);
        toast.success('Teklif durumu güncellendi');
        setStatusModalOpen(false);
      })
      .catch(() => toast.error('Durum güncellenemedi'))
      .finally(() => setUpdatingStatus(false));
  };

  const handlePdfDownload = () => {
    if (!id || !quote) return;
    setPdfLoading('download');
    const fallbackPdf = () => {
      companyApi
        .get()
        .then(({ data }) => {
          const company = data as CompanyInfo | null;
          const doc = generateQuotePdf(quote, company);
          doc.save(`teklif-${quote.quoteNumber}_v${quote.revision ?? 1}.pdf`);
          toast.success('PDF indirildi (jspdf)');
        })
        .catch(() => toast.error('PDF indirilemedi'));
    };
    quotesApi
      .getPdf(id)
      .then(({ data }) => {
        const blob = data as Blob;
        if (!(blob instanceof Blob) || blob.size === 0 || (blob.type && blob.type.includes('json'))) {
          toast.error('Sunucudan gelen yanıt PDF değil.');
          return;
        }
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `teklif-${quote.quoteNumber}_v${quote.revision}.pdf`;
        a.click();
        URL.revokeObjectURL(url);
        toast.success('PDF indirildi');
      })
      .catch(fallbackPdf)
      .finally(() => setPdfLoading(null));
  };

  const handlePrint = () => {
    if (!id || !quote) return;
    setPdfLoading('print');
    const fallbackPdf = () => {
      companyApi
        .get()
        .then(({ data }) => {
          const company = data as CompanyInfo | null;
          const doc = generateQuotePdf(quote, company);
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
    quotesApi
      .getPdf(id)
      .then(({ data }) => {
        const blob = data as Blob;
        if (!(blob instanceof Blob) || blob.size === 0 || (blob.type && blob.type.includes('json'))) {
          toast.error('Sunucudan gelen yanıt PDF değil.');
          return;
        }
        const url = URL.createObjectURL(blob);
        const w = window.open(url, '_blank');
        if (w) {
          w.addEventListener('load', () => {
            w.print();
            URL.revokeObjectURL(url);
          }, { once: true });
          toast.success('Yazdırma penceresi açıldı');
        } else {
          URL.revokeObjectURL(url);
          toast.error('Yazdırma penceresi açılamadı. Pop-up engelleyicisini kapatıp tekrar deneyin.');
        }
      })
      .catch(fallbackPdf)
      .finally(() => setPdfLoading(null));
  };

  if (loading || !quote) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }

  const canEdit = quote.status !== 'satisa_donustu' && !quote.convertedSaleId;

  return (
    <div className="mx-auto max-w-4xl">
      {/* Üst bar: Geri + Düzenle, Yazdır, PDF — yazdırmada gizlenir */}
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <Link
          to={ROUTES.teklifler}
          className="inline-flex items-center gap-2 text-sm font-medium text-zinc-600 hover:text-zinc-900"
        >
          <ArrowLeftIcon className="h-5 w-5" />
          Teklifler
        </Link>
        <div className="flex flex-wrap items-center gap-2">
          {canEdit && (
            <Link to={ROUTES.teklifDuzenle(quote.id)}>
              <Button variant="secondary" icon={PencilSquareIcon}>Teklifi düzenle</Button>
            </Link>
          )}
          <Button variant="secondary" icon={PrinterIcon} onClick={handlePrint} disabled={!!pdfLoading}>
            {pdfLoading === 'print' ? 'Açılıyor...' : 'Yazdır'}
          </Button>
          <Button variant="secondary" icon={ArrowDownTrayIcon} onClick={handlePdfDownload} disabled={!!pdfLoading}>
            {pdfLoading === 'download' ? 'İndiriliyor...' : 'PDF indir'}
          </Button>
        </div>
      </div>

      <div className="mb-6 flex flex-wrap items-center gap-3 print:hidden">
        {(() => {
          const style = STATUS_STYLES[quote.status] ?? STATUS_STYLES.taslak;
          const Icon = style.icon;
          return (
            <span className={`inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium ${style.bg} ${style.text} ${style.border}`}>
              <Icon className="h-4 w-4" />
              {STATUS_LABELS[quote.status] ?? quote.status}
            </span>
          );
        })()}
        {canEdit && (
          <Button variant="secondary" onClick={openStatusModal}>
            Teklif durumunu düzenle
          </Button>
        )}
        {quote.convertedSaleId && (
          <Link to={ROUTES.satis(quote.convertedSaleId)} className="rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-700 hover:bg-emerald-200">
            Satışa git →
          </Link>
        )}
      </div>

      {/* Teklif durumunu düzenle modal */}
      <Transition appear show={statusModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !updatingStatus && setStatusModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-xl">
                  <Dialog.Title className="text-lg font-semibold text-zinc-900 pb-4 border-b border-zinc-100">Teklif durumunu düzenle</Dialog.Title>
                  <div className="mt-4 space-y-2">
                    {Object.entries(STATUS_LABELS).map(([value, label]) => (
                      <label key={value} className="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-600 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        <input type="radio" name="status" value={value} checked={statusSelect === value} onChange={() => setStatusSelect(value)} className="rounded-full border-zinc-300 text-emerald-600 focus:ring-emerald-500" />
                        <span className={`inline-flex items-center gap-2 rounded-full border px-2.5 py-0.5 text-sm font-medium ${(STATUS_STYLES[value] ?? STATUS_STYLES.taslak).bg} ${(STATUS_STYLES[value] ?? STATUS_STYLES.taslak).text}`}>
                          {label}
                        </span>
                      </label>
                    ))}
                  </div>
                  <div className="mt-6 flex justify-end gap-2">
                    <button type="button" onClick={() => setStatusModalOpen(false)} disabled={updatingStatus} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50">İptal</button>
                    <button type="button" onClick={handleStatusUpdate} disabled={updatingStatus || statusSelect === quote?.status} className="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">{updatingStatus ? 'Kaydediliyor...' : 'Kaydet'}</button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Fatura benzeri kart — teklif/fatura düzeni: firma üstte, toplamlar altta */}
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
        {/* 2. Teklif başlık */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">TEKLİF</h1>
          <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Teklif No: {quote.quoteNumber} (Rev. v{quote.revision})</p>
          <p className="text-sm text-zinc-600 dark:text-zinc-400">Geçerlilik: {formatDate(quote.validUntil)}</p>
        </div>

        <div className="mb-6 grid gap-6 sm:grid-cols-2">
          <div className="rounded-xl border border-zinc-100 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 p-5">
            <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">Müşteri Bilgileri</h2>
            <dl className="space-y-2 text-sm">
              <div>
                <dt className="text-zinc-500">Ad Soyad</dt>
                <dd className="font-medium text-zinc-900">
                  {quote.customer?.id ? (
                    <Link to={ROUTES.musteri(quote.customer.id)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                      {quote.customer.name ?? '—'}
                    </Link>
                  ) : (
                    quote.customer?.name ?? '—'
                  )}
                </dd>
              </div>
              <div>
                <dt className="text-zinc-500">E-posta</dt>
                <dd className="text-zinc-900">{quote.customer?.email ?? '—'}</dd>
              </div>
              <div>
                <dt className="text-zinc-500">Telefon</dt>
                <dd className="text-zinc-900">{quote.customer?.phone ?? '—'}</dd>
              </div>
              {quote.validUntil && (
                <div className="flex items-center gap-1 pt-2">
                  <CalendarDaysIcon className="h-4 w-4 text-zinc-400" />
                  <dt className="text-zinc-500">Geçerlilik</dt>
                  <dd className="text-zinc-900">{formatDate(quote.validUntil)}</dd>
                </div>
              )}
            </dl>
          </div>
          <div className="rounded-xl border border-zinc-100 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 p-5">
            <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">Teklif Bilgileri</h2>
            <dl className="space-y-2 text-sm">
              {quote.personnel?.name && (
                <div>
                  <dt className="text-zinc-500">Hazırlayan</dt>
                  <dd className="font-medium text-zinc-900">{quote.personnel.name}</dd>
                </div>
              )}
              {(quote.createdAt || quote.updatedAt) && (
                <div className="pt-2 border-t border-zinc-100 space-y-1 text-xs text-zinc-400">
                  {quote.createdAt && <div>Oluşturulma: {formatDate(quote.createdAt)}</div>}
                  {quote.updatedAt && <div>Son güncelleme: {formatDate(quote.updatedAt)}</div>}
                </div>
              )}
            </dl>
          </div>
        </div>

        <div className="mb-6">
          <h2 className="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">Kalemler ({quote.items?.length ?? 0})</h2>
          <div className="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-600">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Ürün</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Birim fiyat</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Adet</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">KDV %</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Satır toplam</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {(quote.items ?? []).map((item) => (
                  <tr key={item.id} className="hover:bg-zinc-50/80">
                    <td className="px-4 py-3 text-sm font-medium text-zinc-900">
                      {item.productId ? (
                        <Link to={ROUTES.urun(item.productId)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                          {item.product?.name ?? '—'} {item.product?.sku ? <span className="text-zinc-400 font-normal">({item.product.sku})</span> : ''}
                        </Link>
                      ) : (
                        `${item.product?.name ?? '—'} ${item.product?.sku ? `(${item.product.sku})` : ''}`
                      )}
                    </td>
                    <td className="px-4 py-3 text-sm text-right text-zinc-600">{Number(item.unitPrice).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                    <td className="px-4 py-3 text-sm text-right text-zinc-600">{item.quantity}</td>
                    <td className="px-4 py-3 text-sm text-right text-zinc-600">%{Number(item.kdvRate ?? 0)}</td>
                    <td className="px-4 py-3 text-sm text-right font-medium text-zinc-900">{Number(item.lineTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {(!quote.items || quote.items.length === 0) && (
            <div className="rounded-xl border border-zinc-200 bg-zinc-50/50 px-4 py-8 text-center text-sm text-zinc-500">
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
                <dd className="font-medium text-zinc-900 dark:text-white">{Number(quote.subtotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
              </div>
              {(Number(quote.generalDiscountPercent) > 0 || Number(quote.generalDiscountAmount) > 0) && (
                <div className="flex justify-between text-zinc-600 dark:text-zinc-400">
                  <dt>Genel indirim</dt>
                  <dd>%{quote.generalDiscountPercent} / {Number(quote.generalDiscountAmount ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
                </div>
              )}
              <div className="flex justify-between">
                <dt className="text-zinc-500">KDV</dt>
                <dd className="font-medium text-zinc-900 dark:text-white">{Number(quote.kdvTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
              </div>
              <div className="flex justify-between border-t border-zinc-200 dark:border-zinc-600 pt-3">
                <dt className="font-semibold text-zinc-700 dark:text-zinc-300">Genel toplam</dt>
                <dd className="text-lg font-bold text-zinc-900 dark:text-white">{Number(quote.grandTotal).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</dd>
              </div>
              {quote.notes && (
                <div className="pt-2 border-t border-zinc-100 dark:border-zinc-600">
                  <dt className="text-zinc-500">Notlar</dt>
                  <dd className="text-zinc-900 dark:text-zinc-200 mt-1 whitespace-pre-wrap">{quote.notes}</dd>
                </div>
              )}
            </dl>
          </div>
        </div>
      </div>
    </div>
  );
}
