<?php

namespace App\Modules\Communication\Notification\Controllers;

use App\Events\MerchantNotificationEvent;
use App\Events\PublicNotificationEvent;
use App\Events\UserNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Merchant;
use App\Models\User;
use App\Notifications\TestUserNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminNotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = DatabaseNotification::query()
            ->where('type', TestUserNotification::class)
            ->with('notifiable');

        $isAdminFilter = (string) $request->get('is_admin', '1');
        if ($isAdminFilter === '1') {
            $query->where(function ($q) {
                $q->where('is_admin', true)
                    ->orWhere('data->meta->source', 'admin_management')
                    ->orWhere('data->source', 'admin_management');
            });
        } elseif ($isAdminFilter === '0') {
            $query->where(function ($q) {
                $q->where('is_admin', false)
                    ->orWhere(function ($nested) {
                        $nested->whereNull('data->meta->is_admin')
                            ->whereNull('data->is_admin')
                            ->whereNull('data->meta->source')
                            ->whereNull('data->source');
                    });
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notification_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('topic')) {
            $query->where('topic', $request->string('topic'));
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type'));
        }

        $perPage = (int) $request->get('per_page', 15);
        $items = $query->latest()->paginate($perPage);

        $items->getCollection()->transform(function (DatabaseNotification $row) {
            $data = $row->data ?? [];
            $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
            $notifiable = $row->notifiable;
            $groupId = $meta['notification_group_id'] ?? ($data['notification_group_id'] ?? $row->id);
            $notificationCode = $meta['notification_code'] ?? ($data['notification_code'] ?? ('NTF-' . strtoupper(substr((string) $groupId, 0, 8))));

            return [
                'id' => $row->id,
                'code' => $notificationCode,
                'type' => $row->type,
                'is_admin' => (bool) ($row->is_admin ?? ($meta['is_admin'] ?? false)),
                'topic' => $row->topic ?? ($meta['topic'] ?? ($data['topic'] ?? null)),
                'target_type' => $row->target_type ?? ($meta['target_type'] ?? ($data['target_type'] ?? null)),
                'merchant_id' => $row->merchant_id ?? ($meta['merchant_id'] ?? ($data['merchant_id'] ?? null)),
                'title' => $row->title ?? ($data['title'] ?? null),
                'description' => $row->description ?? ($data['body'] ?? null),
                'image' => $row->image ?? ($meta['image'] ?? null),
                'read_at' => $row->read_at,
                'sent_at' => $data['sent_at'] ?? $row->created_at?->toIso8601String(),
                'created_at' => $row->created_at,
                'notifiable' => $notifiable ? [
                    'id' => $notifiable->id ?? null,
                    'name' => $notifiable->name ?? null,
                    'email' => $notifiable->email ?? null,
                ] : null,
            ];
        });

        return $this->SuccessMessage($items);
    }

    public function show(string $id): JsonResponse
    {
        $item = DatabaseNotification::query()
            ->where('type', TestUserNotification::class)
            ->where('is_admin', true)
            ->with('notifiable')
            ->findOrFail($id);

        $data = $item->data ?? [];
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $groupId = $meta['notification_group_id'] ?? ($data['notification_group_id'] ?? $item->id);

        return $this->SuccessMessage([
            'id' => $item->id,
            'code' => $meta['notification_code'] ?? ($data['notification_code'] ?? ('NTF-' . strtoupper(substr((string) $groupId, 0, 8)))),
            'topic' => $item->topic ?? ($meta['topic'] ?? ($data['topic'] ?? null)),
            'target_type' => $item->target_type ?? ($meta['target_type'] ?? ($data['target_type'] ?? null)),
            'merchant_id' => $item->merchant_id ?? ($meta['merchant_id'] ?? ($data['merchant_id'] ?? null)),
            'user_id' => $item->user_id ?? $item->notifiable_id,
            'title' => $item->title ?? ($data['title'] ?? null),
            'description' => $item->description ?? ($data['body'] ?? null),
            'image' => $item->image ?? ($meta['image'] ?? null),
            'read_at' => $item->read_at,
            'created_at' => $item->created_at,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $imagePath = $this->storeImageIfAny($request);
        $imageUrl = $imagePath ? (function_exists('coreservice_asset') ? coreservice_asset($imagePath) : asset($imagePath)) : null;
        $groupId = (string) Str::uuid();

        $result = DB::transaction(function () use ($data, $imageUrl, $groupId) {
            $recipients = $this->resolveRecipients($data);
            $notificationCode = 'NTF-' . strtoupper(substr($groupId, 0, 8));

            foreach ($recipients as $recipient) {
                $notification = new DatabaseNotification([
                    'id' => (string) Str::uuid(),
                    'type' => TestUserNotification::class,
                    'notifiable_type' => get_class($recipient),
                    'notifiable_id' => (string) $recipient->id,
                    'topic' => $data['topic'],
                    'target_type' => $data['target_type'],
                    'merchant_id' => $data['merchant_id'] ?? null,
                    'user_id' => $data['target_type'] === 'user' ? ($data['user_id'] ?? null) : null,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'image' => $imageUrl,
                    'source' => 'admin_management',
                    'is_admin' => true,
                    'notification_group_id' => $groupId,
                    'notification_code' => $notificationCode,
                    'data' => [
                        'title' => $data['title'],
                        'body' => $data['description'],
                        'sent_at' => now()->toIso8601String(),
                        'meta' => [
                            'topic' => $data['topic'],
                            'target_type' => $data['target_type'],
                            'merchant_id' => $data['merchant_id'] ?? null,
                            'notification_group_id' => $groupId,
                            'notification_code' => $notificationCode,
                            'source' => 'admin_management',
                            'is_admin' => true,
                            'image' => $imageUrl,
                        ],
                    ],
                ]);
                $notification->save();
            }

            $this->dispatchRealtime($data, [
                'topic' => $data['topic'],
                'target_type' => $data['target_type'],
                'merchant_id' => $data['merchant_id'] ?? null,
                'notification_group_id' => $groupId,
                'notification_code' => $notificationCode,
                'source' => 'admin_management',
                'is_admin' => true,
                'image' => $imageUrl,
                'users_count' => $recipients->count(),
            ]);

            return [
                'group_id' => $groupId,
                'code' => $notificationCode,
                'target_type' => $data['target_type'],
                'users_notified' => $recipients->count(),
            ];
        });

        return $this->SuccessMessage($result, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $item = DatabaseNotification::query()
            ->where('type', TestUserNotification::class)
            ->where('is_admin', true)
            ->findOrFail($id);

        $data = $this->validatePayload($request);
        $current = $item->data ?? [];
        $currentMeta = is_array($current['meta'] ?? null) ? $current['meta'] : [];
        $currentMeta['is_admin'] = true;

        if ($request->hasFile('image')) {
            $imagePath = $this->storeImageIfAny($request);
            $currentMeta['image'] = function_exists('coreservice_asset') ? coreservice_asset($imagePath) : asset($imagePath);
        }

        $newData = [
            'title' => $data['title'],
            'body' => $data['description'],
            'sent_at' => now()->toIso8601String(),
            'topic' => $data['topic'],
            'target_type' => $data['target_type'],
            'merchant_id' => $data['merchant_id'] ?? null,
            'meta' => $currentMeta,
            'source' => 'admin_management',
            'is_admin' => true,
            'notification_group_id' => $currentMeta['notification_group_id'] ?? ($current['notification_group_id'] ?? null),
            'notification_code' => $currentMeta['notification_code']
                ?? ($current['notification_code']
                    ?? ('NTF-' . strtoupper(substr((string) (($currentMeta['notification_group_id'] ?? ($current['notification_group_id'] ?? $item->id))), 0, 8)))),
        ];

        $item->topic = $data['topic'];
        $item->target_type = $data['target_type'];
        $item->merchant_id = $data['merchant_id'] ?? null;
        $item->user_id = $data['target_type'] === 'user' ? ($data['user_id'] ?? null) : null;
        $item->title = $data['title'];
        $item->description = $data['description'];
        $item->image = $newData['meta']['image'] ?? null;
        $item->source = 'admin_management';
        $item->is_admin = true;
        $item->notification_group_id = $newData['notification_group_id'] ?? $item->notification_group_id;
        $item->notification_code = $newData['notification_code'] ?? $item->notification_code;
        $item->data = $newData;
        $item->save();

        $this->dispatchRealtime($data, [
            'topic' => $data['topic'],
            'target_type' => $data['target_type'],
            'merchant_id' => $data['merchant_id'] ?? null,
            'notification_group_id' => $newData['notification_group_id'],
            'source' => 'admin_management',
            'image' => $newData['meta']['image'] ?? null,
            'users_count' => 1,
        ]);

        return $this->SuccessMessage([
            'id' => $item->id,
            'code' => $newData['notification_code'],
            'data' => $item->data,
        ]);
    }

    public function resend(string $id): JsonResponse
    {
        $item = DatabaseNotification::query()
            ->where('type', TestUserNotification::class)
            ->where('is_admin', true)
            ->with('notifiable')
            ->findOrFail($id);

        $data = $item->data ?? [];
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $targetType = $item->target_type ?? ($meta['target_type'] ?? ($data['target_type'] ?? null));

        if (!$targetType || !in_array($targetType, ['public', 'merchant', 'user'], true)) {
            return $this->ErrorMessage('Invalid notification target type', 422);
        }

        $payload = [
            'topic' => $item->topic ?? ($meta['topic'] ?? ($data['topic'] ?? 'alert')),
            'target_type' => $item->target_type ?? $targetType,
            'merchant_id' => $item->merchant_id ?? ($meta['merchant_id'] ?? ($data['merchant_id'] ?? null)),
            'user_id' => $targetType === 'user' ? (string) $item->notifiable_id : null,
            'title' => $item->title ?? ($data['title'] ?? ''),
            'description' => $item->description ?? ($data['body'] ?? ''),
        ];

        $this->dispatchRealtime($payload, [
            'topic' => $payload['topic'],
            'target_type' => $payload['target_type'],
            'merchant_id' => $payload['merchant_id'],
            'notification_group_id' => $meta['notification_group_id'] ?? ($data['notification_group_id'] ?? null),
            'notification_code' => $meta['notification_code']
                ?? ($data['notification_code']
                    ?? ('NTF-' . strtoupper(substr((string) (($meta['notification_group_id'] ?? ($data['notification_group_id'] ?? $item->id))), 0, 8)))),
            'source' => 'admin_management',
            'image' => $meta['image'] ?? null,
            'resend' => true,
        ]);

        return $this->SuccessMessage([
            'id' => $item->id,
            'code' => $meta['notification_code']
                ?? ($data['notification_code']
                    ?? ('NTF-' . strtoupper(substr((string) (($meta['notification_group_id'] ?? ($data['notification_group_id'] ?? $item->id))), 0, 8)))),
            'resent' => true,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $item = DatabaseNotification::query()->findOrFail($id);
        $item->delete();

        return $this->SuccessMessage(['message' => 'Notification deleted successfully']);
    }

    public function merchantsSelect(Request $request): JsonResponse
    {
        $query = Merchant::query()
            ->select('id', 'business_name', 'name', 'email')
            ->where('status', 'approved');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $items = $query->limit(100)->get()->map(function (Merchant $merchant) {
            return [
                'id' => $merchant->id,
                'text' => $merchant->business_name ?: $merchant->name,
                'business_name' => $merchant->business_name,
                'name' => $merchant->name,
                'email' => $merchant->email,
            ];
        });

        return $this->SuccessMessage($items);
    }

    public function usersByMerchant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id' => ['required', 'exists:merchants,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = User::query()
            ->select('id', 'name', 'email', 'merchant_id')
            ->where('merchant_id', $validated['merchant_id']);

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->limit(100)->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'text' => "{$user->name} ({$user->email})",
                'name' => $user->name,
                'email' => $user->email,
            ];
        });

        return $this->SuccessMessage($users);
    }

    protected function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'in:payments,service_updates,logs,alert'],
            'target_type' => ['required', 'string', 'in:public,merchant,user'],
            'merchant_id' => ['nullable', 'exists:merchants,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($data['target_type'] === 'merchant' && empty($data['merchant_id'])) {
            abort(response()->json([
                'status' => false,
                'message' => 'merchant_id is required for merchant target',
            ], 422));
        }

        if ($data['target_type'] === 'user') {
            if (empty($data['merchant_id'])) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'merchant_id is required for user target',
                ], 422));
            }
            if (empty($data['user_id'])) {
                abort(response()->json([
                    'status' => false,
                    'message' => 'user_id is required for user target',
                ], 422));
            }
        }

        if ($data['target_type'] === 'public') {
            $data['merchant_id'] = null;
            $data['user_id'] = null;
        }

        if ($data['target_type'] === 'merchant') {
            $data['user_id'] = null;
        }

        return $data;
    }

    protected function storeImageIfAny(Request $request): ?string
    {
        if (!$request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $dir = 'uploads/notifications';
        $fullDir = public_path($dir);
        if (!File::isDirectory($fullDir)) {
            File::makeDirectory($fullDir, 0755, true);
        }
        $name = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($fullDir, $name);

        return "{$dir}/{$name}";
    }

    protected function resolveRecipients(array $payload)
    {
        if ($payload['target_type'] === 'public') {
            return Admin::query()->orderBy('created_at')->limit(1)->get();
        }

        if ($payload['target_type'] === 'merchant') {
            return Merchant::query()->where('id', $payload['merchant_id'])->get();
        }

        return User::query()->where('id', $payload['user_id'])->get();
    }

    protected function dispatchRealtime(array $payload, array $meta): void
    {
        if ($payload['target_type'] === 'public') {
            event(new PublicNotificationEvent($payload['title'], $payload['description'], $meta));

            return;
        }

        if ($payload['target_type'] === 'merchant') {
            event(new MerchantNotificationEvent(
                (string) $payload['merchant_id'],
                $payload['title'],
                $payload['description'],
                $meta,
            ));

            return;
        }

        event(new UserNotificationEvent(
            (string) $payload['user_id'],
            $payload['title'],
            $payload['description'],
            $meta,
        ));
    }
}
