<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Customer extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'balance',
        'birth_date',
        'gender',
        'profile_image',
        'address',
        'country_id',
        'city_id',
        'city',
        'state',
        'zip',
        'merchant_id',
        'merchant_country_id',
        'profile_completed',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'birth_date' => 'datetime',
        'profile_completed' => 'boolean',
    ];

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

    public function getCode()
    {
        return 'CSMR' . str_replace('MERCH', '', $this->merchant->merchant_code) . '' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getProfileImageApi(): ?string
    {
        if (! $this->profile_image) {
            return null;
        }

        return function_exists('coreservice_asset')
            ? coreservice_asset($this->profile_image)
            : asset($this->profile_image);
    }

    public function  scopeWithCountry($query)
    {
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        $customRegionEnabled = (bool) data_get($user, 'custom_region');

        if ($customRegionEnabled) {
            // Prefer many-to-many countries assigned to the user
            $countryIds = [];
            try {
                if (method_exists($user, 'countries')) {
                    if ($user->relationLoaded('countries')) {
                        $countryIds = $user->countries->pluck('id')->all();
                    } else {
                        $countryIds = $user->countries()->pluck('countries.id')->all();
                    }
                }
            } catch (\Throwable $e) {
                $countryIds = [];
            }

            if (!empty($countryIds)) {
                return $query->whereIn($query->getModel()->getTable() . '.merchant_country_id', $countryIds);
            }
        }

        // Fallback to single country_id on the user if present
        $userCountryId = (int) data_get($user, 'country_id');
        if ($userCountryId) {
            return $query->where($query->getModel()->getTable() . '.merchant_country_id', $userCountryId);
        }

        return $query;
    }
}
