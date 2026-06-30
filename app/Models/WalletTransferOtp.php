<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransferOtp extends Model
{
    protected $fillable = [
        'customer_id',
        'token',
        'code',
        'payload',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'code' => 'integer',
        'payload' => 'array',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
