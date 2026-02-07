<?php

namespace App\Http\Controllers;

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
            'supplier_mode' => 'nullable|in:existing,new',
            'newSupplierName' => 'nullable|string|max:255',
        ]);

        $supplierId = null;
        if (($request->input('supplier_mode') === 'new') && !empty(trim($request->input('newSupplierName')))) {
            $supplier = Supplier::create([
                'name' => trim($request->input('newSupplierName')),
                'isActive' => true,
            ]);
            $supplierId = $supplier->id;
        } elseif (!empty($request->input('supplierId'))) {
            $supplierId = $request->input('supplierId');
        }

        XmlFeed::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'supplierId' => $supplierId,
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
        if (!$xmlFeed->supplierId) {
            $request->validate([
                'supplierId' => 'nullable|exists:suppliers,id',
                'newSupplierName' => 'nullable|string|max:255',
            ]);
            $supplierId = null;
            if (!empty(trim((string) $request->input('newSupplierName')))) {
                $supplier = Supplier::create([
                    'name' => trim($request->input('newSupplierName')),
                    'isActive' => true,
                ]);
                $supplierId = $supplier->id;
            } elseif (!empty($request->input('supplierId'))) {
                $supplierId = $request->input('supplierId');
            }
            if (!$supplierId) {
                return redirect()->route('xml-feeds.sync-supplier', $xmlFeed)
                    ->with('error', 'Ürünleri eklemek için bir tedarikçi seçin veya yeni tedarikçi adı girin.');
            }
            $xmlFeed->update(['supplierId' => $supplierId]);
        }

        try {
            $result = $this->xmlFeedService->syncFeed($xmlFeed);
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
