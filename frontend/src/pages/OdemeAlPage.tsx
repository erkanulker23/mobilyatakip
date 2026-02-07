import { useEffect, useRef, useState, Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { CreditCardIcon, BanknotesIcon, BuildingLibraryIcon } from '@heroicons/react/24/outline';
import { customerPaymentsApi } from '../services/api/customerPaymentsApi';
import { salesApi } from '../services/api/salesApi';
import { customersApi } from '../services/api/customersApi';
import { paymentsApi } from '../services/api/paymentsApi';
import { kasaApi } from '../services/api/kasaApi';
import { PageHeader, Card, Button } from '../components/ui';
import toast from 'react-hot-toast';

function PayTrIframe({ token, iframeUrl }: { token: string; iframeUrl: string }) {
  const formRef = useRef<HTMLFormElement>(null);
  useEffect(() => {
    formRef.current?.submit();
  }, [token, iframeUrl]);
  return (
    <Card className="p-6">
      <h2 className="text-lg font-semibold text-zinc-900 mb-4">Kart bilgilerini girin</h2>
      <div className="rounded-xl border border-zinc-200 overflow-hidden bg-white">
        <form ref={formRef} method="post" action={iframeUrl} target="paytr_iframe" className="hidden">
          <input type="hidden" name="paytr_token" value={token} />
        </form>
        <iframe title="PayTR Ödeme" name="paytr_iframe" className="w-full border-0" style={{ height: 500 }} />
      </div>
    </Card>
  );
}

interface DebtRow {
  customerId: string;
  balance: number;
  totalSales: number;
  totalPayments: number;
}

interface SaleOption {
  id: string;
  saleNumber?: string;
  grandTotal?: number;
  paidAmount?: number;
  customerId?: string;
  customer?: { name?: string; email?: string; id?: string };
}

interface KasaOption {
  id: string;
  name: string;
  type: string;
  bankName?: string;
}

type PaymentMethod = 'nakit' | 'kredi_karti' | 'havale';

export default function OdemeAlPage() {
  const [paytrActive, setPaytrActive] = useState(false);
  const [loading, setLoading] = useState(true);
  const [debts, setDebts] = useState<DebtRow[]>([]);
  const [sales, setSales] = useState<SaleOption[]>([]);
  const [customers, setCustomers] = useState<{ id: string; name: string }[]>([]);
  const [customerFilter, setCustomerFilter] = useState('');
  const [banks, setBanks] = useState<KasaOption[]>([]);
  const [cashKasas, setCashKasas] = useState<KasaOption[]>([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [selectedSaleId, setSelectedSaleId] = useState('');
  const [amount, setAmount] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>('nakit');
  const [selectedKasaId, setSelectedKasaId] = useState('');
  const [iframeToken, setIframeToken] = useState<{ token: string; iframeUrl: string } | null>(null);
  const [gettingToken, setGettingToken] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    let done = 0;
    let hadError = false;
    const timeout = window.setTimeout(() => {
      if (done < 4) {
        setLoading(false);
        if (done < 4) toast.error('Bazı veriler zaman aşımına uğradı.');
      }
    }, 8000);
    const checkDone = () => {
      done += 1;
      if (done >= 4) {
        clearTimeout(timeout);
        setLoading(false);
        if (hadError) toast.error('Bazı veriler yüklenemedi.');
      }
    };
    paymentsApi
      .paytrConfig()
      .then(({ data }) => setPaytrActive(Boolean(data?.paytrActive)))
      .catch(() => { setPaytrActive(false); hadError = true; })
      .finally(checkDone);
    customerPaymentsApi
      .withDebt()
      .then(({ data }) => setDebts(Array.isArray(data) ? data : []))
      .catch(() => { setDebts([]); hadError = true; })
      .finally(checkDone);
    salesApi
      .list()
      .then(({ data }) => {
        const res = data as { data?: unknown[] };
        setSales(Array.isArray(res?.data) ? (res.data as SaleOption[]) : []);
      })
      .catch(() => { setSales([]); hadError = true; })
      .finally(checkDone);
    customersApi
      .list({ limit: 500 })
      .then(({ data }) => {
        const res = data as { data?: unknown[] };
        setCustomers(Array.isArray(res?.data) ? (res.data as { id: string; name: string }[]) : []);
      })
      .catch(() => setCustomers([]));
    kasaApi
      .list()
      .then(({ data }) => {
        const list = Array.isArray(data) ? data : [];
        setBanks(list.filter((k: KasaOption) => k.type === 'banka'));
        setCashKasas(list.filter((k: KasaOption) => k.type === 'kasa'));
      })
      .catch(() => { setBanks([]); setCashKasas([]); })
      .finally(checkDone);
    return () => clearTimeout(timeout);
  }, []);

  const selectedSale = sales.find((s) => s.id === selectedSaleId);
  const unpaidForSale = selectedSale
    ? Number(selectedSale.grandTotal ?? 0) - Number(selectedSale.paidAmount ?? 0)
    : 0;
  const suggestedAmount = amount === '' ? unpaidForSale : Number(amount) || 0;

  const openModal = () => {
    setSelectedSaleId('');
    setCustomerFilter('');
    setAmount('');
    setPaymentMethod('nakit');
    setSelectedKasaId('');
    setIframeToken(null);
    setModalOpen(true);
  };

  const salesWithDebt = sales.filter((s) => {
    const custId = s.customerId ?? (s.customer as { id?: string })?.id;
    const bal = custId ? debts.find((d) => d.customerId === custId)?.balance : 0;
    return (bal ?? 0) > 0 || (Number(s.grandTotal ?? 0) - Number(s.paidAmount ?? 0)) > 0;
  });
  const filteredSales = customerFilter
    ? salesWithDebt.filter((s) => (s.customerId ?? (s.customer as { id?: string })?.id) === customerFilter)
    : salesWithDebt;

  const closeModal = () => {
    if (!submitting && !gettingToken) setModalOpen(false);
  };

  const handleSubmitNakitOrHavale = async () => {
    if (!selectedSale) {
      toast.error('Satış seçiniz.');
      return;
    }
    const payAmount = suggestedAmount;
    if (payAmount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    if (paymentMethod === 'havale' && !selectedKasaId) {
      toast.error('Havale için banka seçiniz.');
      return;
    }
    if (paymentMethod === 'nakit' && !selectedKasaId) {
      toast.error('Nakit tahsilat için kasa seçiniz.');
      return;
    }
    setSubmitting(true);
    const customerId = selectedSale.customerId ?? (selectedSale.customer as { id?: string })?.id;
    if (!customerId) {
      toast.error('Müşteri bilgisi bulunamadı.');
      setSubmitting(false);
      return;
    }
    const today = new Date().toISOString().slice(0, 10);
    const paymentType = paymentMethod === 'havale' ? 'havale' : 'nakit';
    const notes = paymentMethod === 'havale' && selectedKasaId
      ? `Havale - ${banks.find((b) => b.id === selectedKasaId)?.name ?? ''}`
      : paymentMethod === 'nakit' && selectedKasaId
        ? `Nakit - ${cashKasas.find((k) => k.id === selectedKasaId)?.name ?? ''}`
        : undefined;
    try {
      await customerPaymentsApi.create({
        customerId,
        amount: payAmount,
        paymentDate: today,
        paymentType,
        saleId: selectedSale.id,
        notes,
        ...(selectedKasaId ? { kasaId: selectedKasaId } : {}),
      });
      toast.success(paymentMethod === 'havale' ? 'Havale ödemesi kaydedildi.' : 'Nakit tahsilat kasaya giriş olarak kaydedildi.');
      setModalOpen(false);
      customerPaymentsApi.withDebt().then(({ data }) => setDebts(Array.isArray(data) ? data : [])).catch(() => {});
    } catch {
      toast.error('Ödeme kaydedilemedi.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleGetPayTrToken = async () => {
    if (!selectedSale) {
      toast.error('Satış seçiniz.');
      return;
    }
    const payAmount = suggestedAmount;
    if (payAmount <= 0) {
      toast.error('Geçerli bir tutar giriniz.');
      return;
    }
    const email = (selectedSale.customer as { email?: string })?.email ?? 'musteri@email.com';
    const name = (selectedSale.customer as { name?: string })?.name ?? 'Müşteri';
    setGettingToken(true);
    setIframeToken(null);
    try {
      const base = window.location.origin;
      const { data } = await paymentsApi.paytrToken({
        merchantOid: `SAT-${selectedSale.saleNumber ?? selectedSale.id.slice(0, 8)}-${Date.now()}`,
        amountKurus: Math.round(payAmount * 100),
        customerEmail: email,
        customerName: name,
        successUrl: `${base}/odeme-al?ok=1`,
        failUrl: `${base}/odeme-al?fail=1`,
      });
      if (data?.token && data?.iframeUrl) {
        setIframeToken({ token: data.token, iframeUrl: data.iframeUrl });
      } else {
        toast.error('Ödeme sayfası açılamadı.');
      }
    } catch (e: unknown) {
      const res = (e as { response?: { data?: { message?: string | string[] } } })?.response?.data?.message;
      toast.error(Array.isArray(res) ? res[0] : res || 'Token alınamadı.');
    } finally {
      setGettingToken(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-24">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Ödeme Al"
        description="Müşteriden nakit, havale veya kredi kartı ile ödeme alın"
        icon={CreditCardIcon}
      />

      <Card className="p-6 max-w-xl">
        <h2 className="text-lg font-semibold text-zinc-900 mb-2">Müşteri'den ödeme al</h2>
        <p className="text-sm text-zinc-500 mb-4">Satış seçip ödeme türünü (Nakit, Kredi kartı veya Havale) belirleyerek tahsilat yapın.</p>
        <Button variant="primary" onClick={openModal} icon={CreditCardIcon}>
          Müşteri'den ödeme al
        </Button>
      </Card>

      <Transition appear show={modalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={closeModal}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 border-b border-zinc-100 pb-4">Müşteri'den ödeme al</Dialog.Title>

                  <div className="mt-4 space-y-4">
                    <div>
                      <label htmlFor="odeme-customer" className="block text-sm font-medium text-zinc-700 mb-1">Müşteriye göre filtrele</label>
                      <select
                        id="odeme-customer"
                        value={customerFilter}
                        onChange={(e) => {
                          setCustomerFilter(e.target.value);
                          setSelectedSaleId('');
                          setAmount('');
                        }}
                        className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Tüm müşteriler</option>
                        {customers.map((c) => (
                          <option key={c.id} value={c.id}>{c.name}</option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label htmlFor="odeme-sale" className="block text-sm font-medium text-zinc-700 mb-1">Satış</label>
                      <select
                        id="odeme-sale"
                        value={selectedSaleId}
                        onChange={(e) => {
                          setSelectedSaleId(e.target.value);
                          setAmount('');
                        }}
                        className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Satış seçin</option>
                        {filteredSales.map((s) => (
                          <option key={s.id} value={s.id}>
                            {s.saleNumber ?? s.id.slice(0, 8)} — {(s.customer as { name?: string })?.name ?? 'Müşteri'} — {(Number(s.grandTotal ?? 0) - Number(s.paidAmount ?? 0)).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                          </option>
                        ))}
                      </select>
                    </div>

                    {selectedSale && (
                      <div>
                        <label htmlFor="odeme-amount" className="block text-sm font-medium text-zinc-700 mb-1">Tutar (₺)</label>
                        <input
                          id="odeme-amount"
                          type="number"
                          step="0.01"
                          min="0"
                          value={amount}
                          onChange={(e) => setAmount(e.target.value)}
                          placeholder={unpaidForSale > 0 ? String(unpaidForSale.toFixed(2)) : ''}
                          className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        />
                        {unpaidForSale > 0 && !amount && (
                          <p className="mt-1 text-xs text-zinc-500">Kalan borç: {unpaidForSale.toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺</p>
                        )}
                      </div>
                    )}

                    <div>
                      <span className="block text-sm font-medium text-zinc-700 mb-2">Ödeme türü</span>
                      <div className="flex flex-wrap gap-2">
                        <button
                          type="button"
                          onClick={() => setPaymentMethod('nakit')}
                          className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            paymentMethod === 'nakit' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'
                          }`}
                        >
                          <BanknotesIcon className="w-5 h-5" />
                          Nakit
                        </button>
                        <button
                          type="button"
                          onClick={() => setPaymentMethod('kredi_karti')}
                          className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            paymentMethod === 'kredi_karti' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'
                          }`}
                          disabled={!paytrActive}
                          title={!paytrActive ? 'PayTR ayarları yapılmadı' : ''}
                        >
                          <CreditCardIcon className="w-5 h-5" />
                          Kredi kartı
                        </button>
                        <button
                          type="button"
                          onClick={() => setPaymentMethod('havale')}
                          className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition ${
                            paymentMethod === 'havale' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200'
                          }`}
                        >
                          <BuildingLibraryIcon className="w-5 h-5" />
                          Havale
                        </button>
                      </div>
                    </div>

                    {paymentMethod === 'nakit' && (
                      <div>
                        <label htmlFor="odeme-kasa-nakit" className="block text-sm font-medium text-zinc-700 mb-1">Tahsilatın yapılacağı kasa *</label>
                        <select
                          id="odeme-kasa-nakit"
                          value={selectedKasaId}
                          onChange={(e) => setSelectedKasaId(e.target.value)}
                          className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        >
                          <option value="">Kasa seçin</option>
                          {cashKasas.map((k) => (
                            <option key={k.id} value={k.id}>{k.name}</option>
                          ))}
                        </select>
                        <p className="mt-1 text-xs text-zinc-500">Tahsilat seçilen kasaya giriş olarak işlenir.</p>
                      </div>
                    )}
                    {paymentMethod === 'havale' && (
                      <div>
                        <label htmlFor="odeme-banka" className="block text-sm font-medium text-zinc-700 mb-1">Havale yapılan banka *</label>
                        <select
                          id="odeme-banka"
                          value={selectedKasaId}
                          onChange={(e) => setSelectedKasaId(e.target.value)}
                          className="block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-zinc-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        >
                          <option value="">Banka seçin</option>
                          {banks.map((b) => (
                            <option key={b.id} value={b.id}>{b.name}{b.bankName ? ` (${b.bankName})` : ''}</option>
                          ))}
                        </select>
                        <p className="mt-1 text-xs text-zinc-500">Tahsilat seçilen banka hesabına giriş olarak işlenir.</p>
                      </div>
                    )}
                  </div>

                  {!iframeToken && (
                    <div className="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4">
                      <button type="button" onClick={closeModal} disabled={submitting || gettingToken} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 disabled:opacity-50">
                        Kapat
                      </button>
                      {paymentMethod === 'nakit' || paymentMethod === 'havale' ? (
                        <button
                          type="button"
                          onClick={handleSubmitNakitOrHavale}
                          disabled={submitting || !selectedSale || suggestedAmount <= 0 || (paymentMethod === 'havale' && !selectedKasaId) || (paymentMethod === 'nakit' && !selectedKasaId)}
                          className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                          {submitting ? 'Kaydediliyor...' : paymentMethod === 'havale' ? 'Havale kaydet' : 'Nakit alındı'}
                        </button>
                      ) : (
                        <button
                          type="button"
                          onClick={handleGetPayTrToken}
                          disabled={gettingToken || !selectedSale || suggestedAmount <= 0 || !paytrActive}
                          className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                          {gettingToken ? 'Hazırlanıyor...' : 'Kredi kartı ile öde'}
                        </button>
                      )}
                    </div>
                  )}
                  {iframeToken && paymentMethod === 'kredi_karti' && (
                    <div className="mt-4 border-t border-zinc-100 pt-4">
                      <PayTrIframe token={iframeToken.token} iframeUrl={iframeToken.iframeUrl} />
                    </div>
                  )}
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

    </div>
  );
}
