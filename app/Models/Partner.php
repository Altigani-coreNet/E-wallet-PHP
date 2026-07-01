<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatus;
use App\Enums\BusinessType;
use Illuminate\Support\Facades\Auth;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;
use App\Models\NotifierConfiguration;
use App\Models\ServiceCategory;


class Partner extends Model
{
    use HasFactory, HasStatus, AppliesCountryScope, HasUuids;

    protected $table = 'partners';

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
        'business_phone',
        'address',
        'business_type',
        'business_name',
        'country_id',
        'partner_category_id',
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
        'parent_id',
        'is_parent',
        'account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_parent' => 'boolean',
        'business_type' => BusinessType::class,
    ];

    protected $appends = ['logo_url'];

    /**
     * Get the user associated with this partner.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function attachments()
    {
        return $this->morphMany(Attachments::class, "attachable");
    }

    /**
     * Get the logs for this partner.
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

        // Automatically generate merchant_code if not provided
        static::creating(function ($partner) {
            if (empty($partner->merchant_code)) {
                $partner->merchant_code = self::generateMerchantCode();
            }
        });

        static::created(function ($partner) {
            self::logChange($partner, 'created', null, $partner->toArray());
        });

        static::updated(function ($partner) {
            $changes = $partner->getDirty();
            
            // Remove status and is_active from changes since they are logged separately
            unset($changes['status'], $changes['is_active'], $changes['updated_at'], $changes['created_at']);
            
            if (!empty($changes)) {
                $oldValues = array_intersect_key($partner->getOriginal(), $changes);
                self::logChange($partner, 'updated', $oldValues, $changes);
            }
        });
    }

    public function LatestLogs()
    {
        return $this->morphMany(Log::class, 'loggable')->latest()->take(5);
    }
    

    /**
     * Log changes to the partner.
     */
    public static function logChange($partner, $action, $oldValues = null, $newValues = null)
    {
        $user = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user();
        $partner->logs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null
        ]);
    }

    /**
     * Gateway / subscription services owned by this partner.
     */
    public function services()
    {
        return $this->hasMany(Service::class, 'partner_id');
    }

    /**
     * Get the branches for this partner.
     */
    public function branches()
    {
        return $this->hasMany(Branch::class, 'partner_id');
    }

    /**
     * Get the active branches for this partner.
     */
    public function activeBranches()
    {
        return $this->hasMany(Branch::class, 'partner_id')->where('is_active', true);
    }

    /**
     * Get the terminal groups for this partner.
     */
    public function terminalGroups()
    {
        return $this->hasMany(TerminalGroup::class, 'partner_id');
    }

    /**
     * Get the active terminal groups for this partner.
     */
    public function activeTerminalGroups()
    {
        return $this->hasMany(TerminalGroup::class, 'partner_id')->where('is_active', true);
    }

    /**
     * Get the terminals for this partner.
     */
    public function terminals()
    {
        return $this->hasMany(Terminal::class, 'partner_id');
    }

    /**
     * Get the active terminals for this partner.
     */
    public function activeTerminals()
    {
        return $this->hasMany(Terminal::class, 'partner_id')->where('is_active', true);
    }

    /**
     * Generate a unique merchant code
     */
    public static function generateMerchantCode()
    {
        do {
            $code = 'CP' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('merchant_code', $code)->exists());

        return $code;
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use generateMerchantCode() instead
     */
    public static function generatePartnerCode()
    {
        return self::generateMerchantCode();
    }



    /**
     * Get the logo URL
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            // Handle both public assets path and storage path
            $logoPath = $this->logo;
            
            // If it's a storage path, convert it to storage URL
            if (str_starts_with($logoPath, 'storage/')) {
                return function_exists('coreservice_asset') 
                    ? coreservice_asset($logoPath) 
                    : asset($logoPath);
            }
            
            // If it's already an assets path, use it directly
            if (str_starts_with($logoPath, 'assets/')) {
                return function_exists('coreservice_asset') 
                    ? coreservice_asset($logoPath) 
                    : asset($logoPath);
            }
            
            // Default: assume it's a relative path from public
            return function_exists('coreservice_asset') 
                ? coreservice_asset($logoPath) 
                : asset($logoPath);
        }
        return function_exists('coreservice_asset') 
            ? coreservice_asset('assets/media/avatars/300-1.jpg') 
            : asset('assets/media/avatars/300-1.jpg');
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
                   "<img src='" . asset($this->logo) . "' alt='Partner Logo' width='60' height='60'/>" .
                   "</div>";
        }
        return "<img src='" . asset("assets/media/avatars/300-1.jpg") . "' alt='Partner Logo' width='60' height='60' />";
    }

    /**
     * Get business type display name
     */
    public function getBusinessTypeDisplayNameAttribute(): string
    {
        return $this->business_type?->getDisplayName() ?? 'N/A';
    }

    /**
     * Calculate partner profile completion percentage and missing requirements
     * Static method that replicates the functionality from PartnerProfileHeader component
     * 
     * @param Partner $partner
     * @return array
     */
    public static function calculateProfileCompletion(Partner|null $partner = null): array
    {
        $completion = 10; // Default minimum
        $missingFields = []; // To store missing field messages
        $pointsPerItem = 18; // 90% divided by 5 main criteria

        $partner = $partner ?? Auth::user()->partner;
        // 1. Check if partner has basic profile info
        $hasProfile = $partner->name && $partner->owner_name && $partner->email && $partner->phone && $partner->address;
        if ($hasProfile) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Complete your business profile information.';
        }

        // 2. Check required documents
        $requiredDocuments = ['company_logo', 'user_id_document', 'tax_certification', 'trade_license'];
        $documentCount = $partner->attachments()
            ->whereIn('url_type', $requiredDocuments)
            ->count();
            // dd($documentCount);
        
        if ($documentCount === count($requiredDocuments)) {
            $completion += $pointsPerItem;
        } else {
            $missingDocs = array_diff($requiredDocuments, 
                $partner->attachments()->whereIn('url_type', $requiredDocuments)->pluck('url_type')->toArray());
            // dd($missingDocs);
                foreach ($missingDocs as $doc) {
                $missingFields[] = ucwords(str_replace('_', ' ', $doc)) . ' is required.';
            }
        }

        // 3. Check account approval status
        if ($partner->status === 'approved') {
            $completion += $pointsPerItem;
        } else if ($partner->status === 'rejected') {
            $missingFields[] = 'Account approval was rejected. Reason: ' . ($partner->rejection_reason ?? 'Not specified');
        } else {
            $missingFields[] = 'Account is pending approval.';
        }

        // 4. Check if partner has at least one user
        $hasUsers = $partner->users()->count() > 0;
        if ($hasUsers) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Add at least one user to your account.';
        }

        // 5. Check if partner has at least one terminal
        $hasTerminal = $partner->terminals()->count() > 0;
        if ($hasTerminal) {
            $completion += $pointsPerItem;
        } else {
            $missingFields[] = 'Add at least one terminal to your account.';
        }

        return [
            'completion' => min(round($completion), 100), // Ensure max is 100%
            'missing' => $missingFields, // Return list of missing fields
            'status' => $partner->status, // Include account status
            'documents' => [
                'total_required' => count($requiredDocuments),
                'uploaded' => $documentCount
            ],
            'users_count' => $partner->users()->count(),
            'terminals_count' => $partner->terminals()->count()
        ];
    }


    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function partnerCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'partner_category_id');
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
     * Get the plan associated with this partner.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    
    public function users()
    {
        return $this->hasMany(User::class, 'partner_id');
    }

    /**
     * Get the notifier configurations for this partner.
     */
    public function notifierConfigurations()
    {
        return $this->hasMany(NotifierConfiguration::class, 'partner_id');
    }

    /**
     * Parent partner (root). Sub-partners reference this via parent_id.
     */
    public function parentPartner()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Direct sub-partners under this partner.
     */
    public function subPartners()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

}
