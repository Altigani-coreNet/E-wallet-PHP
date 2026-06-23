<?php

namespace App\Traits;

use App\Events\ServicesUpdateignEvent;

trait BroadcastsServicesListingUpdate
{
    protected ?string $servicesListingBroadcastWarning = null;

    protected function broadcastServicesListingUpdate(): bool
    {
        try {
            event(new ServicesUpdateignEvent([]));
            $this->servicesListingBroadcastWarning = null;

            return true;
        } catch (\Throwable $exception) {
            report($exception);
            $this->servicesListingBroadcastWarning = 'Failed to send updates to connected applications.';

            return false;
        }
    }

    protected function getServicesListingBroadcastWarning(): ?string
    {
        return $this->servicesListingBroadcastWarning;
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    protected function appendServicesListingBroadcastWarning($payload)
    {
        $warning = $this->getServicesListingBroadcastWarning();
        if ($warning && is_array($payload)) {
            $payload['warning'] = $warning;
        }

        return $payload;
    }
}
