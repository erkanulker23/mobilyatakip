import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { CubeIcon, ArrowLeftIcon, PencilSquareIcon } from '@heroicons/react/24/outline';
import { ROUTES } from '../config/routes';
import { productsApi } from '../services/api/productsApi';
import { suppliersApi } from '../services/api/suppliersApi';
import { uploadApi } from '../services/api/uploadApi';
import { Button } from '../components/ui';
import toast from 'react-hot-toast';

interface ProductDetail {
  id: string;
  name: string;
  sku?: string | null;
  unitPrice: number;
  kdvIncluded?: boolean;
  kdvRate?: number;
  minStockLevel?: number;
  description?: string | null;
  isActive?: boolean;
  images?: string[] | null;
  supplierId?: string | null;
  supplier?: { id: string; name: string } | null;
}

interface SupplierOption {
  id: string;
  name: string;
}

export default function ProductDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [product, setProduct] = useState<ProductDetail | null>(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [uploadingImages, setUploadingImages] = useState(false);
  const [suppliers, setSuppliers] = useState<SupplierOption[]>([]);
  const [form, setForm] = useState({
    name: '',
    sku: '',
    unitPrice: '',
    kdvIncluded: 'true',
    kdvRate: '18',
    minStockLevel: '0',
    description: '',
    isActive: 'true',
    supplierId: '',
    imageUrls: [] as string[],
  });

  const loadProduct = () => {
    if (!id) return;
    setLoading(true);
    productsApi
      .get(id)
      .then(({ data }) => {
        const p = data as ProductDetail;
        setProduct(p);
        setForm({
          name: p.name ?? '',
          sku: p.sku ?? '',
          unitPrice: String(p.unitPrice ?? 0),
          kdvIncluded: p.kdvIncluded === false ? 'false' : 'true',
          kdvRate: String(p.kdvRate ?? 18),
          minStockLevel: String(p.minStockLevel ?? 0),
          description: p.description ?? '',
          isActive: p.isActive === false ? 'false' : 'true',
          supplierId: p.supplierId ?? p.supplier?.id ?? '',
          imageUrls: Array.isArray(p.images) ? [...p.images] : [],
        });
      })
      .catch(() => toast.error('Ürün yüklenemedi'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadProduct();
  }, [id]);

  useEffect(() => {
    suppliersApi.list({ limit: 500 }).then(({ data }) => {
      const res = data as { data?: SupplierOption[] };
      setSuppliers(Array.isArray(res?.data) ? res.data : []);
    });
  }, []);

  const handleSave = (e: React.FormEvent) => {
    e.preventDefault();
    if (!id) return;
    setSaving(true);
    const payload = {
      name: form.name.trim(),
      sku: form.sku.trim() || undefined,
      unitPrice: parseFloat(form.unitPrice) || 0,
      kdvIncluded: form.kdvIncluded === 'true',
      kdvRate: parseFloat(form.kdvRate) || 18,
      minStockLevel: parseInt(form.minStockLevel, 10) || 0,
      description: form.description.trim() || undefined,
      isActive: form.isActive === 'true',
      supplierId: form.supplierId || undefined,
      images: form.imageUrls.length > 0 ? form.imageUrls : undefined,
    };
    productsApi
      .update(id, payload)
      .then(({ data }) => {
        setProduct(data as ProductDetail);
        setEditing(false);
        toast.success('Ürün güncellendi');
      })
      .catch(() => toast.error('Ürün güncellenemedi'))
      .finally(() => setSaving(false));
  };

  if (loading || !product) {
    return (
      <div className="flex items-center justify-center py-16">
        <p className="text-zinc-500">Yükleniyor...</p>
      </div>
    );
  }

  const apiBase = import.meta.env.VITE_API_URL || '';
  const images = editing ? form.imageUrls : (Array.isArray(product.images) ? product.images : []);

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="flex flex-wrap items-center gap-3">
          <Link
            to={ROUTES.urunler}
            className="inline-flex items-center gap-2 rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-200 shadow-sm transition hover:bg-zinc-50 dark:hover:bg-zinc-600"
          >
            <ArrowLeftIcon className="h-4 w-4" />
            Ürünler
          </Link>
          <h1 className="text-2xl font-bold text-zinc-900">{editing ? 'Ürünü düzenle' : product.name}</h1>
          {!editing && product.isActive === false && (
            <span className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Pasif</span>
          )}
        </div>
        {!editing && (
          <Button variant="secondary" icon={PencilSquareIcon} onClick={() => setEditing(true)}>
            Düzenle
          </Button>
        )}
      </div>

      {editing ? (
        <form onSubmit={handleSave} className="space-y-6">
          <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-semibold text-zinc-800">Ürün bilgileri</h2>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="sm:col-span-2">
                <label className="block text-sm font-medium text-zinc-700">Ürün adı *</label>
                <input
                  type="text"
                  required
                  value={form.name}
                  onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">SKU</label>
                <input
                  type="text"
                  value={form.sku}
                  onChange={(e) => setForm((f) => ({ ...f, sku: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">Tedarikçi</label>
                <select
                  value={form.supplierId}
                  onChange={(e) => setForm((f) => ({ ...f, supplierId: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                >
                  <option value="">Seçiniz</option>
                  {suppliers.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">Fiyat türü</label>
                <select
                  value={form.kdvIncluded}
                  onChange={(e) => setForm((f) => ({ ...f, kdvIncluded: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                >
                  <option value="true">KDV Dahil</option>
                  <option value="false">KDV Hariç</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">Durum</label>
                <select
                  value={form.isActive}
                  onChange={(e) => setForm((f) => ({ ...f, isActive: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                >
                  <option value="true">Aktif</option>
                  <option value="false">Pasif</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">Birim fiyat (₺)</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  value={form.unitPrice}
                  onChange={(e) => setForm((f) => ({ ...f, unitPrice: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">KDV %</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  value={form.kdvRate}
                  onChange={(e) => setForm((f) => ({ ...f, kdvRate: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-zinc-700">Min. stok seviyesi</label>
                <input
                  type="number"
                  min="0"
                  value={form.minStockLevel}
                  onChange={(e) => setForm((f) => ({ ...f, minStockLevel: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                />
              </div>
              <div className="sm:col-span-2">
                <label className="block text-sm font-medium text-zinc-700">Açıklama</label>
                <textarea
                  rows={3}
                  value={form.description}
                  onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                  className="mt-1.5 block w-full rounded-xl border border-zinc-300 px-3 py-2.5 text-zinc-900 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                />
              </div>
              <div className="sm:col-span-2">
                <label className="block text-sm font-medium text-zinc-700">Ürün resimleri</label>
                {form.imageUrls.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {form.imageUrls.map((url, idx) => (
                      <div key={`${url}-${idx}`} className="relative inline-block">
                        <img
                          src={url.startsWith('http') ? url : `${apiBase}${url.startsWith('/') ? '' : '/'}${url}`}
                          alt=""
                          className="h-20 w-20 rounded-lg border border-zinc-200 object-cover"
                        />
                        <button
                          type="button"
                          onClick={() => setForm((f) => ({ ...f, imageUrls: f.imageUrls.filter((_, i) => i !== idx) }))}
                          className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                        >
                          ×
                        </button>
                      </div>
                    ))}
                  </div>
                )}
                <div className="mt-2">
                  <input
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    multiple
                    disabled={uploadingImages}
                    onChange={(e) => {
                      const files = Array.from(e.target.files ?? []);
                      if (!files.length) return;
                      setUploadingImages(true);
                      uploadApi
                        .images(files)
                        .then(({ data }) => {
                          const newUrls = (data as { urls?: string[] }).urls ?? [];
                          setForm((f) => ({ ...f, imageUrls: [...f.imageUrls, ...newUrls] }));
                        })
                        .catch(() => toast.error('Resim yüklenemedi'))
                        .finally(() => {
                          setUploadingImages(false);
                          e.target.value = '';
                        });
                    }}
                    className="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-medium file:text-emerald-700"
                  />
                  {uploadingImages && <p className="mt-1 text-xs text-zinc-500">Yükleniyor...</p>}
                </div>
              </div>
            </div>
            <div className="mt-6 flex gap-3 border-t border-zinc-100 pt-4">
              <Button type="button" variant="ghost" onClick={() => setEditing(false)}>
                İptal
              </Button>
              <Button type="submit" disabled={saving}>
                {saving ? 'Kaydediliyor...' : 'Kaydet'}
              </Button>
            </div>
          </div>
        </form>
      ) : (
        <div className="grid gap-6 lg:grid-cols-2">
          {/* Resimler */}
          <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
            <h2 className="mb-4 flex items-center gap-2 text-lg font-semibold text-zinc-800">
              <CubeIcon className="h-5 w-5 text-emerald-600" />
              Görseller
            </h2>
            {images.length > 0 ? (
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {images.map((url, i) => {
                  const fullUrl = url.startsWith('http') ? url : `${apiBase}${url.startsWith('/') ? '' : '/'}${url}`;
                  return (
                    <div key={`img-${i}`} className="aspect-square overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700">
                      <img
                        src={fullUrl}
                        alt=""
                        className="h-full w-full object-cover"
                        onError={(e) => {
                          (e.target as HTMLImageElement).style.display = 'none';
                        }}
                      />
                    </div>
                  );
                })}
              </div>
            ) : (
              <div className="flex aspect-video items-center justify-center rounded-xl border border-dashed border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500">
                <CubeIcon className="h-12 w-12" />
              </div>
            )}
          </div>

          {/* Bilgiler (sadece görüntüleme) */}
          <div className="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 shadow-sm">
            <h2 className="mb-4 text-lg font-semibold text-zinc-800">Ürün bilgileri</h2>
            <dl className="space-y-4">
              <div>
                <dt className="text-xs font-medium uppercase tracking-wider text-zinc-500">SKU</dt>
                <dd className="mt-1 font-medium text-zinc-900">{product.sku ?? '—'}</dd>
              </div>
              <div>
                <dt className="text-xs font-medium uppercase tracking-wider text-zinc-500">Tedarikçi</dt>
                <dd className="mt-1">
                  {product.supplier?.id ? (
                    <Link
                      to={ROUTES.tedarikci(product.supplier.id)}
                      className="font-medium text-emerald-600 hover:underline"
                    >
                      {product.supplier.name}
                    </Link>
                  ) : (
                    <span className="text-zinc-600">—</span>
                  )}
                </dd>
              </div>
              <div>
                <dt className="text-xs font-medium uppercase tracking-wider text-zinc-500">Birim fiyat (KDV)</dt>
                <dd className="mt-1 text-lg font-semibold text-zinc-900">
                  {Number(product.unitPrice ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                  <span className="ml-2 text-sm font-normal text-zinc-500">
                    {product.kdvIncluded === false ? 'Hariç' : 'Dahil'}
                  </span>
                </dd>
              </div>
              {product.kdvRate != null && (
                <div>
                  <dt className="text-xs font-medium uppercase tracking-wider text-zinc-500">KDV oranı</dt>
                  <dd className="mt-1 text-zinc-900">%{Number(product.kdvRate)}</dd>
                </div>
              )}
              {product.minStockLevel != null && Number(product.minStockLevel) > 0 && (
                <div>
                  <dt className="text-xs font-medium uppercase tracking-wider text-zinc-500">Min. stok seviyesi</dt>
                  <dd className="mt-1 text-zinc-900">{product.minStockLevel}</dd>
                </div>
              )}
              {product.description && (
                <div>
                  <dt className="text-xs font-medium uppercase tracking-wider text-zinc-500">Açıklama</dt>
                  <dd className="mt-1 whitespace-pre-wrap text-zinc-700">{product.description}</dd>
                </div>
              )}
            </dl>
          </div>
        </div>
      )}
    </div>
  );
}
