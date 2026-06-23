<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasStatus;
use App\Traits\AppliesCountryScope;
use App\Enums\ServiceType;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasStatus, AppliesCountryScope, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['service_name', 'description'];

    protected $fillable = [
        'category_id',
        'sub_category_id',
        'partner_id',
        'country_id',
        'service_type',
        'service_name',
        'image',
        'description',
        'status',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'service_type' => ServiceType::class,
        'service_name' => 'array',
    ];

    public const STATUS = ['active', 'inactive', 'pending', 'staging', 'testing'];

    /**
     * Get the category that this service belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(ServiceSubCategory::class, 'sub_category_id');
    }

    /**
     * Get the country that this service belongs to.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the partner that owns this service.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use partner() instead
     */
    public function merchant()
    {
        return $this->partner();
    }

    /**
     * Get the products for this service.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active services.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by partner.
     */
    public function scopeByPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use scopeByPartner() instead
     */
    public function scopeByMerchant($query, $merchantId)
    {
        return $this->scopeByPartner($query, $merchantId);
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeByCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope a query to filter by service type.
     */
    public function scopeByServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    /**
     * Get the status badge HTML
     */
    public function getSpanStatus(): string
    {
        $class = match ($this->status) {
            'active' => 'success',
            'inactive' => 'danger',
            'pending' => 'warning',
            'staging' => 'primary',
            'testing' => 'info',
            default => 'secondary',
        };

        $status = $this->status ?? 'pending';
        return "<span class='badge badge-light-{$class}'>" . __('translation.' . $status) . '</span>';
    }

    /**
     * Resolved label for API/resources (uses Spatie getTranslation, with legacy fallbacks).
     *
     * @param  'en'|'ar'  $locale
     */
    public function serviceNameForLocale(string $locale): ?string
    {
        $direct = $this->getTranslation('service_name', $locale, false);
        if ($direct !== null && $direct !== '') {
            return $direct;
        }

        $sn = $this->service_name;
        if (is_array($sn)) {
            return $sn[$locale] ?? null;
        }
        if (is_string($sn) && $sn !== '') {
            return $locale === 'en' ? $sn : null;
        }

        return null;
    }
}
