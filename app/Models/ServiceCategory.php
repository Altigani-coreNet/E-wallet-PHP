<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasStatus;

class ServiceCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasStatus;

    public const TYPE_SERVICE = 'service';

    public const TYPE_PARTNER = 'partner';

    protected $fillable = [
        'type',
        'name_en',
        'name_ar',
        'code',
        'parent_id',
        'is_active',
        'image',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the services for this category.
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    public function subCategories()
    {
        return $this->hasMany(ServiceSubCategory::class, 'category_id');
    }

    /**
     * Parent category relation.
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child categories relation.
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the active services for this category.
     */
    public function activeServices()
    {
        return $this->hasMany(Service::class, 'category_id')->where('is_active', true);
    }

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the localized name based on current locale
     */
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    /**
     * Generate a unique category code
     */
    public static function generateCategoryCode(string $type = self::TYPE_SERVICE): string
    {
        do {
            $code = 'CAT' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('code', $code)->where('type', $type)->exists());

        return $code;
    }
}










