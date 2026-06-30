<?php

namespace App\Modules\CustomerAuth\Services;

use App\Events\CustomerNotificationEvent;

class CustomerEphemeralBroadcastService
{
    /**
     * Push a real-time message to the customer channel without persisting a notification row.
     *
     * @param  array<string, mixed>  $meta
     */
    public function notifyCustomer(string $customerId, string $title, string $message, array $meta = []): void
    {
        event(new CustomerNotificationEvent($customerId, $title, $message, $meta));
    }
}
