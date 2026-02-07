@extends('layouts.app')
@section('title', 'Ayarlar')
@section('content')

<div x-data="{ deleteOpen: false, activeTab: 'firma', submitting: false }">
    <div class="mb-6">
        <h1 class="page-title">Ayarlar</h1>
        <p class="page-desc">Firma bilgileri, logo, SEO, SMS, ödeme ve e-posta ayarları</p>
    </div>

    @if($company?->logoUrl)
    <div x-show="deleteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="delete-logo-title">
        <div class="fixed inset-0 bg-black/50" @click="deleteOpen = false"></div>
        <div class="relative card max-w-sm w-full p-6" @keydown.escape.window="deleteOpen = false">
            <h2 id="delete-logo-title" class="text-base font-semibold text-slate-900 dark:text-slate-100">Logoyu sil</h2>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Mevcut logoyu silmek istediğinize emin misiniz?</p>
            <div class="mt-6 flex gap-3 justify-end">
                <button type="button" @click="deleteOpen = false" class="btn-secondary">İptal</button>
                <form method="POST" action="{{ route('settings.delete-logo') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 text-white text-sm font-medium hover:bg-red-700">Sil</button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" @submit="submitting = true">
        @csrf

        {{-- Tab list --}}
        <div class="flex flex-wrap gap-1 p-1 mb-6 rounded-xl bg-slate-100 dark:bg-slate-800 max-w-4xl" role="tablist">
            <button type="button" role="tab" @click="activeTab = 'firma'" :aria-selected="activeTab === 'firma'"
                :class="activeTab === 'firma' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">Firma</button>
            <button type="button" role="tab" @click="activeTab = 'logo'" :aria-selected="activeTab === 'logo'"
                :class="activeTab === 'logo' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">Logo</button>
            @if(\Illuminate\Support\Facades\Schema::hasColumn('companies', 'metaTitle'))
            <button type="button" role="tab" @click="activeTab = 'seo'" :aria-selected="activeTab === 'seo'"
                :class="activeTab === 'seo' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">SEO</button>
            @endif
            <button type="button" role="tab" @click="activeTab = 'sms'" :aria-selected="activeTab === 'sms'"
                :class="activeTab === 'sms' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">SMS</button>
            <button type="button" role="tab" @click="activeTab = 'paytr'" :aria-selected="activeTab === 'paytr'"
                :class="activeTab === 'paytr' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">PayTR</button>
            <button type="button" role="tab" @click="activeTab = 'efatura'" :aria-selected="activeTab === 'efatura'"
                :class="activeTab === 'efatura' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">E-Fatura</button>
            <button type="button" role="tab" @click="activeTab = 'mail'" :aria-selected="activeTab === 'mail'"
                :class="activeTab === 'mail' ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/60 dark:hover:bg-slate-600'"
                class="rounded-lg px-4 py-2.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-100 dark:focus:ring-offset-slate-800">E-posta</button>
        </div>

        {{-- Tab: Firma Bilgileri --}}
        <div x-show="activeTab === 'firma'" x-cloak class="card overflow-hidden mb-6">
            <div class="card-header">Firma Bilgileri</div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="form-label">Firma Adı</label><input type="text" name="name" value="{{ old('name', $company?->name) }}" class="form-input" placeholder="Firma adı"></div>
                    <div><label class="form-label">Vergi No</label><input type="text" name="taxNumber" value="{{ old('taxNumber', $company?->taxNumber) }}" class="form-input"></div>
                    <div><label class="form-label">Vergi Dairesi</label><input type="text" name="taxOffice" value="{{ old('taxOffice', $company?->taxOffice) }}" class="form-input"></div>
                    <div><label class="form-label">Telefon</label><input type="tel" name="phone" value="{{ old('phone', $company?->phone) }}" class="form-input" placeholder="0555 123 45 67" inputmode="tel" autocomplete="tel" pattern="[0-9+][0-9\s\-()]{9,19}" title="Örn: 0555 123 45 67"></div>
                    <div class="md:col-span-2"><label class="form-label">E-posta</label><input type="email" name="email" value="{{ old('email', $company?->email) }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email"></div>
                    <div class="md:col-span-2"><label class="form-label">Adres</label><textarea name="address" rows="2" class="form-input form-textarea">{{ old('address', $company?->address) }}</textarea></div>
                    <div><label class="form-label">Web sitesi</label><input type="text" name="website" value="{{ old('website', $company?->website) }}" class="form-input" placeholder="https://"></div>
                </div>
            </div>
        </div>

        {{-- Tab: Logo --}}
        <div x-show="activeTab === 'logo'" x-cloak class="space-y-6 mb-6">
            @if($company?->logoUrl)
            <div class="card overflow-hidden">
                <div class="card-header">Mevcut logo</div>
                <div class="p-5 flex flex-wrap items-center gap-4">
                    <img src="{{ asset($company->logoUrl) }}" alt="Firma logosu" class="h-24 w-auto object-contain border border-slate-200 dark:border-slate-600 rounded-xl p-2">
                    <button type="button" @click="deleteOpen = true" class="btn-secondary text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">Logoyu sil</button>
                </div>
            </div>
            @endif
            <div class="card overflow-hidden">
                <div class="card-header">Firma Logosu</div>
                <div class="p-5">
                    <label class="form-label">Yeni logo yükle</label>
                    <input type="file" name="logo" accept="image/*" class="form-input py-2">
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">PNG, JPG, max 2MB. Önerilen: 200×80px</p>
                </div>
            </div>
        </div>

        @if(\Illuminate\Support\Facades\Schema::hasColumn('companies', 'metaTitle'))
        {{-- Tab: SEO --}}
        <div x-show="activeTab === 'seo'" x-cloak class="card overflow-hidden mb-6">
            <div class="card-header">SEO Ayarları</div>
            <div class="p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Fatura ve PDF çıktıları, arama motoru meta bilgileri.</p>
                <div class="space-y-4">
                    <div><label class="form-label">Meta Başlık (max 70)</label><input type="text" name="metaTitle" value="{{ old('metaTitle', $company?->metaTitle ?? $company?->name) }}" class="form-input" maxlength="70"></div>
                    <div><label class="form-label">Meta Açıklama (max 160)</label><textarea name="metaDescription" rows="2" class="form-input form-textarea" maxlength="160">{{ old('metaDescription', $company?->metaDescription) }}</textarea></div>
                </div>
            </div>
        </div>
        @endif

        {{-- Tab: SMS --}}
        <div x-show="activeTab === 'sms'" x-cloak class="card overflow-hidden mb-6">
            <div class="card-header">SMS Entegrasyonu (NTGSM)</div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="form-label">Kullanıcı Adı</label><input type="text" name="ntgsmUsername" value="{{ old('ntgsmUsername', $company?->ntgsmUsername) }}" class="form-input"></div>
                    <div><label class="form-label">Şifre</label><input type="password" name="ntgsmPassword" value="{{ old('ntgsmPassword', $company?->ntgsmPassword) }}" class="form-input" placeholder="Değiştirmek için doldurun"></div>
                    <div><label class="form-label">Originator (Başlık)</label><input type="text" name="ntgsmOriginator" value="{{ old('ntgsmOriginator', $company?->ntgsmOriginator) }}" class="form-input"></div>
                    <div><label class="form-label">API URL</label><input type="text" name="ntgsmApiUrl" value="{{ old('ntgsmApiUrl', $company?->ntgsmApiUrl) }}" class="form-input"></div>
                </div>
            </div>
        </div>

        {{-- Tab: PayTR --}}
        <div x-show="activeTab === 'paytr'" x-cloak class="card overflow-hidden mb-6">
            <div class="card-header">Sanal Pos (PayTR)</div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="form-label">Merchant ID</label><input type="text" name="paytrMerchantId" value="{{ old('paytrMerchantId', $company?->paytrMerchantId) }}" class="form-input"></div>
                    <div><label class="form-label">Merchant Key</label><input type="text" name="paytrMerchantKey" value="{{ old('paytrMerchantKey', $company?->paytrMerchantKey) }}" class="form-input"></div>
                    <div><label class="form-label">Merchant Salt</label><input type="text" name="paytrMerchantSalt" value="{{ old('paytrMerchantSalt', $company?->paytrMerchantSalt) }}" class="form-input"></div>
                    <div class="md:col-span-2 flex items-center pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="paytrTestMode" value="1" {{ old('paytrTestMode', $company?->paytrTestMode) ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-500 text-emerald-600 focus:ring-emerald-500 bg-slate-100 dark:bg-slate-700">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Test modu</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: E-Fatura --}}
        <div x-show="activeTab === 'efatura'" x-cloak class="card overflow-hidden mb-6">
            <div class="card-header">E-Fatura Entegrasyonu (GİB / Entegratör)</div>
            <div class="p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">E-fatura gönderimi için GİB entegratör veya özel entegratör (örn. Fitbulut, Logo) API bilgilerini girin. UBL-TR 1.2 formatında fatura üretilir ve bu endpoint’e gönderilir.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="form-label">Sağlayıcı (opsiyonel)</label><input type="text" name="efaturaProvider" value="{{ old('efaturaProvider', $company?->efaturaProvider) }}" class="form-input" placeholder="gib, fitbulut, logo, vb."></div>
                    <div><label class="form-label">API Endpoint URL</label><input type="url" name="efaturaEndpoint" value="{{ old('efaturaEndpoint', $company?->efaturaEndpoint) }}" class="form-input" placeholder="https://..."></div>
                    <div><label class="form-label">Kullanıcı Adı</label><input type="text" name="efaturaUsername" value="{{ old('efaturaUsername', $company?->efaturaUsername) }}" class="form-input"></div>
                    <div><label class="form-label">Şifre</label><input type="password" name="efaturaPassword" value="{{ old('efaturaPassword', $company?->efaturaPassword) }}" class="form-input" placeholder="Değiştirmek için doldurun"></div>
                    <div class="md:col-span-2 flex items-center pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="efaturaTestMode" value="1" {{ old('efaturaTestMode', $company?->efaturaTestMode ?? true) ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-500 text-emerald-600 focus:ring-emerald-500 bg-slate-100 dark:bg-slate-700">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Test modu</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: E-posta --}}
        <div x-show="activeTab === 'mail'" x-cloak class="card overflow-hidden mb-6">
            <div class="card-header">E-posta (SMTP)</div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="form-label">SMTP Host</label><input type="text" name="mailHost" value="{{ old('mailHost', $company?->mailHost) }}" class="form-input"></div>
                    <div><label class="form-label">Port</label><input type="number" name="mailPort" value="{{ old('mailPort', $company?->mailPort) }}" class="form-input"></div>
                    <div><label class="form-label">Kullanıcı</label><input type="text" name="mailUser" value="{{ old('mailUser', $company?->mailUser) }}" class="form-input"></div>
                    <div><label class="form-label">Şifre</label><input type="password" name="mailPassword" value="{{ old('mailPassword', $company?->mailPassword) }}" class="form-input" placeholder="Değiştirmek için doldurun"></div>
                    <div><label class="form-label">Gönderen adresi</label><input type="email" name="mailFrom" value="{{ old('mailFrom', $company?->mailFrom) }}" class="form-input" placeholder="ornek@email.com" inputmode="email" autocomplete="email"></div>
                    <div class="md:col-span-2 flex items-center pt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="mailSecure" value="1" {{ old('mailSecure', $company?->mailSecure) ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-500 text-emerald-600 focus:ring-emerald-500 bg-slate-100 dark:bg-slate-700">
                            <span class="text-sm text-slate-700 dark:text-slate-300">SSL/TLS kullan</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" :disabled="submitting" class="btn-primary disabled:opacity-70 disabled:cursor-not-allowed">
                <template x-if="submitting"><svg class="w-5 h-5 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></template>
                <template x-if="!submitting"><svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></template>
                <span x-text="submitting ? 'Kaydediliyor...' : 'Ayarları Kaydet'">Ayarları Kaydet</span>
            </button>
            <a href="{{ route('dashboard') }}" class="btn-secondary">İptal</a>
        </div>
    </form>
</div>
@endsection
