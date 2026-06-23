<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Full snapshot of visible banners (same shape as GET /advertisements "data" array).
 */
class BannersListingUpdatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, array{id: int, name: string, image_url: string, country_id: int|null}>  $data
     */
    public function __construct(
        public array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('banners_updates')];
    }

    public function broadcastAs(): string
    {
        return 'banners.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'success' => true,
            'data' => $this->data,
        ];
    }
}
