import { useEffect, useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { notificationsApi } from '../services/api/notificationsApi';
import { customerPaymentsApi } from '../services/api/customerPaymentsApi';
import { Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { BellIcon } from '@heroicons/react/24/outline';
import { PageHeader, Card, EmptyState, Button } from '../components/ui';

interface NotifItem {
  id: string;
  type: string;
  title: string;
  body?: string;
  isRead: boolean;
  createdAt: string;
}

export default function NotificationsPage() {
  const { user } = useAuth();
  const [notifications, setNotifications] = useState<NotifItem[]>([]);
  const [debtList, setDebtList] = useState<Array<{ customerId: string; balance: number }>>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!user?.id) return;
    Promise.all([
      notificationsApi.byUser(user.id).then(({ data }) => Array.isArray(data) ? data : []),
      customerPaymentsApi.withDebt().then(({ data }) => Array.isArray(data) ? data : []),
    ]).then(([notifs, debt]) => {
      setNotifications(notifs);
      setDebtList(debt);
    }).catch(() => {}).finally(() => setLoading(false));
  }, [user?.id]);

  if (loading) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Bildirimler" description="Sistem bildirimleri ve borçlu müşteri hatırlatmaları" icon={BellIcon} />
      {debtList.length > 0 && (
        <Card className="border-amber-200 bg-amber-50/50">
          <h2 className="font-semibold text-amber-800 dark:text-amber-200 mb-2">Borçlu Müşteriler ({debtList.length})</h2>
          <p className="text-sm text-amber-700 dark:text-amber-300 mb-3">Ödeme yapmamış veya borcu olan müşteriler. Muhasebe cari sayfasından detayları görebilirsiniz.</p>
          <Link to={ROUTES.musteriCari}>
            <Button variant="secondary" className="border-amber-300 text-amber-800 hover:bg-amber-100">Müşteri Cari</Button>
          </Link>
        </Card>
      )}
      <Card padding="none">
        {notifications.length === 0 ? (
          <EmptyState
            icon={BellIcon}
            title="Bildirim yok"
            description="Henüz bildirim bulunmuyor."
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <ul className="divide-y divide-zinc-200 dark:divide-zinc-700">
            {notifications.map((n) => (
              <li key={n.id} className={`px-6 py-4 transition-colors ${n.isRead ? 'bg-zinc-50/50 dark:bg-zinc-800/50' : 'bg-white dark:bg-zinc-800'}`}>
                <p className="font-medium text-zinc-900 dark:text-white">{n.title}</p>
                {n.body ? <p className="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{n.body}</p> : null}
                <p className="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{n.createdAt ? new Date(n.createdAt).toLocaleString('tr-TR') : ''}</p>
              </li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  );
}
