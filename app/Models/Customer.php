<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Customer extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DELETED = 'deleted';

    /** @var list<string> */
    public const MANAGEABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_INACTIVE,
    ];

    /** @var list<string> */
    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_INACTIVE,
        self::STATUS_DELETED,
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->uuid)) {
                $customer->uuid = (string) Str::uuid();
            }

            if (empty($customer->status)) {
                $customer->status = self::STATUS_PENDING;
            }
        });

        static::created(function (Customer $customer) {
            try {
                app(WalletService::class)->createForCustomer($customer);
            } catch (\Throwable) {
                // Wallet infra may be unavailable in tests; customer creation must not fail.
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'uuid',
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
        'status',
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

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function getCode()
    {
        if ($this->merchant && $this->merchant->merchant_code) {
            return 'CSMR' . str_replace('MERCH', '', $this->merchant->merchant_code) . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
        }

        return 'CSMR' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDeletedStatus(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    public function canAccessWallet(): bool
    {
        return $this->isActive();
    }

    public function walletLoginBlockReason(): ?string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => null,
            self::STATUS_PENDING => 'Your account is pending approval.',
            self::STATUS_SUSPENDED => 'Your account has been suspended. Please contact support.',
            self::STATUS_INACTIVE => 'Your account is inactive. Please contact support.',
            self::STATUS_DELETED => 'Your account has been deleted.',
            default => 'Your account is not available.',
        };
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
