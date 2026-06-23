<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Log extends Model
{
    protected $fillable = [
        'action',
        'old_values',
        'new_values',
        'metadata',
        'loggable_id',
        'loggable_type',
        'user_id',
        'user_type'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array'
    ];

    protected $appends = [
        'label',
        'time',
        'message',
        'text',
    ];

    /**
     * Get the parent loggable model.
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user');
    }

    

    public function getLoggableType()
    {
        return match ($this->loggable_type) {
            Merchant::class => "Merchants",
            Admin::class => "Admins",
            Terminal::class => "Terminals",
            User::class => "Users",
        };
    }


    public function getLabelAttribute()
    {
        return match ($this->action) {
            'created' => 'success',
            'updated' => 'primary',
            'deleted' => 'danger',
            "status_changed" => "warning",
            'rejected' => 'danger',
            'approved' => 'success',
            'suspended' => 'danger',
            'activated' => 'success',
            'attachments_updated' => 'success',
            'viewed' => 'info',
            'registered' => 'success',
            'retrieved' => 'info',
            'user_assigned' => 'success',
            'user_removed' => 'warning',
            'group_assigned' => 'info',
            'group_removed' => 'warning',
            'user_group_assigned' => 'info',
            'user_group_removed' => 'warning',
            'logged_in' => 'success',
            'logged_out' => 'info',
            'terminal_usage_approved' => 'success',
            'terminal_usage_denied' => 'danger',
            'password_changed' => 'warning',
            'roles_updated' => 'info',
            'roles_removed' => 'warning',
            'terminals_updated' => 'info',
            'terminal_removed' => 'warning',
            default => 'secondary'
        };
    }   


    public function getTimeAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the message from metadata if it exists
     */
    public function getMessageAttribute()
    {
        if (!$this->metadata || !is_array($this->metadata)) {
            return null;
        }

        return $this->metadata['message'] ?? null;
    }

    public function getTextAttribute()
    {
        // If we have a custom message in metadata, use that
        if ($this->message) {
            return $this->message;
        }

        // Do not resolve polymorphic relations here. Some user_type values (e.g. ExternalUser)
        // are auth-only objects and not Eloquent models, which breaks morph loading.
        $entityType = class_basename((string) ($this->loggable_type ?: 'Record'));
        $performedBy = is_array($this->metadata) ? ($this->metadata['performed_by'] ?? null) : null;
        $userName = is_string($performedBy) && trim($performedBy) !== '' ? $performedBy : 'System';
        
        // Fallback to default action messages if no custom message exists
        return match ($this->action) {
            'created' => $userName ? "{$entityType} created Success by {$userName}" : " {$entityType} created Success",
            'updated' => "{$entityType} updated information by {$userName}",
            'deleted' => "Deleted {$entityType} by {$userName}",
            'status_changed' => "{$entityType} status changed by {$userName}",
            'rejected' => "{$entityType} rejected by {$userName}",
            'approved' => "{$entityType} approved by {$userName}",
            'suspended' => "{$entityType} suspended by {$userName}",
            'activated' => "{$entityType} activated by {$userName}",
            'attachments_updated' => "{$entityType} attachments updated by {$userName}",
            'viewed' => "{$entityType} viewed by {$userName}",
            'registered' => "{$entityType} registered automatically",
            'retrieved' => "{$entityType} retrieved automatically",
            'user_assigned' => "{$entityType} user assigned by {$userName}",
            'user_removed' => "{$entityType} user removed by {$userName}",
            'group_assigned' => "{$entityType} assigned to group by {$userName}",
            'group_removed' => "{$entityType} removed from group by {$userName}",
            'user_group_assigned' => "{$entityType} assigned to user group by {$userName}",
            'user_group_removed' => "{$entityType} removed from user group by {$userName}",
            'logged_in' => "{$entityType} logged in",
            'logged_out' => "{$entityType} logged out",
            'terminal_usage_approved' => "{$entityType} used approved terminal",
            'terminal_usage_denied' => "{$entityType} attempted to use unauthorized terminal",
            'password_changed' => "{$entityType} password changed by {$userName}",
            'roles_updated' => "{$entityType} roles updated by {$userName}",
            'roles_removed' => "{$entityType} roles removed by {$userName}",
            'terminals_updated' => "{$entityType} terminals updated by {$userName}",
            'terminal_removed' => "{$entityType} terminal removed by {$userName}",
            default => "Action on {$entityType} by {$userName}"
        };
        
    }

    public function getLabelWithSpan(): string
    {
        return "<span class='badge badge-" . $this->label . "'>" . __('translation.' . $this->action) . " <span>";
    }

    public function getLoggableRoute(): string
    {
        return match ($this->loggable_type) {
            Merchant::class => 'merchants.show',
            Admin::class => 'users.show',
            Terminal::class => 'terminals.show',
            User::class => 'users.show',
        };
    }
}