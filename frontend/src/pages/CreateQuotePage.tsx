import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import {
  DocumentTextIcon,
  UserCircleIcon,
  Squares2X2Icon,
  PlusIcon,
  TrashIcon,
  ArrowLeftIcon,
} from '@heroicons/react/24/outline';
import { customersApi } from '../services/api/customersApi';
import { productsApi } from '../services/api/productsApi';
import { personnelApi } from '../services/api/personnelApi';
import { quotesApi } from '../services/api/quotesApi';
import { PageHeader, Card, CardTitle, Button, EmptyState } from '../components/ui';
import toast from 'react-hot-toast';

interface CustomerOption {
  id: string;
  name: string;
}

interface PersonnelOption {
  id: string;
  name: string;
}

interface ProductOption {
  id: string;
  name: string;
  sku?: string;
  unitPrice: number;
  kdvRate?: number;
}

interface QuoteRow {
  id: string;
  productId: string;
  productName: string;
  unitPrice: string;
  quantity: string;
  lineDiscountPercent: string;
  lineDiscountAmount: string;
  kdvRate: string;
}

const inputBase =
  'w-full rounded-xl border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 transition focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 dark:focus:ring-emerald-400/30 dark:focus:border-emerald-400';

const selectBase = `${inputBase} appearance-none cursor-pointer pr-9 bg-[length:1.25rem] bg-[right_0.5rem_center] bg-no-repeat`;

export default function CreateQuotePage() {
  const navigate = useNavigate();
  const [customers, setCustomers] = useState<CustomerOption[]>([]);
  const [products, setProducts] = useState<ProductOption[]>([]);
  const [personnel, setPersonnel] = useState<PersonnelOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [customerId, setCustomerId] = useState('');
  const [customerSource, setCustomerSource] = useState('');
  const [personnelId, setPersonnelId] = useState('');
  const [generalDiscountPercent, setGeneralDiscountPercent] = useState('');
  const [generalDiscountAmount, setGeneralDiscountAmount] = useState('');
  const [validUntil, setValidUntil] = useState('');
  const [notes, setNotes] = useState('');
  const [rows, setRows] = useState<QuoteRow[]>([]);
  const [productSearch, setProductSearch] = useState('');

  useEffect(() => {
    Promise.all([
      customersApi.list(),
      productsApi.list({ limit: 100 }),
      personnelApi.list(),
    ])
      .then(([custRes, prodRes, persRes]) => {
        const custData = (custRes.data as { data?: unknown[] })?.data;
        const prodData = (prodRes.data as { data?: unknown[] })?.data;
        const persData = (persRes.data as { data?: unknown[] })?.data;
        setCustomers(Array.isArray(custData) ? (custData as CustomerOption[]) : []);
        setProducts(Array.isArray(prodData) ? (prodData as ProductOption[]) : []);
        setPersonnel(Array.isArray(persData) ? (persData as PersonnelOption[]) : []);
      })
      .catch(() => toast.error('Veriler yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  const addRow = () => {
    setRows((r) => [
      ...r,
      {
        id: crypto.randomUUID(),
        productId: '',
        productName: '',
        unitPrice: '',
        quantity: '1',
        lineDiscountPercent: '0',
        lineDiscountAmount: '0',
        kdvRate: '18',
      },
    ]);
  };

  const removeRow = (index: number) => {
    setRows((r) => r.filter((_, i) => i !== index));
  };

  const updateRow = (index: number, field: keyof QuoteRow, value: string) => {
    setRows((r) => {
      const next = [...r];
      const row = { ...next[index] };
      (row as Record<string, string>)[field] = value;
      if (field === 'productId') {
        const product = products.find((p) => p.id === value);
        if (product) {
          row.productName = product.name;
          row.unitPrice = String(product.unitPrice ?? '');
          row.kdvRate = String(product.kdvRate ?? 18);
        }
      }
      next[index] = row;
      return next;
    });
  };

  const filteredProducts = productSearch.trim()
    ? products.filter(
        (p) =>
          p.name.toLowerCase().includes(productSearch.trim().toLowerCase()) ||
          (p.sku ?? '').toLowerCase().includes(productSearch.trim().toLowerCase())
      )
    : products;

  const validRows = rows.filter(
    (r) => r.productId && r.quantity && Number.parseFloat(r.quantity) > 0
  );
  let subtotal = 0;
  for (const r of validRows) {
    const lineTotal =
      (Number.parseFloat(r.unitPrice) || 0) *
        (Number.parseInt(r.quantity, 10) || 1) *
        (1 - (Number.parseFloat(r.lineDiscountPercent) || 0) / 100) -
      (Number.parseFloat(r.lineDiscountAmount) || 0);
    subtotal += Math.max(0, lineTotal);
  }
  const discP = (Number.parseFloat(generalDiscountPercent) || 0) / 100;
  const discA = Number.parseFloat(generalDiscountAmount) || 0;
  const afterDisc = Math.max(0, subtotal - subtotal * discP - discA);

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!customerId) {
      toast.error('Müşteri seçiniz.');
      return;
    }
    if (validRows.length === 0) {
      toast.error('En az bir ürün satırı ekleyiniz.');
      return;
    }
    setSaving(true);
    const payload = {
      customerId,
      customerSource: customerSource || undefined,
      personnelId: personnelId || undefined,
      items: validRows.map((r) => ({
        productId: r.productId,
        unitPrice: Number.parseFloat(r.unitPrice) || 0,
        quantity: Number.parseInt(r.quantity, 10) || 1,
        lineDiscountPercent: Number.parseFloat(r.lineDiscountPercent) || 0,
        lineDiscountAmount: Number.parseFloat(r.lineDiscountAmount) || 0,
        kdvRate: Number.parseFloat(r.kdvRate) ?? 18,
      })),
      generalDiscountPercent: Number.parseFloat(generalDiscountPercent) || 0,
      generalDiscountAmount: Number.parseFloat(generalDiscountAmount) || 0,
      validUntil: validUntil || undefined,
      notes: notes.trim() || undefined,
    };
    quotesApi
      .create(payload)
      .then(() => {
        toast.success('Teklif oluşturuldu.');
        navigate(ROUTES.teklifler);
      })
      .catch(() => toast.error('Teklif oluşturulamadı.'))
      .finally(() => setSaving(false));
  };

  if (loading) {
    return (
      <div className="flex min-h-[320px] items-center justify-center">
        <div className="flex flex-col items-center gap-3">
          <div className="h-10 w-10 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent" />
          <p className="text-sm text-zinc-500 dark:text-zinc-400">Yükleniyor...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-8 pb-8">
      <PageHeader
        title="Yeni Teklif"
        description="Müşteri ve ürünleri seçerek teklif oluşturun"
        icon={DocumentTextIcon}
        action={
          <Link to={ROUTES.teklifler}>
            <Button variant="secondary" icon={ArrowLeftIcon}>
              Teklifler
            </Button>
          </Link>
        }
      />

      <form onSubmit={handleSubmit} className="space-y-8 max-w-4xl">
        {/* Müşteri */}
        <Card>
          <CardTitle icon={UserCircleIcon}>Müşteri bilgileri</CardTitle>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <label htmlFor="customerId" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Müşteri *
              </label>
              <select
                id="customerId"
                required
                value={customerId}
                onChange={(e) => setCustomerId(e.target.value)}
                className={selectBase}
                style={{
                  backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2371717a'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E")`,
                }}
              >
                <option value="">Seçiniz</option>
                {customers.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="customerSource" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Müşteri kaynağı
              </label>
              <select
                id="customerSource"
                value={customerSource}
                onChange={(e) => setCustomerSource(e.target.value)}
                className={selectBase}
                style={{
                  backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2371717a'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E")`,
                }}
              >
                <option value="">Seçiniz</option>
                <option value="web">Web / İnternet</option>
                <option value="telefon">Telefon</option>
                <option value="magaza">Mağaza</option>
                <option value="referans">Referans</option>
                <option value="sosyal_medya">Sosyal Medya</option>
                <option value="diger">Diğer</option>
              </select>
            </div>
            <div>
              <label htmlFor="personnelId" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Teklifi oluşturan
              </label>
              <select
                id="personnelId"
                value={personnelId}
                onChange={(e) => setPersonnelId(e.target.value)}
                className={selectBase}
                style={{
                  backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2371717a'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E")`,
                }}
              >
                <option value="">Seçiniz</option>
                {personnel.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </Card>

        {/* Kalemler */}
        <Card padding="none">
          <div className="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
            <CardTitle icon={Squares2X2Icon}>Ürün kalemleri</CardTitle>
            <div className="flex flex-wrap items-center gap-3">
              <input
                type="search"
                value={productSearch}
                onChange={(e) => setProductSearch(e.target.value)}
                placeholder="Ürün ara (ad veya SKU)..."
                className={`${inputBase} max-w-xs py-2`}
                aria-label="Ürün ara"
              />
              <Button type="button" variant="primary" icon={PlusIcon} onClick={addRow}>
                Satır ekle
              </Button>
            </div>
          </div>
          {rows.length === 0 ? (
            <div className="px-6 pb-6">
              <EmptyState
                icon={Squares2X2Icon}
                title="Henüz ürün eklenmedi"
                description="Teklife ürün eklemek için aşağıdaki butona tıklayın."
                action={
                  <Button type="button" variant="primary" icon={PlusIcon} onClick={addRow}>
                    İlk satırı ekle
                  </Button>
                }
              />
            </div>
          ) : (
            <div className="overflow-x-auto -mx-px">
              <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead className="bg-zinc-50 dark:bg-zinc-800/80 sticky top-0 z-10">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                      Ürün
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 w-28">
                      Birim Fiyat
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 w-20">
                      Adet
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 w-24">
                      İnd. %
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 w-24">
                      İnd. ₺
                    </th>
                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 w-20">
                      KDV %
                    </th>
                    <th className="px-4 py-3 w-14" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-100 dark:divide-zinc-700/70 bg-white dark:bg-zinc-800/50">
                  {rows.map((row, index) => (
                    <tr
                      key={row.id}
                      className="transition-colors hover:bg-zinc-50/80 dark:hover:bg-zinc-700/30"
                    >
                      <td className="px-4 py-2.5">
                        <select
                          required
                          value={row.productId}
                          onChange={(e) => updateRow(index, 'productId', e.target.value)}
                          className={`${selectBase} min-w-[200px] py-2`}
                          style={{
                            backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2371717a'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E")`,
                          }}
                        >
                        <option value="">Ürün seçin</option>
                        {filteredProducts.map((p) => (
                          <option key={p.id} value={p.id}>
                            {p.name} {p.sku ? `(${p.sku})` : ''}
                          </option>
                        ))}
                        </select>
                      </td>
                      <td className="px-4 py-2.5">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={row.unitPrice}
                          onChange={(e) => updateRow(index, 'unitPrice', e.target.value)}
                          className={`${inputBase} w-full text-right max-w-[7rem] ml-auto block`}
                        />
                      </td>
                      <td className="px-4 py-2.5">
                        <input
                          type="number"
                          min="1"
                          value={row.quantity}
                          onChange={(e) => updateRow(index, 'quantity', e.target.value)}
                          className={`${inputBase} w-full text-right max-w-[5rem] ml-auto block`}
                        />
                      </td>
                      <td className="px-4 py-2.5">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={row.lineDiscountPercent}
                          onChange={(e) =>
                            updateRow(index, 'lineDiscountPercent', e.target.value)
                          }
                          className={`${inputBase} w-full text-right max-w-[5rem] ml-auto block`}
                        />
                      </td>
                      <td className="px-4 py-2.5">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={row.lineDiscountAmount}
                          onChange={(e) =>
                            updateRow(index, 'lineDiscountAmount', e.target.value)
                          }
                          className={`${inputBase} w-full text-right max-w-[5rem] ml-auto block`}
                        />
                      </td>
                      <td className="px-4 py-2.5">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={row.kdvRate}
                          onChange={(e) => updateRow(index, 'kdvRate', e.target.value)}
                          className={`${inputBase} w-full text-right max-w-[4rem] ml-auto block`}
                        />
                      </td>
                      <td className="px-4 py-2.5">
                        <button
                          type="button"
                          onClick={() => removeRow(index)}
                          className="flex h-9 w-9 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                          title="Satırı sil"
                        >
                          <TrashIcon className="h-5 w-5" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Card>

        {/* Genel indirim & notlar */}
        <Card>
          <CardTitle icon={DocumentTextIcon}>Genel indirim ve notlar</CardTitle>
          <div className="grid gap-5 sm:grid-cols-2 mb-5">
            <div>
              <label htmlFor="generalDiscountPercent" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Genel indirim %
              </label>
              <input
                id="generalDiscountPercent"
                type="number"
                step="0.01"
                min="0"
                value={generalDiscountPercent}
                onChange={(e) => setGeneralDiscountPercent(e.target.value)}
                className={inputBase}
                placeholder="0"
              />
            </div>
            <div>
              <label htmlFor="generalDiscountAmount" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Genel indirim tutar (₺)
              </label>
              <input
                id="generalDiscountAmount"
                type="number"
                step="0.01"
                min="0"
                value={generalDiscountAmount}
                onChange={(e) => setGeneralDiscountAmount(e.target.value)}
                className={inputBase}
                placeholder="0"
              />
            </div>
          </div>

          <div className="rounded-xl border border-emerald-200/60 dark:border-emerald-800/50 bg-emerald-50/50 dark:bg-emerald-900/10 p-4 mb-5">
            <div className="space-y-1.5 text-sm">
              <div className="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span>Ara toplam</span>
                <span className="font-medium text-zinc-900 dark:text-white">
                  {subtotal.toFixed(2)} ₺
                </span>
              </div>
              <div className="flex justify-between text-zinc-600 dark:text-zinc-400">
                <span>İndirim (% + ₺)</span>
                <span>- {(subtotal * discP + discA).toFixed(2)} ₺</span>
              </div>
              <div className="flex justify-between pt-2 font-semibold text-zinc-900 dark:text-white border-t border-emerald-200/60 dark:border-emerald-800/50">
                <span>Genel toplam (KDV öncesi)</span>
                <span>{afterDisc.toFixed(2)} ₺</span>
              </div>
            </div>
          </div>

          <div className="grid gap-5 sm:grid-cols-2 mb-5">
            <div>
              <label htmlFor="validUntil" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Geçerlilik tarihi
              </label>
              <input
                id="validUntil"
                type="date"
                value={validUntil}
                onChange={(e) => setValidUntil(e.target.value)}
                className={inputBase}
              />
            </div>
          </div>
          <div>
            <label htmlFor="notes" className="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
              Notlar
            </label>
            <textarea
              id="notes"
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              className={`${inputBase} resize-y min-h-[80px]`}
              placeholder="Teklif ile ilgili notlar..."
            />
          </div>
        </Card>

        {/* Alt aksiyonlar */}
        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end sm:gap-3 pt-2">
          <Link to={ROUTES.teklifler} className="sm:order-2">
            <Button type="button" variant="secondary" className="w-full sm:w-auto">
              İptal
            </Button>
          </Link>
          <Button
            type="submit"
            disabled={saving}
            icon={DocumentTextIcon}
            className="w-full sm:w-auto sm:order-1"
          >
            {saving ? 'Oluşturuluyor...' : 'Teklif oluştur'}
          </Button>
        </div>
      </form>
    </div>
  );
}
