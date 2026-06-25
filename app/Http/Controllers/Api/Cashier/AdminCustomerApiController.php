<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCustomerStatusRequest;
use App\Http\Requests\AdminCustomerStoreRequest;
use App\Http\Requests\AdminCustomerUpdateRequest;
use App\Http\Resources\AdminCustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Traits\HasFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminCustomerApiController extends Controller
{
    use HasFiles;

    public function __construct(private readonly CustomerService $customerService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Customer::query()->with(['merchant', 'country', 'city'])->withCountry();

            if ($request->filled('merchant_id')) {
                $query->where('merchant_id', $request->merchant_id);
            }

            if ($request->filled('country_id')) {
                $query->where('country_id', $request->country_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

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
                        ->orWhere('phone', 'like', "%{$textSearch}%")
                        ->orWhereHas('merchant', function ($merchantQuery) use ($textSearch) {
                            $merchantQuery->where('name', 'like', "%{$textSearch}%");
                        });
                });
            }

            $perPage = (int) $request->input('per_page', 15);
            $customers = $query->orderByDesc('created_at')->paginate($perPage);

            return $this->jsonSuccess([
                'data' => AdminCustomerResource::collection($customers->items())->resolve(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@index error: '.$exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->jsonError('Failed to fetch customers', 500);
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            $customer = $this->findCustomer($uuid);

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve());
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@show error: '.$exception->getMessage());

            return $this->jsonError('Customer not found', 404);
        }
    }

    public function store(AdminCustomerStoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('profile_image')) {
                $data['profile_image'] = $this->uploadImageAndGetFileName($request, 'profile_image', 'customer_profiles');
            }

            $customer = $this->customerService->create($data);
            $customer->load(['merchant', 'country', 'city']);

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve(), 'Customer created successfully', 201);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@store error: '.$exception->getMessage());

            return $this->jsonError('Failed to create customer', 500);
        }
    }

    public function update(AdminCustomerUpdateRequest $request, string $uuid): JsonResponse
    {
        try {
            $customer = $this->findCustomer($uuid);
            $data = $request->validated();

            if ($request->hasFile('profile_image')) {
                $this->deleteProfileImageFile($customer->profile_image);
                $data['profile_image'] = $this->uploadImageAndGetFileName($request, 'profile_image', 'customer_profiles');
            }

            $customer = $this->customerService->update($customer, $data);
            $customer->load(['merchant', 'country', 'city']);

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve(), 'Customer updated successfully');
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@update error: '.$exception->getMessage());

            return $this->jsonError('Failed to update customer', 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $customer = $this->findCustomer($uuid);
            $this->customerService->delete($customer);

            return $this->jsonSuccess(null, 'Customer deleted successfully');
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@destroy error: '.$exception->getMessage());

            return $this->jsonError('Failed to delete customer', 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid|exists:customers,uuid',
        ]);

        try {
            $deletedCount = $this->customerService->bulkDeleteByUuid($validated['ids']);

            return $this->jsonSuccess(
                ['deleted_count' => $deletedCount],
                "{$deletedCount} customers deleted successfully"
            );
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@bulkDelete error: '.$exception->getMessage());

            return $this->jsonError('Failed to delete customers', 500);
        }
    }

    public function updateStatus(AdminCustomerStatusRequest $request, string $uuid): JsonResponse
    {
        try {
            $customer = $this->findCustomer($uuid);
            $customer = $this->customerService->updateStatus($customer, $request->validated('status'));

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve(), 'Customer status updated successfully');
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@updateStatus error: '.$exception->getMessage());

            return $this->jsonError('Failed to update customer status', 500);
        }
    }

    public function toggleStatus(string $uuid): JsonResponse
    {
        try {
            $customer = $this->findCustomer($uuid);
            $customer = $this->customerService->toggleStatus($customer);

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve(), 'Customer status updated successfully');
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@toggleStatus error: '.$exception->getMessage());

            return $this->jsonError('Failed to update customer status', 500);
        }
    }

    private function findCustomer(string $uuid): Customer
    {
        return Customer::query()
            ->with(['merchant', 'country', 'city'])
            ->withCountry()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function deleteProfileImageFile(?string $profileImage): void
    {
        if (! $profileImage) {
            return;
        }

        $oldImagePath = public_path($profileImage);
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    }

    private function jsonSuccess(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'status' => true,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    private function jsonError(string $message, int $status = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
