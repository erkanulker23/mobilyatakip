import { useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  UserGroupIcon,
  BuildingOffice2Icon,
  DocumentCheckIcon,
  BanknotesIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { PageHeader } from '../components/ui';
import { kasaApi } from '../services/api/kasaApi';
import toast from 'react-hot-toast';

const sections = [
  { name: 'Kasa / Banka Tanımları', description: 'Kasa ve banka hesapları tanımlama', href: ROUTES.kasa, icon: BanknotesIcon },
  { name: 'Masraflar', description: 'Masraf girişi, ödeme yapılan kasa seçimi', href: ROUTES.masrafCikisi, icon: CurrencyDollarIcon },
  { name: 'Tedarikçi Cari', description: 'Tedarikçi alış/ödeme mahsuplaşma ve bakiye', href: ROUTES.tedarikciCari, icon: BuildingOffice2Icon },
  { name: 'Müşteri Cari', description: 'Müşteri borç/alacak takibi, borçlu müşteri bildirimi', href: ROUTES.musteriCari, icon: UserGroupIcon },
  { name: 'Mutabakat', description: 'Tedarikçi mutabakatları ve PDF çıktı', href: ROUTES.mutabakatlar, icon: DocumentCheckIcon },
  { name: 'Raporlar', description: 'Satış, alış, masraf özeti ve dönem raporları', href: ROUTES.raporlar, icon: ChartBarIcon },
];

export default function AccountingPage() {
  const navigate = useNavigate();

  useEffect(() => {
    kasaApi
      .list()
      .then(() => {})
      .catch((err: { response?: { status?: number } }) => {
        if (err?.response?.status === 403) {
          toast.error('Bu sayfaya erişim yetkiniz yok.');
          navigate(ROUTES.home, { replace: true });
        }
      });
  }, [navigate]);

  return (
    <div className="space-y-8">
      <PageHeader title="Muhasebe" description="Kasa, cari ve mutabakat" icon={BanknotesIcon} />
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {(sections ?? []).map((item) => (
          <Link
            key={item?.name ?? item?.href ?? ''}
            to={item?.href ?? ROUTES.muhasebe}
            className="group flex items-start gap-4 rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-lg shadow-zinc-200/50 transition hover:border-emerald-200 hover:shadow-xl hover:shadow-emerald-100/30"
          >
            <span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 transition group-hover:bg-emerald-500/20">
              <item.icon className="h-6 w-6" />
            </span>
            <div className="min-w-0 flex-1">
              <h2 className="text-lg font-semibold text-zinc-900 group-hover:text-emerald-700">{item?.name ?? ''}</h2>
              <p className="mt-0.5 text-sm text-zinc-500">{item?.description ?? ''}</p>
            </div>
            <ArrowRightIcon className="h-5 w-5 shrink-0 text-zinc-300 transition group-hover:text-emerald-500 group-hover:translate-x-0.5" />
          </Link>
        ))}
      </div>
    </div>
  );
}
