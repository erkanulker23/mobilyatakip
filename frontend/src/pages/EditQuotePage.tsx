import { useEffect, useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { DocumentTextIcon } from '@heroicons/react/24/outline';
import { customersApi } from '../services/api/customersApi';
import { productsApi } from '../services/api/productsApi';
import { personnelApi } from '../services/api/personnelApi';
import { quotesApi } from '../services/api/quotesApi';
import { PageHeader, Card } from '../components/ui';
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
  productId: string;
  productName: string;
  unitPrice: string;
  quantity: string;
  lineDiscountPercent: string;
  lineDiscountAmount: string;
  kdvRate: string;
}

interface QuoteDetail {
  id: string;
  quoteNumber: string;
  revision: number;
  customerId: string;
  personnelId?: string;
  customerSource?: string;
  generalDiscountPercent?: number;
  generalDiscountAmount?: number;
  validUntil?: string;
  notes?: string;
  items?: Array<{
    id: string;
    productId: string;
    product?: { name: string; sku?: string };
    unitPrice: number;
    quantity: number;
    lineDiscountPercent?: number;
    lineDiscountAmount?: number;
    kdvRate?: number;
  }>;
}

export default function EditQuotePage() {
  const { id } = useParams<{ id: string }>();
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
    if (!id) return;
    Promise.all([
      quotesApi.get(id),
      customersApi.list(),
      productsApi.list({ limit: 100 }),
      personnelApi.list(),
    ])
      .then(([quoteRes, custRes, prodRes, persRes]) => {
        const quote = (quoteRes as { data: QuoteDetail }).data;
        const custData = (custRes.data as { data?: unknown[] })?.data;
        const prodData = (prodRes.data as { data?: unknown[] })?.data;
        const persData = (persRes.data as { data?: unknown[] })?.data;
        setCustomers(Array.isArray(custData) ? custData as CustomerOption[] : []);
        setProducts(Array.isArray(prodData) ? prodData as ProductOption[] : []);
        setPersonnel(Array.isArray(persData) ? persData as PersonnelOption[] : []);
        setCustomerId(quote.customerId ?? '');
        setCustomerSource(quote.customerSource ?? '');
        setPersonnelId(quote.personnelId ?? '');
        setGeneralDiscountPercent(String(quote.generalDiscountPercent ?? 0));
        setGeneralDiscountAmount(String(quote.generalDiscountAmount ?? 0));
        setValidUntil(quote.validUntil ? String(quote.validUntil).slice(0, 10) : '');
        setNotes(quote.notes ?? '');
        setRows(
          (quote.items ?? []).map((item) => ({
            productId: item.productId,
            productName: (item.product as { name?: string })?.name ?? '',
            unitPrice: String(item.unitPrice ?? 0),
            quantity: String(item.quantity ?? 1),
            lineDiscountPercent: String(item.lineDiscountPercent ?? 0),
            lineDiscountAmount: String(item.lineDiscountAmount ?? 0),
            kdvRate: String(item.kdvRate ?? 18),
          }))
        );
      })
      .catch(() => toast.error('Teklif veya veriler yüklenemedi'))
      .finally(() => setLoading(false));
  }, [id]);

  const addRow = () => {
    setRows((r) => [
      ...r,
      {
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

  const filteredProducts = productSearch.trim()
    ? products.filter(
        (p) =>
          p.name.toLowerCase().includes(productSearch.trim().toLowerCase()) ||
          (p.sku ?? '').toLowerCase().includes(productSearch.trim().toLowerCase())
      )
    : products;

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

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!id) return;
    if (!customerId) {
      toast.error('Müşteri seçiniz.');
      return;
    }
    const validRows = rows.filter((r) => r.productId && r.quantity && parseFloat(r.quantity) > 0);
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
        unitPrice: parseFloat(r.unitPrice) || 0,
        quantity: parseInt(r.quantity, 10) || 1,
        lineDiscountPercent: parseFloat(r.lineDiscountPercent) || 0,
        lineDiscountAmount: parseFloat(r.lineDiscountAmount) || 0,
        kdvRate: parseFloat(r.kdvRate) ?? 18,
      })),
      generalDiscountPercent: parseFloat(generalDiscountPercent) || 0,
      generalDiscountAmount: parseFloat(generalDiscountAmount) || 0,
      validUntil: validUntil || undefined,
      notes: notes.trim() || undefined,
    };
    quotesApi
      .newRevision(id, payload)
      .then(() => {
        toast.success('Teklif güncellendi (yeni revizyon).');
        navigate(ROUTES.teklif(id));
      })
      .catch((err) => toast.error(err.response?.data?.message ?? 'Güncelleme başarısız.'))
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
        title="Teklifi düzenle"
        description="Değişiklikler yeni revizyon olarak kaydedilir"
        icon={DocumentTextIcon}
        action={<Link to={ROUTES.teklif(id!)} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">← Teklif detayı</Link>}
      />
      <form onSubmit={handleSubmit} className="space-y-6 max-w-4xl">
        <Card>
          <h2 className="text-lg font-semibold text-zinc-800 mb-4">Müşteri</h2>
          <div>
            <label className="block text-sm font-medium text-zinc-700">Müşteri *</label>
            <select
              required
              value={customerId}
              onChange={(e) => setCustomerId(e.target.value)}
              className="mt-1.5 block w-full max-w-md rounded-xl border border-zinc-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            >
              <option value="">Seçiniz</option>
              {customers.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>
          <div className="mt-4">
            <label className="block text-sm font-medium text-zinc-700">Müşteri kaynağı</label>
            <select
              value={customerSource}
              onChange={(e) => setCustomerSource(e.target.value)}
              className="mt-1.5 block w-full max-w-md rounded-xl border border-zinc-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
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
          <div className="mt-4">
            <label className="block text-sm font-medium text-zinc-700">Personel</label>
            <select
              value={personnelId}
              onChange={(e) => setPersonnelId(e.target.value)}
              className="mt-1.5 block w-full max-w-md rounded-xl border border-zinc-300 px-3 py-2.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            >
              <option value="">Seçiniz</option>
              {personnel.map((p) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
          </div>
        </Card>

        <Card>
          <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 className="text-lg font-semibold text-zinc-800">Kalemler</h2>
            <div className="flex flex-wrap items-center gap-2">
              <input
                type="search"
                value={productSearch}
                onChange={(e) => setProductSearch(e.target.value)}
                placeholder="Ürün ara (ad veya SKU)..."
                className="block max-w-xs rounded-xl border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                aria-label="Ürün ara"
              />
              <button type="button" onClick={addRow} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                + Satır Ekle
              </button>
            </div>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50">
                <tr>
                  <th className="px-3 py-2 text-left text-xs font-medium text-zinc-500 uppercase">Ürün</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase">Birim Fiyat</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase">Adet</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase">İndirim %</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase">İndirim ₺</th>
                  <th className="px-3 py-2 text-right text-xs font-medium text-zinc-500 uppercase">KDV %</th>
                  <th className="px-3 py-2 w-10" />
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200">
                {rows.map((row, index) => (
                  <tr key={index}>
                    <td className="px-3 py-2">
                      <select
                        required
                        value={row.productId}
                        onChange={(e) => updateRow(index, 'productId', e.target.value)}
                        className="block w-full min-w-[180px] rounded-xl border border-zinc-300 px-2 py-1.5 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                      >
                        <option value="">Ürün seçin</option>
                        {filteredProducts.map((p) => (
                          <option key={p.id} value={p.id}>{p.name} {p.sku ? `(${p.sku})` : ''}</option>
                        ))}
                      </select>
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.unitPrice}
                        onChange={(e) => updateRow(index, 'unitPrice', e.target.value)}
                        className="block w-24 text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm ml-auto focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        min="1"
                        value={row.quantity}
                        onChange={(e) => updateRow(index, 'quantity', e.target.value)}
                        className="block w-20 text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm ml-auto focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.lineDiscountPercent}
                        onChange={(e) => updateRow(index, 'lineDiscountPercent', e.target.value)}
                        className="block w-20 text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm ml-auto focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.lineDiscountAmount}
                        onChange={(e) => updateRow(index, 'lineDiscountAmount', e.target.value)}
                        className="block w-20 text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm ml-auto focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.kdvRate}
                        onChange={(e) => updateRow(index, 'kdvRate', e.target.value)}
                        className="block w-16 text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm ml-auto focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <button type="button" onClick={() => removeRow(index)} className="text-red-600 hover:text-red-800 text-sm">
                        Sil
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {rows.length === 0 && <p className="py-4 text-center text-zinc-500 text-sm">Satır ekleyin.</p>}
          </div>
        </Card>

        <Card>
          <h2 className="text-lg font-semibold text-zinc-800 mb-4">Genel indirim & notlar</h2>
          <div className="grid gap-4 sm:grid-cols-2 mb-4">
            <div>
              <label className="block text-sm font-medium text-zinc-700">Genel indirim %</label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={generalDiscountPercent}
                onChange={(e) => setGeneralDiscountPercent(e.target.value)}
                className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-zinc-700">Genel indirim tutar (₺)</label>
              <input
                type="number"
                step="0.01"
                min="0"
                value={generalDiscountAmount}
                onChange={(e) => setGeneralDiscountAmount(e.target.value)}
                className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
              />
            </div>
          </div>
          <div className="mb-4">
            <label className="block text-sm font-medium text-zinc-700">Geçerlilik tarihi</label>
            <input
              type="date"
              value={validUntil}
              onChange={(e) => setValidUntil(e.target.value)}
              className="mt-1.5 block w-full max-w-xs rounded-xl border border-zinc-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-zinc-700">Notlar</label>
            <textarea
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
            />
          </div>
        </Card>

        <div className="flex gap-3">
          <Link to={ROUTES.teklif(id!)} className="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
            İptal
          </Link>
          <button
            type="submit"
            disabled={saving}
            className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50"
          >
            {saving ? 'Kaydediliyor...' : 'Revizyon olarak kaydet'}
          </button>
        </div>
      </form>
    </div>
  );
}
