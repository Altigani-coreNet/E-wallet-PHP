<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCustomerResource;
use App\Models\Customer;
use App\Models\Log;
use App\Modules\AdminKyc\Services\AdminKycQueueService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as Logger;

class AdminCustomerKycController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AdminKycQueueService $queueService,
    ) {
    }

    public function pending(Request $request): JsonResponse
    {
        try {
            $query = Customer::query()
                ->with(['merchant', 'country', 'city', 'wallet'])
                ->withCountry()
                ->where('status', Customer::STATUS_PENDING);

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('created_at', [
                    $request->date('date_from')->startOfDay(),
                    $request->date('date_to')->endOfDay(),
                ]);
            } elseif ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date('date_from')->startOfDay());
            } elseif ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date('date_to')->endOfDay());
            }

            $textSearch = is_array($request->search)
                ? ($request->search['value'] ?? null)
                : $request->get('search');

            if (! empty($textSearch)) {
                $query->where(function ($q) use ($textSearch) {
                    $q->where('name', 'like', "%{$textSearch}%")
                        ->orWhere('email', 'like', "%{$textSearch}%")
                        ->orWhere('phone', 'like', "%{$textSearch}%");
                });
            }

            $perPage = max(1, min((int) $request->input('per_page', 15), 100));
            $customers = $query->orderByDesc('created_at')->paginate($perPage);

            return $this->SuccessMessage([
                'data' => AdminCustomerResource::collection($customers->items())->resolve(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ]);
        } catch (\Throwable $exception) {
            Logger::error('AdminCustomerKycController@pending: '.$exception->getMessage());

            return $this->ErrorMessage('Failed to fetch pending customers', null, 500);
        }
    }

    public function events(Request $request): JsonResponse
    {
        try {
            $scopedCustomerIds = Customer::query()->withCountry()->select('id');
            $perPage = max(1, min((int) $request->input('per_page', 15), 100));
            $search = trim((string) $request->input('search', ''));

            $query = Log::query()
                ->where('loggable_type', Customer::class)
                ->whereIn('loggable_id', $scopedCustomerIds)
                ->with(['loggable', 'user'])
                ->latest();

            if ($action = $request->input('action')) {
                $query->where('action', $action);
            }

            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder->where('action', 'like', '%'.$search.'%')
                        ->orWhere('metadata->message', 'like', '%'.$search.'%')
                        ->orWhereHasMorph('loggable', [Customer::class], function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        });
                });
            }

            $logs = $query->paginate($perPage);

            $items = collect($logs->items())->map(function (Log $log) {
                /** @var Customer|null $customer */
                $customer = $log->loggable;

                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'label' => $log->label,
                    'text' => $log->text,
                    'message' => $log->message,
                    'time' => $log->time,
                    'created_at' => $log->created_at?->toIso8601String(),
                    'customer' => $customer ? [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'status' => $customer->status,
                    ] : null,
                ];
            })->values()->all();

            return $this->SuccessMessage([
                'data' => $items,
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ]);
        } catch (\Throwable $exception) {
            Logger::error('AdminCustomerKycController@events: '.$exception->getMessage());

            return $this->ErrorMessage('Failed to fetch customer events', null, 500);
        }
    }

    public function eventShow(string $logId): JsonResponse
    {
        try {
            $scopedCustomerIds = Customer::query()->withCountry()->pluck('id');

            $log = Log::query()
                ->where('loggable_type', Customer::class)
                ->whereIn('loggable_id', $scopedCustomerIds)
                ->with(['loggable', 'user'])
                ->whereKey($logId)
                ->firstOrFail();

            /** @var Customer|null $customer */
            $customer = $log->loggable;

            return $this->SuccessMessage([
                'id' => $log->id,
                'action' => $log->action,
                'label' => $log->label,
                'text' => $log->text,
                'message' => $log->message,
                'time' => $log->time,
                'metadata' => $log->metadata,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'created_at' => $log->created_at?->toIso8601String(),
                'customer' => $customer ? AdminCustomerResource::make(
                    $customer->loadMissing(['merchant', 'country', 'city', 'wallet'])
                )->resolve() : null,
                'performer' => $log->user ? [
                    'id' => $log->user_id,
                    'type' => $log->user_type,
                    'name' => $log->user->name ?? $log->user->email ?? null,
                ] : null,
            ]);
        } catch (\Throwable $exception) {
            Logger::error('AdminCustomerKycController@eventShow: '.$exception->getMessage());

            return $this->ErrorMessage('Failed to fetch event details', null, 404);
        }
    }

    public function queueCounts(): JsonResponse
    {
        try {
            return $this->SuccessMessage($this->queueService->counts());
        } catch (\Throwable $exception) {
            Logger::error('AdminCustomerKycController@queueCounts: '.$exception->getMessage());

            return $this->ErrorMessage('Failed to fetch KYC queue counts', null, 500);
        }
    }
}
