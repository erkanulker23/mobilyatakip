import { useEffect, useState, Fragment } from 'react';
import { useNavigate } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { WalletIcon, PlusIcon, PlusCircleIcon, ArrowsRightLeftIcon } from '@heroicons/react/24/outline';
import { kasaApi } from '../services/api/kasaApi';
import { kasaHareketApi } from '../services/api/kasaHareketApi';
import { PageHeader, Card, EmptyState, Button, ActionButtons } from '../components/ui';
import toast from 'react-hot-toast';

interface KasaRow {
  id: string;
  name: string;
  type: string;
  accountNumber?: string;
  iban?: string;
  bankName?: string;
  openingBalance?: number;
  currency: string;
  isActive?: boolean;
}

export default function KasaPage() {
  const navigate = useNavigate();
  const [list, setList] = useState<KasaRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: '',
    type: 'kasa',
    accountNumber: '',
    iban: '',
    bankName: '',
    openingBalance: '0',
    currency: 'TRY',
  });
  const [search, setSearch] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [girisModalOpen, setGirisModalOpen] = useState(false);
  const [virmanModalOpen, setVirmanModalOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [girisForm, setGirisForm] = useState({
    kasaId: '',
    amount: '',
    movementDate: new Date().toISOString().slice(0, 10),
    description: '',
  });
  const [virmanForm, setVirmanForm] = useState({
    fromKasaId: '',
    toKasaId: '',
    amount: '',
    movementDate: new Date().toISOString().slice(0, 10),
    description: '',
  });

  const load = () => {
    kasaApi
      .list()
      .then(({ data }) => setList(Array.isArray(data) ? data : []))
      .catch((err: { response?: { status?: number } }) => {
        if (err?.response?.status === 403) {
          toast.error('Bu sayfaya erişim yetkiniz yok.');
          navigate(ROUTES.home, { replace: true });
        } else {
          toast.error('Kasa listesi yüklenemedi');
        }
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    load();
  }, []);

  const filtered = list.filter((k) => {
    if (search.trim() && !k.name.toLowerCase().includes(search.toLowerCase())) return false;
    if (typeFilter && k.type !== typeFilter) return false;
    return true;
  });

  const openCreate = () => {
    setEditingId(null);
    setForm({ name: '', type: 'kasa', accountNumber: '', iban: '', bankName: '', openingBalance: '0', currency: 'TRY' });
    setModalOpen(true);
  };

  const openEdit = (k: KasaRow) => {
    setEditingId(k.id);
    setForm({
      name: k.name,
      type: k.type ?? 'kasa',
      accountNumber: k.accountNumber ?? '',
      iban: k.iban ?? '',
      bankName: k.bankName ?? '',
      openingBalance: String(k.openingBalance ?? 0),
      currency: k.currency ?? 'TRY',
    });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
      name: form.name.trim(),
      type: form.type,
      accountNumber: form.accountNumber.trim() || undefined,
      iban: form.iban.trim() || undefined,
      bankName: form.bankName.trim() || undefined,
      openingBalance: Number.parseFloat(form.openingBalance) || 0,
      currency: form.currency,
    };
    if (editingId) {
      kasaApi.update(editingId, payload).then(() => { toast.success('Kasa güncellendi'); setModalOpen(false); load(); }).catch(() => toast.error('Kasa güncellenemedi'));
    } else {
      kasaApi.create(payload).then(() => { toast.success('Kasa eklendi'); setModalOpen(false); load(); }).catch(() => toast.error('Kasa eklenemedi'));
    }
  };

  const handleDelete = (id: string) => {
    if (!globalThis.confirm('Bu kasayı silmek istediğinize emin misiniz?')) return;
    kasaApi.delete(id).then(() => { toast.success('Kasa silindi'); load(); }).catch(() => toast.error('Kasa silinemedi'));
  };

  const openGirisModal = () => {
    setGirisForm({
      kasaId: list[0]?.id ?? '',
      amount: '',
      movementDate: new Date().toISOString().slice(0, 10),
      description: '',
    });
    setGirisModalOpen(true);
  };

  const openVirmanModal = () => {
    setVirmanForm({
      fromKasaId: list[0]?.id ?? '',
      toKasaId: list[1]?.id ?? list[0]?.id ?? '',
      amount: '',
      movementDate: new Date().toISOString().slice(0, 10),
      description: '',
    });
    setVirmanModalOpen(true);
  };

  const handleGirisSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const amount = Number.parseFloat(girisForm.amount);
    if (!girisForm.kasaId || !Number.isFinite(amount) || amount <= 0) {
      toast.error('Kasa seçiniz ve geçerli bir tutar giriniz.');
      return;
    }
    setSubmitting(true);
    kasaHareketApi
      .giris({
        kasaId: girisForm.kasaId,
        amount,
        movementDate: girisForm.movementDate,
        description: girisForm.description.trim() || undefined,
      })
      .then(() => {
        toast.success('Kasaya para eklendi.');
        setGirisModalOpen(false);
        load();
      })
      .catch(() => toast.error('İşlem kaydedilemedi.'))
      .finally(() => setSubmitting(false));
  };

  const handleVirmanSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const amount = Number.parseFloat(virmanForm.amount);
    if (!Number.isFinite(amount) || amount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    if (!virmanForm.fromKasaId || !virmanForm.toKasaId || virmanForm.fromKasaId === virmanForm.toKasaId) {
      toast.error('Farklı kaynak ve hedef kasa seçiniz.');
      return;
    }
    setSubmitting(true);
    kasaHareketApi
      .virman({
        fromKasaId: virmanForm.fromKasaId,
        toKasaId: virmanForm.toKasaId,
        amount,
        movementDate: virmanForm.movementDate,
        description: virmanForm.description.trim() || undefined,
      })
      .then(() => {
        toast.success('Virman kaydedildi.');
        setVirmanModalOpen(false);
        load();
      })
      .catch(() => toast.error('Virman kaydedilemedi.'))
      .finally(() => setSubmitting(false));
  };

  return (
    <div className="space-y-6">
      {/* Üst aksiyon çubuğu - her zaman görünür */}
      <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-4 py-4 shadow-sm">
        <Button variant="primary" icon={PlusIcon} onClick={openCreate}>
          Yeni Kasa
        </Button>
        <Button variant="primary" icon={PlusCircleIcon} onClick={openGirisModal} disabled={list.length === 0}>
          Kasaya para ekle
        </Button>
        <Button variant="primary" icon={ArrowsRightLeftIcon} onClick={openVirmanModal} disabled={list.length < 2}>
          Hesaplar arası virman
        </Button>
      </div>
      <PageHeader
        title="Kasa / Banka Tanımları"
        description="Kasa ve banka hesapları"
        icon={WalletIcon}
      />
      <div className="flex flex-wrap items-center gap-3">
        <input
          type="text"
          placeholder="Ara..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-48"
        />
        <select
          value={typeFilter}
          onChange={(e) => setTypeFilter(e.target.value)}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tümü</option>
          <option value="kasa">Kasa</option>
          <option value="banka">Banka</option>
        </select>
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
          </div>
        ) : filtered.length === 0 ? (
          <EmptyState
            icon={WalletIcon}
            title="Kasa / banka bulunamadı"
            description="Henüz kasa veya banka tanımı yok. Yeni ekleyebilirsiniz."
            action={<Button icon={PlusIcon} onClick={openCreate}>Yeni Kasa</Button>}
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ad</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tür</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Banka / Hesap</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Açılış Bakiye</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {filtered.map((k) => (
                  <tr key={k.id} className="hover:bg-zinc-50/80 transition-colors">
                    <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                      <Link to={ROUTES.kasaDetay(k.id)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                        {k.name}
                      </Link>
                    </td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{k.type === 'banka' ? 'Banka' : 'Kasa'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{k.bankName || k.accountNumber || k.iban || '—'}</td>
                    <td className="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">{Number(k.openingBalance ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} {k.currency}</td>
                    <td className="px-6 py-4 text-right">
                      <ActionButtons
                        viewHref={ROUTES.kasaDetay(k.id)}
                        onEdit={() => openEdit(k)}
                        onDelete={() => handleDelete(k.id)}
                      />
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
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">{editingId ? 'Kasa Düzenle' : 'Yeni Kasa / Banka'}</Dialog.Title>
                  <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Ad *</label>
                      <input type="text" required value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" placeholder="Örn: Ana Kasa, X Bankası" />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tür</label>
                      <select value={form.type} onChange={(e) => setForm((f) => ({ ...f, type: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                        <option value="kasa">Kasa</option>
                        <option value="banka">Banka</option>
                      </select>
                    </div>
                    {form.type === 'banka' && (
                      <>
                        <div>
                          <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Banka adı</label>
                          <input type="text" value={form.bankName} onChange={(e) => setForm((f) => ({ ...f, bankName: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Hesap no</label>
                          <input type="text" value={form.accountNumber} onChange={(e) => setForm((f) => ({ ...f, accountNumber: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                        </div>
                        <div>
                          <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">IBAN</label>
                          <input type="text" value={form.iban} onChange={(e) => setForm((f) => ({ ...f, iban: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                        </div>
                      </>
                    )}
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Açılış bakiye</label>
                      <input type="number" step="0.01" value={form.openingBalance} onChange={(e) => setForm((f) => ({ ...f, openingBalance: e.target.value }))} className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button type="button" onClick={() => setModalOpen(false)} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600">İptal</button>
                      <button type="submit" className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">{editingId ? 'Güncelle' : 'Ekle'}</button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Kasaya para ekle modal */}
      <Transition appear show={girisModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !submitting && setGirisModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-black/30" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">Kasaya para ekle</Dialog.Title>
                  <form onSubmit={handleGirisSubmit} className="mt-4 space-y-4">
                    <div>
                      <label htmlFor="giris-kasa" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Kasa *</label>
                      <select
                        id="giris-kasa"
                        required
                        value={girisForm.kasaId}
                        onChange={(e) => setGirisForm((f) => ({ ...f, kasaId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Seçiniz</option>
                        {list.map((k) => (
                          <option key={k.id} value={k.id}>{k.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label htmlFor="giris-amount" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tutar *</label>
                      <input
                        id="giris-amount"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        value={girisForm.amount}
                        onChange={(e) => setGirisForm((f) => ({ ...f, amount: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label htmlFor="giris-date" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tarih *</label>
                      <input
                        id="giris-date"
                        type="date"
                        required
                        value={girisForm.movementDate}
                        onChange={(e) => setGirisForm((f) => ({ ...f, movementDate: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label htmlFor="giris-desc" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Açıklama (paranın nereden geldiği)</label>
                      <input
                        id="giris-desc"
                        type="text"
                        placeholder="Örn: Satış tahsilatı..."
                        value={girisForm.description}
                        onChange={(e) => setGirisForm((f) => ({ ...f, description: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                      <button type="button" onClick={() => setGirisModalOpen(false)} disabled={submitting} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50">İptal</button>
                      <button type="submit" disabled={submitting} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">{submitting ? 'Kaydediliyor...' : 'Kaydet'}</button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Hesaplar arası virman modal */}
      <Transition appear show={virmanModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !submitting && setVirmanModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-black/30" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">Hesaplar arası virman</Dialog.Title>
                  <form onSubmit={handleVirmanSubmit} className="mt-4 space-y-4">
                    <div>
                      <label htmlFor="virman-from" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Kaynak kasa *</label>
                      <select
                        id="virman-from"
                        required
                        value={virmanForm.fromKasaId}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, fromKasaId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Seçiniz</option>
                        {list.map((k) => (
                          <option key={k.id} value={k.id}>{k.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label htmlFor="virman-to" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Hedef kasa *</label>
                      <select
                        id="virman-to"
                        required
                        value={virmanForm.toKasaId}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, toKasaId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Seçiniz</option>
                        {list.map((k) => (
                          <option key={k.id} value={k.id}>{k.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label htmlFor="virman-amount" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tutar *</label>
                      <input
                        id="virman-amount"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        value={virmanForm.amount}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, amount: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label htmlFor="virman-date" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tarih *</label>
                      <input
                        id="virman-date"
                        type="date"
                        required
                        value={virmanForm.movementDate}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, movementDate: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label htmlFor="virman-desc" className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Açıklama (isteğe bağlı)</label>
                      <input
                        id="virman-desc"
                        type="text"
                        value={virmanForm.description}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, description: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                      <button type="button" onClick={() => setVirmanModalOpen(false)} disabled={submitting} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50">İptal</button>
                      <button type="submit" disabled={submitting} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">{submitting ? 'Kaydediliyor...' : 'Virman yap'}</button>
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
