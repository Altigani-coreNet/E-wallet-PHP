<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $targetCustomerId,
        public string $title,
        public string $message,
        public array $meta = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("customer-notifications.{$this->targetCustomerId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.customer';
    }

    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->targetCustomerId,
            'title' => $this->title,
            'message' => $this->message,
            'meta' => $this->meta,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
