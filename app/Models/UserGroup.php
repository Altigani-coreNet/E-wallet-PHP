<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatus;
use App\Traits\AppliesCountryScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class UserGroup extends Model
{
    use HasFactory, HasStatus , AppliesCountryScope, HasUuids;

    protected $fillable = [
        'name',
        'group_id',
        'merchant_id',
        'branch_id',
        'description',
        'is_active',
        'country_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the merchant that owns this user group.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the branch that owns this user group.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the users in this group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_group_user');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the terminal groups associated with this user group.
     */
    public function terminalGroups()
    {
        return $this->belongsToMany(TerminalGroup::class, 'terminal_group_user_group');
    }



    /**
     * Get the active users in this group.
     */
    public function activeUsers()
    {
        return $this->belongsToMany(User::class, 'user_group_user')
                    ->where('status', true);
    }

    /**
     * Get the status display name
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get status with span for DataTables
     */
    public function getStatusWithSpan(): string
    {
        $status = $this->is_active ? 'Active' : 'Inactive';
        $badgeClass = $this->is_active ? 'badge-light-success' : 'badge-light-warning';
        
        return "<span class='badge {$badgeClass} '>{$status}</span>";
    }



    /**
     * Scope to get only active user groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive user groups
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Generate a unique group ID
     */
    public static function generateGroupId()
    {
        do {
            $id = 'USR_GRP' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('group_id', $id)->exists());

        return $id;
    }

    /**
     * Get the full group information
     */
    public function getFullInfoAttribute(): string
    {
        $info = $this->name . ' (' . $this->group_id . ')';
        
        if ($this->merchant) {
            $info .= ' - ' . $this->merchant->name;
        }
        
        if ($this->branch) {
            $info .= ' - ' . $this->branch->name;
        }
        
        return $info;
    }

    /**
     * Get users count
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }


} 