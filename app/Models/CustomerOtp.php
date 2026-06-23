<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOtp extends Model
{
    protected $table = 'customers_otp';

    protected $fillable = [
        'identifier',
        'channel',
        'code',
        'token',
        'is_verified',
        'expires_at',
    ];

    protected $casts = [
        'code' => 'integer',
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
    ];
}
