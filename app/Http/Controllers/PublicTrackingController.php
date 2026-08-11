<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\PublicTrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    public function __construct(
        private PublicTrackingService $tracking
    ) {}

    public function index(Request $request): View
    {
        $company = Company::first();
        $code = $request->query('kod', $request->query('code', ''));

        return view('tracking.index', [
            'company' => $company,
            'code' => is_string($code) ? $this->tracking->normalizeCode($code) : '',
            'result' => null,
            'notFound' => false,
        ]);
    }

    public function lookup(Request $request): View
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40'],
        ], [
            'code.required' => 'Takip kodunu girin.',
            'code.min' => 'Takip kodu çok kısa.',
        ]);

        $code = $this->tracking->normalizeCode($validated['code']);
        $found = $this->tracking->findByCode($code);
        $company = Company::first();

        if (! $found) {
            return view('tracking.index', [
                'company' => $company,
                'code' => $code,
                'result' => null,
                'notFound' => true,
            ]);
        }

        $result = $found['type'] === 'sale'
            ? $this->tracking->buildSalePayload($found['sale'])
            : $this->tracking->buildTicketPayload($found['ticket']);

        return view('tracking.index', [
            'company' => $company,
            'code' => $found['code'],
            'result' => $result,
            'notFound' => false,
        ]);
    }

    public function show(string $code): View
    {
        $code = $this->tracking->normalizeCode($code);
        $found = $this->tracking->findByCode($code);
        $company = Company::first();

        if (! $found) {
            return view('tracking.index', [
                'company' => $company,
                'code' => $code,
                'result' => null,
                'notFound' => true,
            ]);
        }

        $result = $found['type'] === 'sale'
            ? $this->tracking->buildSalePayload($found['sale'])
            : $this->tracking->buildTicketPayload($found['ticket']);

        return view('tracking.index', [
            'company' => $company,
            'code' => $found['code'],
            'result' => $result,
            'notFound' => false,
        ]);
    }
}
