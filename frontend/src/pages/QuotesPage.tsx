import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { DocumentTextIcon, PlusIcon, PencilSquareIcon, TrashIcon, DocumentArrowDownIcon, PrinterIcon, PencilIcon } from '@heroicons/react/24/outline';
import { quotesApi } from '../services/api/quotesApi';
import { customersApi } from '../services/api/customersApi';
import { warehousesApi } from '../services/api/warehousesApi';
import { PageHeader, Card, EmptyState, Button, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

const STATUS_LABELS: Record<string, string> = {
  taslak: 'Beklemede',
  gonderildi: 'Onay Bekliyor',
  onaylandi: 'Onaylandı',
  reddedildi: 'Reddedildi',
  satisa_donustu: 'Satışa dönüştürüldü',
};

export default function QuotesPage() {
  const [quotes, setQuotes] = useState<unknown[]>([]);
  const [customers, setCustomers] = useState<{ id: string; name: string }[]>([]);
  const [warehouses, setWarehouses] = useState<{ id: string; name: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [customerFilter, setCustomerFilter] = useState('');
  const [convertModalOpen, setConvertModalOpen] = useState(false);
  const [convertQuoteId, setConvertQuoteId] = useState<string | null>(null);
  const [convertWarehouseId, setConvertWarehouseId] = useState('');
  const [converting, setConverting] = useState(false);
  const [deletingId, setDeletingId] = useState<string | null>(null);
  const [statusModalOpen, setStatusModalOpen] = useState(false);
  const [statusEditQuote, setStatusEditQuote] = useState<{ id: string; quoteNumber: string; status: string } | null>(null);
  const [statusEditValue, setStatusEditValue] = useState('');
  const [updatingStatus, setUpdatingStatus] = useState(false);
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);

  const loadQuotes = () => {
    const params: { status?: string; customerId?: string; page?: number; limit?: number } = { page, limit };
    if (statusFilter) params.status = statusFilter;
    if (customerFilter) params.customerId = customerFilter;
    quotesApi
      .list(params)
      .then(({ data }) => {
        const res = data as { data?: unknown[]; total?: number; totalPages?: number };
        setQuotes(Array.isArray(res?.data) ? res.data : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Teklifler yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    loadQuotes();
  }, [statusFilter, customerFilter, page, limit]);

  useEffect(() => {
    customersApi.list({ limit: 500 }).then(({ data }) => {
      const res = data as { data?: unknown[] };
      setCustomers(Array.isArray(res?.data) ? (res.data as { id: string; name: string }[]) : []);
    });
    warehousesApi.list().then(({ data }) => setWarehouses(Array.isArray(data) ? data : []));
  }, []);

  const openConvertModal = (quoteId: string) => {
    setConvertQuoteId(quoteId);
    setConvertWarehouseId(warehouses[0]?.id ?? '');
    setConvertModalOpen(true);
  };

  const handleConvertToSale = () => {
    if (!convertQuoteId || !convertWarehouseId) {
      toast.error('Depo seçiniz.');
      return;
    }
    setConverting(true);
    quotesApi
      .convertToSale(convertQuoteId, convertWarehouseId)
      .then(() => {
        toast.success('Teklif satışa dönüştürüldü.');
        setConvertModalOpen(false);
        setConvertQuoteId(null);
        loadQuotes();
      })
      .catch((err) => toast.error(err.response?.data?.message ?? 'Dönüştürme başarısız'))
      .finally(() => setConverting(false));
  };

  const handlePdfView = (id: string) => {
    quotesApi
      .getPdf(id)
      .then(({ data }) => {
        const blob = data as Blob;
        if (!(blob instanceof Blob) || blob.size === 0 || (blob.type && blob.type.includes('json'))) {
          toast.error('PDF yanıtı geçersiz.');
          return;
        }
        const url = URL.createObjectURL(blob);
        const w = window.open(url, '_blank');
        if (w) w.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        else {
          URL.revokeObjectURL(url);
          toast.error('Pencere açılamadı. Pop-up engelleyicisini kapatıp tekrar deneyin.');
        }
      })
      .catch((err) => toast.error(err?.response?.data?.message ?? 'PDF açılamadı.'));
  };

  const handlePrint = (id: string) => {
    quotesApi
      .getPdf(id)
      .then(({ data }) => {
        const blob = data as Blob;
        if (!(blob instanceof Blob) || blob.size === 0 || (blob.type && blob.type.includes('json'))) {
          toast.error('PDF yanıtı geçersiz.');
          return;
        }
        const url = URL.createObjectURL(blob);
        const w = window.open(url, '_blank');
        if (w) {
          w.addEventListener('load', () => {
            w.print();
            URL.revokeObjectURL(url);
          }, { once: true });
        } else {
          URL.revokeObjectURL(url);
          toast.error('Pencere açılamadı. Pop-up engelleyicisini kapatıp tekrar deneyin.');
        }
      })
      .catch((err) => toast.error(err?.response?.data?.message ?? 'PDF açılamadı.'));
  };

  const handleDelete = (id: string, quoteNumber: string) => {
    if (!window.confirm(`"${quoteNumber}" teklifini silmek istediğinize emin misiniz?`)) return;
    setDeletingId(id);
    quotesApi.delete(id)
      .then(() => {
        toast.success('Teklif silindi.');
        loadQuotes();
      })
      .catch((err) => toast.error(err.response?.data?.message ?? 'Silinemedi.'))
      .finally(() => setDeletingId(null));
  };

  const openStatusModal = (row: Record<string, unknown>) => {
    setStatusEditQuote({
      id: String(row.id),
      quoteNumber: String(row.quoteNumber ?? '—'),
      status: String(row.status ?? 'taslak'),
    });
    setStatusEditValue(String(row.status ?? 'taslak'));
    setStatusModalOpen(true);
  };

  const handleStatusUpdate = () => {
    if (!statusEditQuote || !statusEditValue) return;
    setUpdatingStatus(true);
    quotesApi
      .updateStatus(statusEditQuote.id, statusEditValue)
      .then(() => {
        toast.success('Teklif durumu güncellendi.');
        setStatusModalOpen(false);
        setStatusEditQuote(null);
        loadQuotes();
      })
      .catch((err) => toast.error(err.response?.data?.message ?? 'Durum güncellenemedi.'))
      .finally(() => setUpdatingStatus(false));
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Teklifler"
        description="Teklif oluşturun ve takip edin"
        icon={DocumentTextIcon}
        action={
          <Link to={ROUTES.teklifYeni}>
            <Button icon={PlusIcon}>Yeni Teklif</Button>
          </Link>
        }
      />
      <div className="flex flex-wrap gap-3">
        <select
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tüm durumlar</option>
          {Object.entries(STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <select
          value={customerFilter}
          onChange={(e) => { setCustomerFilter(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 min-w-[180px]"
        >
          <option value="">Tüm müşteriler</option>
          {customers.map((c) => (
            <option key={c.id} value={c.id}>{c.name}</option>
          ))}
        </select>
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
          </div>
        ) : quotes.length === 0 ? (
          <EmptyState
            icon={DocumentTextIcon}
            title="Teklif bulunamadı"
            description="Henüz teklif yok. Yeni teklif oluşturarak başlayın."
            action={
              <Link to={ROUTES.teklifYeni}>
                <Button icon={PlusIcon}>Yeni Teklif</Button>
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
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Teklif No</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Durum</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Toplam</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">İşlem</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                  {quotes.map((q) => {
                    const row = q as Record<string, unknown>;
                    const canConvert = row.status !== 'satisa_donustu' && !row.convertedSaleId;
                    return (
                      <tr key={String(row.id)} className="hover:bg-zinc-50/80 transition-colors">
                        <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                          <Link to={ROUTES.teklif(String(row.id))} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                            {String(row.quoteNumber ?? '—')}
                          </Link>
                        </td>
                        <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                          <button
                            type="button"
                            onClick={() => openStatusModal(row)}
                            className="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 -mx-2 text-left hover:bg-zinc-100 transition-colors group"
                            title="Teklif durumunu düzenle"
                          >
                            <span>{STATUS_LABELS[String(row.status)] ?? String(row.status ?? '—')}</span>
                            <PencilIcon className="w-3.5 h-3.5 text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                          </button>
                        </td>
                        <td className="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">{Number(row.grandTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                        <td className="px-6 py-4 text-right">
                          <div className="flex items-center justify-end gap-1 flex-wrap">
                            <Link to={ROUTES.teklifDuzenle(String(row.id))} className="inline-flex p-2 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/30" title="Düzenle">
                              <PencilSquareIcon className="w-5 h-5" />
                            </Link>
                            <button type="button" onClick={() => handlePdfView(String(row.id))} className="inline-flex p-2 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30" title="PDF görüntüle">
                              <DocumentArrowDownIcon className="w-5 h-5" />
                            </button>
                            <button type="button" onClick={() => handlePrint(String(row.id))} className="inline-flex p-2 rounded-lg text-zinc-500 hover:text-zinc-700 hover:bg-zinc-100" title="Yazdır">
                              <PrinterIcon className="w-5 h-5" />
                            </button>
                            {canConvert && (
                              <Button variant="ghost" onClick={() => openConvertModal(String(row.id))}>
                                Satışa dönüştür
                              </Button>
                            )}
                            {!row.convertedSaleId && (
                              <button type="button" onClick={() => handleDelete(String(row.id), String(row.quoteNumber))} disabled={deletingId === row.id} className="inline-flex p-2 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-50" title="Sil">
                                <TrashIcon className="w-5 h-5" />
                              </button>
                            )}
                          </div>
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

      {/* Teklif durumu düzenle modal */}
      <Transition appear show={statusModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !updatingStatus && setStatusModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-sm rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">Teklif durumu düzenle</Dialog.Title>
                  {statusEditQuote && (
                    <>
                      <p className="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Teklif: <span className="font-medium text-zinc-700 dark:text-zinc-200">{statusEditQuote.quoteNumber}</span></p>
                      <div className="mt-4">
                        <label htmlFor="quote-status-edit" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Durum</label>
                        <select
                          id="quote-status-edit"
                          value={statusEditValue}
                          onChange={(e) => setStatusEditValue(e.target.value)}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        >
                          {Object.entries(STATUS_LABELS).map(([k, v]) => (
                            <option key={k} value={k}>{v}</option>
                          ))}
                        </select>
                      </div>
                      <div className="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4">
                        <button type="button" onClick={() => setStatusModalOpen(false)} disabled={updatingStatus} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 disabled:opacity-50">İptal</button>
                        <button type="button" onClick={handleStatusUpdate} disabled={updatingStatus} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{updatingStatus ? 'Kaydediliyor...' : 'Kaydet'}</button>
                      </div>
                    </>
                  )}
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      <Transition appear show={convertModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setConvertModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-sm rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">Satışa dönüştür</Dialog.Title>
                  <p className="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Stok düşülecek depoyu seçin.</p>
                  <div className="mt-4">
                    <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Depo *</label>
                    <select
                      value={convertWarehouseId}
                      onChange={(e) => setConvertWarehouseId(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    >
                      <option value="">Seçiniz</option>
                      {warehouses.map((w) => (
                        <option key={w.id} value={w.id}>{w.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4">
                    <button type="button" onClick={() => setConvertModalOpen(false)} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50">İptal</button>
                    <button type="button" onClick={handleConvertToSale} disabled={converting || !convertWarehouseId} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{converting ? 'Dönüştürülüyor...' : 'Satışa dönüştür'}</button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  );
}
