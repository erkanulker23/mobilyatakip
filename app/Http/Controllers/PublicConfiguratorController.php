<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class PublicConfiguratorController extends Controller
{
    public function index(): View
    {
        $company = Company::first();

        return view('configurator.index', [
            'company' => $company,
            'materialsUrl' => route('configurator.materials'),
        ]);
    }

    public function materials(): JsonResponse
    {
        $root = resource_path('data/catalog/kastamonu-entegre');
        $categories = [
            'glossmax-pro' => ['label' => 'Glossmax Pro', 'finish_hint' => 'highgloss'],
            'lakli-panel' => ['label' => 'Laklı Panel', 'finish_hint' => 'gloss'],
            'boyali-panel' => ['label' => 'Boyalı Panel', 'finish_hint' => 'matte'],
            'melamin-kapli-panel' => ['label' => 'Melamin Kaplı Panel', 'finish_hint' => 'matte'],
            'melamin-kapli-compact-panel' => ['label' => 'Compact Panel', 'finish_hint' => 'matte'],
        ];

        $groups = [];
        foreach ($categories as $slug => $meta) {
            $path = $root.'/'.$slug.'.json';
            if (! File::isFile($path)) {
                continue;
            }
            $data = json_decode(File::get($path), true);
            if (! is_array($data)) {
                continue;
            }

            $items = [];
            foreach ($data['products'] ?? [] as $p) {
                if (empty($p['image'])) {
                    continue;
                }
                $items[] = [
                    'id' => $slug.'-'.($p['code'] ?? uniqid()),
                    'code' => $p['code'] ?? '',
                    'name' => $p['name'] ?? '',
                    'brand' => $p['brand'] ?? '',
                    'series' => $p['series'] ?? null,
                    'finish_hint' => $meta['finish_hint'],
                    'image' => asset('catalog/kastamonu-entegre/'.$slug.'/'.$p['image']),
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'slug' => $slug,
                'label' => $meta['label'],
                'finish_hint' => $meta['finish_hint'],
                'items' => $items,
            ];
        }

        return response()->json([
            'manufacturer' => 'Kastamonu Entegre',
            'groups' => $groups,
        ]);
    }

    public function model(string $file)
    {
        if (! preg_match('/^[a-z0-9\\-]+\\.glb$/', $file)) {
            abort(404);
        }
        $path = storage_path('app/models/tv-stands/'.$file);
        if (! is_file($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'model/gltf-binary',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
