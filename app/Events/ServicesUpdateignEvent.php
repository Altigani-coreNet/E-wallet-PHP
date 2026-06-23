<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Full snapshot of service categories listing.
 */
class ServicesUpdateignEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('services_updates')];
    }

    public function broadcastAs(): string
    {
        return 'services.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'success' => true,
            'data' => $this->data,
        ];
    }
}
