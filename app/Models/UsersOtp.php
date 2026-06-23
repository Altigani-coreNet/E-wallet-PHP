<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersOtp extends Model
{
    protected $table = 'users_otp';

    protected $fillable = [
        'phone_number',
        'code',
        'is_verified',
        'token',
        'expires_at'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime'
    ];

    /**
     * Generate a 6-digit OTP number
     */
    public static function generateOtpNumber()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}