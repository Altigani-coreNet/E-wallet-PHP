<?php

namespace App\Modules\CustomerAuth\Services;

use App\Events\CustomerNotificationEvent;
use App\Models\Customer;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Notifications\TestUserNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerSystemNotificationService
{
    /**
     * Send a system notification to a customer. Never throws — failures are logged only.
     */
    public function send(Customer $customer, CustomerNotificationType $type, array $context = []): void
    {
        try {
            $this->persistAndBroadcast($customer, $type, $context);
        } catch (\Throwable $exception) {
            Log::error('Customer system notification failed', [
                'customer_id' => $customer->id,
                'type' => $type->value,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function persistAndBroadcast(Customer $customer, CustomerNotificationType $type, array $context): void
    {
        $title = $type->title($context);
        $description = $type->body($context);
        $topic = $type->topic();
        $groupId = (string) Str::uuid();
        $notificationCode = 'NTF-'.strtoupper(substr($groupId, 0, 8));

        DB::transaction(function () use ($customer, $title, $description, $topic, $groupId, $notificationCode, $type) {
            $notification = new DatabaseNotification([
                'id' => (string) Str::uuid(),
                'type' => TestUserNotification::class,
                'notifiable_type' => Customer::class,
                'notifiable_id' => (string) $customer->id,
                'topic' => $topic,
                'target_type' => 'customer',
                'merchant_id' => null,
                'user_id' => null,
                'title' => $title,
                'description' => $description,
                'image' => null,
                'source' => 'system',
                'is_admin' => false,
                'notification_group_id' => $groupId,
                'notification_code' => $notificationCode,
                'data' => [
                    'title' => $title,
                    'body' => $description,
                    'sent_at' => now()->toIso8601String(),
                    'meta' => [
                        'topic' => $topic,
                        'target_type' => 'customer',
                        'customer_id' => (string) $customer->id,
                        'notification_group_id' => $groupId,
                        'notification_code' => $notificationCode,
                        'source' => 'system',
                        'is_admin' => false,
                        'event_type' => $type->value,
                        'image' => null,
                    ],
                ],
            ]);
            $notification->save();

            event(new CustomerNotificationEvent(
                (string) $customer->id,
                $title,
                $description,
                [
                    'topic' => $topic,
                    'target_type' => 'customer',
                    'notification_group_id' => $groupId,
                    'notification_code' => $notificationCode,
                    'source' => 'system',
                    'is_admin' => false,
                    'event_type' => $type->value,
                ],
            ));
        });
    }
}
