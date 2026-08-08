<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasaHareket extends BaseModel
{
    protected $table = 'kasa_hareket';

    protected $fillable = [
        'type',
        'amount',
        'movementDate',
        'description',
        'kasaId',
        'fromKasaId',
        'toKasaId',
        'createdBy',
        'refType',
        'refId',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'movementDate' => 'date',
    ];

    public function kasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'kasaId');
    }

    public function fromKasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'fromKasaId');
    }

    public function toKasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'toKasaId');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdBy');
    }

    /** Kasa defterinde gösterilecek gerçek hareketler (gider iptal artıkları hariç). */
    public function scopeLedger(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('description')
                ->orWhere('description', 'not like', 'Gider iptal%');
        });
    }
}
