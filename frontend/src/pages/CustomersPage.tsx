import { useEffect, useState } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { UserGroupIcon, PlusIcon, CreditCardIcon, EyeIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { customersApi } from '../services/api/customersApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons } from '../components/ui';
import toast from 'react-hot-toast';

interface CustomerRow {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  taxNumber?: string;
  taxOffice?: string;
  identityNumber?: string;
  balance?: number;
  salesCount?: number;
}

export default function CustomersPage() {
  const navigate = useNavigate();
  const [customers, setCustomers] = useState<CustomerRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    address: '',
    taxNumber: '',
    taxOffice: '',
    identityNumber: '',
  });
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState('');
  const [page] = useState(1);
  const [limit] = useState(20);
  const [, setTotal] = useState(0);
  const [, setTotalPages] = useState(1);

  const loadCustomers = () => {
    const params: { active?: boolean; search?: string; page?: number; limit?: number; withBalance?: boolean } = { page, limit, withBalance: true };
    if (activeFilter === 'true' || activeFilter === 'false') params.active = activeFilter === 'true';
    if (search.trim()) params.search = search.trim();
    customersApi
      .list(params)
      .then(({ data }) => {
        const res = data as { data?: unknown[]; total?: number; page?: number; limit?: number; totalPages?: number };
        setCustomers(Array.isArray(res.data) ? (res.data as CustomerRow[]) : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Müşteriler yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    loadCustomers();
  }, [activeFilter, page, limit]);

  const filteredCustomers = customers;

  const openCreate = () => {
    setEditingId(null);
    setForm({
      name: '',
      email: '',
      phone: '',
      address: '',
      taxNumber: '',
      taxOffice: '',
      identityNumber: '',
    });
    setModalOpen(true);
  };

  const handleDelete = (c: CustomerRow) => {
    if (!globalThis.confirm(`"${c.name}" müşterisini silmek istediğinize emin misiniz?`)) return;
    customersApi
      .delete(c.id)
      .then(() => {
        toast.success('Müşteri silindi');
        loadCustomers();
      })
      .catch(() => toast.error('Müşteri silinemedi'));
  };

  const openEdit = (c: CustomerRow) => {
    setEditingId(c.id);
    setForm({
      name: c.name,
      email: c.email ?? '',
      phone: c.phone ?? '',
      address: c.address ?? '',
      taxNumber: c.taxNumber ?? '',
      taxOffice: c.taxOffice ?? '',
      identityNumber: c.identityNumber ?? '',
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
      identityNumber: form.identityNumber.trim() || undefined,
    };
    if (editingId) {
      customersApi
        .update(editingId, payload)
        .then(() => {
          toast.success('Müşteri güncellendi');
          setModalOpen(false);
          loadCustomers();
        })
        .catch(() => toast.error('Müşteri güncellenemedi'));
    } else {
      customersApi
        .create(payload)
        .then(() => {
          toast.success('Müşteri eklendi');
          setModalOpen(false);
          loadCustomers();
        })
        .catch(() => toast.error('Müşteri eklenemedi'));
    }
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Müşteriler"
        description="Müşteri kayıtlarını yönetin"
        icon={UserGroupIcon}
        action={
          <div className="flex gap-2">
            <Link to={ROUTES.odemeAl}>
              <Button variant="secondary" icon={CreditCardIcon}>Müşterilerden ödeme al</Button>
            </Link>
            <Button icon={PlusIcon} onClick={openCreate}>Yeni Müşteri</Button>
          </div>
        }
      />
      <div className="flex flex-wrap gap-3">
        <input
          type="text"
          placeholder="Ad, e-posta veya telefon ara..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-56"
        />
        <select
          value={activeFilter}
          onChange={(e) => setActiveFilter(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tümü</option>
          <option value="true">Aktif</option>
          <option value="false">Pasif</option>
        </select>
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
          </div>
        ) : filteredCustomers.length === 0 ? (
          <EmptyState
            icon={UserGroupIcon}
            title="Müşteri bulunamadı"
            description="Henüz müşteri yok veya filtreye uygun kayıt yok. Yeni müşteri ekleyebilirsiniz."
            action={<Button icon={PlusIcon} onClick={openCreate}>Yeni Müşteri</Button>}
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ad</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">E-posta</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Telefon</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Adres</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Borç</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Satış</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {filteredCustomers.map((c) => (
                  <tr
                    key={c.id}
                    role="button"
                    tabIndex={0}
                    onClick={() => navigate(ROUTES.musteri(c.id))}
                    onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); navigate(ROUTES.musteri(c.id)); } }}
                    className="hover:bg-zinc-50/80 dark:hover:bg-zinc-700/80 transition-colors cursor-pointer"
                  >
                    <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                      <span className="text-emerald-600 hover:text-emerald-700 hover:underline">{c.name}</span>
                    </td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{c.email ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{c.phone ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400 max-w-[200px] truncate">{c.address ?? '—'}</td>
                    <td className="px-6 py-4 text-right text-sm font-medium">
                      {(c.balance ?? 0) > 0 ? (
                        <span className="text-amber-700 dark:text-amber-400">{Number(c.balance).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</span>
                      ) : (
                        <span className="text-zinc-500 dark:text-zinc-400">—</span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-300">
                      {(c.salesCount ?? 0) > 0 ? (
                        <span>{c.salesCount} satış</span>
                      ) : (
                        <span className="text-zinc-400 dark:text-zinc-500">—</span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right" onClick={(e) => e.stopPropagation()}>
                      <div className="flex items-center justify-end gap-1">
                        <Link
                          to={ROUTES.musteri(c.id)}
                          className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
                          title="Müşteri detayı"
                        >
                          <EyeIcon className="h-4 w-4" />
                          Detay
                        </Link>
                        <ActionButtons
                          onEdit={() => openEdit(c)}
                          onDelete={() => handleDelete(c)}
                        />
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <Transition appear show={modalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setModalOpen(false)}>
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-200"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-150"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-200"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-150"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 dark:text-white pb-4 border-b border-zinc-100 dark:border-zinc-700">
                    {editingId ? 'Müşteri Düzenle' : 'Yeni Müşteri'}
                  </Dialog.Title>
                  <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Ad / Unvan *</label>
                      <input
                        type="text"
                        required
                        value={form.name}
                        onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">E-posta *</label>
                      <input
                        type="email"
                        required
                        value={form.email}
                        onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="musteri@ornek.com"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Telefon *</label>
                      <input
                        type="text"
                        required
                        value={form.phone}
                        onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="05XX XXX XX XX"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Adres</label>
                      <textarea
                        rows={2}
                        value={form.address}
                        onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Vergi no</label>
                        <input
                          type="text"
                          value={form.taxNumber}
                          onChange={(e) => setForm((f) => ({ ...f, taxNumber: e.target.value }))}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Vergi dairesi</label>
                        <input
                          type="text"
                          value={form.taxOffice}
                          onChange={(e) => setForm((f) => ({ ...f, taxOffice: e.target.value }))}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        />
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">TC Kimlik No</label>
                      <input
                        type="text"
                        value={form.identityNumber}
                        onChange={(e) => setForm((f) => ({ ...f, identityNumber: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="Fatura için zorunlu değil"
                        maxLength={11}
                      />
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-700">
                      <button
                        type="button"
                        onClick={() => setModalOpen(false)}
                        className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600"
                      >
                        İptal
                      </button>
                      <button
                        type="submit"
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                      >
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
    </div>
  );
}
