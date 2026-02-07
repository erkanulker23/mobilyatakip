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
        ]);
        XmlFeed::create($validated);
        return redirect()->route('xml-feeds.index')->with('success', 'XML Feed kaydedildi.');
    }

    public function sync(Request $request, XmlFeed $xmlFeed)
    {
        try {
            $result = $this->xmlFeedService->syncFeed($xmlFeed);
            $msg = sprintf('%d ürün eklendi, %d güncellendi.', $result['created'], $result['updated'] ?? 0);
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
