<?php

namespace App\Modules\Communication\Advertisement\Models;

use App\Models\Country;
use App\Scopes\CountryScope;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advertisement extends Model
{
    use HasFactory, HasStatus;

    protected $table = 'advertisements';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'image',
        'country_id',
        'status',
        'start_date',
        'end_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getImageUrlAttribute(): string
    {
        if (!$this->image) {
            return '';
        }

        return function_exists('coreservice_asset') ? coreservice_asset($this->image) : asset($this->image);
    }

    public function scopeVisibleInPublicApiListing(Builder $query): Builder
    {
        $now = now()->startOfDay();

        return $query->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->where(function ($q) use ($now) {
                    $q->whereNull('start_date')
                        ->whereNull('end_date');
                })
                    ->orWhere(function ($q) use ($now) {
                        $q->where(function ($dateQuery) use ($now) {
                            $dateQuery->whereNull('start_date')
                                ->orWhere('start_date', '<=', $now);
                        })
                            ->where(function ($dateQuery) use ($now) {
                                $dateQuery->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $now);
                            });
                    });
            });
    }

    public function isActiveNow(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now()->startOfDay();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function getStatusBadge(): string
    {
        return match ($this->status) {
            'active' => '<span class="badge badge-light-success">Active</span>',
            'inactive' => '<span class="badge badge-light-danger">Inactive</span>',
            default => '<span class="badge badge-light-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CountryScope());
    }
}
