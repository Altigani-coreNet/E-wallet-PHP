<?php

namespace App\Traits;

use App\Scopes\CountryScope;
use Illuminate\Database\Eloquent\Builder;

trait AppliesCountryScope
{
    /**
     * Apply country scope conditionally for current user (custom_region + country_id).
     */
    public function scopeWithCountry(Builder $query): Builder
    {
        return $query->tap(function (Builder $builder) {
            (new CountryScope())->apply($builder, $this);
        });
    }

    /**
     * Force apply the country filter for a given country id.
     */
    public function scopeForCountry(Builder $query, $countryId): Builder
    {
        $column = $this->getTable() . '.country_id';
        if (is_array($countryId)) {
            return $query->whereIn($column, $countryId);
        }
        return $query->where($column, $countryId);
    }
}


