<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class SaleProductionStageSchema
{
    private static ?bool $ready = null;

    public static function isReady(): bool
    {
        if (self::$ready !== null) {
            return self::$ready;
        }

        return self::$ready = Schema::hasTable('sale_production_stages')
            && Schema::hasColumn('sale_production_stages', 'saleId')
            && Schema::hasColumn('sale_production_stages', 'isCompleted');
    }

    /**
     * @param  Builder<\App\Models\Sale>  $query
     */
    public static function applyCounts(Builder $query, bool $detailed = false): void
    {
        if (! self::isReady()) {
            return;
        }

        if ($detailed) {
            $query->withCount([
                'productionStages',
                'productionStages as open_stages_count' => fn ($q) => $q->where('isCompleted', false),
                'productionStages as open_deficiencies_count' => fn ($q) => $q->where('type', 'eksiklik')->where('isCompleted', false),
            ]);

            return;
        }

        $query->withCount('productionStages');
    }

    public static function abortIfNotReady(): void
    {
        if (! self::isReady()) {
            abort(503, 'Üretim aşaması modülü henüz kurulmadı. Sistem yöneticisine başvurun.');
        }
    }
}
