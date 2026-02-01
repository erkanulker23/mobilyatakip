import { useEffect, useState, Fragment } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import {
  WalletIcon,
  ArrowLeftIcon,
  BanknotesIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  CalendarDaysIcon,
  PlusCircleIcon,
  ArrowsRightLeftIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { kasaApi } from '../services/api/kasaApi';
import { kasaHareketApi } from '../services/api/kasaHareketApi';
import { expensesApi } from '../services/api/expensesApi';
import { PageHeader, Card, CardTitle, StatCard, EmptyState, Button } from '../components/ui';
import toast from 'react-hot-toast';

interface KasaDetail {
  id: string;
  name: string;
  type: string;
  accountNumber?: string;
  iban?: string;
  bankName?: string;
  openingBalance: number;
  currency: string;
  isActive?: boolean;
}

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

interface KasaHareketRow {
  id: string;
  type: 'giris' | 'cikis' | 'virman';
  amount: number;
  movementDate: string;
  description?: string;
  kasaId?: string;
  fromKasaId?: string;
  toKasaId?: string;
  kasa?: { id: string; name: string };
  fromKasa?: { id: string; name: string };
  toKasa?: { id: string; name: string };
  createdByUser?: { id: string; name: string };
}

interface KasaOption {
  id: string;
  name: string;
}

type RowType = 'masraf' | 'giris' | 'cikis' | 'virman_in' | 'virman_out';

interface UnifiedRow {
  id: string;
  date: string;
  type: RowType;
  description: string;
  amount: number;
  isInflow: boolean;
  extra?: string;
  performedBy?: string;
}

export default function KasaDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [kasa, setKasa] = useState<KasaDetail | null>(null);
  const [expenses, setExpenses] = useState<ExpenseRow[]>([]);
  const [hareketler, setHareketler] = useState<KasaHareketRow[]>([]);
  const [kasaList, setKasaList] = useState<KasaOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [girisModalOpen, setGirisModalOpen] = useState(false);
  const [virmanModalOpen, setVirmanModalOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [girisForm, setGirisForm] = useState({
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
    if (!id) return;
    setLoading(true);
    Promise.all([
      kasaApi.get(id),
      expensesApi.list({ kasaId: id }),
      kasaHareketApi.list({ kasaId: id }),
    ])
      .then(([kRes, eRes, hRes]) => {
        setKasa((kRes as { data: KasaDetail }).data ?? kRes);
        setExpenses(Array.isArray((eRes as { data: ExpenseRow[] }).data) ? (eRes as { data: ExpenseRow[] }).data : []);
        setHareketler(Array.isArray((hRes as { data: KasaHareketRow[] }).data) ? (hRes as { data: KasaHareketRow[] }).data : []);
      })
      .catch(() => toast.error('Kasa bilgileri yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [id]);

  useEffect(() => {
    kasaApi.list().then(({ data }) => setKasaList(Array.isArray(data) ? data : []));
  }, []);

  const totalExpenses = expenses.reduce((sum, e) => sum + Number(e.amount), 0);
  const totalGiris = hareketler
    .filter((h) => h.type === 'giris' && h.kasaId === id)
    .reduce((sum, h) => sum + Number(h.amount), 0);
  const totalCikis = hareketler
    .filter((h) => h.type === 'cikis' && h.kasaId === id)
    .reduce((sum, h) => sum + Number(h.amount), 0);
  const virmanIn = hareketler
    .filter((h) => h.type === 'virman' && h.toKasaId === id)
    .reduce((sum, h) => sum + Number(h.amount), 0);
  const virmanOut = hareketler
    .filter((h) => h.type === 'virman' && h.fromKasaId === id)
    .reduce((sum, h) => sum + Number(h.amount), 0);
  const currentBalance =
    Number(kasa?.openingBalance ?? 0) + totalGiris + virmanIn - totalExpenses - virmanOut - totalCikis;

  const unifiedRows: UnifiedRow[] = [
    ...expenses.map((e) => ({
      id: `exp-${e.id}`,
      date: e.expenseDate,
      type: 'masraf' as RowType,
      description: e.description,
      amount: Number(e.amount),
      isInflow: false,
      extra: e.category ?? undefined,
      performedBy: e.createdByUser?.name,
    })),
    ...hareketler
      .filter((h) => h.type === 'giris' && h.kasaId === id)
      .map((h) => ({
        id: `h-${h.id}`,
        date: h.movementDate,
        type: 'giris' as RowType,
        description: h.description || 'Kasaya para girişi',
        amount: Number(h.amount),
        isInflow: true,
        performedBy: (h as KasaHareketRow).createdByUser?.name,
      })),
    ...hareketler
      .filter((h) => h.type === 'virman' && h.toKasaId === id)
      .map((h) => ({
        id: `h-in-${h.id}`,
        date: h.movementDate,
        type: 'virman_in' as RowType,
        description: h.description || 'Virman girişi',
        amount: Number(h.amount),
        isInflow: true,
        extra: (h.fromKasa as { name?: string })?.name,
        performedBy: (h as KasaHareketRow).createdByUser?.name,
      })),
    ...hareketler
      .filter((h) => h.type === 'virman' && h.fromKasaId === id)
      .map((h) => ({
        id: `h-out-${h.id}`,
        date: h.movementDate,
        type: 'virman_out' as RowType,
        description: h.description || 'Virman çıkışı',
        amount: Number(h.amount),
        isInflow: false,
        extra: (h.toKasa as { name?: string })?.name,
        performedBy: (h as KasaHareketRow).createdByUser?.name,
      })),
    ...hareketler
      .filter((h) => h.type === 'cikis' && h.kasaId === id)
      .map((h) => ({
        id: `h-cikis-${h.id}`,
        date: h.movementDate,
        type: 'cikis' as RowType,
        description: h.description || 'Kasa çıkışı',
        amount: Number(h.amount),
        isInflow: false,
        performedBy: (h as KasaHareketRow).createdByUser?.name,
      })),
  ].sort((a, b) => (b.date > a.date ? 1 : b.date < a.date ? -1 : 0));

  const openGirisModal = () => {
    setGirisForm({
      amount: '',
      movementDate: new Date().toISOString().slice(0, 10),
      description: '',
    });
    setGirisModalOpen(true);
  };

  const openVirmanModal = () => {
    setVirmanForm({
      fromKasaId: id ?? '',
      toKasaId: kasaList.find((k) => k.id !== id)?.id ?? '',
      amount: '',
      movementDate: new Date().toISOString().slice(0, 10),
      description: '',
    });
    setVirmanModalOpen(true);
  };

  const handleGirisSubmit = (ev: React.FormEvent) => {
    ev.preventDefault();
    const amount = Number.parseFloat(girisForm.amount);
    if (!id || !Number.isFinite(amount) || amount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    setSubmitting(true);
    kasaHareketApi
      .giris({
        kasaId: id,
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

  const handleVirmanSubmit = (ev: React.FormEvent) => {
    ev.preventDefault();
    const amount = Number.parseFloat(virmanForm.amount);
    if (!Number.isFinite(amount) || amount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    if (!virmanForm.fromKasaId || !virmanForm.toKasaId) {
      toast.error('Kaynak ve hedef kasa seçiniz.');
      return;
    }
    if (virmanForm.fromKasaId === virmanForm.toKasaId) {
      toast.error('Kaynak ve hedef kasa farklı olmalıdır.');
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

  const typeLabel = (t: RowType) => {
    switch (t) {
      case 'masraf':
        return 'Masraf';
      case 'giris':
        return 'Para girişi';
      case 'cikis':
        return 'Kasa çıkışı';
      case 'virman_in':
        return 'Virman (giriş)';
      case 'virman_out':
        return 'Virman (çıkış)';
      default:
        return t;
    }
  };

  if (loading || !kasa) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <PageHeader
          title={kasa.name}
          description={kasa.type === 'banka' ? 'Banka hesabı' : 'Kasa'}
          icon={WalletIcon}
          action={
            <div className="flex flex-wrap items-center gap-2">
              <Button variant="primary" icon={PlusCircleIcon} onClick={openGirisModal}>
                Kasaya para ekle
              </Button>
              <Button variant="secondary" icon={ArrowsRightLeftIcon} onClick={openVirmanModal}>
                Hesaplar arası virman
              </Button>
              <Link to={ROUTES.kasa}>
                <Button variant="secondary" icon={ArrowLeftIcon}>
                  Kasalar
                </Button>
              </Link>
            </div>
          }
        />
      </div>

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          title="Açılış bakiye"
          value={`${Number(kasa.openingBalance ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ${kasa.currency}`}
          icon={BanknotesIcon}
        />
        <StatCard
          title="Toplam giriş"
          value={`${(totalGiris + virmanIn).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ${kasa.currency}`}
          icon={PlusCircleIcon}
        />
        <StatCard
          title="Toplam çıkış (masraf + virman + çıkış)"
          value={`${(totalExpenses + virmanOut + totalCikis).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ${kasa.currency}`}
          icon={CurrencyDollarIcon}
        />
        <StatCard
          title="Güncel bakiye"
          value={`${currentBalance.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ${kasa.currency}`}
          icon={WalletIcon}
          description="Açılış + girişler − çıkışlar"
        />
      </div>

      <Card className="flex flex-col justify-center">
        <p className="text-sm font-medium text-zinc-500 dark:text-zinc-300">Hesap bilgisi</p>
        <p className="mt-1 text-sm font-medium text-zinc-900 dark:text-white">{kasa.bankName || kasa.accountNumber || kasa.iban || '—'}</p>
        {kasa.type === 'banka' && (kasa.accountNumber || kasa.iban) && (
          <p className="mt-0.5 text-xs text-zinc-500 dark:text-zinc-300">{kasa.accountNumber || kasa.iban}</p>
        )}
      </Card>

      <Card>
        <CardTitle icon={DocumentTextIcon}>Hesap detayı</CardTitle>
        <dl className="grid gap-3 text-sm sm:grid-cols-2">
          <div>
            <dt className="text-zinc-500 dark:text-zinc-300">Tür</dt>
            <dd className="font-medium text-zinc-900">{kasa.type === 'banka' ? 'Banka' : 'Kasa'}</dd>
          </div>
          {kasa.bankName && (
            <div>
              <dt className="text-zinc-500 dark:text-zinc-300">Banka</dt>
              <dd className="font-medium text-zinc-900">{kasa.bankName}</dd>
            </div>
          )}
          {kasa.accountNumber && (
            <div>
              <dt className="text-zinc-500 dark:text-zinc-300">Hesap no</dt>
              <dd className="font-medium text-zinc-900">{kasa.accountNumber}</dd>
            </div>
          )}
          {kasa.iban && (
            <div>
              <dt className="text-zinc-500 dark:text-zinc-300">IBAN</dt>
              <dd className="font-medium text-zinc-900 break-all">{kasa.iban}</dd>
            </div>
          )}
          <div>
            <dt className="text-zinc-500 dark:text-zinc-300">Para birimi</dt>
            <dd className="font-medium text-zinc-900">{kasa.currency}</dd>
          </div>
        </dl>
      </Card>

      <Card padding="none">
        <div className="border-b border-zinc-200 bg-zinc-50/80 px-6 py-4">
          <h2 className="flex items-center gap-2 text-lg font-semibold text-zinc-900 dark:text-white">
            <CalendarDaysIcon className="h-5 w-5 text-emerald-600" />
            Kasa hareketleri — {unifiedRows.length} kayıt
          </h2>
        </div>
        {unifiedRows.length === 0 ? (
          <EmptyState
            icon={CurrencyDollarIcon}
            title="Bu kasada henüz hareket yok"
            description="Kasaya para ekleyebilir, hesaplar arası virman yapabilir veya masraflar sayfasından çıkış ekleyebilirsiniz."
            className="rounded-b-2xl m-0 border-0"
            action={
              <div className="flex flex-wrap gap-2 justify-center">
                <Button variant="primary" icon={PlusCircleIcon} onClick={openGirisModal}>
                  Kasaya para ekle
                </Button>
                <Button variant="secondary" icon={ArrowsRightLeftIcon} onClick={openVirmanModal}>
                  Virman
                </Button>
                <Link to={ROUTES.masrafCikisi}>
                  <Button variant="secondary" icon={CurrencyDollarIcon}>
                    Masraflar
                  </Button>
                </Link>
              </div>
            }
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tarih</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tür</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Açıklama</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Detay</th>
                  <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlemi yapan</th>
                  <th className="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">Tutar</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {unifiedRows.map((row) => (
                  <tr key={row.id} className="hover:bg-zinc-50/80 transition-colors">
                    <td className="px-6 py-4 text-sm text-zinc-900 dark:text-white">
                      {row.date ? new Date(row.date).toLocaleDateString('tr-TR') : '—'}
                    </td>
                    <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{typeLabel(row.type)}</td>
                    <td className="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-200">{row.description}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{row.extra ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{row.performedBy ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-right font-medium">
                      {row.isInflow ? (
                        <span className="text-emerald-600">
                          + {row.amount.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} {kasa.currency}
                        </span>
                      ) : (
                        <span className="text-red-600">
                          − {row.amount.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} {kasa.currency}
                        </span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
        <div className="border-t border-zinc-200 bg-zinc-50/50 dark:bg-zinc-800/50 px-6 py-3 flex flex-wrap items-center gap-4 text-sm font-medium text-zinc-700 dark:text-zinc-200">
          <Link to={ROUTES.masrafCikisi} className="text-emerald-600 hover:text-emerald-700 hover:underline">
            Masraflar sayfasına git →
          </Link>
        </div>
      </Card>

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
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">
                    Kasaya para ekle
                  </Dialog.Title>
                  <form onSubmit={handleGirisSubmit} className="mt-4 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tutar *</label>
                      <input
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
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tarih *</label>
                      <input
                        type="date"
                        required
                        value={girisForm.movementDate}
                        onChange={(e) => setGirisForm((f) => ({ ...f, movementDate: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Açıklama (paranın nereden geldiği)</label>
                      <input
                        type="text"
                        placeholder="Örn: Satış tahsilatı, müşteri ödemesi..."
                        value={girisForm.description}
                        onChange={(e) => setGirisForm((f) => ({ ...f, description: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                      <button
                        type="button"
                        onClick={() => setGirisModalOpen(false)}
                        disabled={submitting}
                        className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50"
                      >
                        İptal
                      </button>
                      <button
                        type="submit"
                        disabled={submitting}
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                      >
                        {submitting ? 'Kaydediliyor...' : 'Kaydet'}
                      </button>
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
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">
                    Hesaplar arası virman
                  </Dialog.Title>
                  <form onSubmit={handleVirmanSubmit} className="mt-4 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Kaynak kasa *</label>
                      <select
                        required
                        value={virmanForm.fromKasaId}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, fromKasaId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Seçiniz</option>
                        {kasaList.map((k) => (
                          <option key={k.id} value={k.id}>
                            {k.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Hedef kasa *</label>
                      <select
                        required
                        value={virmanForm.toKasaId}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, toKasaId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Seçiniz</option>
                        {kasaList.map((k) => (
                          <option key={k.id} value={k.id}>
                            {k.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tutar *</label>
                      <input
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
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Tarih *</label>
                      <input
                        type="date"
                        required
                        value={virmanForm.movementDate}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, movementDate: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Açıklama (isteğe bağlı)</label>
                      <input
                        type="text"
                        value={virmanForm.description}
                        onChange={(e) => setVirmanForm((f) => ({ ...f, description: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                      <button
                        type="button"
                        onClick={() => setVirmanModalOpen(false)}
                        disabled={submitting}
                        className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50"
                      >
                        İptal
                      </button>
                      <button
                        type="submit"
                        disabled={submitting}
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                      >
                        {submitting ? 'Kaydediliyor...' : 'Virman yap'}
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
