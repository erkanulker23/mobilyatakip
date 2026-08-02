<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Kasa;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\ServiceTicket;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use Illuminate\Support\Str;

class PageSeo
{
    public static function siteName(): string
    {
        return CompanyBranding::siteName();
    }

    /** @param  array<int, array{name: string, url?: string|null}>  $breadcrumbs */
    public static function pack(string $title, string $description, string $canonical, array $breadcrumbs = []): array
    {
        return [
            'title' => $title,
            'description' => self::limit($description),
            'canonical' => $canonical,
            'breadcrumbs' => $breadcrumbs,
        ];
    }

    public static function customer(Customer $customer): array
    {
        $phone = $customer->phone ? " Telefon: {$customer->phone}." : '';

        return self::pack(
            $customer->name,
            "{$customer->name} müşteri detayı, cari bakiye, satış geçmişi ve tahsilat bilgileri.{$phone}",
            route('customers.show', $customer),
            [
                ['name' => 'Müşteriler', 'url' => route('customers.index')],
                ['name' => $customer->name, 'url' => route('customers.show', $customer)],
            ]
        );
    }

    public static function sale(Sale $sale): array
    {
        $customer = $sale->customer?->name ?? 'Müşteri';
        $total = number_format((float) ($sale->grandTotal ?? 0), 0, ',', '.');
        $date = $sale->saleDate?->format('d.m.Y') ?? '';

        return self::pack(
            'Satış ' . $sale->saleNumber,
            "Satış faturası {$sale->saleNumber}. Müşteri: {$customer}. Toplam: {$total} ₺" . ($date ? " Tarih: {$date}." : ''),
            route('sales.show', $sale),
            [
                ['name' => 'Satışlar', 'url' => route('sales.index')],
                ['name' => $sale->saleNumber, 'url' => route('sales.show', $sale)],
            ]
        );
    }

    public static function quote(Quote $quote): array
    {
        $customer = $quote->customer?->name ?? 'Müşteri';
        $total = number_format((float) ($quote->grandTotal ?? 0), 0, ',', '.');
        $valid = $quote->validUntil?->format('d.m.Y');

        return self::pack(
            'Teklif ' . $quote->quoteNumber,
            "Teklif {$quote->quoteNumber}. Müşteri: {$customer}. Tutar: {$total} ₺" . ($valid ? " Geçerlilik: {$valid}." : ''),
            route('quotes.show', $quote),
            [
                ['name' => 'Teklifler', 'url' => route('quotes.index')],
                ['name' => $quote->quoteNumber, 'url' => route('quotes.show', $quote)],
            ]
        );
    }

    public static function product(Product $product): array
    {
        $sku = $product->sku ? " SKU: {$product->sku}." : '';
        $price = number_format((float) ($product->unitPrice ?? 0), 2, ',', '.');

        return self::pack(
            $product->name,
            "Ürün detayı: {$product->name}. Birim fiyat: {$price} ₺.{$sku}",
            route('products.show', $product),
            [
                ['name' => 'Ürünler', 'url' => route('products.index')],
                ['name' => $product->name, 'url' => route('products.show', $product)],
            ]
        );
    }

    public static function serviceTicket(ServiceTicket $serviceTicket): array
    {
        $customer = $serviceTicket->customer?->name ?? 'Müşteri';
        $status = ServiceTicketStatus::label($serviceTicket->status);

        return self::pack(
            'SSH ' . $serviceTicket->ticketNumber,
            "Servis kaydı {$serviceTicket->ticketNumber}. Müşteri: {$customer}. Durum: {$status}.",
            route('service-tickets.show', $serviceTicket),
            [
                ['name' => 'SSH Kayıtları', 'url' => route('service-tickets.index')],
                ['name' => $serviceTicket->ticketNumber, 'url' => route('service-tickets.show', $serviceTicket)],
            ]
        );
    }

    public static function purchase(Purchase $purchase): array
    {
        $supplier = $purchase->supplier?->name ?? 'Tedarikçi';
        $total = number_format((float) ($purchase->grandTotal ?? 0), 0, ',', '.');

        return self::pack(
            'Alış ' . $purchase->purchaseNumber,
            "Alış fişi {$purchase->purchaseNumber}. Tedarikçi: {$supplier}. Toplam: {$total} ₺.",
            route('purchases.show', $purchase),
            [
                ['name' => 'Alışlar', 'url' => route('purchases.index')],
                ['name' => $purchase->purchaseNumber, 'url' => route('purchases.show', $purchase)],
            ]
        );
    }

    public static function supplier(Supplier $supplier): array
    {
        return self::pack(
            $supplier->name,
            "{$supplier->name} tedarikçi detayı, alış geçmişi ve cari bilgileri.",
            route('suppliers.show', $supplier),
            [
                ['name' => 'Tedarikçiler', 'url' => route('suppliers.index')],
                ['name' => $supplier->name, 'url' => route('suppliers.show', $supplier)],
            ]
        );
    }

    public static function personnel(Personnel $personnel): array
    {
        $role = $personnel->title ? " Görev: {$personnel->title}." : '';

        return self::pack(
            $personnel->name,
            "Personel detayı: {$personnel->name}.{$role}",
            route('personnel.show', $personnel),
            [
                ['name' => 'Personel', 'url' => route('personnel.index')],
                ['name' => $personnel->name, 'url' => route('personnel.show', $personnel)],
            ]
        );
    }

    public static function warehouse(Warehouse $warehouse): array
    {
        return self::pack(
            $warehouse->name,
            "{$warehouse->name} depo detayı ve stok bilgileri.",
            route('warehouses.show', $warehouse),
            [
                ['name' => 'Depolar', 'url' => route('warehouses.index')],
                ['name' => $warehouse->name, 'url' => route('warehouses.show', $warehouse)],
            ]
        );
    }

    public static function kasa(Kasa $kasa): array
    {
        return self::pack(
            $kasa->name,
            "{$kasa->name} kasa detayı, bakiye özeti ve hareket geçmişi.",
            route('kasa.show', $kasa),
            [
                ['name' => 'Kasa', 'url' => route('kasa.index')],
                ['name' => $kasa->name, 'url' => route('kasa.show', $kasa)],
            ]
        );
    }

    public static function shippingCompany(ShippingCompany $shippingCompany): array
    {
        return self::pack(
            $shippingCompany->name,
            "{$shippingCompany->name} nakliye firması detayı, araç filosu ve ödeme geçmişi.",
            route('shipping-companies.show', $shippingCompany),
            [
                ['name' => 'Nakliye Firmaları', 'url' => route('shipping-companies.index')],
                ['name' => $shippingCompany->name, 'url' => route('shipping-companies.show', $shippingCompany)],
            ]
        );
    }

    public static function customerPayment(CustomerPayment $payment): array
    {
        $customer = $payment->customer?->name ?? 'Müşteri';
        $amount = number_format((float) ($payment->amount ?? 0), 0, ',', '.');
        $date = $payment->paymentDate?->format('d.m.Y') ?? '';

        return self::pack(
            'Tahsilat Makbuzu',
            "Müşteri tahsilatı: {$amount} ₺. Müşteri: {$customer}" . ($date ? " Tarih: {$date}." : ''),
            route('customer-payments.show', $payment),
            [
                ['name' => 'Tahsilatlar', 'url' => route('customer-payments.create', ['list' => 1])],
                ['name' => 'Tahsilat Makbuzu', 'url' => route('customer-payments.show', $payment)],
            ]
        );
    }

    public static function supplierPayment(SupplierPayment $payment): array
    {
        $supplier = $payment->supplier?->name ?? 'Tedarikçi';
        $amount = number_format((float) ($payment->amount ?? 0), 0, ',', '.');

        return self::pack(
            'Tedarikçi Ödemesi',
            "Tedarikçi ödemesi: {$amount} ₺. Tedarikçi: {$supplier}.",
            route('supplier-payments.show', $payment),
            [
                ['name' => 'Tedarikçi Ödemeleri', 'url' => route('supplier-payments.create')],
                ['name' => 'Ödeme Detayı', 'url' => route('supplier-payments.show', $payment)],
            ]
        );
    }

    public static function shippingCompanyPayment(ShippingCompanyPayment $payment): array
    {
        $company = $payment->shippingCompany?->name ?? 'Nakliye firması';
        $amount = number_format((float) ($payment->amount ?? 0), 0, ',', '.');

        return self::pack(
            'Nakliye Ödemesi',
            "Nakliye ödemesi: {$amount} ₺. Firma: {$company}.",
            route('shipping-company-payments.show', $payment),
            [
                ['name' => 'Nakliye Ödemeleri', 'url' => route('shipping-company-payments.create')],
                ['name' => 'Ödeme Detayı', 'url' => route('shipping-company-payments.show', $payment)],
            ]
        );
    }

    public static function expense(Expense $expense): array
    {
        $amount = number_format((float) ($expense->amount ?? 0), 0, ',', '.');
        $date = $expense->expenseDate?->format('d.m.Y') ?? '';
        $desc = Str::limit((string) ($expense->description ?? 'Gider'), 80);

        return self::pack(
            'Gider Detayı',
            "Gider kaydı: {$desc}. Tutar: {$amount} ₺" . ($date ? " Tarih: {$date}." : ''),
            route('expenses.show', $expense),
            [
                ['name' => 'Giderler', 'url' => route('expenses.index')],
                ['name' => 'Gider Detayı', 'url' => route('expenses.show', $expense)],
            ]
        );
    }

    private static function limit(string $text, int $max = 160): string
    {
        $text = preg_replace('/\s+/', ' ', trim(strip_tags($text))) ?? '';

        return Str::limit($text, $max);
    }
}
