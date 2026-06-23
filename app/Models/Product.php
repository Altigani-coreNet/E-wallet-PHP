<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasStatus;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasStatus, HasTranslations;

    /** @var array<int, string> */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'service_id',
        'service_sub_category_id',
        'type_id',
        'country_id',
        'name',
        'description',
        'service_url',
        'notify_url',
        'prepay_url',
        'image',
        'status',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'status' => 'boolean',
    ];

    /**
     * Get the service that owns this product.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Country stored on the product (may mirror the service country).
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function serviceForms()
    {
        return $this->hasMany(ProductServiceForm::class);
    }

    /**
     * Scope a query to filter by service.
     */
    public function scopeByService($query, $serviceId)
    {
        return $query->where('service_id', $serviceId);
    }

    /**
     * Get the status badge HTML
     */
    public function getSpanStatus(): string
    {
        $isActive = (bool) ($this->status ?? false);
        $class = $isActive ? 'success' : 'danger';
        $label = $isActive ? __('translation.active') : __('translation.inactive');
        return "<span class='badge badge-light-{$class}'>" . $label . '</span>';
    }
}
