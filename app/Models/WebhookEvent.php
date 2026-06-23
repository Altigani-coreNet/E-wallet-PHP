<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'version',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the webhooks subscribed to this event
     */
    public function webhooks(): BelongsToMany
    {
        return $this->belongsToMany(
            Webhook::class,
            'webhook_event_subscriptions',
            'webhook_event_id',
            'webhook_id'
        )->withTimestamps();
    }

    /**
     * Scope to get events by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get active events
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all events grouped by category
     */
    public static function groupedByCategory()
    {
        return self::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
    }
}

