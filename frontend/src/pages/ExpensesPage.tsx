import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { ROUTES } from '../config/routes';
import { CurrencyDollarIcon, PencilIcon, PlusIcon, TagIcon, TrashIcon } from '@heroicons/react/24/outline';
import { expensesApi } from '../services/api/expensesApi';
import { kasaApi } from '../services/api/kasaApi';
import { expenseCategoriesApi } from '../services/api/expenseCategoriesApi';
import { suppliersApi } from '../services/api/suppliersApi';
import { supplierPaymentsApi } from '../services/api/supplierPaymentsApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons } from '../components/ui';
import toast from 'react-hot-toast';

const TEDARIKCI_ODEMESI_CATEGORY = 'Tedarikçi ödemesi';

interface ExpenseRow {
  id: string;
  amount: number;
  expenseDate: string;
  description: string;
  category?: string;
  kasaId: string;
  kasa?: { id: string; name: string };
  createdByUser?: { id: string; name: string };
}

interface KasaOption {
  id: string;
  name: string;
}

interface CategoryOption {
  id: string;
  name: string;
}

export default function ExpensesPage() {
  const navigate = useNavigate();
  const [list, setList] = useState<ExpenseRow[]>([]);
  const [kasaList, setKasaList] = useState<KasaOption[]>([]);
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [suppliers, setSuppliers] = useState<{ id: string; name: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState({
    amount: '',
    expenseDate: new Date().toISOString().slice(0, 10),
    description: '',
    category: '',
    kasaId: '',
    supplierId: '',
  });
  const [kasaFilter, setKasaFilter] = useState('');
  const [fromFilter, setFromFilter] = useState('');
  const [toFilter, setToFilter] = useState('');
  const [categoryModalOpen, setCategoryModalOpen] = useState(false);
  const [newCategoryName, setNewCategoryName] = useState('');
  const [addingCategory, setAddingCategory] = useState(false);
  const [editingCategoryId, setEditingCategoryId] = useState<string | null>(null);
  const [editingCategoryName, setEditingCategoryName] = useState('');
  const [deletingCategoryId, setDeletingCategoryId] = useState<string | null>(null);

  const loadCategories = () => {
    expenseCategoriesApi.list().then(({ data }) => setCategories(Array.isArray(data) ? data : [])).catch(() => {});
  };

  const load = () => {
    const params: { kasaId?: string; from?: string; to?: string } = {};
    if (kasaFilter) params.kasaId = kasaFilter;
    if (fromFilter) params.from = fromFilter;
    if (toFilter) params.to = toFilter;
    expensesApi
      .list(params)
      .then(({ data }) => setList(Array.isArray(data) ? data : []))
      .catch((err: { response?: { status?: number } }) => {
        if (err?.response?.status === 403) {
          toast.error('Bu sayfaya erişim yetkiniz yok.');
          navigate(ROUTES.home, { replace: true });
        } else {
          toast.error('Masraflar yüklenemedi');
        }
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    load();
  }, [kasaFilter, fromFilter, toFilter]);

  useEffect(() => {
    kasaApi
      .list()
      .then(({ data }) => setKasaList(Array.isArray(data) ? data : []))
      .catch((err: { response?: { status?: number } }) => {
        if (err?.response?.status === 403) {
          toast.error('Bu sayfaya erişim yetkiniz yok.');
          navigate(ROUTES.home, { replace: true });
        }
      });
    loadCategories();
    suppliersApi.list().then(({ data }) => setSuppliers(Array.isArray(data) ? data : [])).catch(() => {});
  }, [navigate]);

  const handleAddCategory = () => {
    const name = newCategoryName.trim();
    if (!name) return;
    setAddingCategory(true);
    expenseCategoriesApi
      .create({ name })
      .then(({ data }) => {
        const created = data as { id: string; name: string };
        setCategories((prev) => [...prev, { id: created.id ?? name, name: created.name ?? name }]);
        setForm((f) => ({ ...f, category: created.name ?? name }));
        setNewCategoryName('');
        toast.success('Masraf kategorisi eklendi');
      })
      .catch(() => toast.error('Kategori eklenemedi'))
      .finally(() => setAddingCategory(false));
  };

  const startEditCategory = (c: CategoryOption) => {
    setEditingCategoryId(c.id);
    setEditingCategoryName(c.name);
  };

  const cancelEditCategory = () => {
    setEditingCategoryId(null);
    setEditingCategoryName('');
  };

  const saveEditCategory = () => {
    const name = editingCategoryName.trim();
    if (!editingCategoryId || !name) return;
    expenseCategoriesApi
      .update(editingCategoryId, { name })
      .then(() => {
        setCategories((prev) => prev.map((c) => (c.id === editingCategoryId ? { ...c, name } : c)));
        setForm((f) => (f.category === (categories.find((x) => x.id === editingCategoryId)?.name) ? { ...f, category: name } : f));
        cancelEditCategory();
        toast.success('Kategori güncellendi');
      })
      .catch(() => toast.error('Kategori güncellenemedi'));
  };

  const handleDeleteCategory = (c: CategoryOption) => {
    if (!globalThis.confirm(`"${c.name}" kategorisini silmek istediğinize emin misiniz? Bu kategorideki masraflar kategorisiz görünebilir.`)) return;
    setDeletingCategoryId(c.id);
    expenseCategoriesApi
      .delete(c.id)
      .then(() => {
        setCategories((prev) => prev.filter((x) => x.id !== c.id));
        if (form.category === c.name) setForm((f) => ({ ...f, category: '' }));
        toast.success('Kategori silindi');
      })
      .catch(() => toast.error('Kategori silinemedi (kullanımda olabilir).'))
      .finally(() => setDeletingCategoryId(null));
  };

  const openCreate = () => {
    setEditingId(null);
    setForm({
      amount: '',
      expenseDate: new Date().toISOString().slice(0, 10),
      description: '',
      category: '',
      kasaId: kasaList[0]?.id ?? '',
      supplierId: '',
    });
    setModalOpen(true);
  };

  const openEdit = (e: ExpenseRow) => {
    setEditingId(e.id);
    setForm({
      amount: String(e.amount),
      expenseDate: e.expenseDate ? String(e.expenseDate).slice(0, 10) : new Date().toISOString().slice(0, 10),
      description: e.description ?? '',
      category: e.category ?? '',
      kasaId: e.kasaId ?? '',
      supplierId: '',
    });
    setModalOpen(true);
  };

  const categoryOptions: { id: string; name: string }[] = [
    ...categories.map((c) => ({ id: c.name, name: c.name })),
  ];
  if (!categories.some((c) => c.name === TEDARIKCI_ODEMESI_CATEGORY)) {
    categoryOptions.push({ id: TEDARIKCI_ODEMESI_CATEGORY, name: TEDARIKCI_ODEMESI_CATEGORY });
  }

  const handleSubmit = (ev: React.FormEvent) => {
    ev.preventDefault();
    if (!form.kasaId) {
      toast.error('Ödeme yapılan kasa seçiniz.');
      return;
    }
    const isSupplierPayment = form.category === TEDARIKCI_ODEMESI_CATEGORY;
    if (isSupplierPayment && !editingId && !form.supplierId) {
      toast.error('Tedarikçi ödemesi için tedarikçi seçiniz.');
      return;
    }
    const amount = Number.parseFloat(form.amount) || 0;
    const supplierName = suppliers.find((s) => s.id === form.supplierId)?.name ?? '';
    const payload = {
      amount,
      expenseDate: form.expenseDate,
      description: isSupplierPayment && supplierName ? `Tedarikçi ödemesi - ${supplierName}` : form.description.trim(),
      category: form.category.trim() || undefined,
      kasaId: form.kasaId,
    };
    if (editingId) {
      expensesApi.update(editingId, payload).then(() => { toast.success('Masraf güncellendi'); setModalOpen(false); load(); }).catch(() => toast.error('Masraf güncellenemedi'));
    } else {
      expensesApi.create(payload).then(() => {
        if (isSupplierPayment && form.supplierId) {
          supplierPaymentsApi.create({
            supplierId: form.supplierId,
            amount,
            paymentDate: form.expenseDate,
            paymentType: 'nakit',
            notes: form.description.trim() || undefined,
          }).then(() => {
            toast.success('Masraf ve tedarikçi ödemesi kaydedildi');
            setModalOpen(false);
            load();
          }).catch(() => toast.error('Masraf kaydedildi ancak tedarikçi ödemesi kaydedilemedi.'));
        } else {
          toast.success('Masraf eklendi');
          setModalOpen(false);
          load();
        }
      }).catch(() => toast.error('Masraf eklenemedi'));
    }
  };

  const handleDelete = (id: string) => {
    if (!globalThis.confirm('Bu masrafı silmek istediğinize emin misiniz?')) return;
    expensesApi.delete(id).then(() => { toast.success('Masraf silindi'); load(); }).catch(() => toast.error('Masraf silinemedi'));
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Masraflar"
        description="Masraf girişi ve takip"
        icon={CurrencyDollarIcon}
        action={
          <div className="flex flex-wrap items-center gap-2">
            <Button variant="secondary" icon={TagIcon} onClick={() => { setNewCategoryName(''); setCategoryModalOpen(true); }}>
              Masraf kategorisi ekle
            </Button>
            <Button icon={PlusIcon} onClick={openCreate}>Yeni Masraf</Button>
          </div>
        }
      />
      <div className="flex flex-wrap items-center gap-3">
        <Button variant="secondary" icon={TagIcon} onClick={() => { setNewCategoryName(''); setCategoryModalOpen(true); }} className="shrink-0">
          Masraf kategorisi ekle
        </Button>
        <select
          value={kasaFilter}
          onChange={(e) => setKasaFilter(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tüm kasalar</option>
          {kasaList.map((k) => (
            <option key={k.id} value={k.id}>{k.name}</option>
          ))}
        </select>
        <input type="date" value={fromFilter} onChange={(e) => setFromFilter(e.target.value)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" title="Başlangıç" />
        <input type="date" value={toFilter} onChange={(e) => setToFilter(e.target.value)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" title="Bitiş" />
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
          </div>
        ) : list.length === 0 ? (
          <EmptyState
            icon={CurrencyDollarIcon}
            title="Masraf bulunamadı"
            description="Henüz masraf kaydı yok veya filtreye uygun kayıt yok."
            action={<Button icon={PlusIcon} onClick={openCreate}>Yeni Masraf</Button>}
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tarih</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Açıklama</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Kategori</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ödeme (Kasa)</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşleyen</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tutar</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {list.map((e) => (
                  <tr key={e.id} className="hover:bg-zinc-50/80 transition-colors">
                    <td className="px-6 py-4 text-sm text-zinc-900 dark:text-white">{e.expenseDate ? new Date(e.expenseDate).toLocaleDateString('tr-TR') : '—'}</td>
                    <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{e.description}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{e.category ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{(e.kasa as { name?: string })?.name ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{e.createdByUser?.name ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">{Number(e.amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                    <td className="px-6 py-4 text-right">
                      <ActionButtons onEdit={() => openEdit(e)} onDelete={() => handleDelete(e.id)} />
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
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">{editingId ? 'Masraf Düzenle' : 'Yeni Masraf'}</Dialog.Title>
                  <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Ödeme yapılan kasa / banka *</label>
                      <select required value={form.kasaId} onChange={(e) => setForm((f) => ({ ...f, kasaId: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Seçiniz</option>
                        {kasaList.map((k) => (
                          <option key={k.id} value={k.id}>{k.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tutar (₺) *</label>
                      <input type="number" step="0.01" required value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tarih *</label>
                      <input type="date" required value={form.expenseDate} onChange={(e) => setForm((f) => ({ ...f, expenseDate: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Açıklama *</label>
                      <input type="text" required value={form.description} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Kategori</label>
                      <select value={form.category} onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Seçiniz</option>
                        {categoryOptions.map((c) => (
                          <option key={c.id} value={c.name}>{c.name}</option>
                        ))}
                      </select>
                      <button type="button" onClick={() => { setCategoryModalOpen(true); }} className="mt-1.5 text-sm text-emerald-600 hover:text-emerald-700">+ Yeni kategori ekle</button>
                    </div>
                    {form.category === TEDARIKCI_ODEMESI_CATEGORY && !editingId && (
                      <div>
                        <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tedarikçi *</label>
                        <select
                          required={form.category === TEDARIKCI_ODEMESI_CATEGORY}
                          value={form.supplierId}
                          onChange={(e) => setForm((f) => ({ ...f, supplierId: e.target.value }))}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        >
                          <option value="">Tedarikçi seçin</option>
                          {suppliers.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                          ))}
                        </select>
                        <p className="mt-1 text-xs text-zinc-500 dark:text-zinc-300">Bu ödeme tedarikçi detayında ve tedarikçi carisinde görünecektir.</p>
                      </div>
                    )}
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button type="button" onClick={() => setModalOpen(false)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600">İptal</button>
                      <button type="submit" className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">{editingId ? 'Güncelle' : 'Ekle'}</button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Masraf kategorileri modal (ekle / düzenle / sil) */}
      <Transition appear show={categoryModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !addingCategory && !editingCategoryId && setCategoryModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-black/30" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-2xl">
                  <Dialog.Title className="text-lg font-semibold text-zinc-900 pb-3 border-b border-zinc-100">Masraf kategorileri</Dialog.Title>
                  <div className="mt-4 space-y-4">
                    <div>
                      <label htmlFor="new-expense-cat" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Yeni kategori ekle</label>
                      <div className="mt-1.5 flex gap-2">
                        <input
                          id="new-expense-cat"
                          type="text"
                          value={newCategoryName}
                          onChange={(e) => setNewCategoryName(e.target.value)}
                          placeholder="Örn: Personel, Kira"
                          className="block flex-1 rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        />
                        <button type="button" onClick={handleAddCategory} disabled={addingCategory || !newCategoryName.trim()} className="shrink-0 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">{addingCategory ? 'Ekleniyor...' : 'Ekle'}</button>
                      </div>
                    </div>
                    <div>
                      <p className="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Mevcut kategoriler</p>
                      {categories.length === 0 ? (
                        <p className="rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">Henüz kategori yok. Yukarıdan ekleyebilirsiniz.</p>
                      ) : (
                        <ul className="space-y-1 rounded-xl border border-zinc-200 divide-y divide-zinc-100 max-h-56 overflow-y-auto">
                          {categories.map((c) => (
                            <li key={c.id} className="flex items-center gap-2 px-4 py-3 bg-white dark:bg-zinc-800 hover:bg-zinc-50/80 dark:hover:bg-zinc-700/80">
                              {editingCategoryId === c.id ? (
                                <>
                                  <input
                                    type="text"
                                    value={editingCategoryName}
                                    onChange={(e) => setEditingCategoryName(e.target.value)}
                                    className="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                                    autoFocus
                                  />
                                  <button type="button" onClick={saveEditCategory} disabled={!editingCategoryName.trim()} className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">Kaydet</button>
                                  <button type="button" onClick={cancelEditCategory} className="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600">İptal</button>
                                </>
                              ) : (
                                <>
                                  <span className="flex-1 text-sm font-medium text-zinc-900 dark:text-white">{c.name}</span>
                                  <button type="button" onClick={() => startEditCategory(c)} className="rounded-lg p-2 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-600 hover:text-zinc-700 dark:hover:text-zinc-200" title="Düzenle">
                                    <PencilIcon className="h-4 w-4" />
                                  </button>
                                  <button type="button" onClick={() => handleDeleteCategory(c)} disabled={deletingCategoryId === c.id} className="rounded-lg p-2 text-zinc-500 hover:bg-red-50 hover:text-red-600 disabled:opacity-50" title="Sil">
                                    <TrashIcon className="h-4 w-4" />
                                  </button>
                                </>
                              )}
                            </li>
                          ))}
                        </ul>
                      )}
                    </div>
                    <div className="flex justify-end pt-2 border-t border-zinc-100">
                      <button type="button" onClick={() => { cancelEditCategory(); setCategoryModalOpen(false); }} disabled={addingCategory} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50">Kapat</button>
                    </div>
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
