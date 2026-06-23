<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'mode',
        'public_key',
        'secret_key',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'secret_key', // Hide secret key by default
    ];

    /**
     * Get the merchant that owns the API key.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Generate a unique public key
     */
    public static function generatePublicKey($mode = 'test')
    {
        $prefix = $mode === 'live' ? 'pk_live_' : 'pk_test_';
        
        do {
            $key = $prefix . Str::random(32);
        } while (self::where('public_key', $key)->exists());

        return $key;
    }

    /**
     * Generate a unique secret key
     */
    public static function generateSecretKey($mode = 'test')
    {
        $prefix = $mode === 'live' ? 'sk_live_' : 'sk_test_';
        
        do {
            $key = $prefix . Str::random(32);
        } while (self::where('secret_key', $key)->exists());

        return $key;
    }

    /**
     * Generate a new API key pair for a merchant
     */
    public static function generateForMerchant($merchantId, $mode = 'test')
    {
        // Check if merchant already has an active key for this mode
        $existingKey = self::where('merchant_id', $merchantId)
            ->where('mode', $mode)
            ->where('is_active', true)
            ->first();

        if ($existingKey) {
            // Return existing key
            return $existingKey;
        }

        // Create new key
        return self::create([
            'merchant_id' => $merchantId,
            'mode' => $mode,
            'public_key' => self::generatePublicKey($mode),
            'secret_key' => self::generateSecretKey($mode),
            'is_active' => true,
        ]);
    }

    /**
     * Mark key as used (update last_used_at)
     */
    public function markAsUsed()
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope for active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for test mode keys
     */
    public function scopeTestMode($query)
    {
        return $query->where('mode', 'test');
    }

    /**
     * Scope for live mode keys
     */
    public function scopeLiveMode($query)
    {
        return $query->where('mode', 'live');
    }
}

