<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class PublicCatalogController extends Controller
{
    public function index(): View
    {
        $company = Company::first();
        $manufacturers = $this->listManufacturers();

        return view('catalog.index', [
            'company' => $company,
            'manufacturers' => $manufacturers,
        ]);
    }

    public function manufacturer(string $manufacturer): View
    {
        $company = Company::first();
        $meta = $this->manufacturerMeta($manufacturer);
        if (! $meta) {
            abort(404);
        }

        $categories = $this->listCategories($manufacturer);

        return view('catalog.manufacturer', [
            'company' => $company,
            'manufacturer' => $meta,
            'categories' => $categories,
        ]);
    }

    public function category(Request $request, string $manufacturer, string $category): View
    {
        $company = Company::first();
        $catalog = $this->loadCategory($manufacturer, $category);
        if (! $catalog) {
            abort(404);
        }

        $q = trim((string) $request->query('q', ''));
        $brand = trim((string) $request->query('marka', ''));
        $series = trim((string) $request->query('seri', ''));
        $onlyNew = $request->boolean('yeni');
        $viewMode = $request->query('gorunum', 'dekor') === 'ic-mekan' ? 'ic-mekan' : 'dekor';
        $tab = $request->query('sekme', 'urunler');
        if (! in_array($tab, ['urunler', 'ozellikler', 'dokumanlar'], true)) {
            $tab = 'urunler';
        }

        $products = collect($catalog['products'] ?? []);

        $brands = $products
            ->pluck('brand')
            ->filter(fn ($b) => is_string($b) && $b !== '' && $b !== '—')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $seriesList = $products
            ->pluck('series')
            ->filter(fn ($s) => is_string($s) && $s !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $products = $products->filter(function (array $p) use ($needle) {
                $hay = mb_strtolower(($p['name'] ?? '').' '.($p['code'] ?? '').' '.($p['brand'] ?? '').' '.($p['line'] ?? '').' '.($p['series'] ?? ''));

                return str_contains($hay, $needle);
            });
        }

        if ($brand !== '') {
            $products = $products->filter(fn (array $p) => ($p['brand'] ?? '') === $brand);
        }

        if ($series !== '') {
            $products = $products->filter(fn (array $p) => ($p['series'] ?? '') === $series);
        }

        if ($onlyNew) {
            $products = $products->filter(fn (array $p) => ! empty($p['is_new']));
        }

        $products = $products->values()->all();
        $assetBase = asset('catalog/'.$manufacturer.'/'.$category);
        $documents = collect($catalog['documents'] ?? [])
            ->filter(fn ($doc) => is_array($doc) && ! empty($doc['file']))
            ->values()
            ->all();

        return view('catalog.category', [
            'company' => $company,
            'manufacturer' => $catalog['manufacturer'],
            'category' => $catalog['category'],
            'siblingCategories' => $catalog['sibling_categories'] ?? [],
            'products' => $products,
            'brands' => $brands,
            'seriesList' => $seriesList,
            'documents' => $documents,
            'features' => $catalog['features'] ?? [],
            'specs' => $catalog['specs'] ?? [],
            'totalCount' => count($catalog['products'] ?? []),
            'filteredCount' => count($products),
            'q' => $q,
            'selectedBrand' => $brand,
            'selectedSeries' => $series,
            'onlyNew' => $onlyNew,
            'viewMode' => $viewMode,
            'tab' => $tab,
            'assetBase' => $assetBase,
        ]);
    }

    /**
     * @return list<array{slug: string, name: string, categories: list<array{slug: string, name: string}>}>
     */
    private function listManufacturers(): array
    {
        $root = resource_path('data/catalog');
        if (! File::isDirectory($root)) {
            return [];
        }

        $out = [];
        foreach (File::directories($root) as $dir) {
            $slug = basename($dir);
            $meta = $this->manufacturerMeta($slug);
            if (! $meta) {
                continue;
            }
            $meta['categories'] = $this->listCategories($slug);
            $out[] = $meta;
        }

        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $out;
    }

    /**
     * @return array{slug: string, name: string, short_name?: string}|null
     */
    private function manufacturerMeta(string $slug): ?array
    {
        $dir = resource_path('data/catalog/'.$slug);
        if (! File::isDirectory($dir)) {
            return null;
        }

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $data = json_decode(File::get($file->getPathname()), true);
            if (is_array($data) && isset($data['manufacturer'])) {
                return $data['manufacturer'];
            }
        }

        return [
            'slug' => $slug,
            'name' => str_replace('-', ' ', ucwords($slug, '-')),
        ];
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    private function listCategories(string $manufacturer): array
    {
        $dir = resource_path('data/catalog/'.$manufacturer);
        if (! File::isDirectory($dir)) {
            return [];
        }

        $out = [];
        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $data = json_decode(File::get($file->getPathname()), true);
            if (! is_array($data) || ! isset($data['category'])) {
                continue;
            }
            $out[] = [
                'slug' => $data['category']['slug'] ?? $file->getFilenameWithoutExtension(),
                'name' => $data['category']['name'] ?? $file->getFilenameWithoutExtension(),
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadCategory(string $manufacturer, string $category): ?array
    {
        $path = resource_path('data/catalog/'.$manufacturer.'/'.$category.'.json');
        if (! File::isFile($path)) {
            return null;
        }

        $data = json_decode(File::get($path), true);

        return is_array($data) ? $data : null;
    }
}
