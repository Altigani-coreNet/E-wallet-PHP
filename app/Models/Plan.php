<?php

namespace App\Models;

use App\Enums\RecurringType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasStatus;
use Spatie\Translatable\HasTranslations;

class Plan extends Model
{
    /** @use HasFactory<\Database\Factories\PlanFactory> */
    use HasFactory, HasTranslations, HasStatus;

    public array $translatable = ['name', "description"];

    public $fillable = ["name", "description", "price", "plan_type", "stripe_id", "status", "has_discount", "current_price"];

    const PLAN_TYPE = ["monthly", "yearly"];

    protected $casts = [
        'plan_type' => RecurringType::class,
    ];

    public function getDisCountBadge(): string
    {
        if ($this->has_discount == 1) return "<span class='badge badge-light-info'> " . ___('translation.discount_enabled') . " </span>";
        else  return "<span class='badge badge-light-danger'> " . ___('translation.discount_disabled') . "</span>";
    }

    public function getCode()
    {
        $code = $this->stripe_id;

        if (strlen($code) > 11) {
            return substr($code, 0, 7) . '***************' . substr($code, -4);
        }

        return $code;
    }

    public function features(): HasMany
    {
        return $this->hasMany(Feature::class);
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(PlanScope::class);
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }
}
