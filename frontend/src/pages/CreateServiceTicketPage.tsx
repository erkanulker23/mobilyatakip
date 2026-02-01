import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { Disclosure } from '@headlessui/react';
import { ChevronDownIcon, ChevronUpIcon, WrenchScrewdriverIcon, UserIcon, DocumentTextIcon, PhotoIcon, UserPlusIcon, PhoneIcon } from '@heroicons/react/24/outline';
import { salesApi } from '../services/api/salesApi';
import { serviceTicketsApi } from '../services/api/serviceTicketsApi';
import { uploadApi } from '../services/api/uploadApi';
import { authApi } from '../services/api/authApi';
import { suppliersApi } from '../services/api/suppliersApi';
import { productsApi } from '../services/api/productsApi';
import { PageHeader, Card } from '../components/ui';
import toast from 'react-hot-toast';

interface SaleOption {
  id: string;
  saleNumber?: string;
  customerId: string;
  customer?: { id: string; name: string; phone?: string; email?: string };
}

interface SaleDetailItem {
  id: string;
  productId: string;
  product?: { name: string; sku?: string };
  quantity: number;
  unitPrice: number;
  lineTotal: number;
}

interface SaleDetail {
  id: string;
  saleNumber?: string;
  items?: SaleDetailItem[];
}

const ISSUE_TYPES = [
  'Montaj hatası',
  'Parça arızası',
  'Teslimat hasarı',
  'Boyama / kaplama',
  'Kumaş / döşeme',
  'Kapak / çekmece',
  'Ayak / taban',
  'Cam / ayna',
  'Diğer',
];

const PRIORITY_OPTIONS = [
  { value: '', label: 'Belirtilmedi' },
  { value: 'acil', label: 'Acil' },
  { value: 'normal', label: 'Normal' },
  { value: 'dusuk', label: 'Düşük' },
];

const CONTACT_PREFERENCE_OPTIONS = [
  { value: '', label: 'Belirtilmedi' },
  { value: 'arama', label: 'Telefon ile arama' },
  { value: 'eposta', label: 'E-posta' },
  { value: 'sms', label: 'SMS' },
];

export default function CreateServiceTicketPage() {
  const navigate = useNavigate();
  const [sales, setSales] = useState<SaleOption[]>([]);
  const [assignableUsers, setAssignableUsers] = useState<{ id: string; name: string }[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [saleId, setSaleId] = useState('');
  const [saleDetail, setSaleDetail] = useState<SaleDetail | null>(null);
  const [customerId, setCustomerId] = useState('');
  const [underWarranty, setUnderWarranty] = useState(false);
  const [issueType, setIssueType] = useState('');
  const [description, setDescription] = useState('');
  const [affectedProductNote, setAffectedProductNote] = useState('');
  const [priority, setPriority] = useState('');
  const [contactPreference, setContactPreference] = useState('');
  const [preferredServiceDate, setPreferredServiceDate] = useState('');
  const [addressNote, setAddressNote] = useState('');
  const [notes, setNotes] = useState('');
  const [assignedUserId, setAssignedUserId] = useState('');
  const [assignedVehiclePlate, setAssignedVehiclePlate] = useState('');
  const [assignedDriverName, setAssignedDriverName] = useState('');
  const [assignedDriverPhone, setAssignedDriverPhone] = useState('');
  const [imageFiles, setImageFiles] = useState<File[]>([]);
  const [uploading, setUploading] = useState(false);
  const [serviceChargeAmount, setServiceChargeAmount] = useState('');
  const [serviceFree, setServiceFree] = useState(false);
  const [suppliers, setSuppliers] = useState<{ id: string; name: string }[]>([]);
  const [partsSupplierId, setPartsSupplierId] = useState('');
  const [partsProducts, setPartsProducts] = useState<{ id: string; name: string; sku?: string }[]>([]);
  const [partsRows, setPartsRows] = useState<{ productId: string; productName: string; quantity: string }[]>([]);

  useEffect(() => {
    Promise.all([
      salesApi.list().then(({ data }) => {
        const res = data as { data?: unknown[] };
        return Array.isArray(res?.data) ? res.data : [];
      }),
      authApi.assignableUsers().then(({ data }) => Array.isArray(data) ? data : []).catch(() => []),
      suppliersApi.list().then(({ data }) => {
        const res = data as { data?: unknown[] };
        return Array.isArray(res?.data) ? res.data : [];
      }),
    ])
      .then(([salesList, usersList, suppliersList]) => {
        setSales(salesList as SaleOption[]);
        setAssignableUsers(usersList as { id: string; name: string }[]);
        setSuppliers(suppliersList as { id: string; name: string }[]);
      })
      .catch(() => toast.error('Veriler yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!partsSupplierId) {
      setPartsProducts([]);
      return;
    }
    productsApi.list({ supplierId: partsSupplierId }).then(({ data }) => {
      const res = data as { data?: unknown[] };
      setPartsProducts(Array.isArray(res?.data) ? (res.data as { id: string; name: string; sku?: string }[]) : []);
    }).catch(() => setPartsProducts([]));
  }, [partsSupplierId]);

  useEffect(() => {
    if (!saleId) {
      setSaleDetail(null);
      return;
    }
    salesApi
      .get(saleId)
      .then(({ data }) => setSaleDetail(data as SaleDetail))
      .catch(() => {
        setSaleDetail(null);
        toast.error('Satış detayı yüklenemedi');
      });
  }, [saleId]);

  const selectedSale = sales.find((s) => s.id === saleId);

  const onAssignedUserChange = (userId: string) => {
    setAssignedUserId(userId);
    const u = assignableUsers.find((x) => x.id === userId);
    if (u) setAssignedDriverName(u.name ?? '');
    else setAssignedDriverName('');
  };

  const onSaleChange = (id: string) => {
    setSaleId(id);
    setSaleDetail(null);
    const s = sales.find((x) => x.id === id);
    if (s) setCustomerId(s.customerId);
  };

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = Array.from(e.target.files ?? []);
    setImageFiles((prev) => [...prev, ...files].slice(0, 10));
  };

  const removeImage = (index: number) => {
    setImageFiles((prev) => prev.filter((_, i) => i !== index));
  };

  const addPartsRow = () => {
    setPartsRows((r) => [...r, { productId: '', productName: '', quantity: '1' }]);
  };

  const updatePartsRow = (index: number, field: 'productId' | 'productName' | 'quantity', value: string) => {
    setPartsRows((r) => {
      const next = [...r];
      const row = { ...next[index], [field]: value };
      if (field === 'productId') {
        const product = partsProducts.find((p) => p.id === value);
        row.productName = product?.name ?? '';
      }
      next[index] = row;
      return next;
    });
  };

  const removePartsRow = (index: number) => {
    setPartsRows((r) => r.filter((_, i) => i !== index));
  };

  const buildNotes = (): string => {
    const parts: string[] = [];
    if (priority) {
      const p = PRIORITY_OPTIONS.find((o) => o.value === priority);
      if (p) parts.push(`Öncelik: ${p.label}`);
    }
    if (contactPreference) {
      const c = CONTACT_PREFERENCE_OPTIONS.find((o) => o.value === contactPreference);
      if (c) parts.push(`İletişim: ${c.label}`);
    }
    if (preferredServiceDate) parts.push(`Tercih edilen tarih: ${preferredServiceDate}`);
    if (addressNote.trim()) parts.push(`Adres: ${addressNote.trim()}`);
    if (partsRows.some((r) => r.productId && r.quantity)) {
      const supplierName = suppliers.find((s) => s.id === partsSupplierId)?.name ?? 'Tedarikçi';
      const lines = partsRows.filter((r) => r.productId && r.quantity).map((r) => `  - ${r.productName}: ${r.quantity} adet`);
      if (lines.length) parts.push(`Kullanılan parçalar (${supplierName}):\n${lines.join('\n')}`);
    }
    const header = parts.length ? parts.join('\n') + (notes.trim() ? '\n\n' : '') : '';
    return header + notes.trim();
  };

  const buildDescription = (): string => {
    const parts: string[] = [];
    if (affectedProductNote.trim()) parts.push(`İlgili ürün: ${affectedProductNote.trim()}`);
    if (description.trim()) parts.push(description.trim());
    return parts.join('\n\n') || '';
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!saleId || !customerId) {
      toast.error('Satış ve müşteri seçiniz.');
      return;
    }
    if (!issueType.trim()) {
      toast.error('Sorun türü giriniz.');
      return;
    }
    setSaving(true);
    let urls: string[] = [];
    if (imageFiles.length > 0) {
      setUploading(true);
      try {
        const { data } = await uploadApi.images(imageFiles);
        urls = data.urls ?? [];
      } catch {
        toast.error('Resimler yüklenemedi.');
        setSaving(false);
        setUploading(false);
        return;
      }
      setUploading(false);
    }
    const finalNotes = buildNotes().trim() || undefined;
    const finalDescription = buildDescription() || undefined;
    const chargeAmount = serviceFree ? null : (serviceChargeAmount.trim() ? Number(serviceChargeAmount) : null);
    serviceTicketsApi
      .create({
        saleId,
        customerId,
        underWarranty,
        issueType: issueType.trim(),
        description: finalDescription,
        notes: finalNotes,
        assignedUserId: assignedUserId || undefined,
        assignedVehiclePlate: assignedVehiclePlate.trim() || undefined,
        assignedDriverName: assignedDriverName.trim() || undefined,
        assignedDriverPhone: assignedDriverPhone.trim() || undefined,
        images: urls.length ? urls : undefined,
        serviceChargeAmount: chargeAmount !== undefined && chargeAmount !== null && !Number.isNaN(chargeAmount) ? chargeAmount : null,
      })
      .then(({ data }) => {
        toast.success('Servis kaydı oluşturuldu.');
        navigate(ROUTES.servisTalebi((data as { id: string }).id));
      })
      .catch(() => toast.error('Kayıt oluşturulamadı.'))
      .finally(() => setSaving(false));
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Yeni Servis Kaydı (SSH)"
        description="Satış sonrası hizmet kaydı oluşturun"
        icon={WrenchScrewdriverIcon}
        action={<Link to={ROUTES.servisTalepleri} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">← SSH Kayıtları</Link>}
      />
      <form onSubmit={handleSubmit} className="max-w-3xl space-y-4">
        <Disclosure defaultOpen>
          {({ open }) => (
            <Card className="overflow-hidden">
              <Disclosure.Button className="flex w-full items-center justify-between p-4 text-left">
                <span className="flex items-center gap-2 font-semibold text-zinc-900">
                  <UserIcon className="h-5 w-5 text-emerald-600" />
                  Müşteri & Satış
                </span>
                {open ? <ChevronUpIcon className="h-5 w-5 text-zinc-500" /> : <ChevronDownIcon className="h-5 w-5 text-zinc-500" />}
              </Disclosure.Button>
              <Disclosure.Panel className="border-t border-zinc-100 p-4 pt-3">
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Satış *</label>
                    <select
                      required
                      value={saleId}
                      onChange={(e) => onSaleChange(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    >
                      <option value="">Satış seçin</option>
                      {sales.map((s) => (
                        <option key={s.id} value={s.id}>
                          {(s as SaleOption).saleNumber ?? s.id.slice(0, 8)} — {(s.customer as { name?: string })?.name ?? 'Müşteri'}
                        </option>
                      ))}
                    </select>
                  </div>
                  {selectedSale && (
                    <div className="rounded-xl bg-zinc-50 p-4 text-sm space-y-2">
                      <p className="font-medium text-zinc-900">Müşteri: {(selectedSale.customer as { name?: string })?.name ?? '—'}</p>
                      {(selectedSale.customer as { address?: string })?.address && (
                        <p className="text-zinc-600"><span className="font-medium">Adres:</span> {(selectedSale.customer as { address?: string }).address}</p>
                      )}
                      {(selectedSale.customer as { phone?: string })?.phone && (
                        <p className="text-zinc-600">Telefon: {(selectedSale.customer as { phone?: string }).phone}</p>
                      )}
                      {(selectedSale.customer as { email?: string })?.email && (
                        <p className="text-zinc-600">E-posta: {(selectedSale.customer as { email?: string }).email}</p>
                      )}
                    </div>
                  )}
                  {saleDetail?.items && saleDetail.items.length > 0 && (
                    <div>
                      <p className="text-sm font-medium text-zinc-700 mb-2">Satıştaki ürünler</p>
                      <ul className="rounded-xl border border-zinc-200 divide-y divide-zinc-100 bg-white">
                        {saleDetail.items.map((item) => (
                          <li key={item.id} className="px-4 py-2.5 flex justify-between text-sm">
                            <span className="text-zinc-900">{(item.product as { name?: string })?.name ?? 'Ürün'}</span>
                            <span className="text-zinc-500">Adet: {item.quantity}</span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              </Disclosure.Panel>
            </Card>
          )}
        </Disclosure>

        <Disclosure defaultOpen>
          {({ open }) => (
            <Card className="overflow-hidden">
              <Disclosure.Button className="flex w-full items-center justify-between p-4 text-left">
                <span className="flex items-center gap-2 font-semibold text-zinc-900">
                  <DocumentTextIcon className="h-5 w-5 text-emerald-600" />
                  Sorun Detayı
                </span>
                {open ? <ChevronUpIcon className="h-5 w-5 text-zinc-500" /> : <ChevronDownIcon className="h-5 w-5 text-zinc-500" />}
              </Disclosure.Button>
              <Disclosure.Panel className="border-t border-zinc-100 p-4 pt-3">
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Sorun türü *</label>
                    <select
                      required
                      value={issueType}
                      onChange={(e) => setIssueType(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    >
                      <option value="">Seçin</option>
                      {ISSUE_TYPES.map((t) => (
                        <option key={t} value={t}>{t}</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">İlgili ürün / parça</label>
                    <input
                      type="text"
                      value={affectedProductNote}
                      onChange={(e) => setAffectedProductNote(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      placeholder="Örn: Koltuk takımı, Yatak odası dolabı..."
                    />
                    {saleDetail?.items && saleDetail.items.length > 0 && (
                      <p className="mt-1 text-xs text-zinc-500">Yukarıdaki satış kalemlerinden ilgili olanı yazabilirsiniz.</p>
                    )}
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Sorun açıklaması</label>
                    <textarea
                      rows={4}
                      value={description}
                      onChange={(e) => setDescription(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      placeholder="Sorun detayı, müşteri beyanı, hasar açıklaması..."
                    />
                  </div>
                  <div className="flex items-center gap-2">
                    <input
                      type="checkbox"
                      id="warranty"
                      checked={underWarranty}
                      onChange={(e) => setUnderWarranty(e.target.checked)}
                      className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                    />
                    <label htmlFor="warranty" className="text-sm font-medium text-zinc-700">Garanti kapsamında</label>
                  </div>
                  <div className="border-t border-zinc-100 pt-4 mt-4 space-y-3">
                    <p className="text-sm font-medium text-zinc-700">Servis ücreti</p>
                    <div className="flex items-center gap-4 flex-wrap">
                      <label className="inline-flex items-center gap-2">
                        <input
                          type="checkbox"
                          checked={serviceFree}
                          onChange={(e) => {
                            setServiceFree(e.target.checked);
                            if (e.target.checked) setServiceChargeAmount('');
                          }}
                          className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        <span className="text-sm text-zinc-700">Ücretsiz</span>
                      </label>
                      {!serviceFree && (
                        <div className="flex items-center gap-2">
                          <input
                            type="number"
                            step="0.01"
                            min="0"
                            value={serviceChargeAmount}
                            onChange={(e) => setServiceChargeAmount(e.target.value)}
                            placeholder="Tutar (₺)"
                            className="w-32 rounded-xl border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                          />
                          <span className="text-sm text-zinc-500">₺</span>
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="border-t border-zinc-100 pt-4 mt-4 space-y-3">
                    <p className="text-sm font-medium text-zinc-700">Kullanılan parçalar / İlgili ürünler (opsiyonel)</p>
                    <p className="text-xs text-zinc-500">Hangi tedarikçinin ürünü kullanıldığını seçebilir veya notlarda manuel yazabilirsiniz.</p>
                    <div>
                      <label className="block text-xs font-medium text-zinc-600 mb-1">Tedarikçi</label>
                      <select
                        value={partsSupplierId}
                        onChange={(e) => {
                          setPartsSupplierId(e.target.value);
                          setPartsRows([]);
                        }}
                        className="block w-full max-w-xs rounded-xl border border-zinc-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      >
                        <option value="">Seçin</option>
                        {suppliers.map((s) => (
                          <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                      </select>
                    </div>
                    {partsSupplierId && (
                      <>
                        <button type="button" onClick={addPartsRow} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                          + Parça ekle
                        </button>
                        {partsRows.length > 0 && (
                          <div className="rounded-xl border border-zinc-200 overflow-hidden">
                            <table className="min-w-full divide-y divide-zinc-200 text-sm">
                              <thead className="bg-zinc-50">
                                <tr>
                                  <th className="px-3 py-2 text-left text-xs font-medium text-zinc-500">Ürün</th>
                                  <th className="px-3 py-2 text-right text-xs font-medium text-zinc-500 w-20">Adet</th>
                                  <th className="w-8" />
                                </tr>
                              </thead>
                              <tbody className="divide-y divide-zinc-100">
                                {partsRows.map((row, idx) => (
                                  <tr key={idx}>
                                    <td className="px-3 py-2">
                                      <select
                                        value={row.productId}
                                        onChange={(e) => updatePartsRow(idx, 'productId', e.target.value)}
                                        className="block w-full rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-emerald-500"
                                      >
                                        <option value="">Ürün seçin</option>
                                        {partsProducts.map((p) => (
                                          <option key={p.id} value={p.id}>{p.name}{p.sku ? ` (${p.sku})` : ''}</option>
                                        ))}
                                      </select>
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                      <input
                                        type="number"
                                        min="1"
                                        value={row.quantity}
                                        onChange={(e) => updatePartsRow(idx, 'quantity', e.target.value)}
                                        className="w-16 text-right rounded-lg border border-zinc-300 px-2 py-1.5 text-sm focus:border-emerald-500"
                                      />
                                    </td>
                                    <td className="px-2 py-2">
                                      <button type="button" onClick={() => removePartsRow(idx)} className="text-red-600 hover:text-red-700 text-sm">×</button>
                                    </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                        )}
                      </>
                    )}
                  </div>
                </div>
              </Disclosure.Panel>
            </Card>
          )}
        </Disclosure>

        <Disclosure>
          {({ open }) => (
            <Card className="overflow-hidden">
              <Disclosure.Button className="flex w-full items-center justify-between p-4 text-left">
                <span className="flex items-center gap-2 font-semibold text-zinc-900">
                  <PhoneIcon className="h-5 w-5 text-emerald-600" />
                  İletişim & Ziyaret Tercihleri
                </span>
                {open ? <ChevronUpIcon className="h-5 w-5 text-zinc-500" /> : <ChevronDownIcon className="h-5 w-5 text-zinc-500" />}
              </Disclosure.Button>
              <Disclosure.Panel className="border-t border-zinc-100 p-4 pt-3">
                <div className="space-y-4">
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Öncelik</label>
                      <select
                        value={priority}
                        onChange={(e) => setPriority(e.target.value)}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        {PRIORITY_OPTIONS.map((o) => (
                          <option key={o.value || 'none'} value={o.value}>{o.label}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">İletişim tercihi</label>
                      <select
                        value={contactPreference}
                        onChange={(e) => setContactPreference(e.target.value)}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        {CONTACT_PREFERENCE_OPTIONS.map((o) => (
                          <option key={o.value || 'none'} value={o.value}>{o.label}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Tercih edilen servis tarihi</label>
                    <input
                      type="date"
                      value={preferredServiceDate}
                      onChange={(e) => setPreferredServiceDate(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Adres / teslimat notu</label>
                    <textarea
                      rows={2}
                      value={addressNote}
                      onChange={(e) => setAddressNote(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      placeholder="Servis için adres veya teslimat bilgisi..."
                    />
                  </div>
                </div>
              </Disclosure.Panel>
            </Card>
          )}
        </Disclosure>

        <Disclosure>
          {({ open }) => (
            <Card className="overflow-hidden">
              <Disclosure.Button className="flex w-full items-center justify-between p-4 text-left">
                <span className="flex items-center gap-2 font-semibold text-zinc-900">
                  <UserPlusIcon className="h-5 w-5 text-emerald-600" />
                  Atama & Ek Notlar
                </span>
                {open ? <ChevronUpIcon className="h-5 w-5 text-zinc-500" /> : <ChevronDownIcon className="h-5 w-5 text-zinc-500" />}
              </Disclosure.Button>
              <Disclosure.Panel className="border-t border-zinc-100 p-4 pt-3">
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Atanan personel (kullanıcı)</label>
                    <select
                      value={assignedUserId}
                      onChange={(e) => onAssignedUserChange(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    >
                      <option value="">Seçin (opsiyonel)</option>
                      {assignableUsers.map((u) => (
                        <option key={u.id} value={u.id}>{u.name}</option>
                      ))}
                    </select>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 rounded-xl bg-zinc-50 p-4">
                      <div>
                        <label className="block text-sm font-medium text-zinc-700">Araç plakası</label>
                        <input
                          type="text"
                          value={assignedVehiclePlate}
                          onChange={(e) => setAssignedVehiclePlate(e.target.value)}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                          placeholder="34 ABC 123"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-zinc-700">Şoför adı</label>
                        <input
                          type="text"
                          value={assignedDriverName}
                          onChange={(e) => setAssignedDriverName(e.target.value)}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                          placeholder="Şoför bilgisi"
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-zinc-700">Telefon</label>
                        <input
                          type="text"
                          value={assignedDriverPhone}
                          onChange={(e) => setAssignedDriverPhone(e.target.value)}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                          placeholder="05xx xxx xx xx"
                        />
                      </div>
                    </div>
                  <div>
                    <label className="block text-sm font-medium text-zinc-700">Ek notlar</label>
                    <textarea
                      rows={3}
                      value={notes}
                      onChange={(e) => setNotes(e.target.value)}
                      className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      placeholder="İç notlar, öncelik vb."
                    />
                  </div>
                </div>
              </Disclosure.Panel>
            </Card>
          )}
        </Disclosure>

        <Disclosure>
          {({ open }) => (
            <Card className="overflow-hidden">
              <Disclosure.Button className="flex w-full items-center justify-between p-4 text-left">
                <span className="flex items-center gap-2 font-semibold text-zinc-900">
                  <PhotoIcon className="h-5 w-5 text-emerald-600" />
                  Resimler
                </span>
                {imageFiles.length > 0 && (
                  <span className="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">{imageFiles.length} dosya</span>
                )}
                {open ? <ChevronUpIcon className="h-5 w-5 text-zinc-500" /> : <ChevronDownIcon className="h-5 w-5 text-zinc-500" />}
              </Disclosure.Button>
              <Disclosure.Panel className="border-t border-zinc-100 p-4 pt-3">
                <div>
                  <label className="block text-sm font-medium text-zinc-700">Fotoğraf ekle (en fazla 10)</label>
                  <input
                    type="file"
                    accept="image/*"
                    multiple
                    onChange={handleImageChange}
                    className="mt-1.5 block w-full text-sm text-zinc-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-emerald-700 file:font-medium"
                  />
                  {imageFiles.length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2">
                      {imageFiles.map((f, i) => (
                        <span key={`f-${i}`} className="inline-flex items-center gap-1.5 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700">
                          {f.name}
                          <button type="button" onClick={() => removeImage(i)} className="text-red-600 hover:text-red-800">×</button>
                        </span>
                      ))}
                    </div>
                  )}
                </div>
              </Disclosure.Panel>
            </Card>
          )}
        </Disclosure>

        <div className="flex gap-3 pt-2">
          <Link to={ROUTES.servisTalepleri} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50">
            İptal
          </Link>
          <button
            type="submit"
            disabled={saving || uploading}
            className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
          >
            {saving ? (uploading ? 'Resimler yükleniyor...' : 'Oluşturuluyor...') : 'Kayıt Aç'}
          </button>
        </div>
      </form>
    </div>
  );
}
