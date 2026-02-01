import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { TruckIcon, PlusIcon, ChevronDownIcon, ChevronRightIcon, MagnifyingGlassIcon, BanknotesIcon, CreditCardIcon, BuildingLibraryIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { suppliersApi } from '../services/api/suppliersApi';
import { supplierPaymentsApi } from '../services/api/supplierPaymentsApi';
import { kasaApi } from '../services/api/kasaApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

type PaymentMethod = 'kredi_karti' | 'havale';

interface KasaOption {
  id: string;
  name: string;
  type: string;
  bankName?: string;
}

interface ProductItem {
  id: string;
  name: string;
  sku?: string;
  unitPrice?: number;
}

interface SupplierRow {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  taxNumber?: string;
  taxOffice?: string;
  isActive?: boolean;
  products?: ProductItem[];
}

const inputClass = 'mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20';
const labelClass = 'block text-sm font-medium text-zinc-700 dark:text-zinc-200';

export default function SuppliersPage() {
  const [suppliers, setSuppliers] = useState<SupplierRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [expandedId, setExpandedId] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
    taxNumber: '',
    taxOffice: '',
  });

  const [paymentModalOpen, setPaymentModalOpen] = useState(false);
  const [supplierForPayment, setSupplierForPayment] = useState<SupplierRow | null>(null);
  const [banks, setBanks] = useState<KasaOption[]>([]);
  const [paymentSubmitting, setPaymentSubmitting] = useState(false);
  const [paymentBalance, setPaymentBalance] = useState<{ totalPurchases: number; totalPayments: number; balance: number } | null>(null);
  const [paymentBalanceLoading, setPaymentBalanceLoading] = useState(false);
  const [paymentForm, setPaymentForm] = useState({
    supplierId: '',
    amount: '',
    paymentDate: new Date().toISOString().slice(0, 10),
    paymentMethod: 'havale' as PaymentMethod,
    kasaId: '',
    notes: '',
  });
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);

  const loadSuppliers = () => {
    suppliersApi
      .list({ page, limit })
      .then(({ data }) => {
        const res = data as { data?: SupplierRow[]; total?: number; totalPages?: number };
        setSuppliers(Array.isArray(res?.data) ? res.data : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Tedarikçiler yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    loadSuppliers();
  }, [page, limit]);

  useEffect(() => {
    if (paymentModalOpen) {
      kasaApi.list().then(({ data }) => {
        const list = Array.isArray(data) ? data : [];
        setBanks(list.filter((k: KasaOption) => k.type === 'banka'));
      }).catch(() => setBanks([]));
    }
  }, [paymentModalOpen]);

  const currentPaymentSupplierId = (supplierForPayment?.id ?? paymentForm.supplierId) || null;
  useEffect(() => {
    if (!paymentModalOpen || !currentPaymentSupplierId) {
      setPaymentBalance(null);
      return;
    }
    setPaymentBalanceLoading(true);
    setPaymentBalance(null);
    supplierPaymentsApi
      .balance(currentPaymentSupplierId)
      .then(({ data }) => {
        if (data && (typeof data.balance === 'number' || typeof data.balance === 'string' || data.balance != null)) {
          const totalPurchases = Number(data.totalPurchases ?? 0);
          const totalPayments = Number(data.totalPayments ?? 0);
          const balance = Number(data.balance ?? totalPurchases - totalPayments);
          setPaymentBalance({ totalPurchases, totalPayments, balance });
        }
      })
      .catch(() => setPaymentBalance(null))
      .finally(() => setPaymentBalanceLoading(false));
  }, [paymentModalOpen, currentPaymentSupplierId]);

  const openPaymentModal = (s: SupplierRow | null) => {
    setSupplierForPayment(s ?? null);
    setPaymentForm({
      supplierId: s?.id ?? '',
      amount: '',
      paymentDate: new Date().toISOString().slice(0, 10),
      paymentMethod: 'havale',
      kasaId: '',
      notes: '',
    });
    setPaymentModalOpen(true);
  };

  const closePaymentModal = () => {
    if (!paymentSubmitting) {
      setPaymentModalOpen(false);
      setSupplierForPayment(null);
      setPaymentBalance(null);
    }
  };

  const handlePaymentSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const supplier = supplierForPayment ?? suppliers.find((s) => s.id === paymentForm.supplierId);
    if (!supplier) {
      toast.error('Tedarikçi seçiniz.');
      return;
    }
    const amount = Number.parseFloat(paymentForm.amount.replace(',', '.')) || 0;
    if (amount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    if (paymentForm.paymentMethod === 'havale' && !paymentForm.kasaId) {
      toast.error('Havale için para çıkışı yapılan bankayı seçiniz.');
      return;
    }
    setPaymentSubmitting(true);
    const bankName = paymentForm.paymentMethod === 'havale' && paymentForm.kasaId
      ? banks.find((b) => b.id === paymentForm.kasaId)?.name ?? ''
      : '';
    const notes = [
      paymentForm.notes.trim(),
      bankName ? `Havale - ${bankName}` : '',
    ].filter(Boolean).join(' | ') || undefined;

    supplierPaymentsApi
      .create({
        supplierId: supplier.id,
        amount,
        paymentDate: paymentForm.paymentDate,
        paymentType: paymentForm.paymentMethod === 'havale' ? 'havale' : 'kredi_karti',
        notes,
        ...(paymentForm.kasaId ? { kasaId: paymentForm.kasaId } : {}),
      })
      .then(() => {
        toast.success('Tedarikçiye ödeme kaydedildi.');
        setPaymentModalOpen(false);
        setSupplierForPayment(null);
      })
      .catch(() => toast.error('Ödeme kaydedilemedi.'))
      .finally(() => setPaymentSubmitting(false));
  };

  const openCreate = () => {
    setEditingId(null);
    setForm({ name: '', email: '', phone: '', address: '', taxNumber: '', taxOffice: '' });
    setModalOpen(true);
  };

  const openEdit = (s: SupplierRow) => {
    setEditingId(s.id);
    setForm({
      name: s.name,
      email: s.email ?? '',
      phone: s.phone ?? '',
      address: s.address ?? '',
      taxNumber: s.taxNumber ?? '',
      taxOffice: s.taxOffice ?? '',
    });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
      name: form.name.trim(),
      email: form.email.trim() || undefined,
      phone: form.phone.trim() || undefined,
      address: form.address.trim() || undefined,
      taxNumber: form.taxNumber.trim() || undefined,
      taxOffice: form.taxOffice.trim() || undefined,
    };
    if (editingId) {
      suppliersApi.update(editingId, payload).then(() => {
        toast.success('Tedarikçi güncellendi');
        setModalOpen(false);
        loadSuppliers();
      }).catch(() => toast.error('Tedarikçi güncellenemedi'));
    } else {
      suppliersApi.create(payload).then(() => {
        toast.success('Tedarikçi eklendi');
        setModalOpen(false);
        loadSuppliers();
      }).catch(() => toast.error('Tedarikçi eklenemedi'));
    }
  };

  const handleDelete = (id: string) => {
    if (!globalThis.confirm('Bu tedarikçiyi silmek istediğinize emin misiniz? Ürünler tedarikçisiz kalacaktır.')) return;
    suppliersApi.delete(id).then(() => {
      toast.success('Tedarikçi silindi');
      loadSuppliers();
    }).catch(() => toast.error('Tedarikçi silinemedi'));
  };

  const filtered = search.trim()
    ? suppliers.filter(
        (s) =>
          s.name.toLowerCase().includes(search.toLowerCase()) ||
          (s.email ?? '').toLowerCase().includes(search.toLowerCase()) ||
          (s.phone ?? '').includes(search)
      )
    : suppliers;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Tedarikçiler"
        description="Tedarikçi kayıtları ve ürün ilişkileri"
        icon={TruckIcon}
        action={
          <div className="flex items-center gap-2 flex-wrap">
            <button
              type="button"
              onClick={(e) => { e.preventDefault(); e.stopPropagation(); openPaymentModal(null); }}
              className="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
            >
              <BanknotesIcon className="h-5 w-5 shrink-0" />
              Tedarikçiye ödeme yap
            </button>
            <Button icon={PlusIcon} onClick={openCreate}>Yeni Tedarikçi</Button>
          </div>
        }
      />

      {!loading && suppliers.length > 0 && (
        <div className="relative">
          <MagnifyingGlassIcon className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" />
          <input
            type="text"
            placeholder="Tedarikçi ara (ad, e-posta, telefon)..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 py-2.5 pl-10 pr-4 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 sm:max-w-xs"
          />
        </div>
      )}

      <Card padding="none" className="overflow-hidden rounded-2xl border border-zinc-200/80 shadow-sm">
        {loading ? (
          <div className="flex items-center justify-center py-20">
            <div className="h-8 w-8 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent" />
          </div>
        ) : filtered.length === 0 ? (
          <EmptyState
            icon={TruckIcon}
            title={search.trim() ? 'Sonuç bulunamadı' : 'Tedarikçi bulunamadı'}
            description={search.trim() ? 'Arama kriterlerini değiştirmeyi deneyin.' : 'Henüz tedarikçi yok. Yeni tedarikçi ekleyebilirsiniz.'}
            action={!search.trim() ? <Button icon={PlusIcon} onClick={openCreate}>Yeni Tedarikçi</Button> : undefined}
            className="rounded-2xl m-0 border-0 py-16"
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-zinc-100">
                <thead>
                  <tr className="bg-zinc-50/80">
                    <th className="w-10 px-4 py-4" scope="col" />
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tedarikçi</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İletişim</th>
                    <th className="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ürün</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlem</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                  {filtered.map((s) => {
                  const expanded = expandedId === s.id;
                  const hasProducts = (s.products?.length ?? 0) > 0;
                  return (
                    <Fragment key={s.id}>
                      <tr className="transition-colors hover:bg-zinc-50/50">
                        <td className="px-4 py-3">
                          {hasProducts ? (
                            <button
                              type="button"
                              onClick={() => setExpandedId(expanded ? null : s.id)}
                              className="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-600 hover:text-zinc-600 dark:hover:text-zinc-300 transition"
                              aria-label={expanded ? 'Daralt' : 'Genişlet'}
                            >
                              {expanded ? <ChevronDownIcon className="h-5 w-5" /> : <ChevronRightIcon className="h-5 w-5" />}
                            </button>
                          ) : (
                            <span className="block w-8" />
                          )}
                        </td>
                        <td className="px-6 py-4">
                          <Link to={ROUTES.tedarikci(s.id)} className="font-semibold text-emerald-600 hover:text-emerald-700 hover:underline">
                            {s.name}
                          </Link>
                          {s.taxOffice && (
                            <p className="mt-0.5 text-xs text-zinc-500 dark:text-zinc-300">{s.taxOffice}</p>
                          )}
                        </td>
                        <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                          <div className="space-y-0.5">
                            {s.email && <p>{s.email}</p>}
                            {s.phone && <p>{s.phone}</p>}
                            {!s.email && !s.phone && <span className="text-zinc-400">—</span>}
                          </div>
                        </td>
                        <td className="px-6 py-4 text-center">
                          <span className="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-700 px-2.5 py-0.5 text-xs font-medium text-zinc-700 dark:text-zinc-200">
                            {s.products?.length ?? 0}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="flex items-center justify-end gap-1">
                            <button
                              type="button"
                              onClick={(e) => { e.preventDefault(); e.stopPropagation(); openPaymentModal(s); }}
                              title="Tedarikçiye ödeme yap"
                              className="inline-flex items-center justify-center gap-2 rounded-lg p-2 text-sm font-medium text-emerald-600 bg-emerald-50 hover:bg-emerald-100 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
                            >
                              <BanknotesIcon className="h-5 w-5" />
                            </button>
                            <ActionButtons
                              viewHref={ROUTES.tedarikci(s.id)}
                              viewLabel="Görüntüle"
                              onEdit={() => openEdit(s)}
                              onDelete={() => handleDelete(s.id)}
                            />
                          </div>
                        </td>
                      </tr>
                      {expanded && hasProducts && (
                        <tr>
                          <td colSpan={5} className="bg-zinc-50/50 px-6 py-4">
                            <div className="ml-8 rounded-xl border border-zinc-100 dark:border-zinc-600 bg-white dark:bg-zinc-800 p-4 shadow-sm">
                              <p className="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ürünler</p>
                              <ul className="space-y-2">
                                {(s.products ?? []).map((p) => (
                                  <li key={p.id} className="flex items-center justify-between rounded-lg bg-zinc-50/80 px-3 py-2 text-sm">
                                    <span className="font-medium text-zinc-800 dark:text-white">{p.name}</span>
                                    {p.sku && <span className="text-zinc-500 dark:text-zinc-300">{p.sku}</span>}
                                    <span className="text-emerald-600 font-medium">
                                      {Number(p.unitPrice ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                                    </span>
                                  </li>
                                ))}
                              </ul>
                            </div>
                          </td>
                        </tr>
                      )}
                    </Fragment>
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

      <Transition appear show={modalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-lg rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/20 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 border-b border-zinc-100 pb-4">
                    {editingId ? 'Tedarikçi düzenle' : 'Yeni tedarikçi'}
                  </Dialog.Title>
                  <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                    <div>
                      <label htmlFor="supplier-name" className={labelClass}>Ad / Firma *</label>
                      <input id="supplier-name" type="text" required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} className={inputClass} placeholder="Firma adı" />
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label htmlFor="supplier-email" className={labelClass}>E-posta</label>
                        <input id="supplier-email" type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} className={inputClass} placeholder="ornek@firma.com" />
                      </div>
                      <div>
                        <label htmlFor="supplier-phone" className={labelClass}>Telefon</label>
                        <input id="supplier-phone" type="text" value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} className={inputClass} placeholder="05xx xxx xx xx" />
                      </div>
                    </div>
                    <div>
                      <label htmlFor="supplier-address" className={labelClass}>Adres</label>
                      <textarea id="supplier-address" rows={2} value={form.address} onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))} className={inputClass} placeholder="Adres" />
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <label htmlFor="supplier-tax" className={labelClass}>Vergi no</label>
                        <input id="supplier-tax" type="text" value={form.taxNumber} onChange={(e) => setForm((f) => ({ ...f, taxNumber: e.target.value }))} className={inputClass} />
                      </div>
                      <div>
                        <label htmlFor="supplier-office" className={labelClass}>Vergi dairesi</label>
                        <input id="supplier-office" type="text" value={form.taxOffice} onChange={(e) => setForm((f) => ({ ...f, taxOffice: e.target.value }))} className={inputClass} />
                      </div>
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button type="button" onClick={() => setModalOpen(false)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2">
                        İptal
                      </button>
                      <button type="submit" className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        {editingId ? 'Güncelle' : 'Ekle'}
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Tedarikçiye ödeme yap modal */}
      <Transition appear show={paymentModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={closePaymentModal}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/20 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 border-b border-zinc-100 pb-4">
                    {supplierForPayment ? `Tedarikçiye ödeme yap — ${supplierForPayment.name}` : 'Tedarikçiye ödeme yap'}
                  </Dialog.Title>
                  <form onSubmit={handlePaymentSubmit} className="mt-5 space-y-4">
                    {!supplierForPayment && (
                      <div>
                        <label htmlFor="payment-supplier" className={labelClass}>Tedarikçi *</label>
                        <select
                          id="payment-supplier"
                          required
                          value={paymentForm.supplierId}
                          onChange={(e) => setPaymentForm((f) => ({ ...f, supplierId: e.target.value }))}
                          className={inputClass}
                        >
                          <option value="">Tedarikçi seçin</option>
                          {suppliers.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                          ))}
                        </select>
                      </div>
                    )}
                    {currentPaymentSupplierId && (
                      <div className="rounded-xl border-2 border-emerald-200 bg-emerald-50/60 p-4">
                        <p className="text-xs font-semibold uppercase tracking-wider text-emerald-700 mb-2">Bakiye</p>
                        {paymentBalanceLoading ? (
                          <p className="text-sm text-zinc-600 dark:text-zinc-300">Yükleniyor...</p>
                        ) : paymentBalance ? (
                          <div className="space-y-1">
                            <p className="text-base font-semibold text-zinc-800 dark:text-white">
                              {Number(paymentBalance.balance) > 0 && `Tedarikçiye borcunuz: ${Number(paymentBalance.balance).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
                              {Number(paymentBalance.balance) < 0 && `Tedarikçiden alacağınız: ${Math.abs(Number(paymentBalance.balance)).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺`}
                              {Number(paymentBalance.balance) === 0 && 'Borç / alacak yok'}
                            </p>
                            <p className="text-xs text-zinc-600 dark:text-zinc-300">
                              Toplam alış: {Number(paymentBalance.totalPurchases).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                              {' · '}
                              Toplam ödeme: {Number(paymentBalance.totalPayments).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                            </p>
                          </div>
                        ) : (
                          <p className="text-sm text-amber-600">Bakiye bilgisi alınamadı.</p>
                        )}
                      </div>
                    )}
                    <div>
                      <label htmlFor="payment-amount" className={labelClass}>Tutar (₺) *</label>
                      <input
                        id="payment-amount"
                        type="text"
                        inputMode="decimal"
                        required
                        value={paymentForm.amount}
                        onChange={(e) => setPaymentForm((f) => ({ ...f, amount: e.target.value }))}
                        className={inputClass}
                        placeholder="0,00"
                      />
                    </div>
                    <div>
                      <label htmlFor="payment-date" className={labelClass}>Ödeme tarihi *</label>
                      <input
                        id="payment-date"
                        type="date"
                        required
                        value={paymentForm.paymentDate}
                        onChange={(e) => setPaymentForm((f) => ({ ...f, paymentDate: e.target.value }))}
                        className={inputClass}
                      />
                    </div>
                    <div>
                      <span className={labelClass}>Ödeme türü</span>
                      <div className="mt-2 flex gap-3">
                        <button
                          type="button"
                          onClick={() => setPaymentForm((f) => ({ ...f, paymentMethod: 'havale', kasaId: f.kasaId }))}
                          className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            paymentForm.paymentMethod === 'havale'
                              ? 'bg-emerald-600 text-white'
                              : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'
                          }`}
                        >
                          <BuildingLibraryIcon className="h-5 w-5" />
                          Havale
                        </button>
                        <button
                          type="button"
                          onClick={() => setPaymentForm((f) => ({ ...f, paymentMethod: 'kredi_karti', kasaId: '' }))}
                          className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            paymentForm.paymentMethod === 'kredi_karti'
                              ? 'bg-emerald-600 text-white'
                              : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'
                          }`}
                        >
                          <CreditCardIcon className="h-5 w-5" />
                          Kredi kartı
                        </button>
                      </div>
                    </div>
                    {paymentForm.paymentMethod === 'havale' && (
                      <div>
                        <label htmlFor="payment-kasa" className={labelClass}>Para çıkışı yapılan banka *</label>
                        <select
                          id="payment-kasa"
                          required={paymentForm.paymentMethod === 'havale'}
                          value={paymentForm.kasaId}
                          onChange={(e) => setPaymentForm((f) => ({ ...f, kasaId: e.target.value }))}
                          className={inputClass}
                        >
                          <option value="">Banka seçin</option>
                          {banks.map((b) => (
                            <option key={b.id} value={b.id}>
                              {b.name}{b.bankName ? ` (${b.bankName})` : ''}
                            </option>
                          ))}
                        </select>
                        <p className="mt-1 text-xs text-zinc-500 dark:text-zinc-300">Havale ile ödemenin yapıldığı banka hesabını seçin.</p>
                      </div>
                    )}
                    <div>
                      <label htmlFor="payment-notes" className={labelClass}>Not</label>
                      <textarea
                        id="payment-notes"
                        rows={3}
                        value={paymentForm.notes}
                        onChange={(e) => setPaymentForm((f) => ({ ...f, notes: e.target.value }))}
                        className={inputClass}
                        placeholder="Ödeme ile ilgili not (opsiyonel)"
                      />
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button
                        type="button"
                        onClick={closePaymentModal}
                        disabled={paymentSubmitting}
                        className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 disabled:opacity-50"
                      >
                        İptal
                      </button>
                      <button
                        type="submit"
                        disabled={
                          paymentSubmitting ||
                          !paymentForm.amount ||
                          (!supplierForPayment && !paymentForm.supplierId) ||
                          (paymentForm.paymentMethod === 'havale' && !paymentForm.kasaId)
                        }
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
                      >
                        {paymentSubmitting ? 'Kaydediliyor...' : 'Ödemeyi kaydet'}
                      </button>
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
