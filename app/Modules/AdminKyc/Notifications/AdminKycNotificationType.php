<?php

namespace App\Modules\AdminKyc\Notifications;

use App\Models\Customer;

enum AdminKycNotificationType: string
{
    case CustomerProfileCompleted = 'customer_profile_completed';
    case CustomerProfileResubmitted = 'customer_profile_resubmitted';
    case CustomerChangeRequestSubmitted = 'customer_change_request_submitted';

    public function topic(): string
    {
        return 'kyc';
    }

    public function title(array $context = []): string
    {
        return match ($this) {
            self::CustomerProfileCompleted => 'New customer KYC submission',
            self::CustomerProfileResubmitted => 'Customer resubmitted KYC',
            self::CustomerChangeRequestSubmitted => 'Customer profile change request',
        };
    }

    public function body(array $context = []): string
    {
        /** @var Customer|null $customer */
        $customer = $context['customer'] ?? null;
        $name = $customer?->name ?: $customer?->phone ?: 'Customer';

        return match ($this) {
            self::CustomerProfileCompleted => "{$name} completed their profile and is awaiting review.",
            self::CustomerProfileResubmitted => "{$name} corrected rejected fields and resubmitted for review.",
            self::CustomerChangeRequestSubmitted => "{$name} requested a profile update.",
        };
    }

    public function deepLink(array $context = []): string
    {
        /** @var Customer|null $customer */
        $customer = $context['customer'] ?? null;

        return match ($this) {
            self::CustomerProfileCompleted,
            self::CustomerProfileResubmitted => $customer
                ? "/admin/customers/{$customer->id}"
                : '/admin/kyc/pending',
            self::CustomerChangeRequestSubmitted => $customer
                ? "/admin/customers/{$customer->id}"
                : '/admin/kyc/change-requests',
        };
    }
}
