<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\XmlFeed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XmlFeedService
{
    public function syncFeed(XmlFeed $feed): array
    {
        $response = Http::timeout(30)->get($feed->url);
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
        $errors = [];
        $suppliersCreated = 0;

        // Resolve suppliers from XML: unique by (feed.url, xml supplier id/code) -> our Supplier id
        $supplierMap = [];
        $defaultSupplierId = $feed->supplierId;

        foreach ($items as $row) {
            $sup = $row['supplier'] ?? null;
            if ($sup && !empty(trim($sup['name'] ?? ''))) {
                $extKey = (string) ($sup['id'] ?? $sup['code'] ?? $sup['name']);
                if ($extKey === '') {
                    continue;
                }
                if (!isset($supplierMap[$extKey])) {
                    $address = trim($sup['contact'] ?? '');
                    $address = $address !== '' ? 'Yetkili: ' . $address : null;
                    $code = trim($sup['code'] ?? '') ?: null;
                    $supplier = Supplier::firstOrCreate(
                        [
                            'externalSource' => $feed->url,
                            'externalId' => $extKey,
                        ],
                        [
                            'code' => $code,
                            'name' => trim($sup['name']),
                            'email' => trim($sup['email'] ?? '') ?: null,
                            'phone' => trim($sup['phone'] ?? '') ?: null,
                            'address' => $address,
                            'isActive' => true,
                        ]
                    );
                    if ($supplier->wasRecentlyCreated) {
                        $suppliersCreated++;
                    } else {
                        $supplier->update([
                            'code' => $code,
                            'name' => trim($sup['name']),
                            'email' => trim($sup['email'] ?? '') ?: null,
                            'phone' => trim($sup['phone'] ?? '') ?: null,
                            'address' => $address,
                        ]);
                    }
                    $supplierMap[$extKey] = $supplier->id;
                }
            }
        }

        if (!$defaultSupplierId && !empty($supplierMap)) {
            $defaultSupplierId = reset($supplierMap);
            $feed->update(['supplierId' => $defaultSupplierId]);
        }
        if (!$defaultSupplierId) {
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

                $sku = trim($row['sku'] ?? '') ?: null;
                $extId = $row['externalId'] ?? $sku;
                $existing = null;
                if ($extId) {
                    $existing = Product::where('externalSource', $feed->url)->where(function ($q) use ($extId) {
                        $q->where('externalId', $extId)->orWhere('sku', $extId);
                    })->first();
                }
                if (!$existing && $sku) {
                    $existing = Product::where('externalSource', $feed->url)->where('sku', $sku)->first();
                }
                if (!$existing && $name) {
                    $existing = Product::where('externalSource', $feed->url)->where('name', $name)->first();
                }

                if ($existing) {
                    $updateData = [
                        'unitPrice' => $row['unitPrice'] ?? $existing->unitPrice,
                        'kdvRate' => $row['kdvRate'] ?? $existing->kdvRate,
                        'supplierId' => $productSupplierId,
                    ];
                    if (array_key_exists('netPurchasePrice', $row) && $row['netPurchasePrice'] !== null) {
                        $updateData['netPurchasePrice'] = $row['netPurchasePrice'];
                    }
                    $existing->update($updateData);
                } else {
                    Product::create([
                        'name' => $name,
                        'sku' => $sku ?? $this->generateSku(),
                        'unitPrice' => (float) ($row['unitPrice'] ?? 0),
                        'netPurchasePrice' => $row['netPurchasePrice'] ?? null,
                        'kdvRate' => (float) ($row['kdvRate'] ?? 18),
                        'externalId' => $row['externalId'] ?? null,
                        'externalSource' => $feed->url,
                        'supplierId' => $productSupplierId,
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => count($items) - $created, 'errors' => $errors, 'suppliersCreated' => $suppliersCreated];
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

        return [
            'name' => $name,
            'sku' => $sku,
            'unitPrice' => $unitPrice,
            'netPurchasePrice' => $netPurchasePrice,
            'kdvRate' => 18,
            'externalId' => $sku,
            'brand' => $brand ?: null,
            'supplier' => $supplier,
        ];
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
