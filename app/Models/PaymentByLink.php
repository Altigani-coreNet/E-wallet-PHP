<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\CountryScope;

class PaymentByLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'merchant_id',
        'status',
        'link',
        'payment_sdk',
        'amount',
        'currency_id',
        'currency_code',
        'currency_object',
        'payment_method_types',
        'scheduled_date',
        'expired_date',
        // 'customer_id', // Commented out - storing customer info directly
        'customer_name',
        'customer_phone',
        'customer_email',
        'country_id',
        'uuid',
        'short_uuid',
        'metadata',
        'payment_status',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    // Commented out - storing customer info directly in payment_by_links table
    // public function customer()
    // {
    //     return $this->belongsTo(Customer::class);
    // }

    public function transaction()
    {
        return $this->hasOne(\App\Models\Transaction::class, 'metadata->payment_link_id', 'id');
    }

    // Commented out - storing currency info directly from AuthService
    // public function currency()
    // {
    //     return $this->belongsTo(Currency::class);
    // }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getStatusSpan()
    {
        $class = match($this->status) {
            'pending' => 'badge-light-info',
            'scheduled' => 'badge-light-warning',
            'completed' => 'badge-light-success',
            'failed' => 'badge-light-danger',
            'expired' => 'badge-light-secondary',
            'canceled' => 'badge-light-dark',
            default => 'badge-light-secondary',
        };

        return '<span class="badge badge-pill '.$class.'">'.$this->status.'</span>';
    }

    protected $casts = [
        'scheduled_date' => 'datetime',
        'expired_date' => 'datetime',
        'payment_method_types' => 'array',
        'currency_object' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Add global scope to automatically filter by regions from X-Regions header
        static::addGlobalScope(new CountryScope());
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function scopeByUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    public function getShortUuidAttribute()
    {
        return substr($this->uuid, 0, 8);
    }

    public function getPaymentLinkUrlAttribute()
    {
        return url('/payment-link/' . $this->uuid);
    }

    /**
     * Get currency symbol from currency_object
     */
    public function getCurrencySymbolAttribute()
    {
        if (!empty($this->attributes['currency_object'])) {
            $currencyObject = is_array($this->attributes['currency_object']) 
                ? $this->attributes['currency_object'] 
                : json_decode($this->attributes['currency_object'], true);
            return $currencyObject['symbol'] ?? '$';
        }
        return '$';
    }

    /**
     * Get currency name from currency_object
     */
    public function getCurrencyNameAttribute()
    {
        if (!empty($this->attributes['currency_object'])) {
            $currencyObject = is_array($this->attributes['currency_object']) 
                ? $this->attributes['currency_object'] 
                : json_decode($this->attributes['currency_object'], true);
            return $currencyObject['name'] ?? '';
        }
        return '';
    }
}
