<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_mode',
        'pan_token',
        'cardholder_name',
        'expiry_month',
        'expiry_year',
        'card_type',
        'card_brand',
        'issuer_bank',
        'cvv_present',
        'pin_present',
        'metadata',
        'payment_channel', 
    ];

    protected $casts = [
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Helper methods
    public function getExpiryDateAttribute()
    {
        if ($this->expiry_month && $this->expiry_year) {
            return sprintf('%02d/%d', $this->expiry_month, $this->expiry_year);
        }
        return null;
    }

    public function getMaskedPanTokenAttribute()
    {
        if ($this->pan_token) {
            $length = strlen($this->pan_token);
            if ($length > 8) {
                return substr($this->pan_token, 0, 4) . str_repeat('*', $length - 8) . substr($this->pan_token, -4);
            }
            return $this->pan_token;
        }
        return null;
    }
} 