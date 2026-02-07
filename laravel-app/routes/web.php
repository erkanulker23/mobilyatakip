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
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class);
    Route::resource('personnel', \App\Http\Controllers\PersonnelController::class);
    Route::resource('kasa', KasaController::class)->parameters(['kasa' => 'kasa']);

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/low', [StockController::class, 'lowStock'])->name('stock.low');

    Route::resource('quotes', QuoteController::class);
    Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');

    Route::resource('sales', SaleController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::resource('service-tickets', ServiceTicketController::class)->parameters(['service-tickets' => 'serviceTicket']);

    Route::get('/odeme-al', [\App\Http\Controllers\CustomerPaymentController::class, 'create'])->name('customer-payments.create');
    Route::post('/odeme-al', [\App\Http\Controllers\CustomerPaymentController::class, 'store'])->name('customer-payments.store');
    Route::get('/odeme-yap', [\App\Http\Controllers\SupplierPaymentController::class, 'create'])->name('supplier-payments.create');
    Route::post('/odeme-yap', [\App\Http\Controllers\SupplierPaymentController::class, 'store'])->name('supplier-payments.store');
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->only(['index', 'create', 'store']);
    Route::get('/raporlar', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
    Route::get('/raporlar/gelir-gider', [\App\Http\Controllers\ReportsController::class, 'incomeExpense'])->name('reports.income-expense');
    Route::get('/raporlar/musteri-cari', [\App\Http\Controllers\ReportsController::class, 'customerLedger'])->name('reports.customer-ledger');
    Route::get('/raporlar/tedarikci-cari', [\App\Http\Controllers\ReportsController::class, 'supplierLedger'])->name('reports.supplier-ledger');
});
