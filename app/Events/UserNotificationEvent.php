<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $targetUserId,
        public string $title,
        public string $message,
        public array $meta = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user-notifications.{$this->targetUserId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.user';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->targetUserId,
            'title' => $this->title,
            'message' => $this->message,
            'meta' => $this->meta,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
