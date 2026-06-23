<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'currency_id',
        'country_id',
        'price',
        'current_price',
        'is_default',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    /**
     * Get the plan that owns this price
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the currency for this price
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the country for this price
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}



