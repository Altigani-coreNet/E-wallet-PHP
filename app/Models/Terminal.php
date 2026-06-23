<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatus;
use App\Traits\AppliesCountryScope;
class Terminal extends Model
{
    use HasFactory, HasStatus, AppliesCountryScope, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'terminal_id',
        'merchant_id',
        'branch_id',
        'country_id',
        'brand',
        'model',
        'manufacturer',
        'serial_no',
        'sdk_id',
        'sdk_version',
        'android_os',
        'add_type',
        'is_active',
        'device_id',
        'terminal_status', // Fixed typo from termainl_status
        'current_user_id', // New field to track current user
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the merchant that owns this terminal.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the branch that owns this terminal.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the terminal groups that this terminal belongs to.
     */
    public function terminalGroups()
    {
        return $this->belongsToMany(TerminalGroup::class, 'terminal_group_terminal');
    }

    /**
     * Get the users assigned to this terminal.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_terminal');
    }

    /**
     * Get the user who is currently using this terminal
     */
    public function currentUser()
    {
        return $this->belongsTo(User::class, 'current_user_id');
    }

    /**
     * Get the logs for this terminal. 
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    /**
     * Get the latest logs for this terminal.
     */
    public function LatestLogs()
    {
        return $this->morphMany(Log::class, 'loggable')->latest()->take(7);
    }

    /**
     * Log an activity for this terminal.
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
     * Log status change for this terminal.
     */
    public function logStatusChange($oldStatus, $newStatus, $user = null)
    {
        $message = "Terminal status changed from {$oldStatus} to {$newStatus}";
        $metadata = [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];
        
        return $this->logActivity('status_changed', $user, $message, null, null, $metadata);
    }

    /**
     * Log user assignment to terminal.
     */
    public function logUserAssignment($user, $assignedUser = null)
    {
        $assignedUserName = $assignedUser ? $this->getUserName($assignedUser) : 'Unknown User';
        $message = "User {$assignedUserName} assigned to terminal";
        
        return $this->logActivity('user_assigned', $user, $message, null, null, [
            'assigned_user_id' => $assignedUser?->id,
            'assigned_user_type' => $assignedUser ? get_class($assignedUser) : null,
        ]);
    }

    /**
     * Log user removal from terminal.
     */
    public function logUserRemoval($user, $removedUser = null)
    {
        $removedUserName = $removedUser ? $this->getUserName($removedUser) : 'Unknown User';
        $message = "User {$removedUserName} removed from terminal";
        
        return $this->logActivity('user_removed', $user, $message, null, null, [
            'removed_user_id' => $removedUser?->id,
            'removed_user_type' => $removedUser ? get_class($removedUser) : null,
        ]);
    }

    /**
     * Get the last user who used this terminal
     */
    public function lastUser()
    {
        return $this->belongsToMany(User::class, 'user_terminal')
                    ->withPivot('created_at')
                    ->orderBy('pivot_created_at', 'desc')
                    ->first();
    }

    /**
     * Get the status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Scope to get only active terminals
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive terminals
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to get terminals by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('terminal_status', $status);
    }

    /**
     * Scope to get online terminals
     */
    public function scopeOnline($query)
    {
        return $query->where('terminal_status', 'online');
    }

    /**
     * Scope to get offline terminals
     */
    public function scopeOffline($query)
    {
        return $query->where('terminal_status', 'offline');
    }

    /**
     * Scope to get testing terminals
     */
    public function scopeTesting($query)
    {
        return $query->where('terminal_status', 'testing');
    }

    /**
     * Scope to get terminals by merchant
     */
    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    /**
     * Generate a unique terminal ID
     */
    public static function generateTerminalId()
    {
        do {
            $id = 'TERM' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('terminal_id', $id)->exists());

        return $id;
    }

    /**
     * Get the full terminal information
     */
    public function getFullInfoAttribute(): string
    {
        $info = $this->name;
        
        if ($this->brand) {
            $info .= ' (' . $this->brand . ')';
        }
        
        if ($this->model) {
            $info .= ' - ' . $this->model;
        }
        
        if ($this->manufacturer) {
            $info .= ' - ' . $this->manufacturer;
        }
        
        return $info;
    }

    public function getTerminalStatus()
    {
        switch ($this->terminal_status) { // Fixed typo
            case 'online':
                return '<span class="badge badge-success">Online</span>';
            case 'offline':
                return '<span class="badge badge-danger">Offline</span>';
            case 'testing':
                return '<span class="badge badge-warning">Testing</span>';
            default:
                return 'N/A';
        }
    }

    /**
     * Get terminal status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        switch ($this->terminal_status) {
            case 'online':
                return 'badge-success';
            case 'offline':
                return 'badge-danger';
            case 'testing':
                return 'badge-warning';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get terminal status text
     */
    public function getStatusTextAttribute(): string
    {
        switch ($this->terminal_status) {
            case 'online':
                return 'Online';
            case 'offline':
                return 'Offline';
            case 'testing':
                return 'Testing';
            default:
                return 'Unknown';
        }
    }
}
