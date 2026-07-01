<?php

namespace App\Http\Controllers\Api\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCustomerStatusRequest;
use App\Http\Requests\AdminCustomerStoreRequest;
use App\Http\Requests\AdminCustomerUpdateRequest;
use App\Http\Resources\AdminCustomerResource;
use App\Http\Resources\AdminWalletResource;
use App\Http\Resources\AdminWalletTransactionResource;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Modules\CustomerAuth\Services\CustomerPasswordSetupService;
use App\Services\Admin\AdminWalletService;
use App\Services\ChangeRequestFormatter;
use App\Services\ChangeRequestService;
use App\Services\CustomerService;
use App\Support\CsvExport;
use App\Traits\HasFiles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCustomerApiController extends Controller
{
    use HasFiles;

    public function __construct(
        private readonly CustomerService $customerService,
        private readonly CustomerPasswordSetupService $passwordSetupService,
        private readonly AdminWalletService $walletService,
        private readonly ChangeRequestService $changeRequestService,
        private readonly ChangeRequestFormatter $changeRequestFormatter,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = $this->buildFilteredQuery($request);
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

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        try {
            $query = $this->buildFilteredQuery($request);
            $fileName = 'customers_export_'.now()->format('Y_m_d_His').'.csv';

            return response()->streamDownload(function () use ($query) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, [
                    'ID',
                    'Name',
                    'Email',
                    'Phone',
                    'Merchant',
                    'Status',
                    'Balance',
                    'Address',
                    'Created At',
                ]);

                $query->orderByDesc('created_at')->chunk(500, function ($customers) use ($handle) {
                    foreach ($customers as $customer) {
                        fputcsv($handle, [
                            $customer->id,
                            $customer->name,
                            $customer->email,
                            CsvExport::asText($customer->phone),
                            optional($customer->merchant)->business_name ?: optional($customer->merchant)->name,
                            $customer->status,
                            $customer->wallet?->balance ?? 0,
                            $customer->address,
                            optional($customer->created_at)?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

                fclose($handle);
            }, $fileName, [
                'Content-Type' => 'text/csv',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@export error: '.$exception->getMessage());

            return $this->jsonError('Failed to export customers', 500);
        }
    }

    public function exportTemplate()
    {
        return $this->customerService->exportTemplate();
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'merchant_id' => 'required|exists:merchants,id',
        ]);

        try {
            $result = $this->customerService->importPreview(
                $request->file('import_file'),
                $request->input('merchant_id')
            );

            return response()->json([
                'success' => true,
                'status' => true,
                'data' => $result['data'] ?? [],
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@importPreview error: '.$exception->getMessage());

            return $this->jsonError('Preview failed: '.$exception->getMessage(), 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'merchant_id' => 'required|exists:merchants,id',
        ]);

        try {
            $result = $this->customerService->import(
                $request->file('import_file'),
                $request->input('merchant_id')
            );

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => $result['message'] ?? 'Customers imported successfully',
                'data' => [
                    'imported_count' => $result['imported_count'] ?? 0,
                    'skipped_count' => $result['skipped_count'] ?? 0,
                    'errors' => $result['errors'] ?? [],
                ],
                'imported_count' => $result['imported_count'] ?? 0,
                'skipped_count' => $result['skipped_count'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@import error: '.$exception->getMessage());

            return $this->jsonError('Import failed: '.$exception->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve());
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@show error: '.$exception->getMessage());

            return $this->jsonError('Customer not found', 404);
        }
    }

    public function wallet(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);

            if (! $customer->wallet) {
                return $this->jsonSuccess([
                    'wallet' => null,
                    'summary' => [
                        'transaction_count' => 0,
                        'total_credits' => 0,
                        'total_debits' => 0,
                    ],
                    'recent_transactions' => [],
                ]);
            }

            $wallet = $this->walletService->show($customer->wallet->id);
            $recentPaginated = $this->walletService->walletTransactions($customer->wallet->id, [], 5);

            return $this->jsonSuccess([
                'wallet' => AdminWalletResource::make($wallet)->resolve(),
                'summary' => $wallet->summary,
                'recent_transactions' => AdminWalletTransactionResource::collection($recentPaginated->items())->resolve(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@wallet error: '.$exception->getMessage());

            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->jsonError('Customer not found', 404);
            }

            return $this->jsonError('Failed to fetch customer wallet', 500);
        }
    }

    public function transactions(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $perPage = (int) $request->input('per_page', 15);

            if (! $customer->wallet) {
                return $this->jsonSuccess([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ]);
            }

            $filters = $this->transactionFilters($request);
            $paginated = $this->walletService->walletTransactions($customer->wallet->id, $filters, $perPage);

            return $this->jsonSuccess([
                'data' => AdminWalletTransactionResource::collection($paginated->items())->resolve(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@transactions error: '.$exception->getMessage());

            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->jsonError('Customer not found', 404);
            }

            return $this->jsonError('Failed to fetch customer transactions', 500);
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

            // Invite the customer to set their own password (email + SMS).
            $this->passwordSetupService->generateAndSend($customer);

            $customer->load(['merchant', 'country', 'city', 'wallet']);

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

    public function update(AdminCustomerUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $data = $request->validated();

            if ($request->hasFile('profile_image')) {
                $this->deleteProfileImageFile($customer->profile_image);
                $data['profile_image'] = $this->uploadImageAndGetFileName($request, 'profile_image', 'customer_profiles');
            }

            $customer = $this->customerService->update($customer, $data);
            $customer->load(['merchant', 'country', 'city', 'wallet']);

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

    public function destroy(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
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
            'ids.*' => 'uuid|exists:customers,id',
        ]);

        try {
            $deletedCount = $this->customerService->bulkDelete($validated['ids']);

            return $this->jsonSuccess(
                ['deleted_count' => $deletedCount],
                "{$deletedCount} customers deleted successfully"
            );
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@bulkDelete error: '.$exception->getMessage());

            return $this->jsonError('Failed to delete customers', 500);
        }
    }

    public function updateStatus(AdminCustomerStatusRequest $request, string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
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

    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $customer = $this->customerService->toggleStatus($customer);

            return $this->jsonSuccess(AdminCustomerResource::make($customer)->resolve(), 'Customer status updated successfully');
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@toggleStatus error: '.$exception->getMessage());

            return $this->jsonError('Failed to update customer status', 500);
        }
    }

    public function resendPasswordInvite(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);

            if (! $customer->email && ! $customer->phone) {
                return $this->jsonError('Customer has no email or phone to send the invite to', 422);
            }

            $this->passwordSetupService->generateAndSend($customer);

            return $this->jsonSuccess(null, 'Password setup invite sent successfully');
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@resendPasswordInvite error: '.$exception->getMessage());

            return $this->jsonError('Failed to send password setup invite', 500);
        }
    }

    public function approve(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $result = $this->customerService->approve($customer);

            return $this->jsonSuccess(
                AdminCustomerResource::make($result['customer'])->resolve(),
                $result['message'],
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@approve error: '.$exception->getMessage());

            return $this->jsonError('Failed to approve customer', 500);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
                'invalid_fields' => 'nullable|array',
                'missing_attachments' => 'nullable|array',
                'missing_attachments.*' => 'in:profile_image,passport_document',
            ]);

            $customer = $this->findCustomer($id);
            $result = $this->customerService->reject(
                $customer,
                $validated['rejection_reason'],
                $validated['invalid_fields'] ?? [],
                $validated['missing_attachments'] ?? [],
            );

            return $this->jsonSuccess(
                AdminCustomerResource::make($result['customer'])->resolve(),
                $result['message'],
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@reject error: '.$exception->getMessage());

            return $this->jsonError('Failed to reject customer', 500);
        }
    }

    public function logs(Request $request, string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $perPage = (int) $request->input('per_page', 15);
            $search = trim((string) $request->input('search', ''));

            $query = $customer->logs()->with('user')->latest();

            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder->where('action', 'like', '%'.$search.'%')
                        ->orWhere('metadata->message', 'like', '%'.$search.'%')
                        ->orWhere('metadata->event', 'like', '%'.$search.'%');
                });
            }

            $logs = $query->paginate($perPage);

            return $this->jsonSuccess([
                'data' => $logs->items(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@logs error: '.$exception->getMessage());

            return $this->jsonError('Failed to fetch customer logs', 500);
        }
    }

    public function changeRequests(string $id): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);

            $requests = ChangeRequest::query()
                ->where('changeable_type', Customer::class)
                ->where('changeable_id', $customer->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (ChangeRequest $changeRequest) => $this->changeRequestFormatter->formatSummary($changeRequest))
                ->values()
                ->all();

            return $this->jsonSuccess($requests);
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@changeRequests error: '.$exception->getMessage());

            return $this->jsonError('Failed to fetch change requests', 500);
        }
    }

    public function changeRequestDetail(string $id, string $changeRequest): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $changeRequestModel = $this->findCustomerChangeRequest($customer, $changeRequest);

            return $this->jsonSuccess(
                $this->changeRequestFormatter->formatDetail($changeRequestModel),
            );
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@changeRequestDetail error: '.$exception->getMessage());

            return $this->jsonError('Failed to fetch change request details', 500);
        }
    }

    public function approveChangeRequest(Request $request, string $id, string $changeRequest): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $changeRequestModel = $this->findCustomerChangeRequest($customer, $changeRequest);

            $this->changeRequestService->approve(
                $changeRequestModel,
                $request->user('admin-api'),
                $request->input('moderation_note'),
            );

            return $this->jsonSuccess(
                AdminCustomerResource::make($customer->fresh(['merchant', 'country', 'city', 'wallet']))->resolve(),
                'Change request approved successfully',
            );
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@approveChangeRequest error: '.$exception->getMessage());

            return $this->jsonError($exception->getMessage(), 400);
        }
    }

    public function rejectChangeRequest(Request $request, string $id, string $changeRequest): JsonResponse
    {
        try {
            $customer = $this->findCustomer($id);
            $changeRequestModel = $this->findCustomerChangeRequest($customer, $changeRequest);

            $this->changeRequestService->reject(
                $changeRequestModel,
                $request->user('admin-api'),
                $request->input('moderation_note'),
            );

            return $this->jsonSuccess(
                AdminCustomerResource::make($customer->fresh(['merchant', 'country', 'city', 'wallet']))->resolve(),
                'Change request rejected successfully',
            );
        } catch (\Throwable $exception) {
            Log::error('AdminCustomerApiController@rejectChangeRequest error: '.$exception->getMessage());

            return $this->jsonError($exception->getMessage(), 400);
        }
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = Customer::query()->with(['merchant', 'country', 'city', 'wallet'])->withCountry();

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
                        $merchantQuery->where('name', 'like', "%{$textSearch}%")
                            ->orWhere('business_name', 'like', "%{$textSearch}%");
                    });
            });
        }

        return $query;
    }

    private function findCustomer(string $id): Customer
    {
        return Customer::query()
            ->with([
                'merchant',
                'country',
                'city',
                'wallet',
                'attachments',
                'rejections' => fn ($query) => $query->latest()->limit(1),
            ])
            ->withCountry()
            ->whereKey($id)
            ->firstOrFail();
    }

    private function findCustomerChangeRequest(Customer $customer, string $changeRequestId): ChangeRequest
    {
        return ChangeRequest::query()
            ->where('changeable_type', Customer::class)
            ->where('changeable_id', $customer->id)
            ->whereKey($changeRequestId)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionFilters(Request $request): array
    {
        return [
            'search' => $request->input('search'),
            'direction' => $request->input('direction'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from', $request->input('start_date')),
            'date_to' => $request->input('date_to', $request->input('end_date')),
            'min_amount' => $request->input('min_amount'),
            'max_amount' => $request->input('max_amount'),
        ];
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
