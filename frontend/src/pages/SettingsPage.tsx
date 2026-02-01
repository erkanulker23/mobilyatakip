import { useEffect, useState } from 'react';
import { Tab } from '@headlessui/react';
import { BuildingOffice2Icon, ChatBubbleLeftRightIcon, CreditCardIcon } from '@heroicons/react/24/outline';
import { companyApi } from '../services/api/companyApi';
import { smsApi } from '../services/api/smsApi';
import { uploadApi } from '../services/api/uploadApi';
import { PageHeader } from '../components/ui';
import toast from 'react-hot-toast';

interface CompanyForm {
  name: string;
  address: string;
  taxNumber: string;
  taxOffice: string;
  phone: string;
  email: string;
  logoUrl: string;
  website: string;
  ntgsmUsername: string;
  ntgsmPassword: string;
  ntgsmOriginator: string;
  ntgsmApiUrl: string;
  paytrMerchantId: string;
  paytrMerchantKey: string;
  paytrMerchantSalt: string;
  paytrTestMode: boolean;
}

const tabs = [
  { name: 'Firma Bilgileri', key: 'firma', icon: BuildingOffice2Icon },
  { name: 'SMS Entegrasyonu (NTGSM)', key: 'ntgsm', icon: ChatBubbleLeftRightIcon },
  { name: 'Sanal Pos (PayTR)', key: 'paytr', icon: CreditCardIcon },
];

const inputClass =
  'block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20';
const labelClass = 'block text-sm font-medium text-zinc-700 dark:text-zinc-200 mb-1.5';

export default function SettingsPage() {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState<string | null>(null);
  const [testSmsPhone, setTestSmsPhone] = useState('');
  const [sendingSms, setSendingSms] = useState(false);
  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [form, setForm] = useState<CompanyForm>({
    name: '',
    address: '',
    taxNumber: '',
    taxOffice: '',
    phone: '',
    email: '',
    logoUrl: '',
    website: '',
    ntgsmUsername: '',
    ntgsmPassword: '',
    ntgsmOriginator: '',
    ntgsmApiUrl: '',
    paytrMerchantId: '',
    paytrMerchantKey: '',
    paytrMerchantSalt: '',
    paytrTestMode: false,
  });

  useEffect(() => {
    setLoading(true);
    companyApi
      .get()
      .then(({ data }) => {
        const c = (data ?? {}) as Record<string, unknown>;
        setForm({
          name: String(c?.name ?? ''),
          address: String(c?.address ?? ''),
          taxNumber: String(c?.taxNumber ?? ''),
          taxOffice: String(c?.taxOffice ?? ''),
          phone: String(c?.phone ?? ''),
          email: String(c?.email ?? ''),
          logoUrl: String(c?.logoUrl ?? ''),
          website: String(c?.website ?? ''),
          ntgsmUsername: String(c?.ntgsmUsername ?? ''),
          ntgsmPassword: String(c?.ntgsmPassword ?? ''),
          ntgsmOriginator: String(c?.ntgsmOriginator ?? ''),
          ntgsmApiUrl: String(c?.ntgsmApiUrl ?? ''),
          paytrMerchantId: String(c?.paytrMerchantId ?? ''),
          paytrMerchantKey: String(c?.paytrMerchantKey ?? ''),
          paytrMerchantSalt: String(c?.paytrMerchantSalt ?? ''),
          paytrTestMode: Boolean(c?.paytrTestMode),
        });
      })
      .catch(() => toast.error('Ayarlar yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  const saveFirma = (e: React.FormEvent) => {
    e.preventDefault();
    setSaving('firma');
    companyApi
      .update({
        name: form.name.trim() || undefined,
        address: form.address.trim() || undefined,
        taxNumber: form.taxNumber.trim() || undefined,
        taxOffice: form.taxOffice.trim() || undefined,
        phone: form.phone.trim() || undefined,
        email: form.email.trim() || undefined,
        logoUrl: form.logoUrl.trim() || undefined,
        website: form.website.trim() || undefined,
      })
      .then(() => toast.success('Firma bilgileri kaydedildi'))
      .catch(() => toast.error('Firma bilgileri kaydedilemedi'))
      .finally(() => setSaving(null));
  };

  const saveNtgsm = (e: React.FormEvent) => {
    e.preventDefault();
    setSaving('ntgsm');
    companyApi
      .update({
        ntgsmUsername: form.ntgsmUsername.trim() || undefined,
        ntgsmPassword: form.ntgsmPassword || undefined,
        ntgsmOriginator: form.ntgsmOriginator.trim() || undefined,
        ntgsmApiUrl: form.ntgsmApiUrl.trim() || undefined,
      })
      .then(() => toast.success('NTGSM SMS API ayarları kaydedildi'))
      .catch(() => toast.error('NTGSM ayarları kaydedilemedi'))
      .finally(() => setSaving(null));
  };

  const savePaytr = (e: React.FormEvent) => {
    e.preventDefault();
    setSaving('paytr');
    companyApi
      .update({
        paytrMerchantId: form.paytrMerchantId.trim() || undefined,
        paytrMerchantKey: form.paytrMerchantKey || undefined,
        paytrMerchantSalt: form.paytrMerchantSalt || undefined,
        paytrTestMode: form.paytrTestMode,
      })
      .then(() => toast.success('PayTR ayarları kaydedildi'))
      .catch(() => toast.error('PayTR ayarları kaydedilemedi'))
      .finally(() => setSaving(null));
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-8">
      <PageHeader title="Ayarlar" description="Firma, SMS ve ödeme ayarları" icon={BuildingOffice2Icon} />

      <Tab.Group>
        <Tab.List className="flex gap-1 p-1 mb-6 rounded-xl bg-zinc-100 dark:bg-zinc-800 max-w-2xl">
          {tabs.map((tab) => (
            <Tab
              key={tab.key}
              className={({ selected }) =>
                `flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-zinc-100 ${
                  selected
                    ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-sm'
                    : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-200/60 dark:hover:bg-zinc-600'
                }`
              }
            >
              <tab.icon className="w-5 h-5 shrink-0" />
              {tab.name}
            </Tab>
          ))}
        </Tab.List>

        <Tab.Panels>
          {/* Firma Bilgileri */}
          <Tab.Panel>
            <div className="max-w-2xl">
              <form onSubmit={saveFirma} className="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 shadow-sm p-6 space-y-4">
                <h2 className="text-lg font-semibold text-slate-800 dark:text-white mb-4">Firma Bilgileri</h2>
                <p className="text-sm text-slate-500 dark:text-zinc-400 mb-4">Şirket adı, adres, vergi bilgileri ve iletişim bilgilerinizi buradan güncelleyebilirsiniz.</p>
                <div>
                  <label className={labelClass}>Firma / Uygulama adı *</label>
                  <input
                    type="text"
                    required
                    value={form.name}
                    onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                    className={inputClass}
                    placeholder="Sidebar ve sayfa başlığında görünür"
                  />
                  <p className="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Bu ad sidebar ve tarayıcı sekmesinde uygulama adı olarak kullanılır.</p>
                </div>
                <div>
                  <label className={labelClass}>Adres</label>
                  <textarea
                    rows={2}
                    value={form.address}
                    onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                    className={inputClass}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className={labelClass}>Vergi no</label>
                    <input
                      type="text"
                      value={form.taxNumber}
                      onChange={(e) => setForm((f) => ({ ...f, taxNumber: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>Vergi dairesi</label>
                    <input
                      type="text"
                      value={form.taxOffice}
                      onChange={(e) => setForm((f) => ({ ...f, taxOffice: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className={labelClass}>Telefon</label>
                    <input
                      type="text"
                      value={form.phone}
                      onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>E-posta</label>
                    <input
                      type="email"
                      value={form.email}
                      onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                </div>
                <div>
                  <label className={labelClass}>Web sitesi</label>
                  <input
                    type="url"
                    value={form.website}
                    onChange={(e) => setForm((f) => ({ ...f, website: e.target.value }))}
                    className={inputClass}
                  />
                </div>
                <div>
                  <label className={labelClass}>Firma logosu</label>
                  {form.logoUrl && (
                    <div className="mb-2 flex items-center gap-3">
                      <img src={form.logoUrl.startsWith('http') ? form.logoUrl : `${import.meta.env.VITE_API_URL || ''}${form.logoUrl.startsWith('/') ? '' : '/'}${form.logoUrl}`} alt="Logo" className="h-12 object-contain border border-zinc-200 dark:border-zinc-600 rounded-lg" />
                      <button type="button" onClick={() => setForm((f) => ({ ...f, logoUrl: '' }))} className="text-sm text-red-600 hover:text-red-700">Kaldır</button>
                    </div>
                  )}
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    disabled={uploadingLogo}
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (!file) return;
                      setUploadingLogo(true);
                      uploadApi.images([file])
                        .then(({ data }) => {
                          const url = (data.urls ?? [])[0];
                          if (url) setForm((f) => ({ ...f, logoUrl: url }));
                        })
                        .catch(() => toast.error('Logo yüklenemedi'))
                        .finally(() => { setUploadingLogo(false); e.target.value = ''; });
                    }}
                    className="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-emerald-700 file:font-medium"
                  />
                  {uploadingLogo && <p className="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>}
                </div>
                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={saving !== null}
                    className="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  >
                    {saving === 'firma' ? 'Kaydediliyor...' : 'Firma Bilgilerini Kaydet'}
                  </button>
                </div>
              </form>
            </div>
          </Tab.Panel>

          {/* NTGSM SMS API */}
          <Tab.Panel>
            <div className="max-w-2xl">
              <form onSubmit={saveNtgsm} className="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 shadow-sm p-6 space-y-4">
                <h2 className="text-lg font-semibold text-slate-800 dark:text-white mb-4">NTGSM SMS API</h2>
                <p className="text-sm text-slate-500 dark:text-zinc-400 mb-4">SMS bildirimleri için NTGSM API bilgilerinizi bu bölümden ayrı olarak yönetebilirsiniz.</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className={labelClass}>Kullanıcı adı</label>
                    <input
                      type="text"
                      value={form.ntgsmUsername}
                      onChange={(e) => setForm((f) => ({ ...f, ntgsmUsername: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>Şifre</label>
                    <input
                      type="password"
                      value={form.ntgsmPassword}
                      onChange={(e) => setForm((f) => ({ ...f, ntgsmPassword: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>Originator (gönderici adı)</label>
                    <input
                      type="text"
                      value={form.ntgsmOriginator}
                      onChange={(e) => setForm((f) => ({ ...f, ntgsmOriginator: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>API URL</label>
                    <input
                      type="url"
                      value={form.ntgsmApiUrl}
                      onChange={(e) => setForm((f) => ({ ...f, ntgsmApiUrl: e.target.value }))}
                      className={inputClass}
                      placeholder="https://..."
                    />
                  </div>
                </div>
                <div className="pt-2 flex flex-wrap gap-3">
                  <button
                    type="submit"
                    disabled={saving !== null}
                    className="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  >
                    {saving === 'ntgsm' ? 'Kaydediliyor...' : 'SMS Ayarlarını Kaydet'}
                  </button>
                  <div className="flex items-center gap-2">
                    <input
                      type="text"
                      value={testSmsPhone}
                      onChange={(e) => setTestSmsPhone(e.target.value)}
                      placeholder="05xx xxx xx xx"
                      className="rounded-lg border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-sm w-40 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100"
                    />
                    <button
                      type="button"
                      disabled={sendingSms || !testSmsPhone.trim()}
                      onClick={() => {
                        setSendingSms(true);
                        smsApi.test({ phone: testSmsPhone.trim() })
                          .then(({ data }) => {
                            if (data?.ok) toast.success('Test SMS gönderildi.');
                            else toast.error(data?.message || 'SMS gönderilemedi.');
                          })
                          .catch(() => toast.error('SMS gönderilemedi.'))
                          .finally(() => setSendingSms(false));
                      }}
                      className="rounded-lg px-4 py-2 text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50"
                    >
                      {sendingSms ? 'Gönderiliyor...' : 'Test SMS Gönder'}
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </Tab.Panel>

          {/* Sanal Pos (PayTR) */}
          <Tab.Panel>
            <div className="max-w-2xl">
              <form onSubmit={savePaytr} className="bg-white dark:bg-zinc-800 rounded-xl border border-slate-200 dark:border-zinc-700 shadow-sm p-6 space-y-4">
                <h2 className="text-lg font-semibold text-slate-800 dark:text-white mb-4">Sanal Pos (PayTR)</h2>
                <p className="text-sm text-slate-500 dark:text-zinc-400 mb-4">Kredi kartı ile ödeme almak için PayTR sanal pos bilgilerinizi girin. Menüdeki &quot;Ödeme Al&quot; sayfasından kullanılır.</p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className={labelClass}>Merchant ID</label>
                    <input
                      type="text"
                      value={form.paytrMerchantId}
                      onChange={(e) => setForm((f) => ({ ...f, paytrMerchantId: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>Merchant Key</label>
                    <input
                      type="password"
                      value={form.paytrMerchantKey}
                      onChange={(e) => setForm((f) => ({ ...f, paytrMerchantKey: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div>
                    <label className={labelClass}>Merchant Salt</label>
                    <input
                      type="password"
                      value={form.paytrMerchantSalt}
                      onChange={(e) => setForm((f) => ({ ...f, paytrMerchantSalt: e.target.value }))}
                      className={inputClass}
                    />
                  </div>
                  <div className="sm:col-span-2 flex items-center gap-2 pt-2">
                    <input
                      type="checkbox"
                      id="paytrTest"
                      checked={form.paytrTestMode}
                      onChange={(e) => setForm((f) => ({ ...f, paytrTestMode: e.target.checked }))}
                      className="rounded border-slate-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500 dark:bg-zinc-700"
                    />
                    <label htmlFor="paytrTest" className={`${labelClass} mb-0`}>Test modu</label>
                  </div>
                </div>
                <div className="pt-2">
                  <button
                    type="submit"
                    disabled={saving !== null}
                    className="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  >
                    {saving === 'paytr' ? 'Kaydediliyor...' : 'PayTR Ayarlarını Kaydet'}
                  </button>
                </div>
              </form>
            </div>
          </Tab.Panel>
        </Tab.Panels>
      </Tab.Group>
    </div>
  );
}
