<?php

namespace App\Modules\CustomerAuth\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Merchant;
use App\Notifications\TestUserNotification;
use App\Support\SuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class CustomerNotificationController
{
    public function index(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $perPage = (int) $request->get('per_page', 6);

        $notifications = $this->accessibleNotificationsQuery($customer)
            ->latest()
            ->paginate($perPage);

        return SuccessResponse::make([
            'data' => NotificationResource::collection($notifications->getCollection()),
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
        ]);
    }

    public function unreadCount(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $count = $this->accessibleNotificationsQuery($customer)
            ->whereNull('read_at')
            ->count();

        return SuccessResponse::make($count);
    }

    public function markAllAsRead(Request $request)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $query = $this->accessibleNotificationsQuery($customer)
            ->whereNull('read_at');

        $firstAdminId = Admin::query()->orderBy('created_at')->value('id');
        if ($firstAdminId) {
            $query->whereNot(function ($q) use ($firstAdminId) {
                $q->where('notifiable_type', Admin::class)
                    ->where('notifiable_id', $firstAdminId)
                    ->where('target_type', 'public');
            });
        }

        $updated = $query->update(['read_at' => now()]);

        return SuccessResponse::make([
            'message' => 'Notifications marked as read',
            'marked_count' => $updated,
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $notification = $this->accessibleNotificationsQuery($customer)->find($id);

        if (! $notification) {
            return SuccessResponse::error('Notification not found', 404);
        }

        $notification->markAsRead();

        return SuccessResponse::make(['message' => 'Notification marked as read']);
    }

    public function destroy(Request $request, string $id)
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        $notification = $this->accessibleNotificationsQuery($customer)->find($id);

        if (! $notification) {
            return SuccessResponse::error('Notification not found', 404);
        }

        $notification->delete();

        return SuccessResponse::make(['message' => 'Notification deleted successfully']);
    }

    protected function accessibleNotificationsQuery(Customer $customer)
    {
        $merchantId = $customer->merchant_id;
        $firstAdminId = Admin::query()->orderBy('created_at')->value('id');
        $customerCreatedAt = $customer->created_at;

        $query = DatabaseNotification::query()
            ->where('type', TestUserNotification::class)
            ->where(function ($q) {
                $q->where('source', 'admin_management')
                    ->orWhere('data->meta->source', 'admin_management')
                    ->orWhere('data->source', 'admin_management');
            })
            ->where(function ($q) use ($customer, $merchantId, $firstAdminId) {
                $q->where(function ($sub) use ($customer) {
                    $sub->where('notifiable_type', Customer::class)
                        ->where('notifiable_id', $customer->id);
                });

                if (! empty($merchantId)) {
                    $q->orWhere(function ($sub) use ($merchantId) {
                        $sub->where('target_type', 'merchant')
                            ->where('merchant_id', (string) $merchantId);
                    });

                    $q->orWhere(function ($sub) use ($merchantId) {
                        $sub->where('notifiable_type', Merchant::class)
                            ->where('notifiable_id', $merchantId);
                    });
                }

                if (! empty($firstAdminId)) {
                    $q->orWhere(function ($sub) use ($firstAdminId) {
                        $sub->where('notifiable_type', Admin::class)
                            ->where('notifiable_id', $firstAdminId)
                            ->where('target_type', 'public');
                    });
                }
            });

        if (! empty($customerCreatedAt)) {
            $query->where('created_at', '>=', $customerCreatedAt);
        }

        return $query;
    }
}
