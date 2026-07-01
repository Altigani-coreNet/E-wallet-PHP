<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminKycQueueUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array{pending_customers: int, pending_change_requests: int}  $counts
     */
    public function __construct(
        public array $counts,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-kyc-queue')];
    }

    public function broadcastAs(): string
    {
        return 'admin.kyc.queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'pending_customers' => $this->counts['pending_customers'] ?? 0,
            'pending_change_requests' => $this->counts['pending_change_requests'] ?? 0,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
