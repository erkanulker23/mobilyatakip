import { useEffect, useState, Fragment } from 'react';
import { useNavigate } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { Dialog, Transition } from '@headlessui/react';
import { UsersIcon, PlusIcon } from '@heroicons/react/24/outline';
import { authApi } from '../services/api/authApi';
import { useAuth } from '../contexts/AuthContext';
import { PageHeader, Card, EmptyState, Button } from '../components/ui';
import toast from 'react-hot-toast';

const ROLE_LABELS: Record<string, string> = {
  admin: 'Yönetici',
  satis: 'Satış',
  depo: 'Depo',
  muhasebe: 'Muhasebe',
  ssh: 'Servis (SSH)',
};

const ROLES = ['admin', 'satis', 'depo', 'muhasebe', 'ssh'];

interface UserRow {
  id: string;
  email: string;
  name: string;
  role: string;
}

export default function UsersPage() {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [list, setList] = useState<UserRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState<string | null>(null);
  const [createModalOpen, setCreateModalOpen] = useState(false);
  const [createSubmitting, setCreateSubmitting] = useState(false);
  const [createForm, setCreateForm] = useState({ name: '', email: '', password: '', role: 'satis' });

  const loadUsers = () => {
    setLoading(true);
    authApi
      .users()
      .then(({ data }) => setList(Array.isArray(data) ? (data as UserRow[]) : []))
      .catch((err: { response?: { status?: number } }) => {
        if (err?.response?.status === 403) {
          toast.error('Bu sayfaya erişim yetkiniz yok.');
          navigate(ROUTES.home, { replace: true });
        } else {
          toast.error('Kullanıcılar yüklenemedi.');
        }
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (!user) return;
    if (user?.role !== 'admin') {
      toast.error('Bu sayfaya erişim yetkiniz yok.');
      navigate(ROUTES.home, { replace: true });
      return;
    }
    loadUsers();
  }, [user, navigate]);

  const openCreateModal = () => {
    setCreateForm({ name: '', email: '', password: '', role: 'satis' });
    setCreateModalOpen(true);
  };

  const handleCreateUser = (e: React.FormEvent) => {
    e.preventDefault();
    if (!createForm.name.trim() || !createForm.email.trim() || !createForm.password.trim()) {
      toast.error('Ad, e-posta ve şifre zorunludur.');
      return;
    }
    if (createForm.password.length < 6) {
      toast.error('Şifre en az 6 karakter olmalıdır.');
      return;
    }
    setCreateSubmitting(true);
    authApi
      .createUser({
        name: createForm.name.trim(),
        email: createForm.email.trim(),
        password: createForm.password,
        role: createForm.role,
      })
      .then(() => {
        toast.success('Kullanıcı eklendi.');
        setCreateModalOpen(false);
        loadUsers();
      })
      .catch((err: { response?: { data?: { message?: string | string[] } } }) => {
        const msg = err?.response?.data?.message;
        toast.error(Array.isArray(msg) ? msg[0] : msg || 'Kullanıcı eklenemedi.');
      })
      .finally(() => setCreateSubmitting(false));
  };

  const handleRoleChange = (userId: string, role: string) => {
    setUpdatingId(userId);
    authApi
      .updateUserRole(userId, role)
      .then(() => {
        setList((prev) => prev.map((u) => (u.id === userId ? { ...u, role } : u)));
        toast.success('Rol güncellendi.');
      })
      .catch(() => toast.error('Rol güncellenemedi.'))
      .finally(() => setUpdatingId(null));
  };

  if (!user) {
    return (
      <div className="flex items-center justify-center py-24">
        <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
      </div>
    );
  }
  if (user?.role !== 'admin') {
    return (
      <div className="flex items-center justify-center py-24">
        <p className="text-zinc-500 dark:text-zinc-300">Bu sayfaya erişim yetkiniz yok. Yönlendiriliyorsunuz...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Kullanıcılar & Roller"
        description="Sisteme giriş yapabilen kullanıcılar ve rollerini yönetin"
        icon={UsersIcon}
        action={
          <Button variant="primary" onClick={openCreateModal} icon={PlusIcon}>
            Yeni kullanıcı
          </Button>
        }
      />
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
          </div>
        ) : list.length === 0 ? (
          <EmptyState
            icon={UsersIcon}
            title="Kullanıcı bulunamadı"
            description="Henüz kullanıcı kaydı yok."
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Ad</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">E-posta</th>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Rol</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 bg-white">
                {(list ?? []).map((u) => (
                  <tr key={u?.id ?? ''} className="hover:bg-zinc-50/80 transition-colors">
                    <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{u?.name ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{u?.email ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{ROLE_LABELS[u?.role ?? ''] ?? u?.role ?? '—'}</td>
                    <td className="px-6 py-4 text-right">
                      <select
                        value={u?.role ?? ''}
                        onChange={(e) => u?.id != null ? handleRoleChange(u.id, e.target.value) : undefined}
                        disabled={updatingId === (u?.id ?? '')}
                        className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 disabled:opacity-50"
                      >
                        {ROLES.map((r) => (
                          <option key={r} value={r}>{ROLE_LABELS[r] ?? r}</option>
                        ))}
                      </select>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <Transition appear show={createModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => !createSubmitting && setCreateModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 pb-4 border-b border-zinc-100">Yeni kullanıcı</Dialog.Title>
                  <form onSubmit={handleCreateUser} className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Ad Soyad *</label>
                      <input
                        type="text"
                        required
                        value={createForm.name}
                        onChange={(e) => setCreateForm((f) => ({ ...f, name: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="Örn. Ahmet Yılmaz"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">E-posta *</label>
                      <input
                        type="email"
                        required
                        value={createForm.email}
                        onChange={(e) => setCreateForm((f) => ({ ...f, email: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="giris@firma.com"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Şifre *</label>
                      <input
                        type="password"
                        required
                        minLength={6}
                        value={createForm.password}
                        onChange={(e) => setCreateForm((f) => ({ ...f, password: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        placeholder="En az 6 karakter"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700 dark:text-zinc-200">Rol</label>
                      <select
                        value={createForm.role}
                        onChange={(e) => setCreateForm((f) => ({ ...f, role: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        {ROLES.map((r) => (
                          <option key={r} value={r}>{ROLE_LABELS[r] ?? r}</option>
                        ))}
                      </select>
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button
                        type="button"
                        onClick={() => setCreateModalOpen(false)}
                        disabled={createSubmitting}
                        className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 disabled:opacity-50"
                      >
                        İptal
                      </button>
                      <button
                        type="submit"
                        disabled={createSubmitting}
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
                      >
                        {createSubmitting ? 'Ekleniyor...' : 'Ekle'}
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
