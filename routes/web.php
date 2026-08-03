<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\KasaController;
use App\Http\Controllers\ServiceTicketController;
use App\Models\Company;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/assets/js/{file}', function (string $file) {
    $allowed = ['turkey-address.js', 'money.js', 'form-inputs.js', 'payment-kasa.js', 'image-upload-compress.js'];
    if (! in_array($file, $allowed, true)) {
        abort(404);
    }
    $path = public_path('js/' . $file);
    if (! is_file($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->where('file', '[a-z\\-]+\\.js')->name('assets.js');

Route::get('/media/{path}', function (string $path) {
    $path = str_replace('\\', '/', $path);
    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    $full = Storage::disk('public')->path($path);
    $mime = @mime_content_type($full) ?: 'application/octet-stream';

    return response()->file($full, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*')->name('storage.file');

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/sifremi-unuttum', [\App\Http\Controllers\PasswordResetController::class, 'showForgotForm'])->name('password.request')->middleware('guest');
Route::post('/sifremi-unuttum', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('guest');
Route::get('/sifre-sifirla/{token}', [\App\Http\Controllers\PasswordResetController::class, 'showResetForm'])->name('password.reset')->middleware('guest');
Route::post('/sifre-sifirla', [\App\Http\Controllers\PasswordResetController::class, 'reset'])->name('password.update')->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/bildirimler/temizle', [\App\Http\Controllers\NotificationController::class, 'dismiss'])->name('notifications.dismiss');
    Route::get('/yapilacaklar', [DashboardController::class, 'tasks'])->name('tasks.index');

    Route::get('/company-logo', function () {
        $company = Company::first();
        if (!$company?->logoUrl) {
            abort(404);
        }
        $path = ltrim(str_replace('/storage/', '', parse_url($company->logoUrl, PHP_URL_PATH) ?: ''), '/');
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }
        return response()->file(Storage::disk('public')->path($path));
    })->name('company.logo');

    Route::post('/api/customers/quick-store', [CustomerController::class, 'quickStore'])->name('api.customers.quick-store');
    Route::get('/api/customers/{customer}/sales', [CustomerController::class, 'salesJson'])->name('api.customers.sales');
    Route::get('/api/turkey/cities', [\App\Http\Controllers\TurkeyLocationController::class, 'cities'])->name('api.turkey.cities');
    Route::get('/api/turkey/districts', [\App\Http\Controllers\TurkeyLocationController::class, 'districts'])->name('api.turkey.districts');
    Route::post('/api/products/quick-store', [ProductController::class, 'quickStore'])->name('api.products.quick-store');
    Route::get('/api/products/search', [ProductController::class, 'searchForSelect'])->name('api.products.search');
    Route::get('/api/user-tasks', [\App\Http\Controllers\UserTaskController::class, 'index'])->name('api.user-tasks.index');
    Route::post('/api/user-tasks', [\App\Http\Controllers\UserTaskController::class, 'store'])->name('api.user-tasks.store');
    Route::patch('/api/user-tasks/{userTask}', [\App\Http\Controllers\UserTaskController::class, 'update'])->name('api.user-tasks.update');
    Route::delete('/api/user-tasks/{userTask}', [\App\Http\Controllers\UserTaskController::class, 'destroy'])->name('api.user-tasks.destroy');
    Route::get('/customers/{customer}/print', [CustomerController::class, 'print'])->name('customers.print');
    Route::get('/customers/{customer}/tahsilatlar/yazdir', [CustomerController::class, 'printPayments'])->name('customers.payments.print');
    Route::get('/customers/excel/export', [CustomerController::class, 'exportExcel'])->name('customers.excel.export');
    Route::post('/customers/excel/import', [CustomerController::class, 'importExcel'])->name('customers.excel.import');
    Route::resource('customers', CustomerController::class);
    Route::get('/suppliers/{supplier}/print', [SupplierController::class, 'print'])->name('suppliers.print');
    Route::get('/suppliers/excel/export', [SupplierController::class, 'exportExcel'])->name('suppliers.excel.export');
    Route::post('/suppliers/excel/import', [SupplierController::class, 'importExcel'])->name('suppliers.excel.import');
    Route::post('/suppliers/actions/bulk-destroy', [SupplierController::class, 'bulkDestroy'])->name('suppliers.bulk-destroy');
    Route::resource('suppliers', SupplierController::class);
    Route::resource('shipping-companies', \App\Http\Controllers\ShippingCompanyController::class)->parameters(['shipping-companies' => 'shippingCompany']);
    Route::post('/shipping-companies/{shippingCompany}/vehicles', [\App\Http\Controllers\ShippingCompanyController::class, 'storeVehicle'])->name('shipping-companies.vehicles.store');
    Route::put('/shipping-companies/{shippingCompany}/vehicles/{shippingCompanyVehicle}', [\App\Http\Controllers\ShippingCompanyController::class, 'updateVehicle'])->name('shipping-companies.vehicles.update');
    Route::delete('/shipping-companies/{shippingCompany}/vehicles/{shippingCompanyVehicle}', [\App\Http\Controllers\ShippingCompanyController::class, 'destroyVehicle'])->name('shipping-companies.vehicles.destroy');
    Route::post('/products/actions/bulk-destroy', [ProductController::class, 'bulkDestroy'])->name('products.bulk-destroy');
    Route::resource('products', ProductController::class);
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class);
    Route::delete('/personnel/{personnel}/resim-sil', [\App\Http\Controllers\PersonnelController::class, 'deletePhoto'])->name('personnel.delete-photo');
    Route::resource('personnel', \App\Http\Controllers\PersonnelController::class);
    Route::post('/kasa/{kasa}/virman', [KasaController::class, 'transfer'])->name('kasa.transfer');
    Route::post('/kasa/{kasa}/acilis-sifirla', [KasaController::class, 'resetOpeningBalance'])->name('kasa.reset-opening');
    Route::delete('/kasa/{kasa}/hareketler/{hareket}', [KasaController::class, 'destroyMovement'])->name('kasa.hareketler.destroy');
    Route::resource('kasa', KasaController::class)->parameters(['kasa' => 'kasa']);

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/low', [StockController::class, 'lowStock'])->name('stock.low');
    Route::get('/stock/{stock}/edit', [StockController::class, 'edit'])->name('stock.edit');
    Route::put('/stock/{stock}', [StockController::class, 'update'])->name('stock.update');

    Route::resource('quotes', QuoteController::class);
    Route::post('/quotes/actions/bulk-destroy', [QuoteController::class, 'bulkDestroy'])->name('quotes.bulk-destroy');
    Route::get('/quotes/{quote}/print', [QuoteController::class, 'print'])->name('quotes.print');
    Route::get('/quotes/{quote}/email', [QuoteController::class, 'email'])->name('quotes.email');
    Route::post('/quotes/{quote}/send-email', [QuoteController::class, 'sendEmail'])->name('quotes.sendEmail');
    Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');

    Route::resource('sales', SaleController::class);
    Route::post('/sales/actions/bulk-destroy', [SaleController::class, 'bulkDestroy'])->name('sales.bulk-destroy');
    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::get('/sales/{sale}/shipment', [SaleController::class, 'shipment'])->name('sales.shipment');
    Route::get('/sales/{sale}/workshop/koltuk', [SaleController::class, 'workshopKoltuk'])->name('sales.workshop.koltuk');
    Route::get('/sales/{sale}/workshop/mobilya', [SaleController::class, 'workshopMobilya'])->name('sales.workshop.mobilya');
    Route::get('/sales/{sale}/shipment/pdf', [SaleController::class, 'shipmentPdf'])->name('sales.shipment.pdf');
    Route::get('/sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales/{sale}/delivered', [SaleController::class, 'markDelivered'])->name('sales.mark-delivered');
    Route::post('/sales/{sale}/undelivered', [SaleController::class, 'unmarkDelivered'])->name('sales.unmark-delivered');
    Route::post('/sales/{sale}/status', [SaleController::class, 'updateStatus'])->name('sales.update-status');
    Route::post('/sales/{sale}/convert-to-quote', [SaleController::class, 'convertToQuote'])->name('sales.convert-to-quote');
    Route::post('/sales/{sale}/send-supplier-email', [SaleController::class, 'sendSupplierEmail'])->name('sales.send-supplier-email');
    Route::post('/sales/{sale}/send-customer-email', [SaleController::class, 'sendCustomerEmail'])->name('sales.send-customer-email');
    Route::post('/sales/{sale}/activity', [SaleController::class, 'addActivity'])->name('sales.activity');
    Route::post('/sales/{sale}/efatura/send', [\App\Http\Controllers\EInvoiceController::class, 'sendSale'])->name('sales.efatura.send');
    Route::get('/sales/{sale}/efatura/xml', [\App\Http\Controllers\EInvoiceController::class, 'downloadSaleXml'])->name('sales.efatura.xml');
    Route::resource('purchases', PurchaseController::class);
    Route::post('/purchases/actions/bulk-destroy', [PurchaseController::class, 'bulkDestroy'])->name('purchases.bulk-destroy');
    Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
    Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('/purchases/{purchase}/efatura/send', [\App\Http\Controllers\EInvoiceController::class, 'sendPurchase'])->name('purchases.efatura.send');
    Route::get('/purchases/{purchase}/efatura/xml', [\App\Http\Controllers\EInvoiceController::class, 'downloadPurchaseXml'])->name('purchases.efatura.xml');
    Route::resource('service-tickets', ServiceTicketController::class)->parameters(['service-tickets' => 'serviceTicket']);
    Route::post('/service-tickets/{serviceTicket}/problem-status', [ServiceTicketController::class, 'updateProblemStatus'])->name('service-tickets.problem-status');
    Route::patch('/service-tickets/{serviceTicket}/status', [ServiceTicketController::class, 'updateStatus'])->name('service-tickets.update-status');
    Route::get('/service-tickets/{serviceTicket}/print', [ServiceTicketController::class, 'print'])->name('service-tickets.print');

    Route::get('/odeme-al', [\App\Http\Controllers\CustomerPaymentController::class, 'create'])->name('customer-payments.create');
    Route::post('/odeme-al', [\App\Http\Controllers\CustomerPaymentController::class, 'store'])->name('customer-payments.store');
    Route::get('/customer-payments/{customerPayment}', [\App\Http\Controllers\CustomerPaymentController::class, 'show'])->name('customer-payments.show');
    Route::get('/customer-payments/{customerPayment}/edit', [\App\Http\Controllers\CustomerPaymentController::class, 'edit'])->name('customer-payments.edit');
    Route::put('/customer-payments/{customerPayment}', [\App\Http\Controllers\CustomerPaymentController::class, 'update'])->name('customer-payments.update');
    Route::delete('/customer-payments/{customerPayment}', [\App\Http\Controllers\CustomerPaymentController::class, 'destroy'])->name('customer-payments.destroy');
    Route::get('/customer-payments/{customerPayment}/print', [\App\Http\Controllers\CustomerPaymentController::class, 'print'])->name('customer-payments.print');
    Route::get('/odeme-yap', [\App\Http\Controllers\SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
    Route::post('/odeme-yap', [\App\Http\Controllers\SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
    Route::get('/supplier-payments/{supplierPayment}', [\App\Http\Controllers\SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
    Route::get('/supplier-payments/{supplierPayment}/edit', [\App\Http\Controllers\SupplierPaymentController::class, 'edit'])->name('supplier-payments.edit');
    Route::put('/supplier-payments/{supplierPayment}', [\App\Http\Controllers\SupplierPaymentController::class, 'update'])->name('supplier-payments.update');
    Route::delete('/supplier-payments/{supplierPayment}', [\App\Http\Controllers\SupplierPaymentController::class, 'destroy'])->name('supplier-payments.destroy');
    Route::get('/nakliye-odeme', [\App\Http\Controllers\ShippingCompanyPaymentController::class, 'create'])->name('shipping-company-payments.create');
    Route::post('/nakliye-odeme', [\App\Http\Controllers\ShippingCompanyPaymentController::class, 'store'])->name('shipping-company-payments.store');
    Route::get('/shipping-company-payments/{shippingCompanyPayment}', [\App\Http\Controllers\ShippingCompanyPaymentController::class, 'show'])->name('shipping-company-payments.show');
    Route::get('/shipping-company-payments/{shippingCompanyPayment}/edit', [\App\Http\Controllers\ShippingCompanyPaymentController::class, 'edit'])->name('shipping-company-payments.edit');
    Route::put('/shipping-company-payments/{shippingCompanyPayment}', [\App\Http\Controllers\ShippingCompanyPaymentController::class, 'update'])->name('shipping-company-payments.update');
    Route::delete('/shipping-company-payments/{shippingCompanyPayment}', [\App\Http\Controllers\ShippingCompanyPaymentController::class, 'destroy'])->name('shipping-company-payments.destroy');

    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);
    Route::get('/raporlar', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
    Route::get('/raporlar/termin-yaklasan', [\App\Http\Controllers\ReportsController::class, 'upcomingDue'])->name('reports.upcoming-due');
    Route::get('/raporlar/termin-yaklasan/yazdir', [\App\Http\Controllers\ReportsController::class, 'upcomingDuePrint'])->name('reports.upcoming-due.print');
    Route::get('/raporlar/termin-yaklasan/sevkiyat-yazdir', [\App\Http\Controllers\ReportsController::class, 'upcomingDueShipmentPrint'])->name('reports.upcoming-due.shipment-print');
    Route::get('/raporlar/satislar', [\App\Http\Controllers\ReportsController::class, 'sales'])->name('reports.sales');
    Route::get('/raporlar/satislar/yazdir', [\App\Http\Controllers\ReportsController::class, 'salesPrint'])->name('reports.sales.print');
    Route::get('/raporlar/gelir-gider', [\App\Http\Controllers\ReportsController::class, 'incomeExpense'])->name('reports.income-expense');
    Route::get('/raporlar/gelir-gider/yazdir', [\App\Http\Controllers\ReportsController::class, 'incomeExpensePrint'])->name('reports.income-expense.print');
    Route::get('/raporlar/kdv', [\App\Http\Controllers\ReportsController::class, 'kdvReport'])->name('reports.kdv');
    Route::get('/raporlar/kdv/yazdir', [\App\Http\Controllers\ReportsController::class, 'kdvReportPrint'])->name('reports.kdv.print');
    Route::get('/raporlar/musteri-cari', [\App\Http\Controllers\ReportsController::class, 'customerLedger'])->name('reports.customer-ledger');
    Route::get('/raporlar/musteri-cari/yazdir', [\App\Http\Controllers\ReportsController::class, 'customerLedgerPrint'])->name('reports.customer-ledger.print');
    Route::get('/raporlar/musteri-cari/{customer}', [\App\Http\Controllers\ReportsController::class, 'customerLedgerDetail'])->name('reports.customer-ledger-detail');
    Route::get('/raporlar/musteri-cari/{customer}/yazdir', [\App\Http\Controllers\ReportsController::class, 'customerLedgerDetailPrint'])->name('reports.customer-ledger-detail.print');
    Route::get('/raporlar/tedarikci-cari', [\App\Http\Controllers\ReportsController::class, 'supplierLedger'])->name('reports.supplier-ledger');
    Route::get('/raporlar/tedarikci-cari/yazdir', [\App\Http\Controllers\ReportsController::class, 'supplierLedgerPrint'])->name('reports.supplier-ledger.print');
    Route::get('/raporlar/tedarikci-cari/{supplier}', [\App\Http\Controllers\ReportsController::class, 'supplierLedgerDetail'])->name('reports.supplier-ledger-detail');
    Route::get('/raporlar/tedarikci-cari/{supplier}/yazdir', [\App\Http\Controllers\ReportsController::class, 'supplierLedgerDetailPrint'])->name('reports.supplier-ledger-detail.print');

    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/sifremi-unuttum', [\App\Http\Controllers\PasswordResetController::class, 'sendResetLinkFromProfile'])->name('profile.password.email');
    Route::post('/profil/foto-sil', [\App\Http\Controllers\ProfileController::class, 'deletePhoto'])->name('profile.delete-photo');

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/ayarlar', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::post('/ayarlar', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/ayarlar/logo-sil', [\App\Http\Controllers\SettingsController::class, 'deleteLogo'])->name('settings.delete-logo');
    });

    Route::get('/xml-feeds', [\App\Http\Controllers\XmlFeedController::class, 'index'])->name('xml-feeds.index');
    Route::get('/xml-feeds/create', [\App\Http\Controllers\XmlFeedController::class, 'create'])->name('xml-feeds.create');
    Route::post('/xml-feeds', [\App\Http\Controllers\XmlFeedController::class, 'store'])->name('xml-feeds.store');
    Route::get('/xml-feeds/{xmlFeed}/sync-supplier', [\App\Http\Controllers\XmlFeedController::class, 'syncSupplierForm'])->name('xml-feeds.sync-supplier');
    Route::post('/xml-feeds/{xmlFeed}/sync', [\App\Http\Controllers\XmlFeedController::class, 'sync'])->name('xml-feeds.sync');
    Route::delete('/xml-feeds/{xmlFeed}', [\App\Http\Controllers\XmlFeedController::class, 'destroy'])->name('xml-feeds.destroy');
});
