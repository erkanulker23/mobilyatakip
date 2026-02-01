import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { CubeIcon, PlusIcon } from '@heroicons/react/24/outline';
import { productsApi } from '../services/api/productsApi';
import { suppliersApi } from '../services/api/suppliersApi';
import { uploadApi } from '../services/api/uploadApi';
import { xmlFeedsApi, type XmlFeedItem } from '../services/api/xmlFeedsApi';
import { ROUTES } from '../config/routes';
import { PageHeader, Card, EmptyState, Button, ActionButtons, Pagination } from '../components/ui';
import toast from 'react-hot-toast';

interface ProductRow {
  id: string;
  name: string;
  sku?: string;
  unitPrice: number;
  kdvIncluded?: boolean;
  kdvRate?: number;
  minStockLevel?: number;
  description?: string;
  isActive?: boolean;
  supplierId?: string;
  supplier?: { id: string; name: string };
  images?: string[] | null;
}

interface SupplierOption {
  id: string;
  name: string;
}

export default function ProductsPage() {
  const [products, setProducts] = useState<ProductRow[]>([]);
  const [suppliers, setSuppliers] = useState<SupplierOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState({
    name: '',
    sku: '',
    unitPrice: '',
    kdvIncluded: 'true',
    kdvRate: '18',
    minStockLevel: '0',
    description: '',
    supplierId: '',
    imageUrls: [] as string[],
  });
  const [importOpen, setImportOpen] = useState(false);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [importSupplierId, setImportSupplierId] = useState('');
  const [importing, setImporting] = useState(false);
  const [exportOpen, setExportOpen] = useState(false);
  const [exportIncludeExisting, setExportIncludeExisting] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [uploadingImages, setUploadingImages] = useState(false);
  const [search, setSearch] = useState('');
  const [supplierFilter, setSupplierFilter] = useState('');
  const [activeFilter, setActiveFilter] = useState('');
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(20);
  const [total, setTotal] = useState(0);
  const [totalPages, setTotalPages] = useState(1);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [bulkDeleting, setBulkDeleting] = useState(false);
  const [feeds, setFeeds] = useState<XmlFeedItem[]>([]);
  const [feedModalOpen, setFeedModalOpen] = useState(false);
  const [feedForm, setFeedForm] = useState({ name: '', url: '', supplierId: '' });
  const [feedSaving, setFeedSaving] = useState(false);
  const [pullingFeedId, setPullingFeedId] = useState<string | null>(null);
  const [quickPullOpen, setQuickPullOpen] = useState(false);
  const [quickPullUrl, setQuickPullUrl] = useState('');
  const [quickPullSupplierId, setQuickPullSupplierId] = useState('');
  const [quickPulling, setQuickPulling] = useState(false);

  const loadProducts = () => {
    const params: { search?: string; supplierId?: string; active?: boolean; page?: number; limit?: number } = {
      page,
      limit,
    };
    if (search.trim()) params.search = search.trim();
    if (supplierFilter) params.supplierId = supplierFilter;
    if (activeFilter === 'true' || activeFilter === 'false') params.active = activeFilter === 'true';
    productsApi
      .list(params)
      .then(({ data }) => {
        const res = data as { data?: unknown[]; total?: number; page?: number; limit?: number; totalPages?: number };
        setProducts(Array.isArray(res.data) ? (res.data as ProductRow[]) : []);
        setTotal(res.total ?? 0);
        setTotalPages(res.totalPages ?? 1);
      })
      .catch(() => toast.error('Ürünler yüklenemedi'))
      .finally(() => setLoading(false));
  };

  const loadSuppliers = () => {
    suppliersApi.list({ limit: 500 }).then(({ data }) => {
      const res = data as { data?: { id: string; name: string }[] };
      setSuppliers(Array.isArray(res?.data) ? res.data : Array.isArray(data) ? (data as { id: string; name: string }[]) : []);
    });
  };

  useEffect(() => {
    setLoading(true);
    loadProducts();
  }, [page, limit]);

  useEffect(() => {
    loadSuppliers();
  }, []);

  const loadFeeds = () => {
    xmlFeedsApi
      .list()
      .then(({ data }) => {
        const list = Array.isArray(data) ? data : (data as { data?: XmlFeedItem[] })?.data ?? [];
        setFeeds(list);
      })
      .catch(() => {
        setFeeds([]);
        toast.error('XML feed listesi yüklenemedi');
      });
  };

  useEffect(() => {
    loadFeeds();
  }, []);

  const applyFilters = () => {
    setPage(1);
    setLoading(true);
    loadProducts();
  };

  const openCreate = () => {
    setEditingId(null);
    setForm({
      name: '',
      sku: '',
      unitPrice: '',
      kdvIncluded: 'true',
      kdvRate: '18',
      minStockLevel: '0',
      description: '',
      supplierId: '',
      imageUrls: [],
    });
    setModalOpen(true);
  };

  const openEdit = (p: ProductRow) => {
    setEditingId(p.id);
    setForm({
      name: p.name,
      sku: p.sku ?? '',
      unitPrice: String(p.unitPrice ?? ''),
      kdvIncluded: p.kdvIncluded !== false ? 'true' : 'false',
      kdvRate: String(p.kdvRate ?? 18),
      minStockLevel: String(p.minStockLevel ?? 0),
      description: p.description ?? '',
      supplierId: p.supplierId ?? p.supplier?.id ?? '',
      imageUrls: Array.isArray(p.images) ? [...p.images] : [],
    });
    setModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const payload = {
      name: form.name.trim(),
      sku: form.sku.trim() || undefined,
      unitPrice: Number.parseFloat(form.unitPrice) || 0,
      kdvIncluded: form.kdvIncluded === 'true',
      kdvRate: Number.parseFloat(form.kdvRate) ?? 18,
      minStockLevel: Number.parseInt(form.minStockLevel, 10) || 0,
      description: form.description.trim() || undefined,
      supplierId: form.supplierId || undefined,
      images: form.imageUrls.length ? form.imageUrls : undefined,
    };
    if (editingId) {
      productsApi
        .update(editingId, payload)
        .then(() => {
          toast.success('Ürün güncellendi');
          setModalOpen(false);
          loadProducts();
        })
        .catch(() => toast.error('Ürün güncellenemedi'));
    } else {
      productsApi
        .create(payload)
        .then(() => {
          toast.success('Ürün eklendi');
          setModalOpen(false);
          loadProducts();
        })
        .catch(() => toast.error('Ürün eklenemedi'));
    }
  };

  const runImport = () => {
    if (!importFile) {
      toast.error('Dosya seçin');
      return;
    }
    setImporting(true);
    productsApi
      .import(importFile, importSupplierId || undefined)
      .then(({ data }) => {
        toast.success(`${data.created} ürün eklendi.${data.errors?.length ? ` ${data.errors.length} hata.` : ''}`);
        setImportOpen(false);
        setImportFile(null);
        setImportSupplierId('');
        loadProducts();
      })
      .catch(() => toast.error('İçe aktarma başarısız'))
      .finally(() => setImporting(false));
  };

  const openFeedModal = () => {
    setFeedForm({ name: '', url: '', supplierId: '' });
    setFeedModalOpen(true);
  };

  const saveFeed = (e: React.FormEvent) => {
    e.preventDefault();
    if (!feedForm.url.trim()) {
      toast.error('Feed URL girin');
      return;
    }
    setFeedSaving(true);
    xmlFeedsApi
      .create({
        name: feedForm.name.trim() || feedForm.url.trim(),
        url: feedForm.url.trim(),
        supplierId: feedForm.supplierId || null,
      })
      .then(() => {
        toast.success('XML feed tanımı eklendi');
        setFeedModalOpen(false);
        loadFeeds();
      })
      .catch(() => toast.error('Feed eklenemedi'))
      .finally(() => setFeedSaving(false));
  };

  const deleteFeed = (id: string) => {
    if (!globalThis.confirm('Bu feed tanımını silmek istediğinize emin misiniz?')) return;
    const shouldDeleteProducts = globalThis.confirm(
      'Bu feed\'den aktarılan ürünler de silinsin mi?\n\nTamam = Ürünler de silinsin\nİptal = Sadece feed silinsin, ürünler kalsın'
    );
    xmlFeedsApi
      .delete(id, shouldDeleteProducts)
      .then(({ data }) => {
        const d = data as { productsDeleted?: number };
        const msg = d?.productsDeleted ? `Feed silindi. ${d.productsDeleted} ürün kaldırıldı.` : 'Feed silindi. Ürünler korundu.';
        toast.success(msg);
        loadFeeds();
        if (shouldDeleteProducts) loadProducts();
      })
      .catch(() => toast.error('Feed silinemedi'));
  };

  const runPullFeed = (feed: XmlFeedItem) => {
    setPullingFeedId(feed.id);
    productsApi
      .importFromFeed({
        feedUrl: feed.url,
        supplierId: feed.supplierId ?? undefined,
      })
      .then(({ data }) => {
        const d = data as { created?: number; updated?: number; errors?: string[] };
        const msg = `${d.created ?? 0} yeni, ${d.updated ?? 0} güncellendi.${(d.errors?.length ?? 0) ? ` ${d.errors?.length ?? 0} uyarı/hata.` : ''}`;
        toast.success(msg);
        loadProducts();
      })
      .catch(() => toast.error('Feed\'den veri çekilemedi'))
      .finally(() => setPullingFeedId(null));
  };

  const runQuickPull = () => {
    if (!quickPullUrl.trim()) {
      toast.error('Feed URL girin');
      return;
    }
    setQuickPulling(true);
    productsApi
      .importFromFeed({
        feedUrl: quickPullUrl.trim(),
        supplierId: quickPullSupplierId || undefined,
      })
      .then(({ data }) => {
        const d = data as { created?: number; updated?: number; errors?: string[] };
        toast.success(`${d.created ?? 0} yeni, ${d.updated ?? 0} güncellendi.${(d.errors?.length ?? 0) ? ` ${d.errors?.length ?? 0} uyarı.` : ''}`);
        setQuickPullOpen(false);
        setQuickPullUrl('');
        setQuickPullSupplierId('');
        loadProducts();
      })
      .catch(() => toast.error('Feed\'den veri çekilemedi'))
      .finally(() => setQuickPulling(false));
  };

  const runExport = () => {
    setExporting(true);
    productsApi
      .export({
        includeExisting: exportIncludeExisting,
        search: search.trim() || undefined,
        supplierId: supplierFilter || undefined,
        active: activeFilter === 'true' || activeFilter === 'false' ? activeFilter === 'true' : undefined,
      })
      .then(({ data }) => {
        const blob = data as Blob;
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `urunler-${new Date().toISOString().slice(0, 10)}.xlsx`;
        a.click();
        URL.revokeObjectURL(url);
        toast.success('Dışa aktarma tamamlandı.');
        setExportOpen(false);
      })
      .catch(() => toast.error('Dışa aktarma başarısız'))
      .finally(() => setExporting(false));
  };

  const handleDelete = (id: string) => {
    if (!globalThis.confirm('Bu ürünü silmek istediğinize emin misiniz?')) return;
    productsApi
      .delete(id)
      .then(() => {
        toast.success('Ürün silindi');
        setSelectedIds((s) => { const n = new Set(s); n.delete(id); return n; });
        loadProducts();
      })
      .catch(() => toast.error('Ürün silinemedi'));
  };

  const toggleSelectAll = () => {
    if (selectedIds.size === products.length) {
      setSelectedIds(new Set());
    } else {
      setSelectedIds(new Set(products.map((p) => p.id)));
    }
  };

  const toggleSelect = (id: string) => {
    setSelectedIds((s) => {
      const n = new Set(s);
      if (n.has(id)) n.delete(id);
      else n.add(id);
      return n;
    });
  };

  const handleBulkDelete = () => {
    const ids = Array.from(selectedIds);
    if (!ids.length) {
      toast.error('Lütfen silmek için en az bir ürün seçin.');
      return;
    }
    if (!globalThis.confirm(`${ids.length} ürünü silmek istediğinize emin misiniz?`)) return;
    setBulkDeleting(true);
    productsApi
      .bulkDelete(ids)
      .then(({ data }) => {
        toast.success(`${data?.deleted ?? ids.length} ürün silindi.`);
        setSelectedIds(new Set());
        loadProducts();
      })
      .catch(() => toast.error('Ürünler silinemedi'))
      .finally(() => setBulkDeleting(false));
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title="Ürünler"
        description="Stok ve ürün yönetimi"
        icon={CubeIcon}
        action={
          <div className="flex gap-3 flex-wrap">
            <Button variant="secondary" onClick={() => setQuickPullOpen(true)}>XML Feed'den Çek</Button>
            <Button variant="secondary" onClick={() => setImportOpen(true)}>Excel / XML İçe Aktar</Button>
            <Button variant="secondary" onClick={() => setExportOpen(true)}>Excel / XML Dışa Aktar</Button>
            {selectedIds.size > 0 && (
              <Button variant="danger" onClick={handleBulkDelete} disabled={bulkDeleting}>
                {bulkDeleting ? 'Siliniyor...' : `Seçilenleri sil (${selectedIds.size})`}
              </Button>
            )}
            <Button icon={PlusIcon} onClick={openCreate}>Yeni Ürün</Button>
          </div>
        }
      />
      <div className="flex flex-wrap gap-3 items-center">
        <input
          type="text"
          placeholder="Ürün adı veya SKU ara..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-56"
        />
        <select
          value={supplierFilter}
          onChange={(e) => setSupplierFilter(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tüm tedarikçiler</option>
          {suppliers.map((s) => (
            <option key={s.id} value={s.id}>{s.name}</option>
          ))}
        </select>
        <select
          value={activeFilter}
          onChange={(e) => setActiveFilter(e.target.value)}
          className="rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 px-4 py-2.5 text-sm text-zinc-900 dark:text-zinc-100 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
        >
          <option value="">Tümü</option>
          <option value="true">Aktif</option>
          <option value="false">Pasif</option>
        </select>
        <Button variant="primary" onClick={applyFilters}>Filtrele</Button>
      </div>

      <Card padding="md">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-base font-semibold text-zinc-900 dark:text-white">XML Feed Tanımı</h3>
          <Button variant="secondary" onClick={openFeedModal}>Yeni Feed Ekle</Button>
        </div>
        <p className="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
          RSS / Google Shopping (g:) yapısındaki XML feed URL'sini tanımlayın. &quot;Verileri Çek&quot; ile tüm ürünler (resimler dahil) sisteme aktarılır. Tedarikçi seçilmezse feed&apos;teki marka (g:brand) ile tedarikçi bulunur veya oluşturulur.
        </p>
        {feeds.length === 0 ? (
          <p className="text-sm text-zinc-500 py-2">Henüz feed tanımı yok. &quot;Yeni Feed Ekle&quot; ile örn. <code className="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">https://rossohome.com/xml-feed/kose-takimlari-zI3nnT21</code> ekleyebilirsiniz.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ad</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">URL</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tedarikçi</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">İşlem</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 bg-white">
                {feeds.map((f) => (
                  <tr key={f.id} className="hover:bg-zinc-50/80">
                    <td className="px-4 py-3 text-sm font-medium text-zinc-900">{f.name}</td>
                    <td className="px-4 py-3 text-sm text-zinc-600 max-w-xs truncate" title={f.url}>{f.url}</td>
                    <td className="px-4 py-3 text-sm text-zinc-600">{(f.supplier as { name?: string } | undefined)?.name ?? '—'}</td>
                    <td className="px-4 py-3 text-right">
                      <button
                        type="button"
                        onClick={() => runPullFeed(f)}
                        disabled={pullingFeedId === f.id}
                        className="mr-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                      >
                        {pullingFeedId === f.id ? 'Çekiliyor...' : 'Verileri Çek'}
                      </button>
                      <button
                        type="button"
                        onClick={() => deleteFeed(f.id)}
                        className="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50"
                      >
                        Sil
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </Card>

      <Card padding="none">
        {loading ? (
          <div className="flex items-center justify-center py-16">
            <p className="text-zinc-500">Yükleniyor...</p>
          </div>
        ) : products.length === 0 ? (
          <EmptyState
            icon={CubeIcon}
            title="Ürün bulunamadı"
            description="Henüz ürün yok veya filtreye uygun kayıt yok. Yeni ürün ekleyebilir veya Excel/XML ile içe aktarabilirsiniz."
            action={
              <>
                <Button variant="secondary" onClick={() => setImportOpen(true)} className="mr-2">Excel / XML İçe Aktar</Button>
                <Button icon={PlusIcon} onClick={openCreate}>Yeni Ürün</Button>
              </>
            }
            className="rounded-2xl m-0 border-0"
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead className="bg-zinc-50 dark:bg-zinc-800">
                  <tr>
                    <th className="px-4 py-4 text-left w-10">
                      <input
                        type="checkbox"
                        checked={products.length > 0 && selectedIds.size === products.length}
                        onChange={toggleSelectAll}
                        className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                      />
                    </th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 w-16">Resim</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ad</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">SKU</th>
                    <th className="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tedarikçi</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Birim Fiyat (KDV)</th>
                    <th className="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">İşlem</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-200 bg-white">
                  {products.map((p) => {
                    const imageUrl = Array.isArray(p.images) && p.images.length > 0 ? p.images[0] : null;
                    const apiBase = import.meta.env.VITE_API_URL || '';
                    const fullImageUrl = imageUrl ? (imageUrl.startsWith('http') ? imageUrl : `${apiBase}${imageUrl.startsWith('/') ? '' : '/'}${imageUrl}`) : null;
                    return (
                      <tr key={p.id} className="hover:bg-zinc-50/80 transition-colors">
                    <td className="px-4 py-4">
                      <input
                        type="checkbox"
                        checked={selectedIds.has(p.id)}
                        onChange={() => toggleSelect(p.id)}
                        className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                      />
                    </td>
                    <td className="px-6 py-4">
                      <div className="relative w-12 h-12 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-700 shrink-0">
                        {fullImageUrl && (
                          <img
                            src={fullImageUrl}
                            alt=""
                            className="absolute inset-0 w-full h-full object-cover"
                            onError={(e) => {
                              e.currentTarget.style.display = 'none';
                              e.currentTarget.nextElementSibling?.classList.remove('hidden');
                            }}
                          />
                        )}
                        <div className={`absolute inset-0 flex items-center justify-center bg-zinc-100 text-zinc-400 ${fullImageUrl ? 'hidden' : ''}`} title="Varsayılan resim">
                          <CubeIcon className="h-6 w-6" />
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                      <Link to={ROUTES.urun(p.id)} className="text-emerald-600 hover:underline">{p.name}</Link>
                    </td>
                    <td className="px-6 py-4 text-sm text-zinc-600">{p.sku ?? '—'}</td>
                    <td className="px-6 py-4 text-sm text-zinc-600">
                      {(p.supplier as { name?: string } | undefined)?.name ?? '—'}
                    </td>
                    <td className="px-6 py-4 text-sm text-right text-zinc-900 dark:text-white">
                      {Number(p.unitPrice ?? 0).toLocaleString('tr-TR', { minimumFractionDigits: 2 })} ₺
                      <span className="text-zinc-400 text-xs ml-1">({p.kdvIncluded === false ? 'Hariç' : 'Dahil'})</span>
                    </td>
                    <td className="px-6 py-4 text-right">
                      <ActionButtons onEdit={() => openEdit(p)} onDelete={() => handleDelete(p.id)} />
                    </td>
                  </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <Pagination
              page={page}
              limit={limit}
              total={total}
              totalPages={totalPages}
              onPageChange={setPage}
              onLimitChange={(l) => { setLimit(l); setPage(1); }}
              limitOptions={[10, 20, 50]}
            />
          </>
        )}
      </Card>

      <Transition appear show={modalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-50" onClose={() => setModalOpen(false)}>
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-200"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-150"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-200"
                enterFrom="opacity-0 scale-95"
                enterTo="opacity-100 scale-100"
                leave="ease-in duration-150"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 dark:text-white pb-4 border-b border-zinc-100 dark:border-zinc-700">
                    {editingId ? 'Ürün Düzenle' : 'Yeni Ürün'}
                  </Dialog.Title>
                  <form onSubmit={handleSubmit} className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Ürün adı *</label>
                      <input
                        type="text"
                        required
                        value={form.name}
                        onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">SKU</label>
                      <input
                        type="text"
                        value={form.sku}
                        onChange={(e) => setForm((f) => ({ ...f, sku: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Tedarikçi</label>
                      <select
                        value={form.supplierId}
                        onChange={(e) => setForm((f) => ({ ...f, supplierId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
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
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="true">KDV Dahil</option>
                        <option value="false">KDV Hariç</option>
                      </select>
                      <p className="mt-0.5 text-xs text-zinc-500">
                        {form.kdvIncluded === 'true'
                          ? 'Girilen fiyat KDV dahil kabul edilir.'
                          : 'Girilen fiyat KDV hariç; KDV ayrıca hesaplanır.'}
                      </p>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-sm font-medium text-zinc-700">Birim fiyat (₺)</label>
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          value={form.unitPrice}
                          onChange={(e) => setForm((f) => ({ ...f, unitPrice: e.target.value }))}
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
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
                          className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                        />
                      </div>
                    </div>
                    {form.unitPrice && form.kdvRate && (
                      <p className="text-xs text-zinc-600">
                        {form.kdvIncluded === 'true'
                          ? `KDV Hariç tutar: ${(Number(form.unitPrice) / (1 + Number(form.kdvRate) / 100)).toFixed(2)} ₺`
                          : `KDV Dahil tutar: ${(Number(form.unitPrice) * (1 + Number(form.kdvRate) / 100)).toFixed(2)} ₺`}
                      </p>
                    )}
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Min. stok</label>
                      <input
                        type="number"
                        min="0"
                        value={form.minStockLevel}
                        onChange={(e) => setForm((f) => ({ ...f, minStockLevel: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Ürün resimleri</label>
                      <p className="text-xs text-zinc-500 mt-0.5">Resim yükleyin veya yüklenenleri kaldırın.</p>
                      {form.imageUrls.length > 0 && (
                        <div className="mt-2 flex flex-wrap gap-2">
                          {form.imageUrls.map((url, idx) => (
                            <div key={idx} className="relative inline-block">
                              <img src={url.startsWith('http') ? url : `${import.meta.env.VITE_API_URL || ''}${url.startsWith('/') ? '' : '/'}${url}`} alt="" className="w-16 h-16 object-cover rounded-lg border border-zinc-200" />
                              <button
                                type="button"
                                onClick={() => setForm((f) => ({ ...f, imageUrls: f.imageUrls.filter((_, i) => i !== idx) }))}
                                className="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center hover:bg-red-600"
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
                                const newUrls = data.urls ?? [];
                                setForm((f) => ({ ...f, imageUrls: [...f.imageUrls, ...newUrls] }));
                              })
                              .catch(() => toast.error('Resim yüklenemedi'))
                              .finally(() => {
                                setUploadingImages(false);
                                e.target.value = '';
                              });
                          }}
                          className="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-xl file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-emerald-700 file:font-medium"
                        />
                        {uploadingImages && <p className="mt-1 text-xs text-zinc-500">Yükleniyor...</p>}
                      </div>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Açıklama</label>
                      <textarea
                        rows={2}
                        value={form.description}
                        onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button
                        type="button"
                        onClick={() => setModalOpen(false)}
                        className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600"
                      >
                        İptal
                      </button>
                      <button
                        type="submit"
                        className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                      >
                        {editingId ? 'Güncelle' : 'Ekle'}
                      </button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Import modal */}
      <Transition appear show={importOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={() => !importing && setImportOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 dark:text-white pb-4 border-b border-zinc-100 dark:border-zinc-700">Excel / XML İçe Aktar</Dialog.Title>
                  <p className="mt-4 text-sm text-zinc-500">.xlsx, .xls veya .xml dosyası yükleyin. İlk satır/sütun başlık kabul edilir (name, sku, unitPrice, kdvRate, kdvIncluded, description, minStockLevel).</p>
                  <div className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Dosya</label>
                      <input
                        type="file"
                        accept=".xlsx,.xls,.xml"
                        onChange={(e) => setImportFile(e.target.files?.[0] ?? null)}
                        className="mt-1.5 block w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Tedarikçi (opsiyonel)</label>
                      <select
                        value={importSupplierId}
                        onChange={(e) => setImportSupplierId(e.target.value)}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Tüm ürünler için tedarikçi atanmasın</option>
                        {suppliers.map((s) => (
                          <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div className="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4">
                    <button type="button" disabled={importing} onClick={() => setImportOpen(false)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50">İptal</button>
                    <button type="button" disabled={importing || !importFile} onClick={runImport} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{importing ? 'Aktarılıyor...' : 'İçe Aktar'}</button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Yeni Feed Ekle modal */}
      <Transition appear show={feedModalOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={() => !feedSaving && setFeedModalOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 dark:text-white pb-4 border-b border-zinc-100 dark:border-zinc-700">Yeni XML Feed Ekle</Dialog.Title>
                  <form onSubmit={saveFeed} className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Feed adı (opsiyonel)</label>
                      <input
                        type="text"
                        value={feedForm.name}
                        onChange={(e) => setFeedForm((f) => ({ ...f, name: e.target.value }))}
                        placeholder="Örn. Köşe Takımları"
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Feed URL *</label>
                      <input
                        type="url"
                        required
                        value={feedForm.url}
                        onChange={(e) => setFeedForm((f) => ({ ...f, url: e.target.value }))}
                        placeholder="https://rossohome.com/xml-feed/kose-takimlari-zI3nnT21"
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Tedarikçi (opsiyonel)</label>
                      <select
                        value={feedForm.supplierId}
                        onChange={(e) => setFeedForm((f) => ({ ...f, supplierId: e.target.value }))}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Feed&apos;teki marka (g:brand) ile bul/oluştur</option>
                        {suppliers.map((s) => (
                          <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                      </select>
                    </div>
                    <div className="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                      <button type="button" disabled={feedSaving} onClick={() => setFeedModalOpen(false)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50">İptal</button>
                      <button type="submit" disabled={feedSaving} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{feedSaving ? 'Kaydediliyor...' : 'Kaydet'}</button>
                    </div>
                  </form>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Hızlı XML Feed'den Çek modal */}
      <Transition appear show={quickPullOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={() => !quickPulling && setQuickPullOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 dark:text-white pb-4 border-b border-zinc-100 dark:border-zinc-700">XML Feed&apos;den Verileri Çek</Dialog.Title>
                  <p className="mt-4 text-sm text-zinc-500">RSS / Google Shopping (g:) yapısındaki XML feed URL&apos;sini girin. Tüm ürünler (resimler dahil) sisteme aktarılır.</p>
                  <div className="mt-5 space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Feed URL *</label>
                      <input
                        type="url"
                        value={quickPullUrl}
                        onChange={(e) => setQuickPullUrl(e.target.value)}
                        placeholder="https://rossohome.com/xml-feed/kose-takimlari-zI3nnT21"
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-zinc-700">Tedarikçi (opsiyonel)</label>
                      <select
                        value={quickPullSupplierId}
                        onChange={(e) => setQuickPullSupplierId(e.target.value)}
                        className="mt-1.5 block w-full rounded-xl border border-zinc-300 dark:border-zinc-600 px-3 py-2.5 text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-700 shadow-sm transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                      >
                        <option value="">Feed&apos;teki marka (g:brand) ile bul/oluştur</option>
                        {suppliers.map((s) => (
                          <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                      </select>
                    </div>
                  </div>
                  <div className="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4">
                    <button type="button" disabled={quickPulling} onClick={() => setQuickPullOpen(false)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50">İptal</button>
                    <button type="button" disabled={quickPulling || !quickPullUrl.trim()} onClick={runQuickPull} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{quickPulling ? 'Çekiliyor...' : 'Verileri Çek'}</button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>

      {/* Dışa aktar modal */}
      <Transition appear show={exportOpen} as={Fragment}>
        <Dialog as="div" className="relative z-[100]" onClose={() => !exporting && setExportOpen(false)}>
          <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0" enterTo="opacity-100" leave="ease-in duration-150" leaveFrom="opacity-100" leaveTo="opacity-0">
            <div className="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm" />
          </Transition.Child>
          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <Transition.Child as={Fragment} enter="ease-out duration-200" enterFrom="opacity-0 scale-95" enterTo="opacity-100 scale-100" leave="ease-in duration-150" leaveFrom="opacity-100 scale-100" leaveTo="opacity-0 scale-95">
                <Dialog.Panel className="w-full max-w-md rounded-2xl border border-zinc-200/80 dark:border-zinc-700/80 bg-white dark:bg-zinc-800 p-6 shadow-2xl shadow-zinc-900/10 dark:shadow-black/30">
                  <Dialog.Title className="text-xl font-semibold text-zinc-900 dark:text-white pb-4 border-b border-zinc-100 dark:border-zinc-700">Excel / XML Dışa Aktar</Dialog.Title>
                  <p className="mt-4 text-sm text-zinc-500">Ürün listesini Excel (.xlsx) olarak indirir. Mevcut filtre (arama, tedarikçi, aktif/pasif) uygulanabilir.</p>
                  <div className="mt-5 space-y-4">
                    <label className="flex items-center gap-3 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={exportIncludeExisting}
                        onChange={(e) => setExportIncludeExisting(e.target.checked)}
                        className="rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500"
                      />
                      <span className="text-sm font-medium text-zinc-700">Mevcut ürünler eklensin (filtreye göre)</span>
                    </label>
                  </div>
                  <div className="mt-6 flex justify-end gap-3 border-t border-zinc-100 pt-4">
                    <button type="button" disabled={exporting} onClick={() => setExportOpen(false)} className="rounded-xl border border-zinc-300 dark:border-zinc-600 px-4 py-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200 transition hover:bg-zinc-50 dark:hover:bg-zinc-600 disabled:opacity-50">İptal</button>
                    <button type="button" disabled={exporting} onClick={runExport} className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50">{exporting ? 'İndiriliyor...' : 'Dışa Aktar'}</button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </div>
  );
}
