<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;

class Settlement extends Model
{
    use HasFactory, AppliesCountryScope;

    protected $fillable = [
        'settlement_number',
        'batch_id',
        'merchant_id',
        'user_id',
        'status',
        'total_amount',
        'transaction_count',
        'currency_id',
        'currency_symbol',
        'settled_at',
        'country_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    /**
     * Get the batch that owns the settlement.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the merchant that owns the settlement.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Scope a query to only include pending settlements.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include settled settlements.
     */
    public function scopeSettled($query)
    {
        return $query->where('status', 'settled');
    }

    /**
     * Scope a query to only include failed settlements.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get status with HTML span for DataTables.
     */
    public function getStatusWithSpan()
    {
        return match($this->status) {
            'pending' => '<span class="badge badge-light-warning">Pending</span>',
            'settled' => '<span class="badge badge-light-success">Settled</span>',
            'failed' => '<span class="badge badge-light-danger">Failed</span>',
            default => '<span class="badge badge-light-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Batch::class, 'id', 'batch_id', 'batch_id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to automatically filter by regions from X-Regions header
        static::addGlobalScope(new CountryScope());
    }
}
