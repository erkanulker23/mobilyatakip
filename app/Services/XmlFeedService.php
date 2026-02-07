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

        $xml = simplexml_load_string($response->body());
        if ($xml === false) {
            throw new \RuntimeException('Geçersiz XML formatı');
        }

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
                    $supplier = Supplier::firstOrCreate(
                        [
                            'externalSource' => $feed->url,
                            'externalId' => $extKey,
                        ],
                        [
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
                    $existing->update([
                        'unitPrice' => $row['unitPrice'] ?? $existing->unitPrice,
                        'kdvRate' => $row['kdvRate'] ?? $existing->kdvRate,
                        'supplierId' => $productSupplierId,
                    ]);
                } else {
                    Product::create([
                        'name' => $name,
                        'sku' => $sku ?? $this->generateSku(),
                        'unitPrice' => (float) ($row['unitPrice'] ?? 0),
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
        $root = $xml->children();
        $ns = $xml->getNamespaces(true);

        if (isset($root->product)) {
            foreach ($root->product as $p) {
                $items[] = $this->parseProductNode($p, $ns);
            }
        } elseif (isset($root->item)) {
            foreach ($root->item as $p) {
                $items[] = $this->parseProductNode($p, $ns);
            }
        } elseif (isset($root->Product)) {
            foreach ($root->Product as $p) {
                $items[] = $this->parseProductNode($p, $ns);
            }
        } elseif (isset($root->channel->item)) {
            foreach ($root->channel->item as $p) {
                $items[] = $this->parseProductNode($p, $ns);
            }
        } else {
            foreach ($root->children() as $child) {
                if (in_array(strtolower($child->getName()), ['product', 'item'], true)) {
                    $items[] = $this->parseProductNode($child, $ns);
                }
            }
        }

        return $items;
    }

    private function parseProductNode(\SimpleXMLElement $p, array $ns): array
    {
        $g = $ns['g'] ?? null;
        $attrs = $p->attributes();

        $name = (string) ($p->title ?? $p->name ?? $p->Name ?? $attrs['name'] ?? ($g ? ($p->children($g)->title ?? '') : '') ?? '');
        $price = (string) ($p->price ?? $p->unitPrice ?? $p->fiyat ?? ($g ? ($p->children($g)->price ?? $p->children($g)->sale_price ?? '') : '') ?? '0');
        $sku = (string) ($p->sku ?? $p->SKU ?? ($g ? ($p->children($g)->id ?? '') : '') ?? '');
        $brand = trim((string) ($p->brand ?? $p->Brand ?? ($g ? ($p->children($g)->brand ?? '') : '') ?? ''));

        $unitPrice = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $price)) ?: 0;
        if ($unitPrice == 0 && isset($p->parcalar->parca)) {
            $firstParca = is_array($p->parcalar->parca) ? $p->parcalar->parca[0] : $p->parcalar->parca;
            $parcaPrice = (string) ($firstParca->indirimli_fiyat ?? $firstParca->fiyat ?? '0');
            $unitPrice = (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $parcaPrice)) ?: 0;
        }

        $supplier = $this->parseSupplierNode($p);

        return [
            'name' => $name,
            'sku' => $sku ?: null,
            'unitPrice' => $unitPrice,
            'kdvRate' => 18,
            'externalId' => $sku ?: null,
            'brand' => $brand ?: null,
            'supplier' => $supplier,
        ];
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
