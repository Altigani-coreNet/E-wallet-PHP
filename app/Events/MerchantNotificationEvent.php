<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MerchantNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $merchantId,
        public string $title,
        public string $message,
        public array $meta = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("merchant-notifications.{$this->merchantId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.merchant';
    }

    public function broadcastWith(): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'title' => $this->title,
            'message' => $this->message,
            'meta' => $this->meta,
            'sent_at' => now()->toIso8601String(),
        ];
    }
}
