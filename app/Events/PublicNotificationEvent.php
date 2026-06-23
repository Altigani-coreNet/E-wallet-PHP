<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $title,
        public string $message,
        public array $meta = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('public-notifications')];
    }

    public function broadcastAs(): string
    {
        return 'notification.public';
    }

    public function broadcastWith(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'meta' => $this->meta,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
