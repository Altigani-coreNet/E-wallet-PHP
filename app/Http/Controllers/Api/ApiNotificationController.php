<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Admin;
use App\Models\Merchant;
use App\Models\User;
use App\Notifications\TestUserNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class ApiNotificationController extends Controller
{
    use ApiResponse;

    public function getNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 6);

        $query = $this->accessibleNotificationsQuery($user);
        $notifications = $query->latest()->paginate($perPage);

        return $this->SuccessMessage([
            'data' => NotificationResource::collection($notifications->getCollection()),
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $this->accessibleNotificationsQuery($user)
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

        return $this->SuccessMessage([
            'message' => 'Notifications marked as read',
            'marked_count' => $updated,
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->accessibleNotificationsQuery($request->user())->find($id);

        if (!$notification) {
            return $this->ErrorMessage('Notification not found', null, 404);
        }

        $notification->markAsRead();

        return $this->SuccessMessage(['message' => 'Notification marked as read']);
    }

    public function deleteNotification(Request $request, string $id): JsonResponse
    {
        $notification = $this->accessibleNotificationsQuery($request->user())->find($id);

        if (!$notification) {
            return $this->ErrorMessage('Notification not found', null, 404);
        }

        $notification->delete();

        return $this->SuccessMessage(['message' => 'Notification deleted successfully']);
    }

    public function getUnreadNotificationCount(Request $request): JsonResponse
    {
        try {
            $count = $this->accessibleNotificationsQuery($request->user())
                ->whereNull('read_at')
                ->count();

            return $this->SuccessMessage($count);
        } catch (\Throwable $exception) {
            return $this->ErrorMessage($exception->getMessage());
        }
    }

    protected function accessibleNotificationsQuery(User $user)
    {
        $merchantId = $user->merchant_id;
        $firstAdminId = Admin::query()->orderBy('created_at')->value('id');
        $userCreatedAt = $user->created_at;

        $query = DatabaseNotification::query()
            ->where('type', TestUserNotification::class)
            ->where(function ($q) {
                $q->where('source', 'admin_management')
                    ->orWhere('data->meta->source', 'admin_management')
                    ->orWhere('data->source', 'admin_management');
            })
            ->where(function ($q) use ($user, $merchantId, $firstAdminId) {
                // User targeted notifications
                $q->where(function ($sub) use ($user) {
                    $sub->where('notifiable_type', User::class)
                        ->where('notifiable_id', $user->id);
                });

                // Merchant targeted notifications
                if (!empty($merchantId)) {
                    $q->orWhere(function ($sub) use ($merchantId) {
                        $sub->where('target_type', 'merchant')
                            ->where('merchant_id', (string) $merchantId);
                    });

                    // Optional direct merchant notifiable mapping safety
                    $q->orWhere(function ($sub) use ($merchantId) {
                        $sub->where('notifiable_type', Merchant::class)
                            ->where('notifiable_id', $merchantId);
                    });
                }

                // Public notifications (stored on first admin)
                if (!empty($firstAdminId)) {
                    $q->orWhere(function ($sub) use ($firstAdminId) {
                        $sub->where('notifiable_type', Admin::class)
                            ->where('notifiable_id', $firstAdminId)
                            ->where('target_type', 'public');
                    });
                }
            });

        if (!empty($userCreatedAt)) {
            $query->where('created_at', '>=', $userCreatedAt);
        }

        return $query;
    }
}

