<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletIdempotencyKey extends Model
{
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'customer_id',
        'scope',
        'idempotency_key',
        'status',
        'response_code',
        'response',
    ];

    protected $casts = [
        'response' => 'array',
        'response_code' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
