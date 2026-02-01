import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ShoppingBagIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { suppliersApi } from '../services/api/suppliersApi';
import { productsApi } from '../services/api/productsApi';
import { warehousesApi } from '../services/api/warehousesApi';
import { purchasesApi } from '../services/api/purchasesApi';
import { stockApi } from '../services/api/stockApi';
import { kasaApi } from '../services/api/kasaApi';
import { PageHeader, Card } from '../components/ui';
import toast from 'react-hot-toast';

interface SupplierOption {
  id: string;
  name: string;
}

interface WarehouseOption {
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

interface PurchaseRow {
  productId: string;
  productName: string;
  unitPrice: string;
  quantity: string;
  kdvRate: string;
}

export default function CreatePurchasePage() {
  const navigate = useNavigate();
  const [suppliers, setSuppliers] = useState<SupplierOption[]>([]);
  const [products, setProducts] = useState<ProductOption[]>([]);
  const [warehouses, setWarehouses] = useState<WarehouseOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [supplierId, setSupplierId] = useState('');
  const [warehouseId, setWarehouseId] = useState('');
  const [purchaseDate, setPurchaseDate] = useState(new Date().toISOString().slice(0, 10));
  const [dueDate, setDueDate] = useState('');
  const [isReturn, setIsReturn] = useState(false);
  const [notes, setNotes] = useState('');
  const [rows, setRows] = useState<PurchaseRow[]>([]);
  const [warehouseStock, setWarehouseStock] = useState<Array<{ productName: string; sku?: string; quantity: number }>>([]);
  const [paymentType, setPaymentType] = useState<'veresiye' | 'nakit'>('veresiye');
  const [purchaseKasaId, setPurchaseKasaId] = useState('');
  const [kasaList, setKasaList] = useState<Array<{ id: string; name: string }>>([]);

  useEffect(() => {
    kasaApi.list().then(({ data }) => setKasaList(Array.isArray(data) ? data : [])).catch(() => setKasaList([]));
  }, []);

  useEffect(() => {
    Promise.all([suppliersApi.list(), warehousesApi.list()])
      .then(([supRes, whRes]) => {
        setSuppliers(Array.isArray(supRes.data) ? supRes.data : []);
        setWarehouses(Array.isArray(whRes.data) ? whRes.data : []);
      })
      .catch(() => toast.error('Veriler yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!supplierId) {
      setProducts([]);
      setRows((r) => r.map((row) => ({ ...row, productId: '', productName: '', unitPrice: '', quantity: row.quantity, kdvRate: row.kdvRate })));
      return;
    }
    productsApi
      .list({ supplierId })
      .then(({ data }) => {
        const list = Array.isArray(data) ? data : [];
        setProducts(list);
        setRows((r) =>
          r.map((row) => {
            if (!row.productId) return row;
            const stillExists = list.some((p: ProductOption) => p.id === row.productId);
            if (!stillExists) return { ...row, productId: '', productName: '', unitPrice: '', quantity: row.quantity, kdvRate: row.kdvRate };
            return row;
          })
        );
      })
      .catch(() => setProducts([]));
  }, [supplierId]);

  useEffect(() => {
    if (!warehouseId) {
      setWarehouseStock([]);
      return;
    }
    stockApi
      .byWarehouse(warehouseId)
      .then(({ data }) => {
        const list = Array.isArray(data) ? data : [];
        setWarehouseStock(
          list.map((s: { product?: { name?: string; sku?: string }; quantity?: number }) => ({
            productName: s.product?.name ?? '-',
            sku: s.product?.sku,
            quantity: Number(s.quantity ?? 0),
          })),
        );
      })
      .catch(() => setWarehouseStock([]));
  }, [warehouseId]);

  const addRow = () => {
    setRows((r) => [
      ...r,
      { productId: '', productName: '', unitPrice: '', quantity: '1', kdvRate: '18' },
    ]);
  };

  const removeRow = (index: number) => {
    setRows((r) => r.filter((_, i) => i !== index));
  };

  const updateRow = (index: number, field: keyof PurchaseRow, value: string) => {
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

  const validRows = rows.filter((r) => r.productId && r.quantity && Number(r.quantity) > 0);
  let subtotal = 0;
  let kdvTotal = 0;
  for (const r of validRows) {
    const lineNet = (Number(r.unitPrice) || 0) * (Number(r.quantity) || 1);
    const kdvRate = Number(r.kdvRate) ?? 18;
    subtotal += lineNet;
    kdvTotal += lineNet * (kdvRate / 100);
  }
  const grandTotal = subtotal + kdvTotal;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!supplierId || !warehouseId) {
      toast.error('Tedarikçi ve depo seçiniz.');
      return;
    }
    if (validRows.length === 0) {
      toast.error('En az bir ürün satırı ekleyiniz.');
      return;
    }
    if (paymentType === 'nakit' && !purchaseKasaId) {
      toast.error('Nakit alış için kasa seçiniz.');
      return;
    }
    setSaving(true);
    const payload = {
      supplierId,
      warehouseId,
      purchaseDate,
      dueDate: dueDate || undefined,
      isReturn,
      notes: notes.trim() || undefined,
      ...(paymentType === 'nakit' && purchaseKasaId ? { paymentType: 'nakit' as const, kasaId: purchaseKasaId } : {}),
      items: validRows.map((r) => ({
        productId: r.productId,
        unitPrice: Number(r.unitPrice) || 0,
        quantity: Number(r.quantity) || 1,
        kdvRate: Number(r.kdvRate) ?? 18,
      })),
    };
    purchasesApi
      .create(payload)
      .then(() => {
        toast.success('Alış kaydı oluşturuldu.');
        navigate(ROUTES.alislar);
      })
      .catch(() => toast.error('Alış oluşturulamadı.'))
      .finally(() => setSaving(false));
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <p className="text-zinc-600">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Yeni Alış" description="Satın alma faturası oluşturun" icon={ShoppingBagIcon} action={<Link to={ROUTES.alislar} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">← Alışlar</Link>} />
      <form onSubmit={handleSubmit} className="space-y-6 max-w-4xl">
        <Card>
          <h2 className="text-lg font-semibold text-zinc-800 mb-4">Tedarikçi & Depo</h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Tedarikçi *</label>
              <select
                required
                value={supplierId}
                onChange={(e) => setSupplierId(e.target.value)}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              >
                <option value="">Seçiniz</option>
                {suppliers.map((s) => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Depo (stok girişi) *</label>
              <select
                required
                value={warehouseId}
                onChange={(e) => setWarehouseId(e.target.value)}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              >
                <option value="">Seçiniz</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>{w.name}</option>
                ))}
              </select>
            </div>
            {warehouseId && (
              <div className="sm:col-span-2">
                <p className="text-sm font-medium text-zinc-700 mb-2">Bu depoda mevcut stok</p>
                <div className="rounded-xl border border-zinc-200 bg-zinc-50/80 max-h-48 overflow-y-auto">
                  {warehouseStock.length === 0 ? (
                    <p className="p-3 text-sm text-zinc-500">Bu depoda henüz stok yok.</p>
                  ) : (
                    <ul className="divide-y divide-zinc-200 p-2">
                      {warehouseStock.map((s, i) => (
                        <li key={i} className="flex justify-between items-center py-2 px-2 text-sm">
                          <span className="font-medium text-zinc-900">{s.productName}</span>
                          {s.sku && <span className="text-zinc-500 text-xs">{s.sku}</span>}
                          <span className="text-emerald-600 font-semibold">{s.quantity} adet</span>
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              </div>
            )}
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Ödeme türü</label>
              <select
                value={paymentType}
                onChange={(e) => setPaymentType(e.target.value as 'veresiye' | 'nakit')}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              >
                <option value="veresiye">Veresiye</option>
                <option value="nakit">Nakit (kasadan çıkış)</option>
              </select>
            </div>
            {paymentType === 'nakit' && (
              <div>
                <label className="block text-sm font-medium text-zinc-700 mb-1">Kasa *</label>
                <select
                  value={purchaseKasaId}
                  onChange={(e) => setPurchaseKasaId(e.target.value)}
                  className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                >
                  <option value="">Seçiniz</option>
                  {kasaList.map((k) => (
                    <option key={k.id} value={k.id}>{k.name}</option>
                  ))}
                </select>
              </div>
            )}
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Alış tarihi *</label>
              <input
                type="date"
                required
                value={purchaseDate}
                onChange={(e) => setPurchaseDate(e.target.value)}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-zinc-700 mb-1">Vade tarihi</label>
              <input
                type="date"
                value={dueDate}
                onChange={(e) => setDueDate(e.target.value)}
                className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              />
            </div>
            <div className="sm:col-span-2 flex items-center gap-2">
              <input
                type="checkbox"
                id="isReturn"
                checked={isReturn}
                onChange={(e) => setIsReturn(e.target.checked)}
                className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
              />
              <label htmlFor="isReturn" className="text-sm font-medium text-zinc-700">İade (stok çıkışı)</label>
            </div>
          </div>
        </Card>

        <Card>
          <div className="flex flex-wrap justify-between items-center gap-3 mb-4">
            <h2 className="text-lg font-semibold text-zinc-800">Kalemler</h2>
            <button
              type="button"
              onClick={addRow}
              className="inline-flex items-center rounded-xl px-3 py-2 text-sm font-medium bg-zinc-100 text-zinc-700 hover:bg-zinc-200"
            >
              + Satır Ekle
            </button>
          </div>
          <div className="overflow-x-auto -mx-2">
            <table className="min-w-full divide-y divide-zinc-200">
              <thead className="bg-zinc-50">
                <tr>
                  <th className="px-3 py-2.5 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Ürün</th>
                  <th className="px-3 py-2.5 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Birim Fiyat</th>
                  <th className="px-3 py-2.5 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">Adet</th>
                  <th className="px-3 py-2.5 text-right text-xs font-medium text-zinc-500 uppercase tracking-wider">KDV %</th>
                  <th className="px-3 py-2.5 w-10" />
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 bg-white">
                {rows.map((row, index) => (
                  <tr key={index}>
                    <td className="px-3 py-2">
                      <select
                        required
                        value={row.productId}
                        onChange={(e) => updateRow(index, 'productId', e.target.value)}
                        className="block w-full min-w-[180px] rounded-xl border border-zinc-300 px-2 py-1.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      >
                        <option value="">Ürün seçin</option>
                        {supplierId ? (
                          products.length === 0 ? (
                            <option value="" disabled>Bu tedarikçiye ait ürün yok</option>
                          ) : (
                            products.map((p) => (
                              <option key={p.id} value={p.id}>
                                {p.name} {p.sku ? `(${p.sku})` : ''}
                              </option>
                            ))
                          )
                        ) : (
                          <option value="" disabled>Önce tedarikçi seçin</option>
                        )}
                      </select>
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.unitPrice}
                        onChange={(e) => updateRow(index, 'unitPrice', e.target.value)}
                        className="block w-24 ml-auto text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        min="1"
                        value={row.quantity}
                        onChange={(e) => updateRow(index, 'quantity', e.target.value)}
                        className="block w-20 ml-auto text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.kdvRate}
                        onChange={(e) => updateRow(index, 'kdvRate', e.target.value)}
                        className="block w-16 ml-auto text-right rounded-xl border border-zinc-300 px-2 py-1.5 text-sm shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <button
                        type="button"
                        onClick={() => removeRow(index)}
                        className="text-red-600 hover:text-red-700 text-sm font-medium"
                      >
                        Sil
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          {rows.length === 0 && (
            <p className="py-6 text-center text-zinc-500 text-sm">Satır ekleyin.</p>
          )}
        </Card>

        <Card>
          <h2 className="text-lg font-semibold text-zinc-800 mb-4">Özet & Notlar</h2>
          <div className="rounded-xl bg-zinc-50 p-4 text-sm mb-4 max-w-sm">
            <p className="flex justify-between text-zinc-700">
              <span>Ara toplam (KDV hariç)</span>
              <span>{subtotal.toFixed(2)} ₺</span>
            </p>
            <p className="flex justify-between text-zinc-600 mt-1">
              <span>KDV</span>
              <span>{kdvTotal.toFixed(2)} ₺</span>
            </p>
            <p className="flex justify-between font-semibold text-zinc-900 mt-2 pt-2 border-t border-zinc-200">
              <span>Genel toplam</span>
              <span>{grandTotal.toFixed(2)} ₺</span>
            </p>
          </div>
          <div>
            <label className="block text-sm font-medium text-zinc-700 mb-1">Notlar</label>
            <textarea
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              className="block w-full rounded-xl border border-zinc-300 px-3 py-2 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              placeholder="Opsiyonel notlar..."
            />
          </div>
        </Card>

        <div className="flex gap-3">
          <Link
            to={ROUTES.alislar}
            className="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium border border-zinc-300 text-zinc-700 hover:bg-zinc-50"
          >
            İptal
          </Link>
          <button
            type="submit"
            disabled={saving}
            className="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
          >
            {saving ? 'Oluşturuluyor...' : 'Alış Oluştur'}
          </button>
        </div>
      </form>
    </div>
  );
}
