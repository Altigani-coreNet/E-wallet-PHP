<?php

namespace App\Modules\AdminKyc\Notifications;

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class AdminKycNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AdminKycNotificationType $type,
        public Customer $customer,
        public array $context = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(Admin $notifiable): array
    {
        return config('notifications.admin_kyc.channels', ['database', 'broadcast']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Admin $notifiable): array
    {
        $title = $this->type->title($this->context);
        $description = $this->type->body(array_merge($this->context, ['customer' => $this->customer]));
        $topic = $this->type->topic();
        $groupId = $this->context['notification_group_id'] ?? null;
        $notificationCode = $this->context['notification_code'] ?? null;

        return [
            'title' => $title,
            'body' => $description,
            'sent_at' => now()->toIso8601String(),
            'meta' => [
                'topic' => $topic,
                'target_type' => 'admin',
                'customer_id' => (string) $this->customer->id,
                'notification_group_id' => $groupId,
                'notification_code' => $notificationCode,
                'source' => 'system',
                'is_admin' => true,
                'event_type' => $this->type->value,
                'deep_link' => $this->type->deepLink(array_merge($this->context, ['customer' => $this->customer])),
                'image' => null,
            ],
        ];
    }

    public function toBroadcast(Admin $notifiable): BroadcastMessage
    {
        $payload = $this->toArray($notifiable);

        return new BroadcastMessage([
            'title' => $payload['title'],
            'message' => $payload['body'],
            'meta' => $payload['meta'],
            'sent_at' => $payload['sent_at'],
        ]);
    }
}
