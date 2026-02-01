import { useEffect, useState, Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { UserPlusIcon, PlusIcon, TagIcon } from '@heroicons/react/24/outline';
import { personnelApi } from '../services/api/personnelApi';
import { personnelCategoriesApi } from '../services/api/personnelCategoriesApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

interface PersonnelCategoryOption {
  id: string;
  name: string;
}

interface PersonnelRow {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  category?: string;
  title?: string;
  vehiclePlate?: string;
  driverInfo?: string;
  isActive?: boolean;
}

export default function PersonnelPage() {
  const [list, setList] = useState<PersonnelRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState({ name: '', email: '', phone: '', category: '', title: '', vehiclePlate: '', driverInfo: '' });
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState<string>('');
  const [categories, setCategories] = useState<PersonnelCategoryOption[]>([]);
  const [newCategoryName, setNewCategoryName] = useState('');
  const [addingCategory, setAddingCategory] = useState(false);
  const [categoryModalOpen, setCategoryModalOpen] = useState(false);
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);

  const load = () => {
    const params: { active?: boolean; page?: number; limit?: number } = { page, limit };
    if (activeFilter === 'true' || activeFilter === 'false') params.active = activeFilter === 'true';
    personnelApi
      .list(params)
      .then(({ data }) => {
        const res = data as { data?: PersonnelRow[]; total?: number; totalPages?: number };
        setList(Array.isArray(res?.data) ? res.data : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Personel yüklenemedi'))
      .finally(() => setLoading(false));
  };

  const loadCategories = () => {
    personnelCategoriesApi.list().then(({ data }) => setCategories(Array.isArray(data) ? data : [])).catch(() => setCategories([]));
  };

  useEffect(() => {
    setLoading(true);
    const t = window.setTimeout(() => setLoading(false), 12000);
    load();
    return () => window.clearTimeout(t);
  }, [activeFilter, page, limit]);

  useEffect(() => {
    loadCategories();
  }, []);

  const filtered = search.trim()
    ? list.filter((p) => p.name.toLowerCase().includes(search.toLowerCase()) || (p.email ?? '').toLowerCase().includes(search.toLowerCase()))
    : list;

  const openCreate = () => {
    setEditingId(null);
    setForm({ name: '', email: '', phone: '', category: '', title: '', vehiclePlate: '', driverInfo: '' });
    setModalOpen(true);
  };

  const openEdit = (p: PersonnelRow) => {
    setEditingId(p.id);
    setForm({ name: p.name, email: p.email ?? '', phone: p.phone ?? '', category: p.category ?? '', title: p.title ?? '', vehiclePlate: p.vehiclePlate ?? '', driverInfo: p.driverInfo ?? '' });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = { name: form.name.trim(), email: form.email.trim() || undefined, phone: form.phone.trim() || undefined, category: form.category.trim() || undefined, title: form.title.trim() || undefined, vehiclePlate: form.vehiclePlate.trim() || undefined, driverInfo: form.driverInfo.trim() || undefined };
    if (editingId) {
      personnelApi.update(editingId, payload).then(() => { toast.success('Personel güncellendi'); setModalOpen(false); load(); }).catch(() => toast.error('Personel güncellenemedi'));
    } else {
      personnelApi.create(payload).then(() => { toast.success('Personel eklendi'); setModalOpen(false); load(); }).catch(() => toast.error('Personel eklenemedi'));
    }
  };

  const handleDelete = (id: string) => {
    if (!globalThis.confirm('Bu personeli silmek istediğinize emin misiniz?')) return;
    personnelApi.delete(id).then(() => { toast.success('Personel silindi'); load(); }).catch(() => toast.error('Personel silinemedi'));
  };

  const openCategoryModal = () => {
    setNewCategoryName('');
    setCategoryModalOpen(true);
  };

  const handleAddCategory = () => {
    const name = newCategoryName.trim();
    if (!name) return;
    setAddingCategory(true);
    personnelCategoriesApi
      .create({ name })
      .then(({ data }) => {
        const created = (data as { id: string; name: string }) ?? {};
        setCategories((prev) => [...prev, { id: created.id ?? name, name: created.name ?? name }]);
        setForm((f) => ({ ...f, category: created.name ?? name }));
        setNewCategoryName('');
        setCategoryModalOpen(false);
        toast.success('Personel kategorisi eklendi');
      })
      .catch(() => toast.error('Kategori eklenemedi'))
      .finally(() => setAddingCategory(false));
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Personel"
        description="Personel listesi ve kategoriler"
        icon={UserPlusIcon}
        action={
          <div className="flex flex-wrap items-center gap-2">
            <Button variant="secondary" icon={TagIcon} onClick={openCategoryModal}>
              Personel kategorisi ekle
            </Button>
            <Button variant="primary" icon={PlusIcon} onClick={openCreate}>
              Yeni Personel
            </Button>
          </div>
        }
      />
      <div className="flex flex-wrap items-center gap-3">
        <Button variant="secondary" icon={TagIcon} onClick={openCategoryModal} className="shrink-0">
          Personel kategorisi ekle
        </Button>
        <input
          type="text"
          placeholder="Ara (ad, e-posta)..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-56"
        />
        <select
          value={activeFilter}
          onChange={(e) => { setActiveFilter(e.target.value); setPage(1); }}
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
            <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
          </div>
        ) : filtered.length === 0 ? (
          <EmptyState
            icon={UserPlusIcon}
            title="Personel bulunamadı"
            description="Henüz personel kaydı yok veya filtreye uygun kayıt yok."
            action={<Button icon={PlusIcon} onClick={openCreate}>Yeni Personel</Button>}
            className="rounded-b-2xl m-0 border-0"
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-zinc-200">
                <thead className="bg-zinc-50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ad</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Kategori</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Personel görevi</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">E-posta</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Telefon</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Araç plakası</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlem</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-200 bg-white">
                  {filtered.map((p) => (
                    <tr key={p.id} className="hover:bg-zinc-50/80 transition-colors">
                      <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{p.name}</td>
                      <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{p.category ?? '—'}</td>
                      <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{p.title ?? '—'}</td>
                      <td className="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-300">{p.email ?? '—'}</td>
                      <td className="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-300">{p.phone ?? '—'}</td>
                      <td className="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-300">{p.vehiclePlate ?? '—'}</td>
                      <td className="px-6 py-4 text-right">
                        <ActionButtons onEdit={() => openEdit(p)} onDelete={() => handleDelete(p.id)} />
                      </td>
                    </tr>
                  ))}
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

      {/* Yeni Personel / Düzenle modal */}
      <Transition appear show={modalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">{editingId ? 'Personel Düzenle' : 'Yeni Personel'}</Dialog.Title>
                  <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                    <div>
                      <label htmlFor="personnel-name" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Ad *</label>
                      <input id="personnel-name" type="text" required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label htmlFor="personnel-email" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">E-posta</label>
                      <input id="personnel-email" type="email" value={form.email} onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label htmlFor="personnel-phone" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Telefon</label>
                      <input id="personnel-phone" type="text" value={form.phone} onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label htmlFor="personnel-category" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Kategori</label>
                      <select id="personnel-category" value={form.category} onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        <option value="">Seçiniz</option>
                        {categories.map((c) => (
                          <option key={c.id} value={c.name}>{c.name}</option>
                        ))}
                      </select>
                      <button type="button" onClick={openCategoryModal} className="mt-1.5 text-sm text-emerald-600 hover:text-emerald-700">+ Personel kategorisi ekle</button>
                    </div>
                    <div>
                      <label htmlFor="personnel-title" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Personel görevi</label>
                      <input id="personnel-title" type="text" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} placeholder="Örn: Satış Temsilcisi, Şoför" className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label htmlFor="personnel-vehicle" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Araç plakası</label>
                      <input id="personnel-vehicle" type="text" value={form.vehiclePlate} onChange={(e) => setForm((f) => ({ ...f, vehiclePlate: e.target.value }))} placeholder="34 ABC 123" className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div>
                      <label htmlFor="personnel-driver" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Şoför bilgisi</label>
                      <textarea id="personnel-driver" rows={2} value={form.driverInfo} onChange={(e) => setForm((f) => ({ ...f, driverInfo: e.target.value }))} placeholder="SSH için şoför notu" className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button type="button" onClick={() => setModalOpen(false)} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">İptal</button>
                      <button type="submit" className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">{editingId ? 'Güncelle' : 'Ekle'}</button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Personel kategorisi ekle modal */}
      <Transition appear show={categoryModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !addingCategory && setCategoryModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-black/30" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-sm rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-2xl">
                  <Dialog.Title className="text-lg font-semibold text-zinc-900 pb-3 border-b border-zinc-100">Personel kategorisi ekle</Dialog.Title>
                  <div className="mt-4 space-y-3">
                    <label htmlFor="new-personnel-cat" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Kategori adı</label>
                    <input
                      id="new-personnel-cat"
                      type="text"
                      value={newCategoryName}
                      onChange={(e) => setNewCategoryName(e.target.value)}
                      placeholder="Örn: Satış, Depo"
                      className="block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    />
                    <div className="flex gap-2 justify-end pt-2">
                      <button type="button" onClick={() => setCategoryModalOpen(false)} disabled={addingCategory} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50">İptal</button>
                      <button type="button" onClick={handleAddCategory} disabled={addingCategory || !newCategoryName.trim()} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">{addingCategory ? 'Ekleniyor...' : 'Ekle'}</button>
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
