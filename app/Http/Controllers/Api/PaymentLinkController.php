<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\HandlesStripePaymentLinkWebhooks;
use App\Models\PaymentByLink;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\PaymentByLinkService;
use App\Http\Resources\PaymentLinkResource;
use App\Support\StripePaymentLinkCurrency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Mail\PaymentMail;
use App\Traits\ApiResponse;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;

class PaymentLinkController extends Controller
{
    use ApiResponse;
    use HandlesStripePaymentLinkWebhooks;
    protected $paymentByLinkService;
    protected $transactionService;

    public function __construct(
        PaymentByLinkService $paymentByLinkService,
        TransactionService $transactionService
    )
    {
        $this->paymentByLinkService = $paymentByLinkService;
        $this->transactionService = $transactionService;
    }

    /**
     * @OA\Get(
     *     path="/api/payment-links",
     *     summary="Get all payment links for authenticated merchant",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment links retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PaymentLinkResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function index(Request $request)
    {
        $merchant = Auth::user()->merchant;

        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $perPage = $request->get('per_page', 15);
        $query = PaymentByLink::where('merchant_id', $merchant->id)
            ->with(['merchant', 'customer'])
            ->latest();
            

            if ($request->has('search') && !is_null($request->search)) {
                $query->where(function ($q) use ($request) {
                    // $q->where('short_uuid', 'like', "%$request->search%");
                    $q->orWhere('amount', 'like', "%$request->search%");
                    $q->orWhereHas('customer', function ($q) use ($request) {
                        $q->where('name', 'like', "%$request->search%");
                    });
                });
            }

            if ($request->has('customer_id') && !is_null($request->country_id)) {
                $query->where('customer_id', $request->country_id);
            }
    
            if ($request->has('from_date') && $request->from_date && !is_null($request->from_date)) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
    
            if ($request->has('to_date') && $request->to_date && !is_null($request->to_date)) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

           $paymentLinks = $query->paginate($perPage);
        $data =  [
            'data' => PaymentLinkResource::collection($paymentLinks),
            'pagination' => [
                'current_page' => $paymentLinks->currentPage(),
                'last_page' => $paymentLinks->lastPage(),
                'per_page' => $paymentLinks->perPage(),
                'total' => $paymentLinks->total(),
            ]
        ];
        return $this->SuccessMessage($data);
    }

    /**
     * @OA\Get(
     *     path="/api/payment-links/statistics",
     *     summary="Get payment links statistics for authenticated merchant",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="active", type="integer"),
     *             @OA\Property(property="inactive", type="integer"),
     *             @OA\Property(property="expired", type="integer"),
     *             @OA\Property(property="completed", type="integer"),
     *             @OA\Property(property="canceled", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function statistics()
    {
        $merchant = Auth::user()->merchant;

        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $stats = $this->getStatistics($merchant->id);
        return response()->json($stats);
    }

    /**
     * Public fetch by UUID (used for frontend redirect)
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByUuid($uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);
            $link->loadMissing('merchant');

            return response()->json([
                'success' => true,
                'data' => new PaymentLinkResource($link)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment link not found'
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment-links",
     *     summary="Create a new payment link",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount","currency","customer_id"},
     *             @OA\Property(property="amount", type="number", format="float", minimum=0.01, description="Payment amount"),
     *             @OA\Property(property="currency", type="string", enum={"USD","AED","SUD","EUR"}, description="Currency code"),
     *             @OA\Property(property="payment_method_types", type="array", @OA\Items(type="string", enum={"card","afterpay_clearpay","alipay","bancontact","eps","giropay","grabpay","ideal","klarna","oxxo","p24","sepa_debit","sofort","us_bank_account","wechat_pay"}), description="Allowed payment methods"),
     *             @OA\Property(property="scheduled_date", type="string", format="date-time", description="Scheduled payment date"),
     *             @OA\Property(property="expired_date", type="string", format="date-time", description="Payment link expiration date"),
     *             @OA\Property(property="customer_id", type="integer", description="Customer ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment link created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function store(Request $request)
    {
        $merchant = Auth::user()->merchant;

        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $request->merge([
            'merchant_id' => $merchant->id,
            'status' => 'active',
            'payment_method_types' => $request->payment_method_types ?? ['card'],
        ]);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'payment_method_types' => 'nullable|array',
            'payment_method_types.*' => 'string|in:card,afterpay_clearpay,alipay,bancontact,eps,giropay,grabpay,ideal,klarna,oxxo,p24,sepa_debit,sofort,us_bank_account,wechat_pay',
            'scheduled_date' => 'nullable|date|after:now',
            'expired_date' => 'nullable|date|after:now',
            'customer_id' => 'required|exists:customers,id',
        ]);

        // Verify customer belongs to this merchant if provided
        if ($request->filled('customer_id')) {
            $customer = Customer::where('id', $request->customer_id)
                ->where('merchant_id', $merchant->id)
                ->first();

            if (!$customer) {
                return response()->json(['error' => 'Invalid customer selected'], 400);
            }
        }

        $validated['merchant_id'] = $merchant->id;
        $validated['short_uuid'] = Str::random(8);
        $validated['status'] = 'active';
        $validated['payment_method_types'] = $request->payment_method_types ?? ['card'];

        try {
            $paymentLink = $this->paymentByLinkService->store($request);
            return response()->json([
                'message' => 'Payment link created successfully',
                'data' => new PaymentLinkResource($paymentLink)
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment-links/pos",
     *     summary="Create a payment link for POS sale",
     *     description="Create a payment link from POS system with customer info and sale metadata",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "currency_id", "customer_name", "customer_email", "customer_phone", "merchant_id"},
     *             @OA\Property(property="amount", type="number", format="float", minimum=0.01),
     *             @OA\Property(property="currency_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_name", type="string"),
     *             @OA\Property(property="customer_email", type="string", format="email"),
     *             @OA\Property(property="customer_phone", type="string"),
     *             @OA\Property(property="merchant_id", type="string", format="uuid"),
     *             @OA\Property(property="customer_id", type="integer", nullable=true),
     *             @OA\Property(property="payment_method_types", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment link created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Merchant not found")
     * )
     */
    public function storeV3PosPaymentLink(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency_id' => 'required|uuid',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:50',
                'merchant_id' => 'required|uuid',
                'customer_id' => 'nullable|uuid|exists:customers,id',
                'payment_method_types' => 'nullable|array',
                'payment_method_types.*' => 'string|in:card,afterpay_clearpay,alipay,bancontact,eps,giropay,grabpay,ideal,klarna,oxxo,p24,sepa_debit,sofort,us_bank_account,wechat_pay',
                'line_items' => 'nullable|array',
                'line_items.*.name' => 'required_with:line_items|string',
                'line_items.*.price' => 'required_with:line_items|integer|min:1',
                'line_items.*.quantity' => 'required_with:line_items|integer|min:1',
                'line_items.*.currency' => 'nullable|string',
                // Preserved for checkout UI (validate() drops keys without rules)
                'line_items.*.image' => 'nullable|string|max:2048',
                'line_items.*.description' => 'nullable|string|max:5000',
                'metadata' => 'nullable|array',
            ]);

            // No need to verify merchant - merchant is managed in AuthService
            // Just accept the merchant_id and use it

            // Persist line items on metadata (not a mass-assignable column) for public checkout UI
            $metadata = array_merge($validated['metadata'] ?? [], [
                'source' => 'pos_v3_api',
                'created_at' => now()->toISOString(),
            ]);
            if (!empty($validated['line_items'])) {
                $metadata['line_items'] = $validated['line_items'];
            }

            // Prepare request data for payment link service
            $request->merge([
                'merchant_id' => $validated['merchant_id'],
                'status' => 'active',
                'payment_method_types' => $validated['payment_method_types'] ?? ['card'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'customer_id' => $validated['customer_id'] ?? null,
                'metadata' => $metadata,
            ]);

            // Create payment link using the service
            $paymentLink = $this->paymentByLinkService->store($request);

            // Build public UUID-based link for sharing
            $publicLink = null;
            if ($paymentLink->uuid) {
                $baseUrl = rtrim(config('app.front_end_url', env('FRONT_END_URL', 'http://localhost:5173')), '/');
                $publicLink = $baseUrl . '/payment-links/uuid/' . $paymentLink->uuid;
            }

            $resource = new PaymentLinkResource($paymentLink);
            $data = $resource->toArray($request);
            
            // Ensure public_link is included
            if (!isset($data['public_link']) && $publicLink) {
                $data['public_link'] = $publicLink;
            }

            return response()->json([
                'message' => 'Payment link created successfully',
                'payment_link' => $data, // Wrap in payment_link key for consistency
                'data' => $data // Also include as data for backward compatibility
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating V3 POS payment link: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function storePosPaymentLink(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency_id' => 'required|uuid',
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:50',
                'merchant_id' => 'required|uuid',
                'customer_id' => 'nullable|uuid|exists:customers,id',
                'payment_method_types' => 'nullable|array',
                'payment_method_types.*' => 'string|in:card,afterpay_clearpay,alipay,bancontact,eps,giropay,grabpay,ideal,klarna,oxxo,p24,sepa_debit,sofort,us_bank_account,wechat_pay',
                'line_items' => 'nullable|array',
                'line_items.*.name' => 'required_with:line_items|string',
                'line_items.*.price' => 'required_with:line_items|integer|min:1',
                'line_items.*.quantity' => 'required_with:line_items|integer|min:1',
                'line_items.*.currency' => 'nullable|string',
                'line_items.*.image' => 'nullable|string|max:2048',
                'line_items.*.description' => 'nullable|string|max:5000',
                'metadata' => 'nullable|array',
            ]);

            // Verify merchant exists and matches authenticated user's merchant
            $merchant = Auth::user()->merchant;
            if (!$merchant || $merchant->id !== $validated['merchant_id']) {
                return response()->json(['error' => 'Unauthorized: Merchant ID mismatch'], 403);
            }

            $metadata = array_merge($validated['metadata'] ?? [], [
                'source' => 'pos_sale',
                'created_at' => now()->toISOString(),
            ]);
            if (!empty($validated['line_items'])) {
                $metadata['line_items'] = $validated['line_items'];
            }

            // Prepare request data for payment link service
            $request->merge([
                'merchant_id' => $validated['merchant_id'],
                'status' => 'active',
                'payment_method_types' => $validated['payment_method_types'] ?? ['card'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'customer_id' => $validated['customer_id'] ?? null,
                'metadata' => $metadata,
            ]);

            // Create payment link using the service
            $paymentLink = $this->paymentByLinkService->store($request);

            // Build public UUID-based link for sharing
            $publicLink = null;
            if ($paymentLink->uuid) {
                $baseUrl = rtrim(config('app.url', config('services.softpos.base_url', 'http://localhost:8001')), '/');
                $publicLink = $baseUrl . '/payment-links/uuid/' . $paymentLink->uuid;
            }

            $resource = new PaymentLinkResource($paymentLink);
            $data = $resource->toArray($request);
            
            // Ensure public_link is included
            if (!$data['public_link'] && $publicLink) {
                $data['public_link'] = $publicLink;
            }

            return response()->json([
                'message' => 'Payment link created successfully',
                'data' => $data
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating POS payment link: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payment-links/{id}",
     *     summary="Get a specific payment link",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment link ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to payment link"),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function show( $paymentLink)
    {
        $merchant = Auth::user()->merchant;

        $paymentLink = PaymentByLink::find($paymentLink);

        // if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
        //     return response()->json(['error' => 'Unauthorized access to payment link'], 403);
        // }

        return response()->json([
            'data' => new PaymentLinkResource($paymentLink->load(['merchant', 'customer']))
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/payment-links/{id}",
     *     summary="Update a payment link",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment link ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount","currency"},
     *             @OA\Property(property="amount", type="number", format="float", minimum=0.01),
     *             @OA\Property(property="currency", type="string", enum={"USD","AED","SUD","EUR"}),
     *             @OA\Property(property="payment_method_types", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="scheduled_date", type="string", format="date-time"),
     *             @OA\Property(property="expired_date", type="string", format="date-time"),
     *             @OA\Property(property="customer_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to payment link"),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function update(Request $request,  $paymentLink)
    {
        $merchant = Auth::user()->merchant;
        $paymentLink = PaymentByLink::find($paymentLink);
        // dd($paymentLink );
        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            return response()->json(['error' => 'Unauthorized access to payment link'], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'payment_method_types' => 'nullable|array',
            'payment_method_types.*' => 'string|in:card,afterpay_clearpay,alipay,bancontact,eps,giropay,grabpay,ideal,klarna,oxxo,p24,sepa_debit,sofort,us_bank_account,wechat_pay',
            'scheduled_date' => 'nullable|date|after:now',
            'expired_date' => 'nullable|date|after:now',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        // Verify customer belongs to this merchant if provided
        if ($request->filled('customer_id')) {
            $customer = Customer::where('id', $request->customer_id)
                ->where('merchant_id', $merchant->id)
                ->first();

            if (!$customer) {
                return response()->json(['error' => 'Invalid customer selected'], 400);
            }
        }

        $validated['payment_method_types'] = $request->payment_method_types ?? ['card'];

        try {
            $this->paymentByLinkService->update($request, $paymentLink->id);
            $updatedPaymentLink = PaymentByLink::with(['merchant', 'customer'])->find($paymentLink->id);
            return response()->json([
                'message' => 'Payment link updated successfully',
                'data' => new PaymentLinkResource($updatedPaymentLink)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/payment-links/{id}",
     *     summary="Delete a payment link",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment link ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to payment link"),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function destroy( $paymentLink)
    {
        $paymentLink = PaymentByLink::find($paymentLink);
        if (!$paymentLink) {
            return response()->json(['error' => 'Payment link not found'], 404);
        }

        $merchant = Auth::user()->merchant;

        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            return response()->json(['error' => 'Unauthorized access to payment link'], 403);
        }

        try {
            $this->paymentByLinkService->cancel($paymentLink->id);
            return response()->json(['message' => 'Payment link Cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment-links/{id}/cancel",
     *     summary="Cancel a payment link",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment link ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link canceled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Payment link cannot be canceled"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Unauthorized access to payment link"),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function cancel($paymentLinkId)
    {
        $merchant = Auth::user()->merchant;
        $paymentLink = PaymentByLink::find($paymentLinkId);

        if (!$paymentLink) {
            return response()->json(['error' => 'Payment link not found'], 404);
        }

        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            return response()->json(['error' => 'Unauthorized access to payment link'], 403);
        }

        try {
            $canceledLink = $this->paymentByLinkService->cancel($paymentLink->id);
            return response()->json([
                'message' => 'Payment link canceled successfully',
                'data' => new PaymentLinkResource($canceledLink->load(['merchant', 'customer']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/payment-links/{id}/update-date",
     *     summary="Update scheduled date for a payment link",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment link ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"scheduled_date"},
     *             @OA\Property(property="scheduled_date", type="string", format="date", description="New scheduled date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link rescheduled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function updateDate(Request $request, $payment_link)
    {
        $request->validate([
            'scheduled_date' => 'required|date',
        ]);

        $merchant = Auth::user()->merchant;
        $link = PaymentByLink::where('id', $payment_link)
            ->where('merchant_id', $merchant->id)
            ->firstOrFail();

        $link->scheduled_date = $request->scheduled_date;
        $link->status = 'scheduled';
        $link->save();

        return response()->json(['success' => true, 'message' => 'Payment link rescheduled successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/payment-links/{id}/send",
     *     summary="Send payment link via email or WhatsApp",
     *     tags={"Payment Links"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Payment link ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="send_email", type="boolean", description="Send via email"),
     *             @OA\Property(property="send_whatsapp", type="boolean", description="Send via WhatsApp")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="No sending method selected"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function send(Request $request, $payment_link)
    {
        $request->validate([
            'send_email' => 'nullable|boolean',
            'send_whatsapp' => 'nullable|boolean',
        ]);

        $merchant = Auth::user()->merchant;
        $link = PaymentByLink::where('id', $payment_link)
            ->where('merchant_id', $merchant->id)
            ->firstOrFail();

        $methods = [];

        if ($request->boolean('send_email')) {
            $methods[] = 'Email';
            $customer = $link->customer;
            if ($customer && !empty($customer->email)) {
                Mail::to($customer->email)->send(new PaymentMail($link));
            }
        }

        if ($request->boolean('send_whatsapp')) {
            $methods[] = 'WhatsApp';
            // Implement actual WhatsApp logic as needed
        }

        if (empty($methods)) {
            return response()->json(['success' => false, 'message' => 'No sending method selected.'], 400);
        }

        $msg = 'Payment link sent via: ' . implode(' and ', $methods);
        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * @OA\Get(
     *     path="/api/payment-links/public/{uuid}",
     *     summary="Show public payment link view",
     *     tags={"Payment Links"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Payment link UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Payment link not found")
     * )
     */
    public function showPublic($uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);

            // Check if payment link is expired
            if ($link->expired_date && now()->isAfter($link->expired_date)) {
                $link->update(['status' => 'expired']);
            }

            return response()->json([
                'data' => new PaymentLinkResource($link->load(['merchant', 'customer']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment link not found'], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/payment-links/pay/{uuid}",
     *     summary="Pay using a payment link UUID",
     *     tags={"Payment Links"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Payment link UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link details and redirect URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="redirect_url", type="string", description="Payment link URL"),
     *             @OA\Property(property="payment_link", ref="#/components/schemas/PaymentLinkResource")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Payment link not found"),
     *     @OA\Response(response=410, description="Payment link has expired")
     * )
     */
    public function pay($uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);

            if ($link->expired_date && now()->isAfter($link->expired_date)) {
                $link->update(['status' => 'expired']);
                return response()->json(['error' => 'Payment link has expired'], 410);
            }

            return response()->json([
                'redirect_url' => $link->link,
                'payment_link' => new PaymentLinkResource($link->load(['merchant', 'customer']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment link not found'], 404);
        }
    }

    /**
     * Step 1 of the secure Stripe flow:
     * Creates a PaymentIntent server-side and returns the client_secret to the frontend.
     * Card data is NEVER sent to this endpoint — Stripe.js collects it directly on the client.
     */
    public function createPaymentIntent(Request $request, $uuid)
    {
        try {
            $validated = $request->validate([
                'cardholder_name' => 'nullable|string|max:255',
                'email'           => 'nullable|email|max:255',
            ]);

            $link = $this->paymentByLinkService->findByUuid($uuid);
            if (!$link) {
                return response()->json(['success' => false, 'message' => 'Payment link not found'], 404);
            }

            if ($link->expired_date && now()->isAfter($link->expired_date)) {
                $link->update(['status' => 'expired']);
                return response()->json(['success' => false, 'message' => 'Payment link has expired'], 410);
            }

            if (in_array($link->status, ['completed', 'canceled', 'expired'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This payment link is not payable.',
                ], 400);
            }

            $displayCurrency = $link->currency_code ?? 'USD';
            $currency        = StripePaymentLinkCurrency::stripeCurrencyFromDisplayCode($displayCurrency);
            $amountSmallestUnit = (int) round(((float) $link->amount) * 100);

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $metadata = [
                'payment_link_id'   => (string) $link->id,
                'payment_link_uuid' => (string) $link->uuid,
                'merchant_id'       => (string) $link->merchant_id,
                'cardholder_name'   => $validated['cardholder_name'] ?? '',
                'display_currency'  => strtoupper(trim((string) $displayCurrency)),
            ];
            if (StripePaymentLinkCurrency::isDemoSudanChargedAsUsdOnStripe($displayCurrency)) {
                $metadata['stripe_demo_sudan_usd'] = '1';
            }

            // Card / on-page flows only: redirect methods (iDEAL, Bancontact, …) require
            // `return_url` on confirm; public checkout does not host a return URL.
            // https://stripe.com/docs/api/payment_intents/create#create_payment_intent-automatic_payment_methods-allow_redirects
            $intent = \Stripe\PaymentIntent::create([
                'amount'                    => $amountSmallestUnit,
                'currency'                  => $currency,
                'automatic_payment_methods' => [
                    'enabled'          => true,
                    'allow_redirects'  => 'never',
                ],
                'receipt_email'             => $validated['email'] ?? $link->customer_email ?? null,
                'metadata'                  => $metadata,
            ]);

            Log::info('PaymentIntent created for payment link', [
                'uuid'               => $uuid,
                'payment_intent_id'  => $intent->id,
                'display_currency'   => $displayCurrency,
                'stripe_currency'    => $currency,
            ]);

            return response()->json([
                'success'            => true,
                'client_secret'      => $intent->client_secret,
                'payment_intent_id'  => $intent->id,
                'stripe_currency'    => $currency,
                'display_currency'   => strtoupper(trim((string) $displayCurrency)),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('createPaymentIntent failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
    public function receiptByUuid(string $uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);
            $link->loadMissing('merchant');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment link not found',
            ], 404);
        }

        $isPaid = ($link->payment_status === 'paid' || $link->status === 'completed');
        if (! $isPaid) {
            return response()->json([
                'success' => false,
                'message' => 'No completed payment found for this link.',
            ], 409);
        }

        $tx = Transaction::with('paymentMethod')
            ->where('merchant_id', $link->merchant_id)
            ->where('transaction_type', 'payment_link')
            ->where(function ($q) use ($link) {
                $q->where('metadata->payment_link_uuid', (string) $link->uuid)
                    ->orWhere('metadata->payment_link_id', (string) $link->id);
            })
            ->orderByDesc('id')
            ->first();

        // Fallback: same payment-link metadata but type may differ on legacy rows
        if (! $tx) {
            $tx = Transaction::with('paymentMethod')
                ->where('merchant_id', $link->merchant_id)
                ->where(function ($q) use ($link) {
                    $q->where('metadata->payment_link_uuid', (string) $link->uuid)
                        ->orWhere('metadata->payment_link_id', (string) $link->id);
                })
                ->orderByDesc('id')
                ->first();
        }

        $maskedCard = null;
        $cardExpiry = null;
        if ($tx) {
            [$maskedCard, $cardExpiry] = $this->transactionService->getMaskedCardAndExpiryForTransaction($tx);
        }

        $meta        = (array) ($link->metadata ?? []);
        $stripePi    = $meta['stripe_payment_intent_id'] ?? null;
        $txMeta      = $tx && is_array($tx->metadata) ? $tx->metadata : [];
        $stripePi    = $stripePi ?: ($txMeta['stripe_payment_intent_id'] ?? null);

        $reference = $stripePi ?: ($tx?->transaction_id ?? null);

        $paidAt = $meta['paid_at'] ?? null;
        if ($paidAt === null && $tx && $tx->transaction_datetime) {
            $paidAt = $tx->transaction_datetime->toIso8601String();
        }

        $pub = (new PaymentLinkResource($link))->toArray(request());

        $merchantCode = $link->merchant?->merchant_code;

        $data = array_merge($pub, [
            'reference' => $reference,
            'stripe_payment_intent_id' => $stripePi,
            'transaction_id' => $tx?->transaction_id,
            'paid_at' => $paidAt,
            'method' => 'card',
            'card_number' => $maskedCard,
            'expiry' => $cardExpiry,
            'merchant_code' => $merchantCode,
            'terminal_id' => $tx?->terminal_id,
        ]);

        // Public receipt: show merchant code instead of internal numeric id
        unset($data['merchant_id']);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }


    /**
     * Step 2 of the secure Stripe flow: attach the Stripe.js PaymentMethod to the PaymentIntent
     * created by {@see createPaymentIntent} and confirm on the server (card data never hits SoftPos).
     */
    public function confirmPaymentIntent(Request $request, string $uuid)
    {
        try {
            $validated = $request->validate([
                'payment_intent_id' => 'required|string|max:255',
                'payment_method_id' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Payment link not found'], 404);
        }

        if ($link->expired_date && now()->isAfter($link->expired_date)) {
            $link->update(['status' => 'expired']);

            return response()->json(['success' => false, 'message' => 'Payment link has expired'], 410);
        }

        if (in_array($link->status, ['completed', 'canceled', 'expired'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This payment link is not payable.',
            ], 400);
        }

        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $intent = \Stripe\PaymentIntent::retrieve($validated['payment_intent_id']);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::warning('confirmPaymentIntent: retrieve failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Invalid or expired payment session.'], 400);
        }

        $metaUuid   = $intent->metadata['payment_link_uuid'] ?? null;
        $metaLinkId = $intent->metadata['payment_link_id'] ?? null;

        if ((string) $metaUuid !== (string) $uuid || (string) $metaLinkId !== (string) $link->id) {
            return response()->json(['success' => false, 'message' => 'Payment session does not match this link.'], 403);
        }

        $expectedAmount = (int) round(((float) $link->amount) * 100);
        if ((int) $intent->amount !== $expectedAmount) {
            return response()->json(['success' => false, 'message' => 'Amount mismatch for this payment.'], 400);
        }

        if ($intent->status === 'succeeded') {
            $this->finalizePaymentLinkFromStripeIntent($link, $intent);

            return response()->json([
                'success'             => true,
                'status'              => 'succeeded',
                'payment_intent_id'   => $intent->id,
            ]);
        }

        if ($intent->status === 'requires_action') {
            return response()->json([
                'success'          => true,
                'requires_action'  => true,
                'client_secret'    => $intent->client_secret,
                'status'           => 'requires_action',
            ]);
        }

        if (in_array($intent->status, ['requires_payment_method', 'requires_confirmation'], true)) {
            try {
                $intent = $intent->confirm([
                    'payment_method' => $validated['payment_method_id'],
                ]);
            } catch (\Stripe\Exception\CardException $e) {
                Log::info('confirmPaymentIntent: card declined', ['uuid' => $uuid, 'error' => $e->getMessage()]);
                $msg = $e->getError() && $e->getError()->message
                    ? $e->getError()->message
                    : ($e->getMessage() ?: 'Card was declined.');

                return response()->json(['success' => false, 'message' => $msg], 402);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error('confirmPaymentIntent: confirm failed', ['uuid' => $uuid, 'error' => $e->getMessage()]);

                return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Payment failed.'], 400);
            }
        }

        if ($intent->status === 'succeeded') {
            $this->finalizePaymentLinkFromStripeIntent($link, $intent);

            return response()->json([
                'success'             => true,
                'status'              => 'succeeded',
                'payment_intent_id'   => $intent->id,
            ]);
        }

        if ($intent->status === 'requires_action') {
            return response()->json([
                'success'          => true,
                'requires_action'  => true,
                'client_secret'    => $intent->client_secret,
                'status'           => 'requires_action',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment could not be completed.',
            'status'  => $intent->status,
        ], 400);
    }

    /**
     * Legacy endpoint kept for backward compatibility.
     * Stripe session generation is intentionally disabled in this controller.
     */
    public function generateStripeSessionUrl(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Stripe integration is not handled by PaymentLinkController.'
        ], 410);
    }

    /**
     * Stripe webhook — verifies the Stripe signature and processes payment_intent.succeeded.
     * Register this endpoint in the Stripe Dashboard: POST /api/stripe/webhook
     */
    public function handleWebhook(Request $request)
    {
        $parsed = $this->parseStripeWebhook($request);
        if ($parsed instanceof Response) {
            return $parsed;
        }
        $event = $parsed;

        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $link   = $this->resolvePaymentLinkFromStripeIntent($intent);

            if ($link) {
                $beforeStatus = $link->status;
                $this->finalizePaymentLinkFromStripeIntent($link, $intent);
                if (! in_array($beforeStatus, ['completed', 'canceled'], true)) {
                    Log::info('Payment link marked completed via webhook', [
                        'uuid'              => $link->uuid,
                        'payment_intent_id' => $intent->id,
                    ]);
                }
            }
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $this->markPaymentLinkFailedFromStripeIntent($event->data->object);
        }

        return response()->json(['received' => true]);
    }

    private function getStatistics($merchantId)
    {
        $total = PaymentByLink::where('merchant_id', $merchantId)->count();
        $active = PaymentByLink::where('merchant_id', $merchantId)
            ->where('status', 'active')
            ->count();
        $inactive = PaymentByLink::where('merchant_id', $merchantId)
            ->where('status', 'inactive')
            ->count();
        $expired = PaymentByLink::where('merchant_id', $merchantId)
            ->where('status', 'expired')
            ->count();
        $completed = PaymentByLink::where('merchant_id', $merchantId)
            ->where('status', 'completed')
            ->count();
        $canceled = PaymentByLink::where('merchant_id', $merchantId)
            ->where('status', 'canceled')
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'expired' => $expired,
            'completed' => $completed,
            'canceled' => $canceled
        ];
    }
}