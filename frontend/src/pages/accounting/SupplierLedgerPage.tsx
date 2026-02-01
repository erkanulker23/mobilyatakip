import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { BuildingOffice2Icon } from '@heroicons/react/24/outline';
import { ROUTES } from '../../config/routes';
import { supplierPaymentsApi } from '../../services/api/supplierPaymentsApi';
import { PageHeader, Card, EmptyState } from '../../components/ui';
import toast from 'react-hot-toast';

interface BalanceRow {
  supplierId: string;
  supplierName: string;
  totalPurchases: number;
  totalPayments: number;
  balance: number;
}

export default function SupplierLedgerPage() {
  const [list, setList] = useState<BalanceRow[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    supplierPaymentsApi
      .balances()
      .then(({ data }) => setList(Array.isArray(data) ? data : []))
      .catch(() => toast.error('Tedarikçi cari yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Tedarikçi Cari (Mahsuplaşma)"
        description="Alışlar ve ödemelere göre tedarikçi bakiyeleri. Alış faturalarını Alışlar sayfasından girebilirsiniz."
        icon={BuildingOffice2Icon}
      />
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
          </div>
        ) : list.length === 0 ? (
          <EmptyState
            icon={BuildingOffice2Icon}
            title="Tedarikçi cari bulunamadı"
            description="Tedarikçi bulunamadı veya henüz alış/ödeme yok."
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tedarikçi</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Toplam Alış (Borç)</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Toplam Ödeme (Alacak)</th>
                  <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Bakiye</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {list.map((row) => {
                  const balance = Number(row.balance);
                  return (
                    <tr key={row.supplierId} className="hover:bg-zinc-50/80 transition-colors">
                      <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                        <Link to={ROUTES.tedarikci(row.supplierId)} className="text-emerald-600 hover:text-emerald-700 hover:underline">
                          {row.supplierName}
                        </Link>
                      </td>
                      <td className="px-6 py-4 text-sm text-right text-zinc-600 dark:text-zinc-300">{Number(row.totalPurchases).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                      <td className="px-6 py-4 text-sm text-right text-zinc-600 dark:text-zinc-300">{Number(row.totalPayments).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</td>
                      <td className="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">
                        {balance.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                        {balance > 0 && <span className="text-amber-600 ml-1">(borç)</span>}
                        {balance < 0 && <span className="text-emerald-600 ml-1">(alacak)</span>}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </Card>
    </div>
  );
}
