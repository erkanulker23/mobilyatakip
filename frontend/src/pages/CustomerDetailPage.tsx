import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  UserCircleIcon,
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon,
  DocumentTextIcon,
  BanknotesIcon,
  ShoppingCartIcon,
  ArrowLeftIcon,
  CurrencyDollarIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { customersApi } from '../services/api/customersApi';
import { Button } from '../components/ui';
import { customerPaymentsApi } from '../services/api/customerPaymentsApi';
import { salesApi } from '../services/api/salesApi';
import { quotesApi } from '../services/api/quotesApi';
import toast from 'react-hot-toast';

interface CustomerDetail {
  id: string;
  name: string;
  email?: string;
  phone?: string;
  address?: string;
  taxNumber?: string;
  taxOffice?: string;
  identityNumber?: string;
  isActive?: boolean;
}

interface BalanceInfo {
  totalSales: number;
  totalPayments: number;
  balance: number;
  overdueAmount?: number;
}

interface SaleRow {
  id: string;
  saleNumber: string;
  saleDate: string;
  grandTotal: number;
  paidAmount?: number;
  dueDate?: string;
  notes?: string;
}

interface QuoteRow {
  id: string;
  quoteNumber: string;
  status: string;
  grandTotal?: number;
  revision?: number;
  createdAt?: string;
}

interface PaymentRow {
  id: string;
  amount: number;
  paymentDate: string;
  paymentType?: string;
  reference?: string;
  notes?: string;
}

function formatPaymentType(type?: string): string {
  if (!type) return '-';
  const map: Record<string, string> = {
    nakit: 'Nakit',
    havale: 'Havale',
    kredi_karti: 'Kredi kartı',
    diger: 'Diğer',
  };
  return map[type] ?? type;
}

export default function CustomerDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [customer, setCustomer] = useState<CustomerDetail | null>(null);
  const [balance, setBalance] = useState<BalanceInfo | null>(null);
  const [sales, setSales] = useState<SaleRow[]>([]);
  const [quotes, setQuotes] = useState<QuoteRow[]>([]);
  const [payments, setPayments] = useState<PaymentRow[]>([]);
  const [loading, setLoading] = useState(true);
  const [deleting, setDeleting] = useState(false);

  useEffect(() => {
    if (!id) return;
    setLoading(true);
    Promise.all([
      customersApi.get(id),
      customerPaymentsApi.balance(id),
      salesApi.list({ customerId: id }),
      quotesApi.list({ customerId: id }),
      customerPaymentsApi.byCustomer(id),
    ])
      .then(([cust, bal, sal, quo, pay]) => {
        const custRes = cust.data as CustomerDetail | undefined;
        setCustomer(custRes ?? (cust as unknown as CustomerDetail));
        setBalance((bal.data as BalanceInfo) ?? bal);
        const salBody = sal.data as { data?: unknown[] };
        const quoBody = quo.data as { data?: unknown[] };
        const payData = pay.data;
        setSales(Array.isArray(salBody?.data) ? (salBody.data as SaleRow[]) : []);
        setQuotes(Array.isArray(quoBody?.data) ? (quoBody.data as QuoteRow[]) : []);
        setPayments(Array.isArray(payData) ? (payData as PaymentRow[]) : []);
      })
      .catch(() => toast.error('Müşteri bilgileri yüklenemedi'))
      .finally(() => setLoading(false));
  }, [id]);

  const handleDelete = () => {
    if (!id || !globalThis.confirm('Bu müşteriyi silmek istediğinize emin misiniz? Bu müşteriye bağlı satış ve teklif kayıtları kalacaktır.')) return;
    setDeleting(true);
    customersApi
      .delete(id)
      .then(() => {
        toast.success('Müşteri silindi');
        navigate(ROUTES.musteriler);
      })
      .catch(() => {
        toast.error('Müşteri silinemedi');
        setDeleting(false);
      });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500 dark:text-zinc-300">Yükleniyor...</p>
      </div>
    );
  }
  if (!customer) {
    return (
      <div className="flex flex-col items-center justify-center py-16 gap-3">
        <p className="text-zinc-500 dark:text-zinc-300">Müşteri bilgileri yüklenemedi.</p>
        <Link to={ROUTES.musteriler} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">
          Müşteriler listesine dön
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3">
          <Link
            to={ROUTES.musteriler}
            className="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-600"
          >
            <ArrowLeftIcon className="h-4 w-4" />
            Müşteriler
          </Link>
          <h1 className="text-2xl font-bold text-zinc-900 dark:text-white">{customer.name}</h1>
          {customer.isActive === false && (
            <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Pasif</span>
          )}
        </div>
        <Button variant="danger" icon={TrashIcon} onClick={handleDelete} disabled={deleting}>
          {deleting ? 'Siliniyor...' : 'Müşteri Sil'}
        </Button>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Müşteri bilgileri */}
        <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800 dark:text-white">
            <UserCircleIcon className="h-5 w-5 text-emerald-600" />
            Müşteri bilgileri
          </h2>
          <dl className="space-y-3 text-sm">
            {customer.email && (
              <div className="flex gap-3">
                <EnvelopeIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500 dark:text-zinc-300">E-posta</dt>
                  <dd className="font-medium text-zinc-900 dark:text-white">
                    <a href={`mailto:${customer.email}`} className="text-emerald-600 hover:underline">
                      {customer.email}
                    </a>
                  </dd>
                </div>
              </div>
            )}
            {customer.phone && (
              <div className="flex gap-3">
                <PhoneIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500 dark:text-zinc-300">Telefon</dt>
                  <dd className="font-medium text-zinc-900 dark:text-white">
                    <a href={`tel:${customer.phone}`} className="text-emerald-600 hover:underline">
                      {customer.phone}
                    </a>
                  </dd>
                </div>
              </div>
            )}
            {customer.address && (
              <div className="flex gap-3">
                <MapPinIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500 dark:text-zinc-300">Adres</dt>
                  <dd className="font-medium text-zinc-900 dark:text-white">{customer.address}</dd>
                </div>
              </div>
            )}
            {(customer.taxNumber || customer.taxOffice || customer.identityNumber) && (
              <div className="flex gap-3">
                <DocumentTextIcon className="h-4 w-4 shrink-0 text-zinc-400" />
                <div>
                  <dt className="text-zinc-500">Vergi / Kimlik</dt>
                  <dd className="font-medium text-zinc-900 dark:text-white">
                    {[customer.taxNumber, customer.taxOffice, customer.identityNumber].filter(Boolean).join(' / ') || '-'}
                  </dd>
                </div>
              </div>
            )}
            {(customer.email || customer.phone || customer.address || customer.taxNumber || customer.taxOffice || customer.identityNumber) ? null : (
              <p className="text-zinc-500 dark:text-zinc-300">Ek bilgi girilmemiş.</p>
            )}
          </dl>
        </div>

        {/* Cari bakiye */}
        <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800 dark:text-white">
            <BanknotesIcon className="h-5 w-5 text-emerald-600" />
            Cari özet
          </h2>
          {balance != null ? (
            <div className="grid grid-cols-3 gap-4">
              <div className="rounded-xl bg-zinc-50 dark:bg-zinc-700 p-4">
                <p className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Toplam satış</p>
                <p className="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                  {Number(balance?.totalSales ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </p>
              </div>
              <div className="rounded-xl bg-zinc-50 dark:bg-zinc-700 p-4">
                <p className="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Toplam tahsilat</p>
                <p className="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                  {Number(balance?.totalPayments ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </p>
              </div>
              <div className={`rounded-xl p-4 ${Number(balance?.balance ?? 0) > 0 ? 'bg-amber-50' : 'bg-emerald-50'}`}>
                <p className={`text-xs font-medium uppercase tracking-wider ${Number(balance?.balance ?? 0) > 0 ? 'text-amber-700' : 'text-emerald-700'}`}>
                  Bakiye {Number(balance?.balance ?? 0) > 0 ? '(borç)' : '(alacak)'}
                </p>
                <p className={`mt-1 text-lg font-semibold ${Number(balance?.balance ?? 0) > 0 ? 'text-amber-800' : 'text-emerald-800'}`}>
                  {Number(balance?.balance ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                </p>
              </div>
            </div>
          ) : (
            <p className="text-sm text-zinc-500 dark:text-zinc-300">Bakiye bilgisi yüklenemedi.</p>
          )}
          <Link
            to={ROUTES.musteriCari}
            className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700"
          >
            <CurrencyDollarIcon className="h-4 w-4" />
            Müşteri cari
          </Link>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Satışlar */}
        <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800 dark:text-white">
            <ShoppingCartIcon className="h-5 w-5 text-emerald-600" />
            Satışlar ({sales.length})
          </h2>
          {sales.length > 0 ? (
            <>
              <div className="overflow-x-auto rounded-xl border border-zinc-200">
                <table className="min-w-full divide-y divide-zinc-200">
                  <thead className="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Fiş no</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tarih</th>
                      <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tutar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                    {sales.slice(0, 10).map((s) => (
                      <tr key={s.id} className="hover:bg-zinc-50/50">
                        <td className="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                          <Link to={ROUTES.satis(s.id)} className="text-emerald-600 hover:underline">
                            {s.saleNumber}
                          </Link>
                        </td>
                        <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                          {s.saleDate ? new Date(s.saleDate).toLocaleDateString('tr-TR') : '-'}
                        </td>
                        <td className="px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-white">
                          {Number(s.grandTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {sales.length > 10 && (
                <Link
                  to={`${ROUTES.satislar}?customerId=${id}`}
                  className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 hover:text-zinc-800"
                >
                  Tümünü gör ({sales.length})
                </Link>
              )}
            </>
          ) : (
            <p className="text-sm text-zinc-500">Henüz satış kaydı yok.</p>
          )}
        </div>

        {/* Teklifler */}
        <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
          <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800 dark:text-white">
            <DocumentTextIcon className="h-5 w-5 text-emerald-600" />
            Teklifler ({quotes.length})
          </h2>
          {quotes.length > 0 ? (
            <>
              <div className="overflow-x-auto rounded-xl border border-zinc-200">
                <table className="min-w-full divide-y divide-zinc-200">
                  <thead className="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Teklif no</th>
                      <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Durum</th>
                      <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tutar</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                    {quotes.slice(0, 10).map((q) => (
                      <tr key={q.id} className="hover:bg-zinc-50/50">
                        <td className="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                          <Link to={ROUTES.teklif(q.id)} className="text-emerald-600 hover:underline">
                            {q.quoteNumber ?? q.id.slice(0, 8)}
                          </Link>
                        </td>
                        <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{q.status ?? '-'}</td>
                        <td className="px-4 py-3 text-right text-sm font-medium text-zinc-900 dark:text-white">
                          {Number(q.grandTotal ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {quotes.length > 10 && (
                <Link
                  to={`${ROUTES.teklifler}?customerId=${id}`}
                  className="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 hover:text-zinc-800"
                >
                  Tümünü gör ({quotes.length})
                </Link>
              )}
            </>
          ) : (
            <p className="text-sm text-zinc-500">Henüz teklif kaydı yok.</p>
          )}
        </div>
      </div>

      {/* Tahsilatlar */}
      <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
        <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800 dark:text-white">
          <CurrencyDollarIcon className="h-5 w-5 text-emerald-600" />
          Tahsilatlar ({payments.length})
        </h2>
        {payments.length > 0 ? (
          <div className="overflow-x-auto rounded-xl border border-zinc-200">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tarih</th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tür</th>
                  <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Referans</th>
                  <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-300">Tutar</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-800">
                {payments.slice(0, 15).map((pay) => (
                  <tr key={pay.id} className="hover:bg-zinc-50/50">
                    <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                      {pay.paymentDate ? new Date(pay.paymentDate).toLocaleDateString('tr-TR') : '-'}
                    </td>
                    <td className="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                      {formatPaymentType(pay.paymentType)}
                    </td>
                    <td className="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{pay.reference ?? '-'}</td>
                    <td className="px-4 py-3 text-right text-sm font-medium text-emerald-700">
                      {Number(pay.amount).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p className="text-sm text-zinc-500">Henüz tahsilat kaydı yok.</p>
        )}
      </div>
    </div>
  );
}
