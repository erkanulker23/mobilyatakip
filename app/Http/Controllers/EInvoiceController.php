<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sale;
use App\Services\EInvoiceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EInvoiceController extends Controller
{
    public function __construct(
        private EInvoiceService $eInvoiceService
    ) {}

    /**
     * Satış faturasını e-fatura olarak GİB/entegratöre gönderir.
     */
    public function sendSale(Sale $sale)
    {
        $result = $this->eInvoiceService->sendSaleEInvoice($sale);
        if (request()->wantsJson()) {
            return response()->json($result);
        }
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Alış faturasını e-fatura olarak gönderir.
     */
    public function sendPurchase(Purchase $purchase)
    {
        $result = $this->eInvoiceService->sendPurchaseEInvoice($purchase);
        if (request()->wantsJson()) {
            return response()->json($result);
        }
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Satış faturası UBL-TR XML'ini indirir.
     */
    public function downloadSaleXml(Sale $sale): Response
    {
        $xml = $this->eInvoiceService->buildSaleInvoiceXml($sale);
        $filename = 'efatura-' . $sale->saleNumber . '-' . now()->format('Y-m-d') . '.xml';
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Alış faturası UBL-TR XML'ini indirir.
     */
    public function downloadPurchaseXml(Purchase $purchase): Response
    {
        $xml = $this->eInvoiceService->buildPurchaseInvoiceXml($purchase);
        $filename = 'efatura-alis-' . $purchase->purchaseNumber . '-' . now()->format('Y-m-d') . '.xml';
        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
