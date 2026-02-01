import { useEffect, useState } from 'react';
import { Outlet, Link, useNavigate, useLocation } from 'react-router-dom';
import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import {
  HomeIcon,
  CubeIcon,
  UserGroupIcon,
  DocumentTextIcon,
  ShoppingCartIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon,
  BanknotesIcon,
  BuildingOffice2Icon,
  TruckIcon,
  WrenchScrewdriverIcon,
  BellIcon,
  UserPlusIcon,
  WalletIcon,
  ChartBarIcon,
  ShoppingBagIcon,
  UsersIcon,
  CreditCardIcon,
  ClipboardDocumentListIcon,
  ArrowTrendingUpIcon,
  ArrowDownCircleIcon,
} from '@heroicons/react/24/outline';
import { useAuth } from '../contexts/AuthContext';
import { notificationsApi } from '../services/api/notificationsApi';
import { customerPaymentsApi } from '../services/api/customerPaymentsApi';
import { companyApi } from '../services/api/companyApi';
import { ROUTES } from '../config/routes';
import ThemeToggle from '../components/ThemeToggle';

const navItems = [
  { name: 'Dashboard', href: ROUTES.home, icon: HomeIcon, exact: true },
  { name: 'Ürünler', href: ROUTES.urunler, icon: CubeIcon, exact: false },
  { name: 'Tedarikçiler', href: ROUTES.tedarikciler, icon: TruckIcon, exact: false },
  { name: 'Personel', href: ROUTES.personel, icon: UserPlusIcon, exact: false },
  { name: 'Müşteriler', href: ROUTES.musteriler, icon: UserGroupIcon, exact: false },
  { name: 'Sipariş Yönetimi', href: ROUTES.siparisYonetimi, icon: ClipboardDocumentListIcon, exact: false },
  { name: 'Teklifler', href: ROUTES.teklifler, icon: DocumentTextIcon, exact: false },
  { name: 'Satışlar', href: ROUTES.satislar, icon: ShoppingCartIcon, exact: false },
  { name: 'Ödeme Al', href: ROUTES.odemeAl, icon: CreditCardIcon, exact: false },
  { name: 'Alışlar', href: ROUTES.alislar, icon: ShoppingBagIcon, exact: false },
  { name: 'Servis (SSH)', href: ROUTES.servisTalepleri, icon: WrenchScrewdriverIcon, exact: false },
  { name: 'Gelir - Gider Takibi', href: ROUTES.gelirGider, icon: ArrowTrendingUpIcon, exact: false },
  { name: 'Raporlar', href: ROUTES.raporlar, icon: ChartBarIcon, exact: false },
  { name: 'Kasa', href: ROUTES.kasa, icon: WalletIcon, exact: false },
  { name: 'Masraf Çıkışı', href: ROUTES.masrafCikisi, icon: ArrowDownCircleIcon, exact: false },
  { name: 'Muhasebe', href: ROUTES.muhasebe, icon: BanknotesIcon, exact: false },
  { name: 'Kullanıcılar', href: ROUTES.kullanicilar, icon: UsersIcon, exact: false, adminOnly: true },
  { name: 'Ayarlar', href: ROUTES.ayarlar, icon: BuildingOffice2Icon, exact: false },
];

export default function DashboardLayout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [notificationCount, setNotificationCount] = useState(0);
  const [debtCount, setDebtCount] = useState(0);
  const [appName, setAppName] = useState('Mobilya Takip');

  useEffect(() => {
    companyApi.get().then(({ data }) => {
      const name = (data as { name?: string })?.name;
      if (name?.trim()) {
        setAppName(name.trim());
        document.title = name.trim();
      }
    }).catch(() => {});
  }, []);

  useEffect(() => {
    if (!user?.id) return;
    notificationsApi.byUser(user.id, true).then(({ data }) => {
      setNotificationCount(Array.isArray(data) ? data.length : 0);
    }).catch(() => setNotificationCount(0));
    customerPaymentsApi.withDebt().then(({ data }) => {
      setDebtCount(Array.isArray(data) ? data.length : 0);
    }).catch(() => setDebtCount(0));
  }, [user?.id]);

  const handleLogout = () => {
    logout();
    navigate(ROUTES.login);
  };

  const totalBadge = notificationCount + debtCount;

  const isActive = (item: (typeof navItems)[0]) => {
    if (item.exact) return location.pathname === item.href;
    return location.pathname === item.href || location.pathname.startsWith(item.href + '/');
  };

  return (
    <div className="min-h-screen bg-zinc-100 dark:bg-zinc-950">
      <aside className="fixed inset-y-0 left-0 w-64 flex flex-col bg-zinc-900 dark:bg-zinc-950 text-zinc-100 border-r border-zinc-800 dark:border-zinc-800">
        <div className="p-6 border-b border-zinc-800">
          <h1 className="text-xl font-bold tracking-tight text-white truncate" title={appName}>{appName}</h1>
        </div>
        <nav className="flex-1 p-3 space-y-0.5 overflow-y-auto">
          {navItems.filter((item) => !(item as { adminOnly?: boolean }).adminOnly || user?.role === 'admin').map((item) => {
            const active = isActive(item);
            return (
              <Link
                key={item.name}
                to={item.href}
                className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors ${
                  active
                    ? 'bg-emerald-600 text-white'
                    : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'
                }`}
              >
                <item.icon className="w-5 h-5 shrink-0" />
                {item.name}
              </Link>
            );
          })}
        </nav>
        <div className="p-3 border-t border-zinc-800">
          <Menu as="div" className="relative">
            <Menu.Button className="flex w-full items-center gap-3 px-3 py-2.5 rounded-xl text-left text-zinc-300 hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-zinc-900">
              <div className="w-9 h-9 rounded-full bg-emerald-600 flex items-center justify-center shrink-0">
                <span className="text-sm font-semibold text-white">
                  {user?.name?.charAt(0) || '?'}
                </span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-medium truncate text-white">{user?.name}</p>
                <p className="text-xs text-zinc-500 truncate">{user?.email}</p>
              </div>
              <Cog6ToothIcon className="w-5 h-5 text-zinc-500 shrink-0" />
            </Menu.Button>
            <Transition
              as={Fragment}
              enter="transition ease-out duration-100"
              enterFrom="opacity-0 scale-95"
              enterTo="opacity-100 scale-100"
              leave="transition ease-in duration-75"
              leaveFrom="opacity-100 scale-100"
              leaveTo="opacity-0 scale-95"
            >
              <Menu.Items className="absolute left-0 bottom-full mb-1 w-56 origin-bottom-left rounded-xl bg-white dark:bg-zinc-800 shadow-xl ring-1 ring-zinc-200 dark:ring-zinc-700 focus:outline-none py-1">
                <Menu.Item>
                  {({ active }) => (
                    <button
                      type="button"
                      onClick={handleLogout}
                      className={`${
                        active ? 'bg-zinc-50 dark:bg-zinc-700' : ''
                      } flex w-full items-center gap-2 px-4 py-2.5 text-sm text-zinc-700 dark:text-zinc-200`}
                    >
                      <ArrowRightOnRectangleIcon className="w-5 h-5" />
                      Çıkış
                    </button>
                  )}
                </Menu.Item>
              </Menu.Items>
            </Transition>
          </Menu>
        </div>
      </aside>
      <main className="pl-64 min-h-screen flex flex-col">
        <header className="sticky top-0 z-10 flex items-center justify-end gap-2 h-16 px-6 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-800 shrink-0">
          <ThemeToggle />
          <Link
            to={ROUTES.bildirimler}
            className="relative p-2.5 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
            title="Bildirimler"
          >
            <BellIcon className="w-6 h-6" />
            {totalBadge > 0 && (
              <span className="absolute top-1.5 right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                {totalBadge > 99 ? '99+' : totalBadge}
              </span>
            )}
          </Link>
        </header>
        <div className="flex-1 overflow-auto bg-gradient-to-br from-zinc-50 via-white to-zinc-100/80 dark:from-zinc-900 dark:via-zinc-800 dark:to-zinc-900">
          <div className="mx-auto max-w-7xl px-6 py-8">
            <Outlet />
          </div>
        </div>
      </main>
    </div>
  );
}
