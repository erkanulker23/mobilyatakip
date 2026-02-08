<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\XmlFeed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class XmlFeedService
{
    public function syncFeed(XmlFeed $feed, bool $createMissingSuppliers = true): array
    {
        set_time_limit(600);

        $response = Http::timeout(60)->get($feed->url);
        if (!$response->successful()) {
            throw new \RuntimeException(sprintf('XML feed indirilemedi: HTTP %d — URL: %s', $response->status(), $feed->url));
        }

        $body = $response->body();
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        if ($xml === false) {
            $err = libxml_get_last_error();
            libxml_clear_errors();
            throw new \RuntimeException($err ? ('Geçersiz XML: ' . trim($err->message)) : 'Geçersiz XML formatı');
        }
        libxml_clear_errors();

        $items = $this->parseXmlToProducts($xml);
        $created = 0;
        $updated = 0;
        $errors = [];
        $suppliersCreated = 0;

        // Resolve suppliers from XML: sistemde ad veya kod ile varsa mevcut tedarikçi, yoksa yeni oluştur
        $supplierMap = [];
        $defaultSupplierId = $feed->supplierId;
        if ($defaultSupplierId && !Supplier::where('id', $defaultSupplierId)->exists()) {
            $defaultSupplierId = null;
            $feed->update(['supplierId' => null]);
        }

        foreach ($items as $row) {
            $sup = $row['supplier'] ?? null;
            if ($sup && !empty(trim($sup['name'] ?? ''))) {
                $name = trim($sup['name']);
                $extKey = (string) ($sup['code'] ?? $sup['id'] ?? $name);
                if ($extKey === '') {
                    continue;
                }
                if (!isset($supplierMap[$extKey])) {
                    $address = trim($sup['contact'] ?? '');
                    $address = $address !== '' ? 'Yetkili: ' . $address : null;
                    $code = trim($sup['code'] ?? '') ?: null;

                    // Önce sistemde kod veya ad ile eşleşen tedarikçi var mı?
                    $supplier = null;
                    if ($code !== null && $code !== '') {
                        $supplier = Supplier::where('code', $code)->first();
                    }
                    if ($supplier === null) {
                        $supplier = Supplier::where('name', $name)->first();
                    }

                    if ($supplier !== null) {
                        // Mevcut tedarikçiyi kullan; bilgileri güncelle (XML’deki daha güncel olabilir)
                        $supplier->update([
                            'code' => $code ?? $supplier->code,
                            'name' => $name,
                            'email' => trim($sup['email'] ?? '') ?: $supplier->email,
                            'phone' => trim($sup['phone'] ?? '') ?: $supplier->phone,
                            'address' => $address ?? $supplier->address,
                        ]);
                        $supplierMap[$extKey] = $supplier->id;
                    } elseif ($createMissingSuppliers) {
                        // Sistemde yok, yeni tedarikçi oluştur
                        $supplier = Supplier::create([
                            'code' => $code,
                            'name' => $name,
                            'email' => trim($sup['email'] ?? '') ?: null,
                            'phone' => trim($sup['phone'] ?? '') ?: null,
                            'address' => $address,
                            'isActive' => true,
                            'externalSource' => $feed->url,
                            'externalId' => $extKey,
                        ]);
                        $suppliersCreated++;
                        $supplierMap[$extKey] = $supplier->id;
                    } else {
                        // Olmayan tedarikçileri kaydetme kapalı: varsayılan tedarikçiye bağla (sadece geçerli id ise)
                        if ($defaultSupplierId) {
                            $supplierMap[$extKey] = $defaultSupplierId;
                        }
                    }
                }
            }
        }

        if (!$defaultSupplierId && !empty($supplierMap)) {
            $defaultSupplierId = reset($supplierMap);
            $feed->update(['supplierId' => $defaultSupplierId]);
        }
        if (!$defaultSupplierId && $createMissingSuppliers) {
            $brands = array_filter(array_unique(array_column($items, 'brand')));
            if (!empty($brands)) {
                $brandName = reset($brands);
                $supplier = Supplier::firstOrCreate(
                    ['name' => $brandName],
                    ['isActive' => true]
                );
                $feed->update(['supplierId' => $supplier->id]);
                $defaultSupplierId = $supplier->id;
            }
        }

        foreach ($items as $row) {
            try {
                $name = trim($row['name'] ?? '');
                if (empty($name)) {
                    continue;
                }
                $sup = $row['supplier'] ?? null;
                $rowSupplierId = null;
                if ($sup && !empty(trim($sup['name'] ?? ''))) {
                    $extKey = (string) ($sup['id'] ?? $sup['code'] ?? $sup['name']);
                    $rowSupplierId = $supplierMap[$extKey] ?? $defaultSupplierId;
                }
                $productSupplierId = $rowSupplierId ?? $defaultSupplierId;
                if ($productSupplierId && !Supplier::where('id', $productSupplierId)->exists()) {
                    $productSupplierId = null;
                }

                $sku = trim($row['sku'] ?? '') ?: null;
                $extId = $row['externalId'] ?? $sku;

                // Mevcut ürünü bul: aynı feed'den aynı ürün iki kez oluşturulmasın (önce sku/externalId, sonra ad)
                $existing = null;
                if ($extId !== null && $extId !== '') {
                    $existing = Product::where('externalSource', $feed->url)->where(function ($q) use ($extId) {
                        $q->where('externalId', $extId)->orWhere('sku', $extId);
                    })->first();
                }
                if (!$existing && $sku) {
                    $existing = Product::where('externalSource', $feed->url)->where('sku', $sku)->first();
                }
                if (!$existing && $name !== '') {
                    $existing = Product::where('externalSource', $feed->url)->where('name', $name)->first();
                }

                $imageUrls = $row['imageUrls'] ?? [];
                $images = !empty($imageUrls) ? $this->downloadProductImages($imageUrls) : null;

                if ($existing) {
                    // Feed'deki fiyat her zaman geçerli: değişmişse güncelle
                    $updateData = [
                        'name' => $name,
                        'unitPrice' => (float) ($row['unitPrice'] ?? $existing->unitPrice),
                        'kdvRate' => (float) ($row['kdvRate'] ?? $existing->kdvRate),
                        'supplierId' => $productSupplierId,
                    ];
                    if (array_key_exists('netPurchasePrice', $row)) {
                        $updateData['netPurchasePrice'] = $row['netPurchasePrice'] !== null && $row['netPurchasePrice'] !== '' ? (float) $row['netPurchasePrice'] : null;
                    }
                    if ($images !== null && $images !== []) {
                        $updateData['images'] = $images;
                    }
                    // SKU feed'de varsa ve mevcut üründe yoksa güncelle (bir daha eşleşsin)
                    if ($sku && !$existing->sku) {
                        $updateData['sku'] = $sku;
                        $updateData['externalId'] = $extId ?: $sku;
                    }
                    $existing->update($updateData);
                    $updated++;
                } else {
                    // Aynı SKU ile (hangi feed’den olursa olsun) başka ürün var mı? Varsa güncelle, çift kayıt oluşturma
                    if ($sku) {
                        $existingBySku = Product::where('sku', $sku)->first();
                        if ($existingBySku) {
                            $updateData = [
                                'name' => $name,
                                'unitPrice' => (float) ($row['unitPrice'] ?? $existingBySku->unitPrice),
                                'kdvRate' => (float) ($row['kdvRate'] ?? $existingBySku->kdvRate),
                                'supplierId' => $productSupplierId,
                                'externalId' => $extId ?: $sku,
                                'externalSource' => $feed->url,
                            ];
                            if (array_key_exists('netPurchasePrice', $row)) {
                                $updateData['netPurchasePrice'] = $row['netPurchasePrice'] !== null && $row['netPurchasePrice'] !== '' ? (float) $row['netPurchasePrice'] : null;
                            }
                            if ($images !== null && $images !== []) {
                                $updateData['images'] = $images;
                            }
                            $existingBySku->update($updateData);
                            $updated++;
                            continue;
                        }
                    }
                    // Son kontrol: aynı feed + aynı ad ile kayıt var mı? Varsa güncelle, çift kayıt oluşturma
                    $existingByName = Product::where('externalSource', $feed->url)->where('name', $name)->first();
                    if ($existingByName) {
                        $updateData = [
                            'name' => $name,
                            'unitPrice' => (float) ($row['unitPrice'] ?? $existingByName->unitPrice),
                            'kdvRate' => (float) ($row['kdvRate'] ?? $existingByName->kdvRate),
                            'supplierId' => $productSupplierId,
                        ];
                        if (array_key_exists('netPurchasePrice', $row)) {
                            $updateData['netPurchasePrice'] = $row['netPurchasePrice'] !== null && $row['netPurchasePrice'] !== '' ? (float) $row['netPurchasePrice'] : null;
                        }
                        if ($sku) {
                            $updateData['sku'] = $sku;
                            $updateData['externalId'] = $extId ?: $sku;
                        }
                        if ($images !== null && $images !== []) {
                            $updateData['images'] = $images;
                        }
                        $existingByName->update($updateData);
                        $updated++;
                    } else {
                        Product::create([
                            'name' => $name,
                            'sku' => $sku ?? $this->generateSku(),
                            'unitPrice' => (float) ($row['unitPrice'] ?? 0),
                            'netPurchasePrice' => array_key_exists('netPurchasePrice', $row) && $row['netPurchasePrice'] !== null && $row['netPurchasePrice'] !== '' ? (float) $row['netPurchasePrice'] : null,
                            'kdvRate' => (float) ($row['kdvRate'] ?? 18),
                            'externalId' => $extId ?: $sku,
                            'externalSource' => $feed->url,
                            'supplierId' => $productSupplierId,
                            'images' => $images ?? [],
                        ]);
                        $created++;
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors, 'suppliersCreated' => $suppliersCreated];
    }

    private function parseXmlToProducts(\SimpleXMLElement $xml): array
    {
        $items = [];
        $ns = $xml->getNamespaces(true);
        $root = $xml->children();

        $productNodes = $this->findProductNodes($xml, $root, $ns);
        foreach ($productNodes as $p) {
            $items[] = $this->parseProductNode($p, $ns);
        }

        return $items;
    }

    /** @return \SimpleXMLElement[] */
    private function findProductNodes(\SimpleXMLElement $xml, \SimpleXMLElement $root, array $ns): array
    {
        $nodes = [];

        $directTags = ['product', 'item', 'Product', 'Item', 'urun', 'Urun'];
        foreach ($directTags as $tag) {
            if (isset($root->{$tag})) {
                foreach ($root->{$tag} as $p) {
                    $nodes[] = $p;
                }
                if (!empty($nodes)) {
                    return $nodes;
                }
            }
        }

        if (isset($root->urunler->urun)) {
            foreach ($root->urunler->urun as $p) {
                $nodes[] = $p;
            }
            if (!empty($nodes)) {
                return $nodes;
            }
        }
        if (isset($root->products->product)) {
            foreach ($root->products->product as $p) {
                $nodes[] = $p;
            }
            if (!empty($nodes)) {
                return $nodes;
            }
        }

        if (isset($root->channel->item)) {
            foreach ($root->channel->item as $p) {
                $nodes[] = $p;
            }
            if (!empty($nodes)) {
                return $nodes;
            }
        }

        $defaultNs = $ns[''] ?? null;
        if ($defaultNs) {
            $rootNs = $xml->children($defaultNs);
            foreach (['product', 'item', 'urun'] as $tag) {
                if (isset($rootNs->{$tag})) {
                    foreach ($rootNs->{$tag} as $p) {
                        $nodes[] = $p;
                    }
                    if (!empty($nodes)) {
                        return $nodes;
                    }
                }
            }
        }

        foreach ($root->children() as $child) {
            $name = strtolower((string) $child->getName());
            if (in_array($name, ['product', 'item', 'urun'], true)) {
                $nodes[] = $child;
            }
        }

        return $nodes;
    }

    private function parseProductNode(\SimpleXMLElement $p, array $ns): array
    {
        $g = $ns['g'] ?? null;
        $attrs = $p->attributes() ?? [];
        $name = $this->getNodeText($p, [
            'title', 'name', 'Name', 'urun_adi', 'urun_adı', 'productName', 'ProductName',
            'description', 'Description',
        ]) ?: (string) ($attrs['name'] ?? $attrs['title'] ?? ($g ? ($p->children($g)->title ?? '') : '') ?? '');
        $name = trim((string) $name);

        $price = $this->getNodeText($p, [
            'price', 'unitPrice', 'fiyat', 'satis_fiyati', 'satış_fiyatı', 'sale_price',
            'indirimli_fiyat', 'liste_fiyati', 'liste_fiyatı', 'Price', 'UnitPrice',
        ]);
        if ($price === '') {
            $price = (string) ($attrs['price'] ?? $attrs['fiyat'] ?? ($g ? ($p->children($g)->price ?? $p->children($g)->sale_price ?? '0') : '0'));
        }
        $unitPrice = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $price)) ?: 0;

        $netPurchasePrice = null;
        $alisFiyat = $this->getNodeText($p, ['alis_fiyati', 'alış_fiyatı', 'alis_fiyat', 'cost_price', 'CostPrice', 'netPrice']);
        if ($alisFiyat !== '') {
            $netPurchasePrice = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $alisFiyat)) ?: null;
        }

        if ($unitPrice == 0 && isset($p->parcalar->parca)) {
            $firstParca = is_array($p->parcalar->parca) ? $p->parcalar->parca[0] : $p->parcalar->parca;
            $parcaPrice = (string) ($firstParca->indirimli_fiyat ?? $firstParca->fiyat ?? '0');
            $unitPrice = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $parcaPrice)) ?: 0;
        }

        $sku = $this->getNodeText($p, ['sku', 'SKU', 'barkod', 'stockCode', 'StockCode', 'code', 'Code', 'gtin', 'mpn']);
        if ($sku === '' && $g) {
            $sku = (string) ($p->children($g)->id ?? '');
        }
        if ($sku === '') {
            $sku = (string) ($attrs['sku'] ?? $attrs['barkod'] ?? $attrs['id'] ?? $attrs['code'] ?? '');
        }
        $sku = trim((string) $sku) ?: null;

        $brand = trim($this->getNodeText($p, ['brand', 'Brand', 'marka', 'manufacturer']) ?: '');

        $supplier = $this->parseSupplierNode($p);

        $imageUrls = $this->parseProductImageUrls($p, $g);

        return [
            'name' => $name,
            'sku' => $sku,
            'unitPrice' => $unitPrice,
            'netPurchasePrice' => $netPurchasePrice,
            'kdvRate' => 18,
            'externalId' => $sku,
            'brand' => $brand ?: null,
            'supplier' => $supplier,
            'imageUrls' => $imageUrls,
        ];
    }

    /**
     * XML ürün node'undan resim URL'lerini toplar (tek veya çoklu).
     * @return string[]
     */
    private function parseProductImageUrls(\SimpleXMLElement $p, ?string $g): array
    {
        $urls = [];
        $seen = [];

        $candidates = [
            'image', 'image_link', 'image_link_1', 'image_link_2', 'image_link_3',
            'img', 'resim', 'picture', 'product_image', 'photo', 'thumbnail',
            'Image', 'ImageLink', 'ProductImage', 'Picture',
        ];
        foreach ($candidates as $tag) {
            $el = $p->{$tag} ?? null;
            if ($el === null) {
                continue;
            }
            $text = trim((string) $el);
            if ($text !== '' && $this->isValidImageUrl($text) && !isset($seen[$text])) {
                $urls[] = $text;
                $seen[$text] = true;
            }
            $href = $el->attributes()->url ?? $el->attributes()->href ?? null;
            if ($href !== null) {
                $text = trim((string) $href);
                if ($text !== '' && $this->isValidImageUrl($text) && !isset($seen[$text])) {
                    $urls[] = $text;
                    $seen[$text] = true;
                }
            }
        }
        if ($g) {
            $gImage = $p->children($g)->image_link ?? $p->children($g)->image ?? null;
            if ($gImage !== null) {
                $text = trim((string) $gImage);
                if ($text !== '' && $this->isValidImageUrl($text) && !isset($seen[$text])) {
                    $urls[] = $text;
                }
            }
        }
        if (isset($p->enclosure)) {
            $enc = is_array($p->enclosure) ? $p->enclosure[0] : $p->enclosure;
            $type = strtolower((string) ($enc->attributes()->type ?? ''));
            $url = trim((string) ($enc->attributes()->url ?? ''));
            if ($url !== '' && (str_contains($type, 'image') || $type === '') && $this->isValidImageUrl($url) && !isset($seen[$url])) {
                $urls[] = $url;
            }
        }
        return array_values(array_unique($urls));
    }

    private function isValidImageUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    /**
     * Resim URL'lerini indirip storage'a kaydeder, dönen dizide yol (/storage/...) verir.
     * @param string[] $imageUrls
     * @return string[]
     */
    private function downloadProductImages(array $imageUrls): array
    {
        $paths = [];
        $dir = 'products/' . date('Y-m-d');
        foreach ($imageUrls as $url) {
            try {
                $response = Http::timeout(15)->get($url);
                if (!$response->successful()) {
                    continue;
                }
                $body = $response->body();
                $contentType = $response->header('Content-Type') ?? '';
                $ext = $this->extensionFromContentType($contentType) ?: $this->extensionFromUrl($url) ?: 'jpg';
                $filename = Str::random(16) . '.' . $ext;
                $path = $dir . '/' . $filename;
                if (Storage::disk('public')->put($path, $body)) {
                    $paths[] = '/storage/' . $path;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        return $paths;
    }

    private function extensionFromContentType(string $contentType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        foreach ($map as $mime => $ext) {
            if (stripos($contentType, $mime) !== false) {
                return $ext;
            }
        }
        return null;
    }

    private function extensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === '') {
            return null;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? $ext : null;
    }

    private function getNodeText(\SimpleXMLElement $node, array $tagNames): string
    {
        foreach ($tagNames as $tag) {
            $el = $node->{$tag} ?? null;
            if ($el !== null && (string) $el !== '') {
                return trim((string) $el);
            }
        }
        return '';
    }

    private function parseSupplierNode(\SimpleXMLElement $p): ?array
    {
        $supplierEl = $p->supplier ?? null;
        if ($supplierEl === null) {
            return null;
        }
        $id = trim((string) ($supplierEl->id ?? ''));
        $code = trim((string) ($supplierEl->code ?? ''));
        $name = trim((string) ($supplierEl->name ?? ''));
        if ($name === '') {
            return null;
        }
        return [
            'id' => $id ?: null,
            'code' => $code ?: null,
            'name' => $name,
            'email' => trim((string) ($supplierEl->email ?? '')) ?: null,
            'contact' => trim((string) ($supplierEl->contact ?? '')) ?: null,
            'phone' => trim((string) ($supplierEl->phone ?? '')) ?: null,
        ];
    }

    private function generateSku(): string
    {
        $year = date('Y');
        $last = Product::where('sku', 'like', "PRD-{$year}-%")
            ->orderBy('sku', 'desc')
            ->first();
        $next = $last ? (int) substr($last->sku, -5) + 1 : 1;
        return sprintf('PRD-%s-%05d', $year, $next);
    }
}
