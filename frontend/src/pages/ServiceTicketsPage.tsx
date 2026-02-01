import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { WrenchScrewdriverIcon, PlusIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { serviceTicketsApi } from '../services/api/serviceTicketsApi';
import { customersApi } from '../services/api/customersApi';
import { PageHeader, Card, EmptyState, Button, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

const STATUS_LABELS: Record<string, string> = {
  acildi: 'Açıldı',
  incelemede: 'İncelemede',
  parca_bekliyor: 'Parça Bekleniyor',
  cozuldu: 'Çözüldü',
  kapandi: 'Kapandı',
};

interface TicketRow {
  id: string;
  ticketNumber: string;
  status: string;
  underWarranty?: boolean;
  issueType?: string;
  openedAt: string;
  sale?: { id: string };
  customer?: { id: string; name: string };
}

export default function ServiceTicketsPage() {
  const [tickets, setTickets] = useState<TicketRow[]>([]);
  const [customers, setCustomers] = useState<{ id: string; name: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [customerFilter, setCustomerFilter] = useState('');
  const [openedAtFrom, setOpenedAtFrom] = useState('');
  const [openedAtTo, setOpenedAtTo] = useState('');
  const [searchText, setSearchText] = useState('');
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    customersApi.list({ limit: 500 }).then(({ data }) => {
      const res = data as { data?: unknown[] };
      setCustomers(Array.isArray(res?.data) ? (res.data as { id: string; name: string }[]) : []);
    }).catch(() => {});
  }, []);

  useEffect(() => {
    setLoading(true);
    const params: { status?: string; customerId?: string; openedAtFrom?: string; openedAtTo?: string; search?: string; page?: number; limit?: number } = { page, limit };
    if (statusFilter) params.status = statusFilter;
    if (customerFilter) params.customerId = customerFilter;
    if (openedAtFrom) params.openedAtFrom = openedAtFrom;
    if (openedAtTo) params.openedAtTo = openedAtTo;
    if (searchText.trim()) params.search = searchText.trim();
    serviceTicketsApi
      .list(params)
      .then(({ data }) => {
        const res = data as { data?: TicketRow[]; total?: number; totalPages?: number };
        setTickets(Array.isArray(res?.data) ? res.data : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Servis kayıtları yüklenemedi'))
      .finally(() => setLoading(false));
  }, [statusFilter, customerFilter, openedAtFrom, openedAtTo, searchText, page, limit]);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Servis Sonrası Destek (SSH)"
        description="Servis kayıtları ve takip"
        icon={WrenchScrewdriverIcon}
        action={
          <Link to={ROUTES.servisTalebiYeni}>
            <Button icon={PlusIcon}>Yeni SSH Kaydı</Button>
          </Link>
        }
      />
      <div className="flex flex-wrap gap-3 items-center">
        <select
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tüm durumlar</option>
          {Object.entries(STATUS_LABELS).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <select
          value={customerFilter}
          onChange={(e) => { setCustomerFilter(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 min-w-[160px]"
        >
          <option value="">Tüm müşteriler</option>
          {customers.map((c) => (
            <option key={c.id} value={c.id}>{c.name}</option>
          ))}
        </select>
        <input
          type="date"
          value={openedAtFrom}
          onChange={(e) => { setOpenedAtFrom(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        />
        <input
          type="date"
          value={openedAtTo}
          onChange={(e) => { setOpenedAtTo(e.target.value); setPage(1); }}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        />
        <input
          type="text"
          placeholder="Kayıt no veya sorun türü ara..."
          value={searchText}
          onChange={(e) => setSearchText(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && setPage(1)}
          className="rounded-xl border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-48"
        />
        <Button variant="primary" onClick={() => setPage(1)}>Filtrele</Button>
      </div>
      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500">Yükleniyor...</p>
          </div>
        ) : tickets.length === 0 ? (
          <EmptyState
            icon={WrenchScrewdriverIcon}
            title="Servis kaydı bulunamadı"
            description="Henüz servis kaydı yok. Yeni SSH kaydı oluşturabilirsiniz."
            action={
              <Link to={ROUTES.servisTalebiYeni}>
                <Button icon={PlusIcon}>Yeni SSH Kaydı</Button>
              </Link>
            }
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-zinc-200">
                <thead className="bg-zinc-50">
                  <tr>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Kayıt No</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Müşteri</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Sorun türü</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Durum</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Garanti</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">Açılış</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">İşlem</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-200 bg-white">
                  {tickets.map((t) => (
                    <tr key={t.id} className="hover:bg-zinc-50/80 transition-colors">
                      <td className="px-6 py-4 text-sm font-medium text-zinc-900">{t.ticketNumber}</td>
                      <td className="px-6 py-4 text-sm text-zinc-600">{(t.customer as { name?: string })?.name ?? '—'}</td>
                      <td className="px-6 py-4 text-sm text-zinc-600">{t.issueType ?? '—'}</td>
                      <td className="px-6 py-4">
                        <span className="inline-flex rounded-lg bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-700">
                          {STATUS_LABELS[t.status] ?? t.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-zinc-600">{t.underWarranty ? 'Evet' : 'Hayır'}</td>
                      <td className="px-6 py-4 text-sm text-zinc-600">{t.openedAt ? new Date(t.openedAt).toLocaleDateString('tr-TR') : '—'}</td>
                      <td className="px-6 py-4 text-right">
                        <Link
                          to={ROUTES.servisTalebi(t.id)}
                          className="text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:underline"
                        >
                          Detay
                        </Link>
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
    </div>
  );
}
