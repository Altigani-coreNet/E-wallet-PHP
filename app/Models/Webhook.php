<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'endpoint_url',
        'secret',
        'is_active',
        'last_triggered_at',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    /**
     * Boot method to generate secret on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (empty($webhook->secret)) {
                $webhook->secret = 'whsec_' . Str::random(32);
            }
        });
    }

    /**
     * Get the events subscribed to this webhook
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            WebhookEvent::class,
            'webhook_event_subscriptions',
            'webhook_id',
            'webhook_event_id'
        )->withTimestamps();
    }

    /**
     * Get the logs for this webhook
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    /**
     * Get recent logs
     */
    public function recentLogs($limit = 10)
    {
        return $this->logs()->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Increment success count
     */
    public function incrementSuccess()
    {
        $this->increment('success_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * Increment failure count
     */
    public function incrementFailure()
    {
        $this->increment('failure_count');
        $this->update(['last_triggered_at' => now()]);
    }
}

