<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WardrobeQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PublicWardrobeController extends Controller
{
    public function index(): View
    {
        return view('wardrobe.index', [
            'company' => Company::first(),
            'quoteUrl' => route('wardrobe.quote'),
        ]);
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
}
