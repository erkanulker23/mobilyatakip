<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WardrobeProject;
use App\Models\WardrobeQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicWardrobeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $token = (string) $request->query('proje', '');
        if (preg_match('/^[A-Za-z0-9]{24}$/', $token)) {
            return redirect()->route('wardrobe.design', ['proje' => $token]);
        }

        return view('wardrobe.projects', [
            'company' => Company::first(),
            'projectStoreUrl' => route('wardrobe.project.store'),
            'projectIndexUrl' => route('wardrobe.project.index'),
            'designUrl' => route('wardrobe.design'),
        ]);
    }

    public function design(): View
    {
        return view('wardrobe.index', [
            'company' => Company::first(),
            'quoteUrl' => route('wardrobe.quote'),
            'materialsUrl' => route('wardrobe.materials'),
            'projectStoreUrl' => route('wardrobe.project.store'),
            'projectIndexUrl' => route('wardrobe.project.index'),
        ]);
    }

    public function modulePreview(): View
    {
        return view('wardrobe.module', [
            'company' => Company::first(),
            'modelUrl' => asset('models/wardrobe/cift-v1-dolu.glb'),
        ]);
    }

    public function destroyProject(string $token): JsonResponse
    {
        $this->findProject($token)->delete();

        return response()->json(['ok' => true, 'message' => 'Proje silindi.']);
    }

    public function storeProject(Request $request): JsonResponse
    {
        if (! Schema::hasTable('wardrobe_projects')) {
            return response()->json([
                'ok' => false,
                'message' => 'Proje kaydı henüz hazır değil.',
            ], 503);
        }

        $data = $this->projectPayload($request);
        $project = WardrobeProject::create([
            'token' => Str::random(24),
            'customerName' => $data['customerName'],
            'phone' => $data['phone'],
            'projectName' => $data['projectName'],
            'note' => $data['note'],
            'design' => $data['design'],
        ]);

        return response()->json([
            'ok' => true,
            'token' => $project->token,
            'message' => 'Proje kaydedildi.',
        ]);
    }

    public function updateProject(Request $request, string $token): JsonResponse
    {
        $project = $this->findProject($token);
        $data = $this->projectPayload($request);
        $project->update([
            'customerName' => $data['customerName'],
            'phone' => $data['phone'],
            'projectName' => $data['projectName'],
            'note' => $data['note'],
            'design' => $data['design'],
        ]);

        return response()->json([
            'ok' => true,
            'token' => $project->token,
            'message' => 'Proje güncellendi.',
        ]);
    }

    public function showProject(string $token): JsonResponse
    {
        $project = $this->findProject($token);

        return response()->json([
            'ok' => true,
            'token' => $project->token,
            'customerName' => $project->customerName,
            'phone' => $project->phone,
            'projectName' => $project->projectName,
            'note' => $project->note,
            'design' => $project->design,
            'updatedAt' => optional($project->updatedAt)->toIso8601String(),
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        if (! Schema::hasTable('wardrobe_projects')) {
            return response()->json(['ok' => true, 'projects' => []]);
        }

        $phone = trim((string) $request->query('phone', ''));
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $projects = WardrobeProject::query()
            ->latest('updatedAt')
            ->limit(200)
            ->get();

        if (strlen($digits) >= 10) {
            $needle = substr($digits, -10);
            $projects = $projects->filter(function (WardrobeProject $project) use ($needle) {
                $stored = preg_replace('/\D+/', '', (string) $project->phone) ?? '';

                return $stored !== '' && str_ends_with($stored, $needle);
            });
        }

        $projects = $projects->take(20)
            ->map(fn (WardrobeProject $project) => [
                'token' => $project->token,
                'customerName' => $project->customerName,
                'phone' => $project->phone,
                'projectName' => $project->projectName,
                'updatedAt' => optional($project->updatedAt)->toIso8601String(),
            ])
            ->values();

        return response()->json(['ok' => true, 'projects' => $projects]);
    }

    private function findProject(string $token): WardrobeProject
    {
        if (! preg_match('/^[A-Za-z0-9]{24}$/', $token) || ! Schema::hasTable('wardrobe_projects')) {
            abort(404);
        }

        return WardrobeProject::query()->where('token', $token)->firstOrFail();
    }

    private function projectPayload(Request $request): array
    {
        $data = $request->validate([
            'customerName' => 'required|string|max:120',
            'phone' => 'nullable|string|max:40',
            'projectName' => 'required|string|max:80',
            'note' => 'nullable|string|max:2000',
            'design' => 'required|array',
        ]);
        $data['note'] = $data['note'] ?? null;
        $data['phone'] = $data['phone'] ?? '';

        return $data;
    }

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:40',
            'note' => 'nullable|string|max:2000',
            'design' => 'required|array',
        ]);

        if (! Schema::hasTable('wardrobe_quotes')) {
            return response()->json([
                'ok' => false,
                'message' => 'Teklif kaydı henüz hazır değil. Tasarımınız tarayıcıda duruyor.',
            ], 503);
        }

        $quote = WardrobeQuote::create([
            'customerName' => $data['name'],
            'phone' => $data['phone'],
            'note' => $data['note'] ?? null,
            'design' => $data['design'],
        ]);

        return response()->json([
            'ok' => true,
            'id' => $quote->id,
            'message' => 'Talebiniz alındı. Atölye sizinle iletişime geçecek.',
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
}
