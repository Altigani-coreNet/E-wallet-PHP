<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'webhook_event_id',
        'event_name',
        'payload',
        'status',
        'http_status_code',
        'response',
        'error_message',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'http_status_code' => 'integer',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Get the webhook
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Get the webhook event
     */
    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    /**
     * Scope for failed logs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for successful logs
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for pending retry
     */
    public function scopePendingRetry($query)
    {
        return $query->where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }
}

