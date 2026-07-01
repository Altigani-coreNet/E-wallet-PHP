<?php

namespace App\Modules\AdminKyc\Services;

use App\Events\AdminNotificationEvent;
use App\Models\Customer;
use App\Modules\AdminKyc\Notifications\AdminKycNotification;
use App\Modules\AdminKyc\Notifications\AdminKycNotificationType;
use App\Notifications\TestUserNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminKycNotificationService
{
    public function __construct(
        private readonly AdminKycQueueService $queueService,
    ) {
    }

    /**
     * Notify the primary admin about a customer KYC event. Never throws.
     */
    public function send(Customer $customer, AdminKycNotificationType $type, array $context = []): void
    {
        try {
            $admin = PrimaryAdminResolver::resolve();
            if (! $admin) {
                Log::warning('Admin KYC notification skipped: no primary admin found');

                return;
            }

            $this->persistAndBroadcast($admin, $customer, $type, $context);
            $this->queueService->broadcast();
        } catch (\Throwable $exception) {
            Log::error('Admin KYC notification failed', [
                'customer_id' => $customer->id,
                'type' => $type->value,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function persistAndBroadcast(
        $admin,
        Customer $customer,
        AdminKycNotificationType $type,
        array $context,
    ): void {
        $title = $type->title($context);
        $description = $type->body(array_merge($context, ['customer' => $customer]));
        $topic = $type->topic();
        $groupId = (string) Str::uuid();
        $notificationCode = 'NTF-'.strtoupper(substr($groupId, 0, 8));
        $deepLink = $type->deepLink(array_merge($context, ['customer' => $customer]));

        DB::transaction(function () use (
            $admin,
            $customer,
            $title,
            $description,
            $topic,
            $groupId,
            $notificationCode,
            $type,
            $deepLink,
        ) {
            $notification = new DatabaseNotification([
                'id' => (string) Str::uuid(),
                'type' => TestUserNotification::class,
                'notifiable_type' => get_class($admin),
                'notifiable_id' => (string) $admin->id,
                'topic' => $topic,
                'target_type' => 'admin',
                'merchant_id' => null,
                'user_id' => null,
                'title' => $title,
                'description' => $description,
                'image' => null,
                'source' => 'system',
                'is_admin' => true,
                'notification_group_id' => $groupId,
                'notification_code' => $notificationCode,
                'data' => [
                    'title' => $title,
                    'body' => $description,
                    'sent_at' => now()->toIso8601String(),
                    'meta' => [
                        'topic' => $topic,
                        'target_type' => 'admin',
                        'customer_id' => (string) $customer->id,
                        'notification_group_id' => $groupId,
                        'notification_code' => $notificationCode,
                        'source' => 'system',
                        'is_admin' => true,
                        'event_type' => $type->value,
                        'deep_link' => $deepLink,
                        'image' => null,
                    ],
                ],
            ]);
            $notification->save();

            event(new AdminNotificationEvent(
                (string) $admin->id,
                $title,
                $description,
                [
                    'topic' => $topic,
                    'target_type' => 'admin',
                    'notification_group_id' => $groupId,
                    'notification_code' => $notificationCode,
                    'source' => 'system',
                    'is_admin' => true,
                    'event_type' => $type->value,
                    'customer_id' => (string) $customer->id,
                    'deep_link' => $deepLink,
                ],
            ));
        });
    }
}
