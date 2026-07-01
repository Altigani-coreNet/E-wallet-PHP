<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory, HasUuids;

    public const TYPES = [
        'topup',
        'payment',
        'transfer',
        'refund',
        'adjustment',
    ];

    public const DIRECTIONS = [
        'credit',
        'debit',
    ];

    protected $fillable = [
        'wallet_id',
        'type',
        'direction',
        'amount',
        'balance_after',
        'reference',
        'reference_id',
        'operation_id',
        'description',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
