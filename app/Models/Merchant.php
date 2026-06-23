<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatus;
use App\Enums\BusinessType;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;


class Merchant extends Model
{
    use HasFactory, HasStatus, AppliesCountryScope, HasUuids, Notifiable;

    

    // protected string $status_attribute = 'is_active';

    public const STATUS = ['pending', 'approved', 'rejected', 'suspended' ,	'viewed' , 'deleted', 'requesting_updated'];
    // public function __construct()
    // {
    //     $this->status_attribute = 'is_active';
    // }

    protected $fillable = [
        'name',
        'owner_name',
        'email',
        'phone',
        'address',
        'business_type',
        'business_name',
        'country_id',
        'city_id',
        'trade_license_number',
        'tax_certified_number',
        'tax_number',
        'trade_license_start_date',
        'trade_license_expired_date',
        'is_active',
        'logo',
        'merchant_code',
        'user_id',
        'status',
        'add_type',
        'scopes',
        'plan_id',
        'currency',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'business_type' => BusinessType::class,
        'scopes' => 'array',
    ];

    /**
     * Get the user associated with this merchant.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachments::class, "attachable");
    }

    /**
     * Get the logs for this merchant.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Add global scope to automatically filter by regions from X-Regions header
        static::addGlobalScope(new CountryScope());

        static::created(function ($merchant) {
            self::logChange($merchant, 'created', null, $merchant->toArray());
        });

        static::updated(function ($merchant) {
            $changes = $merchant->getDirty();
            
            // Remove status and is_active from changes since they are logged separately
            unset($changes['status'], $changes['is_active'], $changes['updated_at'], $changes['created_at']);
            
            if (!empty($changes)) {
                $oldValues = array_intersect_key($merchant->getOriginal(), $changes);
                self::logChange($merchant, 'updated', $oldValues, $changes);
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function LatestLogs()
    {
        return $this->morphMany(Log::class, 'loggable')->latest()->take(5);
    }
    

    /**
     * Log changes to the merchant.
     */
    public static function logChange($merchant, $action, $oldValues = null, $newValues = null)
    {
        $user = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user();
        $merchant->logs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null
        ]);
    }

    /**
     * Get the branches for this merchant.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the active branches for this merchant.
     */
    public function activeBranches()
    {
        return $this->hasMany(Branch::class)->where('is_active', true);
    }

    /**
     * Get the terminal groups for this merchant.
     */
    public function terminalGroups()
    {
        return $this->hasMany(TerminalGroup::class);
    }

    /**
     * Get the active terminal groups for this merchant.
     */
    public function activeTerminalGroups()
    {
        return $this->hasMany(TerminalGroup::class)->where('is_active', true);
    }

    /**
     * Get the terminals for this merchant.
     */
    public function terminals()
    {
        return $this->hasMany(Terminal::class);
    }

    /**
     * Get the active terminals for this merchant.
     */
    public function activeTerminals()
    {
        return $this->hasMany(Terminal::class)->where('is_active', true);
    }

    /**
     * Generate a unique merchant code
     */
    public static function generateMerchantCode()
    {
        do {
            $code = 'MERCH' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('merchant_code', $code)->exists());

        return $code;
    }



    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return function_exists('coreservice_asset') ? coreservice_asset($this->logo) : asset($this->logo);
        }
        return function_exists('coreservice_asset') ? coreservice_asset('assets/media/avatars/300-1.jpg') : asset('assets/media/avatars/300-1.jpg');
    }

    public function getSpanStatus(): string

    { 
        $class = match ($this->status) {
            null => 'warning',
            'pending' => 'warning',
            'viewed' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'suspended' => 'warning',
            'deleted' => 'danger',
            'requesting_updated' => 'warning',
        };
        $status = $this->status ?? 'pending';

        return "<span class='badge badge-light-{$class}'>".   __('translation.' . $status)   .'</span>';
    }

    /**
     * Get the table image for display
     */
    public function getTableImage(): string
    {
        if ($this->logo) {
            return "<div class='cursor-pointer symbol symbol-35px symbol-md-40px'>" .
                   "<img src='" . asset($this->logo) . "' alt='Merchant Logo' width='60' height='60'/>" .
                   "</div>";
        }
        return "<img src='" . asset("assets/media/avatars/300-1.jpg") . "' alt='Merchant Logo' width='60' height='60' />";
    }

    /**
     * Get business type display name
     */
    public function getBusinessTypeDisplayNameAttribute(): string
    {
        return $this->business_type?->getDisplayName() ?? 'N/A';
    }

    /**
     * Calculate merchant profile completion percentage and missing requirements
     * Static method that replicates the functionality from MerchantProfileHeader component
     * 
     * @param Merchant $merchant
     * @return array
     */
    public static function calculateProfileCompletion(Merchant|null $merchant = null): array
    {
        $completion = 10; // Default minimum
        $missingFields = []; // To store missing field messages
        $pointsPerItem = 18; // 90% divided by 5 main criteria

        $merchant = $merchant ?? Auth::user()->merchant;
        // 1. Check if merchant has basic profile info
        $hasProfile = $merchant->name && $merchant->owner_name && $merchant->email && $merchant->phone && $merchant->address;
        if ($hasProfile) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Complete your business profile information.';
        }

        // 2. Check required documents
        $requiredDocuments = ['company_logo', 'user_id_document', 'tax_certification', 'trade_license'];
        $documentCount = $merchant->attachments()
            ->whereIn('url_type', $requiredDocuments)
            ->count();
            // dd($documentCount);
        
        if ($documentCount === count($requiredDocuments)) {
            $completion += $pointsPerItem;
        } else {
            $missingDocs = array_diff($requiredDocuments, 
                $merchant->attachments()->whereIn('url_type', $requiredDocuments)->pluck('url_type')->toArray());
            // dd($missingDocs);
                foreach ($missingDocs as $doc) {
                $missingFields[] = ucwords(str_replace('_', ' ', $doc)) . ' is required.';
            }
        }

        // 3. Check account approval status
        if ($merchant->status === 'approved') {
            $completion += $pointsPerItem;
        } else if ($merchant->status === 'rejected') {
            $missingFields[] = 'Account approval was rejected. Reason: ' . ($merchant->rejection_reason ?? 'Not specified');
        } else {
            $missingFields[] = 'Account is pending approval.';
        }

        // 4. Check if merchant has at least one user
        $hasUsers = $merchant->users()->count() > 0;
        if ($hasUsers) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Add at least one user to your account.';
        }

        // 5. Check if merchant has at least one terminal
        $hasTerminal = $merchant->terminals()->count() > 0;
        if ($hasTerminal) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Add at least one terminal to your account.';
        }

        return [
            'completion' => min(round($completion), 100), // Ensure max is 100%
            'missing' => $missingFields, // Return list of missing fields
            'status' => $merchant->status, // Include account status
            'documents' => [
                'total_required' => count($requiredDocuments),
                'uploaded' => $documentCount
            ],
            'users_count' => $merchant->users()->count(),
            'terminals_count' => $merchant->terminals()->count()
        ];
    }


    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function merchantCurrency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    /**
     * Get the plan associated with this merchant.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

}
