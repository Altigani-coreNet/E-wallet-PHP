<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerActionOtp extends Model
{
    public const PURPOSE_PASSWORD_CHANGE = 'password_change';

    protected $fillable = [
        'customer_id',
        'purpose',
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
