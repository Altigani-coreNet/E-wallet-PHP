<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use Illuminate\Support\Facades\Auth;
class Customer extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, HasUuids, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REQUESTING_UPDATED = 'requesting_updated';

    public const STATUS_DELETED = 'deleted';

    /** @var list<string> */
    public const MANAGEABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_INACTIVE,
        self::STATUS_REQUESTING_UPDATED,
    ];

    /** @var list<string> */
    public const ALL_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_INACTIVE,
        self::STATUS_REJECTED,
        self::STATUS_REQUESTING_UPDATED,
        self::STATUS_DELETED,
    ];

    /** @var list<string> */
    public const REJECTABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REQUESTING_UPDATED,
    ];

    /** @var list<string> */
    public const APPROVABLE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->status)) {
                $customer->status = self::STATUS_PENDING;
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'balance',
        'birth_date',
        'gender',
        'national_id',
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
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'birth_date' => 'datetime',
        'profile_completed' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
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

    public function rejections(): HasMany
    {
        return $this->hasMany(CustomerRejection::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    public function changeRequests(): MorphMany
    {
        return $this->morphMany(ChangeRequest::class, 'changeable');
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachments::class, 'attachable');
    }

    public function getCode(): string
    {
        $suffix = strtoupper(substr(str_replace('-', '', (string) $this->id), -8));

        if ($this->merchant && $this->merchant->merchant_code) {
            return 'CSMR'.str_replace('MERCH', '', $this->merchant->merchant_code).$suffix;
        }

        return 'CSMR'.$suffix;
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

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isRequestingUpdated(): bool
    {
        return $this->status === self::STATUS_REQUESTING_UPDATED;
    }

    public function isDeletedStatus(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    public function canAccessWallet(): bool
    {
        return $this->isActive();
    }

    public function authLoginBlockReason(): ?string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE, self::STATUS_PENDING, self::STATUS_REJECTED, self::STATUS_REQUESTING_UPDATED => null,
            self::STATUS_SUSPENDED => 'Your account has been suspended. Please contact support.',
            self::STATUS_INACTIVE => 'Your account is inactive. Please contact support.',
            self::STATUS_DELETED => 'Your account has been deleted.',
            default => 'Your account is not available.',
        };
    }

    public function walletLoginBlockReason(): ?string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => null,
            self::STATUS_PENDING => 'Your account is pending approval.',
            self::STATUS_REJECTED => 'Your account application was rejected. Please update your profile and resubmit.',
            self::STATUS_REQUESTING_UPDATED => 'Your profile update is pending admin review.',
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

    /**
     * Calculate customer KYC profile completion percentage and missing requirements.
     * Mirrors Merchant::calculateProfileCompletion scoring (10% base + 5 × 18%).
     *
     * @return array{
     *     completion: int,
     *     missing: list<string>,
     *     status: string,
     *     documents: array{total_required: int, uploaded: int}
     * }
     */
    public static function calculateProfileCompletion(?Customer $customer = null): array
    {
        if (! $customer) {
            return [
                'completion' => 0,
                'missing' => ['Customer not found.'],
                'status' => 'unknown',
                'documents' => [
                    'total_required' => 2,
                    'uploaded' => 0,
                ],
            ];
        }

        $completion = 10;
        $missingFields = [];
        $pointsPerItem = 18;

        $requiredDocuments = [
            CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE,
            CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT,
        ];

        $hasPersonalInfo = $customer->name
            && $customer->email
            && $customer->phone
            && $customer->national_id
            && $customer->birth_date
            && $customer->gender
            && $customer->country_id
            && $customer->city_id;

        if ($hasPersonalInfo) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Complete your personal profile information.';
        }

        $uploadedDocumentTypes = self::resolveUploadedCustomerDocumentTypes($customer, $requiredDocuments);
        $documentCount = count($uploadedDocumentTypes);

        if ($documentCount === count($requiredDocuments)) {
            $completion += $pointsPerItem;
        } else {
            $missingDocs = array_diff($requiredDocuments, $uploadedDocumentTypes);
            foreach ($missingDocs as $doc) {
                $missingFields[] = ucwords(str_replace('_', ' ', $doc)).' is required.';
            }
        }

        if ($customer->profile_completed) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Submit your KYC profile for review.';
        }

        $status = $customer->status ?? self::STATUS_PENDING;

        if ($status === self::STATUS_ACTIVE) {
            $completion += $pointsPerItem;
        } elseif ($status === self::STATUS_REJECTED) {
            $rejectionReason = self::resolveLatestRejectionReason($customer);
            $missingFields[] = 'Account approval was rejected.'
                .($rejectionReason ? ' Reason: '.$rejectionReason : '');
        } else {
            $missingFields[] = 'Account is pending approval.';
        }

        $hasWallet = $customer->relationLoaded('wallet')
            ? $customer->wallet !== null
            : $customer->wallet()->exists();

        if ($hasWallet) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Wallet has not been provisioned yet.';
        }

        return [
            'completion' => min((int) round($completion), 100),
            'missing' => $missingFields,
            'status' => $status,
            'documents' => [
                'total_required' => count($requiredDocuments),
                'uploaded' => $documentCount,
            ],
        ];
    }

    /**
     * @param  list<string>  $requiredDocuments
     * @return list<string>
     */
    private static function resolveUploadedCustomerDocumentTypes(Customer $customer, array $requiredDocuments): array
    {
        $uploaded = [];

        if ($customer->profile_image) {
            $uploaded[] = CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE;
        }

        $attachmentTypes = $customer->relationLoaded('attachments')
            ? $customer->attachments->pluck('url_type')->all()
            : $customer->attachments()->pluck('url_type')->all();

        foreach ($attachmentTypes as $urlType) {
            if (in_array($urlType, $requiredDocuments, true) && ! in_array($urlType, $uploaded, true)) {
                $uploaded[] = $urlType;
            }
        }

        return array_values(array_intersect($requiredDocuments, $uploaded));
    }

    private static function resolveLatestRejectionReason(Customer $customer): ?string
    {
        if ($customer->relationLoaded('rejections') && $customer->rejections->isNotEmpty()) {
            return $customer->rejections->first()->rejection_reason;
        }

        $rejection = $customer->rejections()->latest()->first();

        return $rejection?->rejection_reason;
    }

    public function  scopeWithCountry($query)
    {
        $user = Auth::user() ?? auth('admin-api')->user();

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
        $userCountryId = data_get($user, 'country_id');
        if ($userCountryId !== null && $userCountryId !== '') {
            $table = $query->getModel()->getTable();

            return $query->where(function ($scopedQuery) use ($table, $userCountryId) {
                $scopedQuery
                    ->where("{$table}.merchant_country_id", $userCountryId)
                    ->orWhere(function ($fallbackQuery) use ($table, $userCountryId) {
                        $fallbackQuery
                            ->whereNull("{$table}.merchant_country_id")
                            ->where("{$table}.country_id", $userCountryId);
                    });
            });
        }

        return $query;
    }
}
