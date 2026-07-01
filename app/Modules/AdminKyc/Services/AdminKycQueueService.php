<?php

namespace App\Modules\AdminKyc\Services;

use App\Events\AdminKycQueueUpdatedEvent;
use App\Models\ChangeRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class AdminKycQueueService
{
    /**
     * @return array{pending_customers: int, pending_change_requests: int}
     */
    public function counts(): array
    {
        $pendingCustomers = Customer::query()
            ->withCountry()
            ->where('status', Customer::STATUS_PENDING)
            ->count();

        $scopedCustomerIds = Customer::query()->withCountry()->select('id');

        $pendingChangeRequests = ChangeRequest::query()
            ->where('changeable_type', Customer::class)
            ->whereIn('changeable_id', $scopedCustomerIds)
            ->where('status', 'pending')
            ->count();

        return [
            'pending_customers' => $pendingCustomers,
            'pending_change_requests' => $pendingChangeRequests,
        ];
    }

    public function broadcast(): void
    {
        try {
            event(new AdminKycQueueUpdatedEvent($this->counts()));
        } catch (\Throwable $exception) {
            Log::error('Admin KYC queue broadcast failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
