<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends BaseModel
{
    protected $table = 'expenses';

    protected $fillable = [
        'amount',
        'kdvIncluded',
        'kdvRate',
        'kdvAmount',
        'expenseDate',
        'description',
        'category',
        'kasaId',
        'createdBy',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'kdvIncluded' => 'boolean',
        'kdvRate' => 'decimal:2',
        'kdvAmount' => 'decimal:2',
        'expenseDate' => 'date',
    ];

    public function kasa(): BelongsTo
    {
        return $this->belongsTo(Kasa::class, 'kasaId');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdBy');
    }
}
