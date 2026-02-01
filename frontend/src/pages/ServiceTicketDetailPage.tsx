import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { DocumentArrowDownIcon, PrinterIcon } from '@heroicons/react/24/outline';
import { serviceTicketsApi } from '../services/api/serviceTicketsApi';
import { uploadApi } from '../services/api/uploadApi';
import { useAuth } from '../contexts/AuthContext';
import toast from 'react-hot-toast';

const STATUS_LABELS: Record<string, string> = {
  acildi: 'Açıldı',
  incelemede: 'İncelemede',
  parca_bekliyor: 'Parça Bekleniyor',
  cozuldu: 'Çözüldü',
  kapandi: 'Kapandı',
};

const TIMELINE_TYPES = [
  { value: 'Yapıldı', label: 'Yapıldı', bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
  { value: 'Yapılmadı', label: 'Yapılmadı', bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
  { value: 'Yolda', label: 'Yolda', bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
  { value: 'Not', label: 'Not', bg: 'bg-zinc-100', text: 'text-zinc-700', dot: 'bg-zinc-500' },
  { value: '', label: 'Diğer (özel metin)', bg: 'bg-zinc-100', text: 'text-zinc-700', dot: 'bg-zinc-400' },
];

function getTimelineStyle(action: string): { bg: string; text: string; dot: string } {
  const found = TIMELINE_TYPES.find((t) => t.value && action === t.value);
  return found ? { bg: found.bg, text: found.text, dot: found.dot } : { bg: 'bg-zinc-100', text: 'text-zinc-700', dot: 'bg-zinc-400' };
}

const API_BASE = import.meta.env.VITE_API_URL || '';

export default function ServiceTicketDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const [ticket, setTicket] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);
  const [newStatus, setNewStatus] = useState('');
  const [stepType, setStepType] = useState('');
  const [stepAction, setStepAction] = useState('');
  const [stepNotes, setStepNotes] = useState('');
  const [stepImages, setStepImages] = useState<File[]>([]);
  const [addingStep, setAddingStep] = useState(false);

  const load = () => {
    if (!id) return;
    serviceTicketsApi
      .get(id)
      .then(({ data }) => {
        setTicket(data as Record<string, unknown>);
        setNewStatus((data as Record<string, unknown>).status as string);
      })
      .catch(() => toast.error('Kayıt yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    load();
  }, [id]);

  const handleStatusChange = () => {
    if (!id || !newStatus) return;
    setUpdating(true);
    serviceTicketsApi
      .updateStatus(id, newStatus)
      .then(({ data }) => {
        setTicket(data as Record<string, unknown>);
        toast.success('Durum güncellendi');
      })
      .catch(() => toast.error('Güncelleme başarısız'))
      .finally(() => setUpdating(false));
  };

  const handleAddStep = async (e: React.FormEvent) => {
    e.preventDefault();
    const actionText = stepType ? stepType : stepAction.trim();
    if (!id || !user?.id || !actionText) {
      toast.error('Tip seçin veya aşama metni giriniz.');
      return;
    }
    setAddingStep(true);
    let urls: string[] = [];
    if (stepImages.length > 0) {
      try {
        const { data } = await uploadApi.images(stepImages);
        urls = data.urls ?? [];
      } catch {
        toast.error('Resimler yüklenemedi.');
        setAddingStep(false);
        return;
      }
    }
    serviceTicketsApi
      .addDetail(id, {
        userId: user.id,
        action: actionText,
        notes: stepNotes.trim() || undefined,
        images: urls.length ? urls : undefined,
      })
      .then(() => {
        toast.success('Aşama eklendi.');
        setStepType('');
        setStepAction('');
        setStepNotes('');
        setStepImages([]);
        load();
      })
      .catch(() => toast.error('Aşama eklenemedi.'))
      .finally(() => setAddingStep(false));
  };

  const openPdf = (print = false) => {
    if (!id) return;
    serviceTicketsApi
      .getPdf(id)
      .then(({ data }) => {
        const url = URL.createObjectURL(data as Blob);
        const w = window.open(url, '_blank');
        if (w) {
          w.onload = () => { if (print) w.print(); };
        }
        setTimeout(() => URL.revokeObjectURL(url), 60000);
      })
      .catch(() => toast.error('PDF açılamadı'));
  };

  if (loading || !ticket) return <p className="text-zinc-500 py-8">Yükleniyor...</p>;

  const details = (ticket.details as Array<Record<string, unknown>>) ?? [];
  const customer = ticket.customer as Record<string, unknown> | undefined;
  const sale = ticket.sale as Record<string, unknown> | undefined;
  const ticketImages = (ticket.images as string[] | undefined) ?? [];

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-center gap-3">
        <Link to={ROUTES.servisTalepleri} className="text-emerald-600 hover:text-emerald-700 text-sm font-medium">
          ← SSH Kayıtları
        </Link>
        <h1 className="text-2xl font-bold text-zinc-900">Servis Kaydı: {String(ticket.ticketNumber)}</h1>
        <div className="flex gap-2 ml-auto">
          <button type="button" onClick={() => openPdf(false)} className="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
            <DocumentArrowDownIcon className="h-4 w-4" />
            PDF İndir
          </button>
          <button type="button" onClick={() => openPdf(true)} className="inline-flex items-center gap-1.5 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
            <PrinterIcon className="h-4 w-4" />
            Yazdır
          </button>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-zinc-800 mb-4">Bilgiler</h2>
          <dl className="grid grid-cols-2 gap-2 text-sm">
            <dt className="text-zinc-500">Müşteri</dt>
            <dd className="font-medium text-zinc-900">{String((customer as { name?: string })?.name ?? '-')}</dd>
            {(customer as { address?: string })?.address && (
              <>
                <dt className="text-zinc-500">Adres</dt>
                <dd className="font-medium text-zinc-900 col-span-2">{String((customer as { address?: string }).address)}</dd>
              </>
            )}
            <dt className="text-zinc-500">Satış</dt>
            <dd className="font-medium text-zinc-900">{String((sale as { saleNumber?: string })?.saleNumber ?? '-')}</dd>
            <dt className="text-zinc-500">Sorun türü</dt>
            <dd>{String(ticket.issueType ?? '-')}</dd>
            <dt className="text-zinc-500">Garanti</dt>
            <dd>{ticket.underWarranty ? 'Evet' : 'Hayır'}</dd>
            {(ticket.assignedVehiclePlate || ticket.assignedDriverName || ticket.assignedDriverPhone) ? (
              <>
                {ticket.assignedVehiclePlate ? (
                  <>
                    <dt className="text-zinc-500">Araç plakası</dt>
                    <dd className="font-medium text-zinc-900">{String(ticket.assignedVehiclePlate)}</dd>
                  </>
                ) : null}
                {ticket.assignedDriverName ? (
                  <>
                    <dt className="text-zinc-500">Şoför</dt>
                    <dd className="font-medium text-zinc-900">{String(ticket.assignedDriverName)}</dd>
                  </>
                ) : null}
                {ticket.assignedDriverPhone ? (
                  <>
                    <dt className="text-zinc-500">Şoför telefon</dt>
                    <dd className="font-medium text-zinc-900">{String(ticket.assignedDriverPhone)}</dd>
                  </>
                ) : null}
              </>
            ) : null}
            <dt className="text-zinc-500">Açılış</dt>
            <dd>{ticket.openedAt ? new Date(String(ticket.openedAt)).toLocaleString('tr-TR') : '-'}</dd>
            {ticket.closedAt ? (
              <>
                <dt className="text-zinc-500">Kapanış</dt>
                <dd>{new Date(String(ticket.closedAt)).toLocaleString('tr-TR')}</dd>
              </>
            ) : null}
          </dl>
          {ticket.description ? (
            <div className="mt-4">
              <p className="text-sm text-zinc-500">Açıklama</p>
              <p className="mt-1 text-sm text-zinc-900">{String(ticket.description)}</p>
            </div>
          ) : null}
          {ticketImages.length > 0 && (
            <div className="mt-4">
              <p className="text-sm text-zinc-500 mb-2">Kayıt resimleri</p>
              <div className="flex flex-wrap gap-2">
                {ticketImages.map((url, i) => (
                  <a key={i} href={url.startsWith('http') ? url : `${API_BASE}${url}`} target="_blank" rel="noopener noreferrer" className="block w-20 h-20 rounded-lg overflow-hidden border border-zinc-200">
                    <img src={url.startsWith('http') ? url : `${API_BASE}${url}`} alt="" className="w-full h-full object-cover" />
                  </a>
                ))}
              </div>
            </div>
          )}
          <div className="mt-6 pt-4 border-t border-zinc-100">
            <label className="block text-sm font-medium text-zinc-700 mb-2">Durum</label>
            <div className="flex gap-2">
              <select
                value={newStatus}
                onChange={(e) => setNewStatus(e.target.value)}
                className="rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              >
                {Object.entries(STATUS_LABELS).map(([k, v]) => (
                  <option key={k} value={k}>{v}</option>
                ))}
              </select>
              <button
                type="button"
                onClick={handleStatusChange}
                disabled={updating || newStatus === ticket.status}
                className="rounded-xl px-3 py-2 bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50"
              >
                {updating ? 'Kaydediliyor...' : 'Güncelle'}
              </button>
            </div>
          </div>
        </div>

        <div className="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-zinc-800 mb-4">Servis zaman çizelgesi</h2>
          {details.length === 0 ? (
            <p className="text-sm text-zinc-500">Henüz aşama eklenmedi.</p>
          ) : (
            <ul className="relative space-y-0">
              {details.map((d: Record<string, unknown>, idx: number) => {
                const detailImages = (d.images as string[] | undefined) ?? [];
                const isLast = idx === details.length - 1;
                const actionStr = String(d.action ?? '');
                const style = getTimelineStyle(actionStr);
                return (
                  <li key={String(d.id ?? idx)} className="relative flex gap-4 pb-8">
                    {!isLast && <span className="absolute left-[11px] top-6 bottom-0 w-0.5 bg-zinc-200" />}
                    <span className={`relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${style.dot} text-white text-xs font-semibold`} title={actionStr}>
                      {idx + 1}
                    </span>
                    <div className={`flex-1 min-w-0 rounded-xl border border-zinc-100 p-3 ${style.bg}`}>
                      <p className={`font-medium ${style.text}`}>{actionStr}</p>
                      {d.notes ? <p className="mt-1 text-sm text-zinc-600">{String(d.notes)}</p> : null}
                      {detailImages.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-2">
                          {detailImages.map((url, i) => (
                            <a key={i} href={url.startsWith('http') ? url : `${API_BASE}${url}`} target="_blank" rel="noopener noreferrer" className="block w-16 h-16 rounded-lg overflow-hidden border border-zinc-200">
                              <img src={url.startsWith('http') ? url : `${API_BASE}${url}`} alt="" className="w-full h-full object-cover" />
                            </a>
                          ))}
                        </div>
                      )}
                      <p className="mt-1 text-xs text-zinc-400">{d.actionDate ? new Date(String(d.actionDate)).toLocaleString('tr-TR') : ''}</p>
                    </div>
                  </li>
                );
              })}
            </ul>
          )}

          <form onSubmit={handleAddStep} className="mt-6 pt-6 border-t border-zinc-200 space-y-3">
            <h3 className="text-sm font-semibold text-zinc-700">Yeni aşama ekle (timeline)</h3>
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Tip</label>
              <select
                value={stepType}
                onChange={(e) => setStepType(e.target.value)}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              >
                <option value="">Diğer (aşağıya yazın)</option>
                {TIMELINE_TYPES.filter((t) => t.value).map((t) => (
                  <option key={t.value} value={t.value}>{t.label}</option>
                ))}
              </select>
            </div>
            {!stepType && (
              <div>
                <label className="block text-sm font-medium text-zinc-700 mb-1">Aşama / İşlem metni *</label>
                <input
                  type="text"
                  value={stepAction}
                  onChange={(e) => setStepAction(e.target.value)}
                  placeholder="Örn: Müşteri ile görüşüldü, parça siparişi verildi"
                  className="block w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                />
              </div>
            )}
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Notlar</label>
              <textarea
                rows={2}
                value={stepNotes}
                onChange={(e) => setStepNotes(e.target.value)}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                placeholder="Opsiyonel..."
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Resim ekle</label>
              <input
                type="file"
                accept="image/*"
                multiple
                onChange={(e) => setStepImages(Array.from(e.target.files ?? []))}
                className="block w-full text-sm text-zinc-500 file:mr-2 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-emerald-700"
              />
              {stepImages.length > 0 && (
                <p className="mt-1 text-xs text-zinc-500">{stepImages.length} dosya seçildi</p>
              )}
            </div>
            <button
              type="submit"
              disabled={addingStep}
              className="rounded-xl px-4 py-2 bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 disabled:opacity-50"
            >
              {addingStep ? 'Ekleniyor...' : 'Aşama ekle'}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
