<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Double-entry ledger lines. Each business event posts balanced debit/credit rows
 * sharing the same reference + reference_id.
 *
 * Example — wallet top-up of 100:
 *   Debit  Bank Account (1000)              100.00
 *   Credit Customer Wallet Liability (2000)       100.00
 */
class TransactionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'reference',
        'reference_id',
        'reference_sub_id',
        'date',
        'credit',
        'debit',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'credit' => 'decimal:2',
        'debit' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function scopeForReference(Builder $query, string $reference, int $referenceId): Builder
    {
        return $query->where('reference', $reference)
            ->where('reference_id', $referenceId);
    }
}
