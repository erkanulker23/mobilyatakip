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
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/customers/{customer}/print', [CustomerController::class, 'print'])->name('customers.print');
    Route::get('/customers/excel/export', [CustomerController::class, 'exportExcel'])->name('customers.excel.export');
    Route::post('/customers/excel/import', [CustomerController::class, 'importExcel'])->name('customers.excel.import');
    Route::resource('customers', CustomerController::class);
    Route::get('/suppliers/{supplier}/print', [SupplierController::class, 'print'])->name('suppliers.print');
    Route::get('/suppliers/excel/export', [SupplierController::class, 'exportExcel'])->name('suppliers.excel.export');
    Route::post('/suppliers/excel/import', [SupplierController::class, 'importExcel'])->name('suppliers.excel.import');
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class);
    Route::resource('personnel', \App\Http\Controllers\PersonnelController::class);
    Route::resource('kasa', KasaController::class)->parameters(['kasa' => 'kasa']);

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/low', [StockController::class, 'lowStock'])->name('stock.low');
    Route::get('/stock/{stock}/edit', [StockController::class, 'edit'])->name('stock.edit');
    Route::put('/stock/{stock}', [StockController::class, 'update'])->name('stock.update');

    Route::resource('quotes', QuoteController::class);
    Route::get('/quotes/{quote}/print', [QuoteController::class, 'print'])->name('quotes.print');
    Route::get('/quotes/{quote}/email', [QuoteController::class, 'email'])->name('quotes.email');
    Route::post('/quotes/{quote}/send-email', [QuoteController::class, 'sendEmail'])->name('quotes.sendEmail');
    Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');

    Route::resource('sales', SaleController::class);
    Route::get('/sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::post('/sales/{sale}/send-supplier-email', [SaleController::class, 'sendSupplierEmail'])->name('sales.send-supplier-email');
    Route::post('/sales/{sale}/activity', [SaleController::class, 'addActivity'])->name('sales.activity');
    Route::post('/sales/{sale}/efatura/send', [\App\Http\Controllers\EInvoiceController::class, 'sendSale'])->name('sales.efatura.send');
    Route::get('/sales/{sale}/efatura/xml', [\App\Http\Controllers\EInvoiceController::class, 'downloadSaleXml'])->name('sales.efatura.xml');
    Route::resource('purchases', PurchaseController::class);
    Route::get('/purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
    Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
    Route::post('/purchases/{purchase}/efatura/send', [\App\Http\Controllers\EInvoiceController::class, 'sendPurchase'])->name('purchases.efatura.send');
    Route::get('/purchases/{purchase}/efatura/xml', [\App\Http\Controllers\EInvoiceController::class, 'downloadPurchaseXml'])->name('purchases.efatura.xml');
    Route::resource('service-tickets', ServiceTicketController::class)->parameters(['service-tickets' => 'serviceTicket']);
    Route::get('/service-tickets/{serviceTicket}/print', [ServiceTicketController::class, 'print'])->name('service-tickets.print');

    Route::get('/odeme-al', [\App\Http\Controllers\CustomerPaymentController::class, 'create'])->name('customer-payments.create');
    Route::post('/odeme-al', [\App\Http\Controllers\CustomerPaymentController::class, 'store'])->name('customer-payments.store');
    Route::get('/odeme-yap', [\App\Http\Controllers\SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
    Route::post('/odeme-yap', [\App\Http\Controllers\SupplierPaymentController::class, 'store'])->name('supplier-payments.store');

    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);
    Route::get('/raporlar', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
    Route::get('/raporlar/gelir-gider', [\App\Http\Controllers\ReportsController::class, 'incomeExpense'])->name('reports.income-expense');
    Route::get('/raporlar/kdv', [\App\Http\Controllers\ReportsController::class, 'kdvReport'])->name('reports.kdv');
    Route::get('/raporlar/musteri-cari', [\App\Http\Controllers\ReportsController::class, 'customerLedger'])->name('reports.customer-ledger');
    Route::get('/raporlar/musteri-cari/{customer}', [\App\Http\Controllers\ReportsController::class, 'customerLedgerDetail'])->name('reports.customer-ledger-detail');
    Route::get('/raporlar/tedarikci-cari', [\App\Http\Controllers\ReportsController::class, 'supplierLedger'])->name('reports.supplier-ledger');
    Route::get('/raporlar/tedarikci-cari/{supplier}', [\App\Http\Controllers\ReportsController::class, 'supplierLedgerDetail'])->name('reports.supplier-ledger-detail');

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
