<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestUserNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public array $meta = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->message,
            'meta' => $this->meta,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
