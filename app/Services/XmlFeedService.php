<?php

namespace App\Services;

use App\Models\Product;
use App\Models\XmlFeed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class XmlFeedService
{
    public function syncFeed(XmlFeed $feed): array
    {
        $response = Http::timeout(30)->get($feed->url);
        if (!$response->successful()) {
            throw new \RuntimeException('XML feed indirilemedi: ' . $response->status());
        }

        $xml = simplexml_load_string($response->body());
        if ($xml === false) {
            throw new \RuntimeException('Geçersiz XML formatı');
        }

        $items = $this->parseXmlToProducts($xml);
        $created = 0;
        $errors = [];

        foreach ($items as $row) {
            try {
                $name = trim($row['name'] ?? '');
                if (empty($name)) {
                    continue;
                }
                $sku = trim($row['sku'] ?? '') ?: null;
                $existing = Product::where('externalSource', $feed->url)
                    ->where(function ($q) use ($sku, $name) {
                        if ($sku) {
                            $q->where('sku', $sku);
                        } else {
                            $q->where('name', $name);
                        }
                    })->first();

                if ($existing) {
                    $existing->update([
                        'unitPrice' => $row['unitPrice'] ?? $existing->unitPrice,
                        'kdvRate' => $row['kdvRate'] ?? $existing->kdvRate,
                        'supplierId' => $feed->supplierId,
                    ]);
                } else {
                    Product::create([
                        'name' => $name,
                        'sku' => $sku ?? $this->generateSku(),
                        'unitPrice' => (float) ($row['unitPrice'] ?? 0),
                        'kdvRate' => (float) ($row['kdvRate'] ?? 18),
                        'externalId' => $row['externalId'] ?? null,
                        'externalSource' => $feed->url,
                        'supplierId' => $feed->supplierId,
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => count($items) - $created, 'errors' => $errors];
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

        return [
            'name' => $name,
            'sku' => $sku ?: null,
            'unitPrice' => (float) preg_replace('/[^0-9.,]/', '', str_replace(',', '.', $price)) ?: 0,
            'kdvRate' => 18,
            'externalId' => $sku ?: null,
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
