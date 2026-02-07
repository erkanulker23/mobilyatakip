<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    public function index()
    {
        $company = Company::first();
        return view('settings.index', compact('company'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'taxNumber' => 'nullable|string|max:50',
            'taxOffice' => 'nullable|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'email' => 'nullable|email',
            'website' => 'nullable|string|max:255',
            'metaTitle' => 'nullable|string|max:70',
            'metaDescription' => 'nullable|string|max:160',
            'ntgsmUsername' => 'nullable|string|max:255',
            'ntgsmPassword' => 'nullable|string|max:255',
            'ntgsmOriginator' => 'nullable|string|max:50',
            'ntgsmApiUrl' => 'nullable|string|max:500',
            'paytrMerchantId' => 'nullable|string|max:50',
            'paytrMerchantKey' => 'nullable|string|max:255',
            'paytrMerchantSalt' => 'nullable|string|max:255',
            'paytrTestMode' => 'nullable|boolean',
            'mailHost' => 'nullable|string|max:255',
            'mailPort' => 'nullable|integer|min:1|max:65535',
            'mailUser' => 'nullable|string|max:255',
            'mailPassword' => 'nullable|string|max:255',
            'mailFrom' => 'nullable|email',
            'mailSecure' => 'nullable|boolean',
            'efaturaProvider' => 'nullable|string|max:50',
            'efaturaEndpoint' => 'nullable|string|max:500',
            'efaturaUsername' => 'nullable|string|max:255',
            'efaturaPassword' => 'nullable|string|max:255',
            'efaturaTestMode' => 'nullable|boolean',
        ], [
            'phone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);

        $validated['paytrTestMode'] = $request->boolean('paytrTestMode');
        $validated['mailSecure'] = $request->boolean('mailSecure');
        $validated['efaturaTestMode'] = $request->boolean('efaturaTestMode');

        if (empty($request->ntgsmPassword)) unset($validated['ntgsmPassword']);
        if (empty($request->efaturaPassword)) unset($validated['efaturaPassword']);
        if (empty($request->mailPassword)) unset($validated['mailPassword']);

        // metaTitle/metaDescription kolonları yoksa çıkar (eski DB uyumluluğu)
        if (!Schema::hasColumn('companies', 'metaTitle')) unset($validated['metaTitle']);
        if (!Schema::hasColumn('companies', 'metaDescription')) unset($validated['metaDescription']);
        if (!Schema::hasColumn('companies', 'efaturaProvider')) {
            foreach (['efaturaProvider', 'efaturaEndpoint', 'efaturaUsername', 'efaturaPassword', 'efaturaTestMode'] as $k) {
                unset($validated[$k]);
            }
        }

        $company = Company::first();
        if (!$company) {
            $company = Company::create($validated);
        } else {
            $company->update($validated);
        }

        // Logo yükleme
        if ($request->hasFile('logo')) {
            if ($company->logoUrl) {
                Storage::disk('public')->delete(str_replace('/storage/', '', parse_url($company->logoUrl, PHP_URL_PATH)));
            }
            $path = $request->file('logo')->store('company', 'public');
            $company->update(['logoUrl' => '/storage/' . $path]);
        }

        return redirect()->route('settings.index')->with('success', 'Ayarlar kaydedildi.');
    }

    public function deleteLogo(Request $request)
    {
        $company = Company::first();
        if ($company && $company->logoUrl) {
            $path = str_replace('/storage/', '', parse_url($company->logoUrl, PHP_URL_PATH));
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            $company->update(['logoUrl' => null]);
        }
        return redirect()->route('settings.index')->with('success', 'Logo silindi.');
    }
}
