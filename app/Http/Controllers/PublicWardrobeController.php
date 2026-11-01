<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WardrobeProject;
use App\Models\WardrobeQuote;
use App\Services\AuditService;
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
        $this->findProject($token, 'delete')->delete();

        return response()->json(['ok' => true, 'message' => 'Proje silindi.']);
    }

    public function destroyProjectById(string $project): JsonResponse
    {
        $this->findProjectById($project, 'delete')->delete();

        return response()->json(['ok' => true, 'message' => 'Proje silindi.']);
    }

    public function storeProject(Request $request): JsonResponse
    {
        if (! Schema::hasTable('wardrobe_projects') || ! Schema::hasColumn('wardrobe_projects', 'userId')) {
            return response()->json([
                'ok' => false,
                'message' => 'Proje kaydı henüz hazır değil.',
            ], 503);
        }

        $data = $this->projectPayload($request);
        $project = WardrobeProject::create([
            'token' => Str::random(24),
            'userId' => (string) $request->user()->id,
            'customerName' => $data['customerName'],
            'phone' => $data['phone'],
            'projectName' => $data['projectName'],
            'note' => $data['note'],
            'design' => $data['design'],
        ]);

        return response()->json([
            'ok' => true,
            'id' => $project->id,
            'token' => $project->token,
            'message' => 'Proje kaydedildi.',
        ]);
    }

    public function updateProject(Request $request, string $token): JsonResponse
    {
        return $this->updateOwned($request, $this->findProject($token, 'update'));
    }

    public function updateProjectById(Request $request, string $project): JsonResponse
    {
        return $this->updateOwned($request, $this->findProjectById($project, 'update'));
    }

    public function showProject(string $token): JsonResponse
    {
        return $this->projectResponse($this->findProject($token, 'view'));
    }

    public function showProjectById(string $project): JsonResponse
    {
        return $this->projectResponse($this->findProjectById($project, 'view'));
    }

    public function projects(Request $request): JsonResponse
    {
        if (! Schema::hasTable('wardrobe_projects') || ! Schema::hasColumn('wardrobe_projects', 'userId')) {
            return response()->json(['ok' => true, 'projects' => []]);
        }

        $actor = $request->user();
        $projects = WardrobeProject::query()
            ->where(function ($query) use ($actor) {
                $query->where('userId', (string) $actor->id);
                if ($actor->isAdmin()) {
                    $query->orWhereNull('userId');
                }
            })
            ->latest('updatedAt')
            ->limit(20)
            ->get(['id', 'projectName', 'updatedAt'])
            ->map(fn (WardrobeProject $project) => [
                'id' => $project->id,
                'projectName' => $project->projectName,
                'updatedAt' => optional($project->updatedAt)->toIso8601String(),
            ])
            ->values();

        return response()->json(['ok' => true, 'projects' => $projects]);
    }

    private function updateOwned(Request $request, WardrobeProject $project): JsonResponse
    {
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
            'id' => $project->id,
            'token' => $project->token,
            'message' => 'Proje güncellendi.',
        ]);
    }

    private function projectResponse(WardrobeProject $project): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'id' => $project->id,
            'token' => $project->token,
            'customerName' => $project->customerName,
            'phone' => $project->phone,
            'projectName' => $project->projectName,
            'note' => $project->note,
            'design' => $project->design,
            'updatedAt' => optional($project->updatedAt)->toIso8601String(),
        ]);
    }

    private function findProject(string $token, string $ability): WardrobeProject
    {
        if (! preg_match('/^[A-Za-z0-9]{24}$/', $token) || ! Schema::hasTable('wardrobe_projects')) {
            $this->missingProject();
        }

        $project = WardrobeProject::query()->where('token', $token)->first();
        if (! $project) {
            $this->missingProject();
        }

        return $this->authorizeProject($project, $ability);
    }

    private function findProjectById(string $id, string $ability): WardrobeProject
    {
        if (! Schema::hasTable('wardrobe_projects')) {
            $this->missingProject();
        }

        $project = WardrobeProject::query()->where('id', $id)->first();
        if (! $project) {
            $this->missingProject();
        }

        return $this->authorizeProject($project, $ability);
    }

    private function authorizeProject(WardrobeProject $project, string $ability): WardrobeProject
    {
        $user = request()->user();
        if (! $user || ! $user->can($ability, $project)) {
            if ($user) {
                app(AuditService::class)->logAction('wardrobe_project', (string) $project->id, 'denied', [
                    'ability' => $ability,
                ]);
            }
            $this->missingProject();
        }

        return $project;
    }

    private function missingProject(): never
    {
        abort(response()->json([
            'ok' => false,
            'message' => 'Proje bulunamadı.',
        ], 404));
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
                    'image' => asset('catalog/kastamonu-entegre/'.$slug.'/'.$p['image']).'?v=3',
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
