<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Traits\HasStatus;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Passport\HasApiTokens;
use App\Models\Terminal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use App\Traits\AppliesCountryScope;
use App\Scopes\CountryScope;
use Illuminate\Broadcasting\Channel;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, CanResetPassword, HasStatus, HasRoles, HasApiTokens, AppliesCountryScope, HasUuids;

    /**
     * Broadcast user notifications on custom realtime channel.
     */
    public function receivesBroadcastNotificationsOn(): string|array
    {
        return (new Channel("user-notifications.{$this->id}"))->name;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    

    protected $fillable = [
        'name',
        'last_name',
        'user_name',
        'email',
        'password',
        'phone',
        'mobile',
        'profile_image',
        'gender',
        'device_id',
        'manufacturer',
        'model',
        'serial_no',
        'is_approved',
        // 'is_admin',
        'status',
        'merchant_id',
        'branch_id',
        'terminals',
        'current_terminal_id',
        'country_id',
        'module',
        'type', // user type: admin, supervisor, cashier
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function attachments()
    {
        return $this->morphMany(Attachments::class, "attachable");
    }
    
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'is_admin' => 'boolean',
            'terminals' => 'array',
        ];
    }

    public function getTableImage(): string
    {
        if ($this->profile_image) {
                return
                "<div class='cursor-pointer symbol symbol-35px symbol-md-40px'>" .
                "<img src='" . asset($this->profile_image) . "' alt='User Image' width='60' height='60'/>"
                . "</div>";
            }
        return "<img src='" . asset("assets/media/avatars/300-1.jpg") . "' alt='User Image' width='60' height='60' />";
    }

    public function getProfileImageApi(): string
    {
        if ($this->profile_image) {
            return function_exists('coreservice_asset') ? coreservice_asset($this->profile_image) : asset($this->profile_image);
        }
        return function_exists('coreservice_asset') ? coreservice_asset('user_logo.jpg') : asset('assets/media/avatars/300-1.jpg');
    }

    /**
     * Get the merchant associated with this user.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the branch associated with this user.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the current terminal associated with this user.
     */
    public function currentTerminal()
    {
        return $this->belongsTo(Terminal::class, 'current_terminal_id');
    }

    /**
     * Get the user groups that this user belongs to.
     */
    public function userGroups()
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_user');
    }

    /**
     * Get the terminals assigned to this user.
     */
    public function terminals()
    {
        return $this->belongsToMany(Terminal::class, 'user_terminal');
    }

    /**
     * Get the terminal groups assigned to this user.
     */
    public function terminalGroups()
    {
        return $this->belongsToMany(TerminalGroup::class, 'user_terminal_group');
    }

    /**
     * Get the logs for this user.
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

        static::creating(function (User $user) {
            if (blank($user->user_name)) {
                $user->user_name = static::generateUniqueUserName($user);
            }
        });

        static::created(function ($user) {
            self::logChange($user, 'created', null, $user->toArray());
        });

        static::updated(function ($user) {
            $changes = $user->getDirty();
            
            // Remove status and updated_at from changes since they are logged separately
            unset($changes['status'], $changes['updated_at'], $changes['created_at']);
            
            if (!empty($changes)) {
                $oldValues = array_intersect_key($user->getOriginal(), $changes);
                self::logChange($user, 'updated', $oldValues, $changes);
            }
        });
    }

    /**
     * Build a unique user_name from the email prefix plus 2 digits (e.g. jksa89).
     */
    public static function generateUniqueUserName(User $user): string
    {
        $emailPrefix = null;
        if (!blank($user->email) && str_contains($user->email, '@')) {
            $emailPrefix = explode('@', (string) $user->email)[0];
        }

        $raw = $emailPrefix ?: $user->last_name ?: $user->name ?: 'user';
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) $raw));
        if ($base === '') {
            $base = 'user';
        }
        $base = substr($base, 0, 30);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $suffix = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $candidate = $base . $suffix;
            if (! static::withoutGlobalScopes()->where('user_name', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $base . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Log changes to the user.
     */
    public static function logChange($user, $action, $oldValues = null, $newValues = null)
    {
        $performer = Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user();
        $user->logs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $performer?->id,
            'user_type' => $performer ? get_class($performer) : null
        ]);
    }

    /**
     * Get terminal IDs from the JSON field
     */
    public function getTerminalIds(): array
    {
        return $this->terminals ?? [];
    }

    /**
     * Set terminal IDs in the JSON field
     */
    public function setTerminalIds(array $terminalIds): void
    {
        $this->terminals = $terminalIds;
        $this->save();
    }

    /**
     * Add terminal IDs to existing ones
     */
    public function addTerminalIds(array $terminalIds): void
    {
        $currentTerminals = $this->getTerminalIds();
        $newTerminals = array_unique(array_merge($currentTerminals, $terminalIds));
        $this->setTerminalIds($newTerminals);
    }

    /**
     * Remove terminal IDs from existing ones
     */
    public function removeTerminalIds(array $terminalIds): void
    {
        $currentTerminals = $this->getTerminalIds();
        $newTerminals = array_diff($currentTerminals, $terminalIds);
        $this->setTerminalIds(array_values($newTerminals));
    }

    /**
     * Clear all terminal IDs
     */
    public function clearTerminalIds(): void
    {
        $this->setTerminalIds([]);
    }

    /**
     * Check if user has a specific terminal ID
     */
    public function hasTerminalId(int $terminalId): bool
    {
        return in_array($terminalId, $this->getTerminalIds());
    }

    /**
     * Check if user is authorized to use a specific device (terminal_id)
     */
    public function isDeviceAuthorized(string $deviceId): bool
    {
        $userTerminalIds = $this->getTerminalIds();
        
        if (empty($userTerminalIds)) {
            return false; // No terminals assigned
        }
        
        return Terminal::whereIn('id', $userTerminalIds)
            ->where('terminal_id', $deviceId)
            ->exists();
    }

    /**
     * Get the terminal ID from device_id
     */
    public function getTerminalIdFromDeviceId(string $deviceId): ?int
    {
        $userTerminalIds = $this->getTerminalIds();
        
        if (empty($userTerminalIds)) {
            return null;
        }
        
        $terminal = Terminal::whereIn('id', $userTerminalIds)
            ->where('terminal_id', $deviceId)
            ->first();
            
        return $terminal ? $terminal->id : null;
    }

    /**
     * Set current terminal ID and update terminal status to online
     */
    public function setCurrentTerminalAndGoOnline(string $deviceId): bool
    {
        $terminalId = $this->getTerminalIdFromDeviceId($deviceId);
        
        if (!$terminalId) {
            return false;
        }
        
        // If user already has a current terminal, set it to offline first
        if ($this->current_terminal_id && $this->current_terminal_id != $terminalId) {
            $this->clearCurrentTerminalAndGoOffline();
        }
        
        // Update user's current terminal ID
        $this->current_terminal_id = $terminalId;
        $this->save();
        
        // Update terminal status to online
        $terminal = Terminal::find($terminalId);
        if ($terminal) {
            $terminal->terminal_status = 'online';
            $terminal->save();
        }
        
        return true;
    }

    /**
     * Clear current terminal ID and set terminal status to offline
     */
    public function clearCurrentTerminalAndGoOffline(): bool
    {
        if (!$this->current_terminal_id) {
            return false;
        }
        
        // Update terminal status to offline
        $terminal = Terminal::find($this->current_terminal_id);
        if ($terminal) {
            $terminal->terminal_status = 'offline';
            $terminal->save();
        }
        
        // Clear user's current terminal ID
        $this->current_terminal_id = null;
        $this->save();
        
        return true;
    }

    /**
     * Check if user is currently online on a terminal
     */
    public function isOnlineOnTerminal(): bool
    {
        if (!$this->current_terminal_id) {
            return false;
        }
        
        $terminal = Terminal::find($this->current_terminal_id);
        return $terminal && $terminal->terminal_status === 'online';
    }

    /**
     * Get current terminal status
     */
    public function getCurrentTerminalStatus(): ?string
    {
        if (!$this->current_terminal_id) {
            return null;
        }
        
        $terminal = Terminal::find($this->current_terminal_id);
        return $terminal ? $terminal->terminal_status : null;
    }

    /**
     * Force logout from all terminals and set all user terminals to offline
     */
    public function forceLogoutFromAllTerminals(): bool
    {
        $userTerminalIds = $this->getTerminalIds();
        
        if (empty($userTerminalIds)) {
            return true;
        }
        
        // Set all user terminals to offline
        Terminal::whereIn('id', $userTerminalIds)
            ->update(['terminal_status' => 'offline']);
        
        // Clear current terminal ID
        $this->current_terminal_id = null;
        $this->save();
        
        return true;
    }

    /**
     * Check if user is currently logged in on any terminalm
     */
    public function isLoggedInOnAnyTerminal(): bool
    {
        $userTerminalIds = $this->getTerminalIds();
        
        if (empty($userTerminalIds)) {
            return false;
        }
        
        return Terminal::whereIn('id', $userTerminalIds)
            ->where('terminal_status', 'online')
            ->exists();
    }

    /**
     * Get all online terminals for this user
     */
    public function getOnlineTerminals(): Collection
    {
        $userTerminalIds = $this->getTerminalIds();
        
        if (empty($userTerminalIds)) {
            return collect();
        }
        
        return Terminal::whereIn('id', $userTerminalIds)
            ->where('terminal_status', 'online')
            ->get();
    }

    /**
     * Get all offline terminals for this user
     */
    public function getOfflineTerminals(): Collection
    {
        $userTerminalIds = $this->getTerminalIds();
        
        if (empty($userTerminalIds)) {
            return collect();
        }
        
        return Terminal::whereIn('id', $userTerminalIds)
            ->where('terminal_status', 'offline')
            ->get();
    }

    /**
     * Refresh terminal activity (useful for keeping session alive)
     */
    public function refreshTerminalActivity(): bool
    {
        if (!$this->current_terminal_id) {
            return false;
        }
        
        // Update the terminal's last activity timestamp
        $terminal = Terminal::find($this->current_terminal_id);
        if ($terminal) {
            $terminal->touch(); // Updates updated_at timestamp
            return true;
        }
        
        return false;
    }

    /**
     * Check if terminal session has expired (optional feature)
     */
    public function isTerminalSessionExpired(int $timeoutMinutes = 30): bool
    {
        if (!$this->current_terminal_id) {
            return true;
        }
        
        $terminal = Terminal::find($this->current_terminal_id);
        if (!$terminal) {
            return true;
        }
        
        // Check if terminal was updated within the timeout period
        $lastActivity = $terminal->updated_at;
        $timeoutThreshold = now()->subMinutes($timeoutMinutes);
        
        return $lastActivity->lt($timeoutThreshold);
    }

    /**
     * Get the latest logs for this user.
     */
    public function LatestLogs()
    {
        return $this->morphMany(Log::class, 'loggable')->latest()->take(7);
    }

    /**
     * Log an activity for this user.
     */
    public function logActivity($action, $user = null, $message = null, $oldValues = null, $newValues = null, $metadata = null)
    {
        $logData = [
            'action' => $action,
            'loggable_id' => $this->id,
            'loggable_type' => self::class,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ];

        // Add user information if provided
        if ($user) {
            $logData['user_id'] = $user->id;
            $logData['user_type'] = get_class($user);
            
            // Add custom message with user name if provided
            if ($message) {
                $userName = $this->getUserName($user);
                $logData['metadata'] = array_merge($metadata ?? [], [
                    'message' => "{$message} by {$userName}"
                ]);
            }
        }

        return Log::create($logData);
    }

    /**
     * Get user name based on user type.
     */
    private function getUserName($user)
    {
        return match (class_basename($user)) {
            'Admin' => $user->name,
            'User' => $user->name,
            'Merchant' => $user->owner_name ?? $user->name,
            default => $user->name ?? 'Unknown User',
        };
    }

    /**
     * Log user group assignment.
     */
    public function logUserGroupAssignment($admin, $userGroup = null)
    {
        $groupName = $userGroup ? $userGroup->name : 'Unknown Group';
        $message = "User assigned to group '{$groupName}'";
        
        return $this->logActivity('user_group_assigned', $admin, $message, null, null, [
            'group_id' => $userGroup?->id,
            'group_name' => $groupName,
        ]);
    }

    /**
     * Log user group removal.
     */
    public function logUserGroupRemoval($admin, $userGroup = null)
    {
        $groupName = $userGroup ? $userGroup->name : 'Unknown Group';
        $message = "User removed from group '{$groupName}'";
        
        return $this->logActivity('user_group_removed', $admin, $message, null, null, [
            'group_id' => $userGroup?->id,
            'group_name' => $groupName,
        ]);
    }

    /**
     * Check if merchant can create a branch based on plan limits
     *
     * @param string $merchantId
     * @param int $currentCount
     * @return bool
     */
    public static function merchantCanCreateBranch($merchantId, $currentCount): bool
    {
        $merchant = Merchant::find($merchantId, ['*']);
        
        if (!$merchant || !$merchant->plan) {
            // If no merchant or plan, allow (fallback)
            return true;
        }

        $plan = $merchant->plan;
        $scopes = $plan->scopes;

        if (!$scopes || $scopes->isEmpty()) {
            // No plan scopes defined - allow (fallback)
            return true;
        }

        // Find the branches scope
        $branchScope = $scopes->first(function ($scope) {
            return $scope->scope_type === 'branches';
        });

        if (!$branchScope) {
            // Scope not found in plan - allow (fallback)
            return true;
        }

        // Check if scope is enabled
        if (!($branchScope->is_enabled ?? false)) {
            return false;
        }

        // If max_count is null, allow unlimited
        if ($branchScope->max_count === null) {
            return true;
        }

        // Check if current count is less than max_count
        return $currentCount < $branchScope->max_count;
    }

    /**
     * Check if merchant can create a user based on plan limits
     *
     * @param string $merchantId
     * @param int $currentCount
     * @return bool
     */
    public static function merchantCanCreateUser($merchantId, $currentCount): bool
    {
        $merchant = Merchant::find($merchantId, ['*']);
        
        if (!$merchant || !$merchant->plan) {
            // If no merchant or plan, allow (fallback)
            return true;
        }

        $plan = $merchant->plan;
        $scopes = $plan->scopes;

        if (!$scopes || $scopes->isEmpty()) {
            // No plan scopes defined - allow (fallback)
            return true;
        }

        // Find the users scope
        $userScope = $scopes->first(function ($scope) {
            return $scope->scope_type === 'users';
        });

        if (!$userScope) {
            // Scope not found in plan - allow (fallback)
            return true;
        }

        // Check if scope is enabled
        if (!($userScope->is_enabled ?? false)) {
            return false;
        }

        // If max_count is null, allow unlimited
        if ($userScope->max_count === null) {
            return true;
        }

        // Check if current count is less than max_count
        return $currentCount < $userScope->max_count;
    }

}
