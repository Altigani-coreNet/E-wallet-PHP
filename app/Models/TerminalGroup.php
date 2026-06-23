<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasStatus;
use App\Traits\AppliesCountryScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class TerminalGroup extends Model
{   
    use HasFactory, HasStatus, AppliesCountryScope, HasUuids;

    protected $fillable = [
        'name',
        'group_id',
        'parent_id',
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
     * Get the parent terminal group.
     */
    public function parent()
    {
        return $this->belongsTo(TerminalGroup::class, 'parent_id');
    }

    /**
     * Get the child terminal groups.
     */
    public function children()
    {
        return $this->hasMany(TerminalGroup::class, 'parent_id');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors recursively.
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Check if this is a subgroup.
     */
    public function isSubgroup(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if this is a parent group.
     */
    public function isParentGroup(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the merchant that owns this terminal group.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the branch that owns this terminal group.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the terminals in this group.
     */
    public function terminals()
    {
        return $this->belongsToMany(Terminal::class, 'terminal_group_terminal');
    }

    /**
     * Get the active terminals in this group.
     */
    public function activeTerminals()
    {
        return $this->belongsToMany(Terminal::class, 'terminal_group_terminal')
                    ->where('is_active', true);
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
        $badgeClass = $this->is_active ? 'badge-light-success' : 'badge-light-danger';
        
        return "<span class='badge {$badgeClass}'>{$status}</span>";
    }

    /**
     * Scope to get only active terminal groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive terminal groups
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to get only parent groups (no parent_id)
     */
    public function scopeParentGroups($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get only subgroups (has parent_id)
     */
    public function scopeSubgroups($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Generate a unique group ID
     */
    public static function generateGroupId()
    {
        do {
            $id = 'GRP' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('group_id', $id)->exists());

        return $id;
    }

    /**
     * Get the full group information
     */
    public function getFullInfoAttribute(): string
    {
        $info = $this->name . ' (' . $this->group_id . ')';
        
        if ($this->parent) {
            $info .= ' - Subgroup of ' . $this->parent->name;
        }
        
        if ($this->branch) {
            $info .= ' - ' . $this->branch->name;
        }
        
        return $info;
    }

    /**
     * Get the users assigned to this terminal group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_terminal_group');
    }

    /**
     * Get the user groups associated with this terminal group.
     */
    public function userGroups()
    {
        return $this->belongsToMany(UserGroup::class, 'terminal_group_user_group');
    }

    /**
     * Get terminals count
     */
    public function getTerminalsCountAttribute(): int
    {
        return $this->terminals()->count();
    }

    /**
     * Get users count
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get subgroups count
     */
    public function getSubgroupsCountAttribute(): int
    {
        return $this->children()->count();
    }
}
