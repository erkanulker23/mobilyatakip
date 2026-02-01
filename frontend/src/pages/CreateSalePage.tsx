import { useEffect, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { ROUTES } from '../config/routes';
import { ShoppingCartIcon } from '@heroicons/react/24/outline';
import { customersApi } from '../services/api/customersApi';
import { productsApi } from '../services/api/productsApi';
import { warehousesApi } from '../services/api/warehousesApi';
import { salesApi } from '../services/api/salesApi';
import { PageHeader, Card } from '../components/ui';
import toast from 'react-hot-toast';

interface CustomerOption {
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

interface SaleRow {
  productId: string;
  productName: string;
  unitPrice: string;
  quantity: string;
  kdvRate: string;
}

export default function CreateSalePage() {
  const navigate = useNavigate();
  const [customers, setCustomers] = useState<CustomerOption[]>([]);
  const [products, setProducts] = useState<ProductOption[]>([]);
  const [warehouses, setWarehouses] = useState<WarehouseOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [customerId, setCustomerId] = useState('');
  const [warehouseId, setWarehouseId] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [notes, setNotes] = useState('');
  const [rows, setRows] = useState<SaleRow[]>([]);

  useEffect(() => {
    Promise.all([
      customersApi.list(),
      productsApi.list(),
      warehousesApi.list(),
    ])
      .then(([custRes, prodRes, whRes]) => {
        setCustomers(Array.isArray(custRes.data) ? custRes.data : []);
        setProducts(Array.isArray(prodRes.data) ? prodRes.data : []);
        setWarehouses(Array.isArray(whRes.data) ? whRes.data : []);
      })
      .catch(() => toast.error('Veriler yüklenemedi'))
      .finally(() => setLoading(false));
  }, []);

  const addRow = () => {
    setRows((r) => [
      ...r,
      {
        productId: '',
        productName: '',
        unitPrice: '',
        quantity: '1',
        kdvRate: '18',
      },
    ]);
  };

  const removeRow = (index: number) => {
    setRows((r) => r.filter((_, i) => i !== index));
  };

  const updateRow = (index: number, field: keyof SaleRow, value: string) => {
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

  const validRows = rows.filter(
    (r) => r.productId && r.quantity && parseFloat(r.quantity) > 0
  );
  let subtotal = 0;
  let kdvTotal = 0;
  for (const r of validRows) {
    const lineNet =
      (parseFloat(r.unitPrice) || 0) * (parseInt(r.quantity, 10) || 1);
    const kdvRate = parseFloat(r.kdvRate) ?? 18;
    subtotal += lineNet;
    kdvTotal += lineNet * (kdvRate / 100);
  }
  const grandTotal = subtotal + kdvTotal;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!customerId) {
      toast.error('Müşteri seçiniz.');
      return;
    }
    if (!warehouseId) {
      toast.error('Depo seçiniz.');
      return;
    }
    if (validRows.length === 0) {
      toast.error('En az bir ürün satırı ekleyiniz.');
      return;
    }
    setSaving(true);
    const payload = {
      customerId,
      warehouseId,
      dueDate: dueDate || undefined,
      notes: notes.trim() || undefined,
      items: validRows.map((r) => ({
        productId: r.productId,
        unitPrice: parseFloat(r.unitPrice) || 0,
        quantity: parseInt(r.quantity, 10) || 1,
        kdvRate: parseFloat(r.kdvRate) ?? 18,
      })),
    };
    salesApi
      .create(payload)
      .then(({ data }) => {
        const sale = data as { id?: string };
        toast.success('Satış oluşturuldu.');
        navigate(sale?.id ? ROUTES.satis(sale.id) : ROUTES.satislar);
      })
      .catch(() => toast.error('Satış oluşturulamadı.'))
      .finally(() => setSaving(false));
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center py-12">
        <p className="text-slate-600">Yükleniyor...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <PageHeader title="Satış Oluştur" description="Yeni satış fişi" icon={ShoppingCartIcon} action={<Link to={ROUTES.satislar} className="text-sm font-medium text-emerald-600 hover:text-emerald-700">← Satışlar</Link>} />
      <form onSubmit={handleSubmit} className="space-y-6 max-w-4xl">
        <Card>
          <h2 className="text-lg font-semibold text-slate-800 mb-4">
            Müşteri & Depo
          </h2>
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Müşteri *</label>
              <select
                required
                value={customerId}
                onChange={(e) => setCustomerId(e.target.value)}
                className="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
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
              <label className="block text-sm font-medium text-slate-700 mb-1">Depo *</label>
              <select
                required
                value={warehouseId}
                onChange={(e) => setWarehouseId(e.target.value)}
                className="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              >
                <option value="">Seçiniz</option>
                {warehouses.map((w) => (
                  <option key={w.id} value={w.id}>
                    {w.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Vade tarihi</label>
              <input
                type="date"
                value={dueDate}
                onChange={(e) => setDueDate(e.target.value)}
                className="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              />
            </div>
          </div>
        </Card>

        <Card>
          <div className="flex flex-wrap justify-between items-center gap-3 mb-4">
            <h2 className="text-lg font-semibold text-slate-800">Satış Kalemleri</h2>
            <button
              type="button"
              onClick={addRow}
              className="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium bg-slate-100 text-slate-700 hover:bg-slate-200"
            >
              + Satır Ekle
            </button>
          </div>
          <div className="overflow-x-auto -mx-2">
            <table className="min-w-full divide-y divide-slate-200">
              <thead className="bg-slate-50">
                <tr>
                  <th className="px-3 py-2.5 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Ürün
                  </th>
                  <th className="px-3 py-2.5 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Birim Fiyat
                  </th>
                  <th className="px-3 py-2.5 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                    Adet
                  </th>
                  <th className="px-3 py-2.5 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">
                    KDV %
                  </th>
                  <th className="px-3 py-2.5 w-10" />
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 bg-white">
                {rows.map((row, index) => (
                  <tr key={index}>
                    <td className="px-3 py-2">
                      <select
                        required
                        value={row.productId}
                        onChange={(e) =>
                          updateRow(index, 'productId', e.target.value)
                        }
                        className="block w-full min-w-[180px] rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      >
                        <option value="">Ürün seçin</option>
                        {products.map((p) => (
                          <option key={p.id} value={p.id}>
                            {p.name} {p.sku ? `(${p.sku})` : ''}
                          </option>
                        ))}
                      </select>
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.unitPrice}
                        onChange={(e) =>
                          updateRow(index, 'unitPrice', e.target.value)
                        }
                        className="block w-24 ml-auto text-right rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        min="1"
                        value={row.quantity}
                        onChange={(e) =>
                          updateRow(index, 'quantity', e.target.value)
                        }
                        className="block w-20 ml-auto text-right rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                      />
                    </td>
                    <td className="px-3 py-2">
                      <input
                        type="number"
                        step="0.01"
                        min="0"
                        value={row.kdvRate}
                        onChange={(e) =>
                          updateRow(index, 'kdvRate', e.target.value)
                        }
                        className="block w-16 ml-auto text-right rounded-lg border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
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
            <p className="py-6 text-center text-slate-500 text-sm">
              Satır ekleyin.
            </p>
          )}
        </Card>

        <Card>
          <h2 className="text-lg font-semibold text-slate-800 mb-4">Özet & Notlar</h2>
          <div className="rounded-lg bg-slate-50 p-4 text-sm mb-4 max-w-sm">
            <p className="flex justify-between text-slate-700">
              <span>Ara toplam (KDV hariç)</span>
              <span>{subtotal.toFixed(2)} ₺</span>
            </p>
            <p className="flex justify-between text-slate-600 mt-1">
              <span>KDV</span>
              <span>{kdvTotal.toFixed(2)} ₺</span>
            </p>
            <p className="flex justify-between font-semibold text-slate-900 mt-2 pt-2 border-t border-slate-200">
              <span>Genel toplam</span>
              <span>{grandTotal.toFixed(2)} ₺</span>
            </p>
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Notlar</label>
            <textarea
              rows={3}
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              className="block w-full rounded-lg border border-slate-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              placeholder="Opsiyonel notlar..."
            />
          </div>
        </Card>

        <div className="flex gap-3">
          <Link
            to={ROUTES.satislar}
            className="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium border border-slate-300 text-slate-700 hover:bg-slate-50"
          >
            İptal
          </Link>
          <button
            type="submit"
            disabled={saving}
            className="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
          >
            {saving ? 'Oluşturuluyor...' : 'Satış Oluştur'}
          </button>
        </div>
      </form>
    </div>
  );
}
