<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $targetAdminId,
        public string $title,
        public string $message,
        public array $meta = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("admin-notifications.{$this->targetAdminId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.admin';
    }

    public function broadcastWith(): array
    {
        return [
            'admin_id' => $this->targetAdminId,
            'title' => $this->title,
            'message' => $this->message,
            'meta' => $this->meta,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
