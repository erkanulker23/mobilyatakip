<?php

namespace App\Support;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

class ActivityMessage
{
    private const ENTITIES = [
        'sale' => 'Sipariş',
        'purchase' => 'Alış',
        'customer' => 'Müşteri',
        'supplier' => 'Tedarikçi',
        'product' => 'Ürün',
        'quote' => 'Teklif',
        'service_ticket' => 'SSH',
        'customer_payment' => 'Tahsilat',
        'supplier_payment' => 'Tedarikçi ödemesi',
        'shipping_company_payment' => 'Nakliye ödemesi',
        'expense' => 'Gider',
        'kasa' => 'Kasa',
        'personnel' => 'Personel',
        'warehouse' => 'Depo',
        'shipping_company' => 'Nakliye firması',
        'stock' => 'Stok',
        'company' => 'Şirket ayarları',
        'xml_feed' => 'XML feed',
    ];

    private const ACTIONS = [
        'create' => 'oluşturdu',
        'update' => 'güncelledi',
        'delete' => 'sildi',
        'cancel' => 'iptal etti',
        'convert' => 'siparişe dönüştürdü',
        'email' => 'e-posta gönderdi',
        'sync' => 'senkronize etti',
        'status' => 'durumu güncelledi',
        'transfer' => 'virman yaptı',
    ];

    private const ROUTES = [
        'sale' => ['sales.show', 'sale'],
        'purchase' => ['purchases.show', 'purchase'],
        'customer' => ['customers.show', 'customer'],
        'supplier' => ['suppliers.show', 'supplier'],
        'product' => ['products.show', 'product'],
        'quote' => ['quotes.show', 'quote'],
        'service_ticket' => ['service-tickets.show', 'serviceTicket'],
        'customer_payment' => ['customer-payments.show', 'customerPayment'],
        'supplier_payment' => ['supplier-payments.show', 'supplierPayment'],
        'shipping_company_payment' => ['shipping-company-payments.show', 'shippingCompanyPayment'],
        'expense' => ['expenses.show', 'expense'],
        'kasa' => ['kasa.show', 'kasa'],
        'personnel' => ['personnel.show', 'personnel'],
        'warehouse' => ['warehouses.show', 'warehouse'],
        'shipping_company' => ['shipping-companies.show', 'shippingCompany'],
        'stock' => ['stock.edit', 'stock'],
        'company' => ['settings.index', null],
    ];

    public static function from(AuditLog $log): array
    {
        $rawData = $log->newValue ?? $log->oldValue ?? [];
        $userName = $log->user?->name ?? ($rawData['_actorName'] ?? null);
        $data = collect($rawData)->except('_actorName')->all();
        $entityLabel = self::ENTITIES[$log->entity] ?? ucfirst(str_replace('_', ' ', $log->entity));
        $detail = self::detail($log->entity, $data);
        $text = self::buildActionText($log, $entityLabel, $detail, $data);
        $displayUser = $userName ?: 'Sistem';

        return [
            'id' => $log->id,
            'message' => "{$displayUser} {$text}",
            'text' => $text,
            'user' => $displayUser,
            'url' => self::url($log),
            'tone' => self::tone($log->action),
            'time' => $log->createdAt,
            'timeAgo' => self::timeAgo($log->createdAt),
        ];
    }

    private static function buildActionText(AuditLog $log, string $entityLabel, string $detail, array $data): string
    {
        $action = $log->action;

        if ($action === 'status' && ! empty($data['status'])) {
            $statusLabel = $log->entity === 'service_ticket'
                ? ServiceTicketStatus::label($data['status'])
                : ($data['statusLabel'] ?? $data['status']);
            $target = trim("{$entityLabel} {$detail}");

            return trim("{$target} durumunu güncelledi: {$statusLabel}");
        }

        if ($action === 'convert') {
            $saleNo = $data['saleNumber'] ?? '';
            $quote = trim("teklif {$detail}");

            return trim("{$quote} kaydını siparişe dönüştürdü" . ($saleNo ? " ({$saleNo})" : ''));
        }

        if ($action === 'email') {
            $target = trim("{$entityLabel} {$detail}");

            return trim("{$target} için e-posta gönderdi");
        }

        if ($action === 'transfer' && ! empty($data['toKasaName'])) {
            $target = trim("{$entityLabel} {$detail}");
            $amount = ! empty($data['amount'])
                ? ' (' . Money::format($data['amount']) . ' ₺ → ' . $data['toKasaName'] . ')'
                : ' (→ ' . $data['toKasaName'] . ')';

            return trim("{$target} için virman yaptı{$amount}");
        }

        if ($action === 'sync') {
            $target = trim("{$entityLabel} {$detail}");
            $extra = $data['summary'] ?? null;

            return trim("{$target} kaydını senkronize etti" . ($extra ? " — {$extra}" : ''));
        }

        $actionLabel = self::ACTIONS[$action] ?? $action;
        $suffix = self::suffix($log->entity, $data);
        $target = trim("{$entityLabel} {$detail}");

        if ($target !== '') {
            return trim("{$target} kaydını {$actionLabel}{$suffix}");
        }

        return trim("{$entityLabel} kaydını {$actionLabel}{$suffix}");
    }

    private static function detail(string $entity, array $data): string
    {
        return match (true) {
            ! empty($data['saleNumber']) => (string) $data['saleNumber'],
            ! empty($data['purchaseNumber']) => (string) $data['purchaseNumber'],
            ! empty($data['quoteNumber']) => (string) $data['quoteNumber'],
            ! empty($data['ticketNumber']) => (string) $data['ticketNumber'],
            ! empty($data['name']) => (string) $data['name'],
            ! empty($data['description']) && in_array($entity, ['expense'], true) => (string) $data['description'],
            default => '',
        };
    }

    private static function suffix(string $entity, array $data): string
    {
        if (! empty($data['grandTotal'])) {
            return ' (' . Money::format($data['grandTotal']) . ' ₺)';
        }

        if (! empty($data['amount']) && in_array($entity, ['customer_payment', 'supplier_payment', 'shipping_company_payment', 'expense', 'kasa'], true)) {
            return ' (' . Money::format($data['amount']) . ' ₺)';
        }

        if ($entity === 'stock' && isset($data['quantity'])) {
            return ' (' . (int) $data['quantity'] . ' adet)';
        }

        return '';
    }

    private static function tone(string $action): string
    {
        return match ($action) {
            'create', 'convert', 'sync', 'transfer' => 'success',
            'delete', 'cancel' => 'danger',
            'email' => 'info',
            'status' => 'warning',
            default => 'neutral',
        };
    }

    private static function url(AuditLog $log): ?string
    {
        $routeConfig = self::ROUTES[$log->entity] ?? null;
        if (! $routeConfig) {
            return null;
        }

        [$routeName, $paramName] = $routeConfig;
        if (! Route::has($routeName)) {
            return null;
        }

        try {
            if ($paramName === null) {
                return route($routeName);
            }

            if (! $log->entityId) {
                return null;
            }

            return route($routeName, [$paramName => $log->entityId]);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function timeAgo(?Carbon $time): string
    {
        if (! $time) {
            return '';
        }

        $seconds = max(0, now()->diffInSeconds($time));

        if ($seconds < 60) {
            return 'Az önce';
        }

        $minutes = (int) floor($seconds / 60);
        if ($minutes < 60) {
            return "{$minutes} dk önce";
        }

        $hours = (int) floor($minutes / 60);
        if ($hours < 24) {
            return "{$hours} sa önce";
        }

        $days = (int) floor($hours / 24);
        if ($days < 7) {
            return "{$days} gün önce";
        }

        return $time->format('d.m.Y H:i');
    }
}
