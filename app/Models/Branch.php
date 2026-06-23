<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatus;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;

class Branch extends Model
{
    use HasFactory, HasStatus, AppliesCountryScope, HasUuids;

    public const STATUS = ['pending', 'approved', 'rejected', 'suspended' ,	'viewed' , 'deleted'];


    protected $fillable = [
        'name',
        'address',
        'is_active',
        'status',
        'merchant_id',
        'country_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the merchant that owns this branch.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Scope to get only active branches
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive branches
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to get only approved branches
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get only pending branches
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
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