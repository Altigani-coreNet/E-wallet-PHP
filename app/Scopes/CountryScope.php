<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CountryScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // First, try to get regions from X-Regions header
        $countryIds = $this->getRegionsFromHeader();

        // If header doesn't exist or is empty, fallback to authenticated user
        if (empty($countryIds)) {
            $user = Auth::user();

            if (!$user) {
                return;
            }

            $customRegionEnabled = (bool) data_get($user, 'custom_region');

            if (! $customRegionEnabled) {
                return;
            }

            // Prefer many-to-many countries assigned to the user
            try {
                if (method_exists($user, 'countries')) {
                    if ($user->relationLoaded('countries')) {
                        $countryIds = $user->countries->pluck('id')->all();
                    } else {
                        // Use explicit table to avoid ambiguous column in pluck
                        $countryIds = $user->countries()->pluck('countries.id')->all();
                    }
                }
            } catch (\Throwable $e) {
                // Fail closed silently; we'll fallback to single country_id if present
                $countryIds = [];
            }
        }

        $tableColumn = $model->getTable() . '.country_id';

        if (!empty($countryIds)) {
            $builder->whereIn($tableColumn, $countryIds);
            return;
        }
    }

    /**
     * Extract regions from X-Regions header
     * 
     * @return array
     */
    protected function getRegionsFromHeader(): array
    {
        $request = request();
        if (!$request) {
            return [];
        }

        $regionsHeader = $request->header('X-Regions');
        if (!$regionsHeader) {
            return [];
        }

        try {
            $regionIds = json_decode($regionsHeader, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($regionIds)) {
                return [];
            }

            // Ensure all values are strings (UUIDs) and filter out empty values
            return array_filter(array_map('strval', $regionIds));
        } catch (\Exception $e) {
            return [];
        }
    }
}


