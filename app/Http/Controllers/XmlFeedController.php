<?php

namespace App\Http\Controllers;

use App\Jobs\SyncXmlFeedJob;
use App\Models\Product;
use App\Models\XmlFeed;
use App\Models\Supplier;
use App\Services\XmlFeedService;
use Illuminate\Http\Request;

class XmlFeedController extends Controller
{
    public function __construct(private XmlFeedService $xmlFeedService) {}

    public function index()
    {
        $feeds = XmlFeed::with('supplier')->orderBy('name')->get();
        return view('xml-feeds.index', compact('feeds'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        return view('xml-feeds.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'supplierId' => 'nullable|exists:suppliers,id',
            'create_suppliers' => 'nullable|boolean',
        ]);

        $supplierId = $request->filled('supplierId') ? $request->input('supplierId') : null;
        $createSuppliers = $request->boolean('create_suppliers');

        XmlFeed::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'supplierId' => $supplierId,
            'createSuppliers' => $createSuppliers,
        ]);
        return redirect()->route('xml-feeds.index')->with('success', 'XML Feed kaydedildi.');
    }

    public function syncSupplierForm(XmlFeed $xmlFeed)
    {
        if ($xmlFeed->supplierId) {
            return redirect()->route('xml-feeds.index')->with('info', 'Bu feed zaten bir tedarikçiye bağlı. Ürün çekmek için "Ürün Çek" butonunu kullanın.');
        }
        $suppliers = Supplier::orderBy('name')->get();
        return view('xml-feeds.sync-supplier', compact('xmlFeed', 'suppliers'));
    }

    public function sync(Request $request, XmlFeed $xmlFeed)
    {
        if (!$xmlFeed->supplierId && $request->filled('supplierId')) {
            $request->validate(['supplierId' => 'exists:suppliers,id']);
            $xmlFeed->update(['supplierId' => $request->input('supplierId')]);
        }

        $createSuppliers = $request->has('create_suppliers') ? $request->boolean('create_suppliers') : ($xmlFeed->createSuppliers ?? true);
        $runInBackground = $request->boolean('run_in_background', true);

        if ($runInBackground) {
            SyncXmlFeedJob::dispatch($xmlFeed->fresh(), $createSuppliers);
            return redirect()->route('xml-feeds.index')
                ->with('success', 'Ürün çekme işi kuyruğa alındı. Arka planda işlenecek, sayfa donmaz. Queue worker çalışıyorsa kısa süre içinde tamamlanır.');
        }

        set_time_limit(600);
        try {
            $result = $this->xmlFeedService->syncFeed($xmlFeed, $createSuppliers);
            $msg = sprintf('%d ürün eklendi, %d güncellendi.', $result['created'], $result['updated'] ?? 0);
            $suppliersCreated = $result['suppliersCreated'] ?? 0;
            if ($suppliersCreated > 0) {
                $msg .= sprintf(' %d tedarikçi eklendi.', $suppliersCreated);
            }
            if (!empty($result['errors'])) {
                $msg .= ' Hatalar: ' . implode(', ', array_slice($result['errors'], 0, 3));
            }
            return redirect()->route('xml-feeds.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('xml-feeds.index')->with('error', 'Hata: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, XmlFeed $xmlFeed)
    {
        $deleteProducts = $request->boolean('deleteProducts');
        if ($deleteProducts) {
            Product::where('externalSource', $xmlFeed->url)->delete();
        }
        $xmlFeed->delete();
        return redirect()->route('xml-feeds.index')->with('success', 'XML Feed silindi.');
    }
}
