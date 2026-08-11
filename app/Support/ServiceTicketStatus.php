<?php

namespace App\Support;

class ServiceTicketStatus
{
    public const STATUSES = [
        'acildi' => 'Açıldı',
        'devam_ediyor' => 'Devam Ediyor',
        'parca_bekleniyor' => 'Parça Bekleniyor',
        'sevkiyatci_bekleniyor' => 'Eksik ürünler hazır Sevkiyatçı bekleniyor',
        'tamamlandi' => 'Tamamlandı',
        'iptal' => 'İptal',
    ];

    public const PROBLEM_STATUSES = [
        'bekliyor' => 'Bekliyor',
        'duzeltildi' => 'Düzeltildi',
        'duzeltilemedi' => 'Düzeltilemedi',
    ];

    public const ACTION_WORKSHOP_FINISHED = 'atolyede_is_bitti';

    public const WORKSHOP_FINISHED_NOTE = 'Atölyede iş bitti';

    public static function label(?string $status): string
    {
        return self::STATUSES[$status ?? ''] ?? ucfirst(str_replace('_', ' ', $status ?? '—'));
    }

    public static function validationRule(): string
    {
        return 'in:' . implode(',', array_keys(self::STATUSES));
    }

    /** Kapalı olmayan (açık) durumlar. */
    public static function openStatuses(): array
    {
        return array_values(array_filter(
            array_keys(self::STATUSES),
            fn (string $status) => ! self::isClosed($status)
        ));
    }

    public static function problemLabel(?string $status): string
    {
        return self::PROBLEM_STATUSES[$status ?? ''] ?? 'Bekliyor';
    }

    /** @param  array<int, array{description?: string, status?: string}>  $problems */
    public static function normalizeProblems(array $problems): array
    {
        $normalized = [];
        foreach ($problems as $problem) {
            if (is_string($problem)) {
                $description = trim($problem);
                if ($description === '') {
                    continue;
                }
                $normalized[] = [
                    'description' => $description,
                    'status' => 'bekliyor',
                ];
                continue;
            }
            if (! is_array($problem)) {
                continue;
            }
            $description = trim((string) ($problem['description'] ?? ''));
            if ($description === '') {
                continue;
            }
            $status = (string) ($problem['status'] ?? 'bekliyor');
            if (! array_key_exists($status, self::PROBLEM_STATUSES)) {
                $status = 'bekliyor';
            }
            $normalized[] = [
                'description' => $description,
                'status' => $status,
            ];
        }

        return $normalized;
    }

    /** @param  array<int, array{description?: string, status?: string}>|null  $problems */
    public static function problemSummary(?array $problems): string
    {
        $problems = self::normalizeProblems($problems ?? []);
        if ($problems === []) {
            return '—';
        }
        $fixed = collect($problems)->where('status', 'duzeltildi')->count();
        $failed = collect($problems)->where('status', 'duzeltilemedi')->count();
        $total = count($problems);

        if ($fixed === $total) {
            return "{$fixed}/{$total} düzeltildi";
        }
        if ($failed > 0) {
            return "{$fixed}/{$total} düzeltildi, {$failed} düzeltilemedi";
        }

        return "{$fixed}/{$total} düzeltildi";
    }

    public static function detailActionLabel(?string $action): string
    {
        return match ($action) {
            'acildi' => 'Kayıt açıldı',
            'asama' => 'Aşama eklendi',
            self::ACTION_WORKSHOP_FINISHED => 'Atölyede iş bitti',
            'problem_durumu' => 'Problem durumu güncellendi',
            'durum_guncelleme' => 'Durum güncellendi',
            'kapatildi' => 'SSH kapatıldı',
            default => ucfirst(str_replace('_', ' ', $action ?? '—')),
        };
    }

    public static function isClosed(?string $status): bool
    {
        return in_array($status, ['tamamlandi', 'iptal'], true);
    }

    public static function badgeClass(?string $status): string
    {
        return match ($status) {
            'tamamlandi' => 'badge-green',
            'devam_ediyor' => 'badge-blue',
            'parca_bekleniyor' => 'badge-amber',
            'sevkiyatci_bekleniyor' => 'badge-amber',
            'iptal' => 'badge-red',
            default => 'badge-amber',
        };
    }
}
