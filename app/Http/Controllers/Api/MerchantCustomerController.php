<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Repositories\CustomerRepository;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Traits\Select2Trait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantCustomerController extends Controller
{
    use Select2Trait;

    public function __construct(private readonly CustomerService $customerService, private readonly CustomerRepository $customerRepository)
    {
    }

    /**
     * Return customers for select2 dropdown (filtered by merchant)
     */
    public function select(Request $request): JsonResponse
    {
        $user = Auth::user();
        $merchantId = $user?->merchant_id;
        $query = $this->customerRepository->queryByMerchant($merchantId);
        return $this->getSelect2DataInNormalSearch($request, $query, ['name', 'email']);
    }

    /**
     * Get customers list for the authenticated merchant (JSON)
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $merchantId = $user?->merchant_id;
        if (!$merchantId) {
            return response()->json(['message' => 'Merchant not found'], 403);
        }

        $perPage = (int) $request->get('per_page', 15);
        $search = $request->get('search');

        $customers = $this->customerService->listForMerchant($merchantId, $perPage, $search);

        return (new \App\Http\Resources\CustomerCollection($customers))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Display the specified customer details for the authenticated merchant (JSON)
     */
    public function show(Customer $customer): JsonResponse
    {
        $user = Auth::user();
        $merchantId = $user?->merchant_id;
        try {
            $data = $this->customerService->showForMerchant($merchantId, $customer->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        // Wrap customer object inside resource, keep last_payment_link raw
        return response()->json([
            'data' => [
                'customer' => new \App\Http\Resources\CustomerResource($data['customer']),
                'last_payment_link' => $data['last_payment_link'],
            ],
        ]);
    }

    /**
     * Store a newly created customer in storage (JSON)
     */
    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $merchantId = $user?->merchant_id;

        try {
            $customer = $this->customerService->createForMerchant($merchantId, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'data' => null,
            ], 422);
        }

        return (new \App\Http\Resources\CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified customer in storage (JSON)
     */
    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $user = Auth::user();
        $merchantId = $user?->merchant_id;

        $validated = $request->validated();

        try {
            $customer = $this->customerService->updateForMerchant($merchantId, $customer, $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'data' => null,
            ], 422);
        }

        return (new \App\Http\Resources\CustomerResource($customer))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified customer from storage (JSON)
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $user = Auth::user();
        $merchantId = $user?->merchant_id;
        try {
            $this->customerService->deleteForMerchant($merchantId, $customer);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
        ]);
    }
}


