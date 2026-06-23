<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentByLink;
use App\Models\Currency;
use App\Services\PaymentByLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class MerchantPaymentLinkApiController extends Controller
{
    protected $paymentByLinkService;

    public function __construct(PaymentByLinkService $paymentByLinkService)
    {
        $this->paymentByLinkService = $paymentByLinkService;
    }

    /**
     * Get merchant ID from authenticated user
     */
    protected function getMerchantId()
    {
        return (auth()->guard('external')->user()->merchant->id);
        // return auth()->guard('external')->user()->getMerchant()['id'];
    }

    /**
     * List all payment links with pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');
            
            $query = PaymentByLink::where('merchant_id', $merchantId)
                ->with(['merchant']);
            
            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', '%' . $search . '%')
                      ->orWhere('amount', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%')
                      ->orWhere('customer_name', 'like', '%' . $search . '%')
                      ->orWhere('customer_email', 'like', '%' . $search . '%')
                      ->orWhere('customer_phone', 'like', '%' . $search . '%');
                });
            }
            
            // Apply customer filter (by name)
            if ($request->has('customer') && $request->get('customer') !== '') {
                $query->where('customer_name', 'like', '%' . $request->get('customer') . '%');
            }
            
            // Apply date filters
            if ($request->has('from_date') && !empty($request->get('from_date'))) {
                $query->whereDate('created_at', '>=', $request->get('from_date'));
            }
            
            if ($request->has('to_date') && !empty($request->get('to_date'))) {
                $query->whereDate('created_at', '<=', $request->get('to_date'));
            }
            
            // Order by created_at desc
            $query->orderBy('created_at', 'desc');
            
            $paymentLinks = $query->paginate($perPage);
            
            // Transform payment links to include currency details
            $transformedData = $paymentLinks->getCollection()->map(function ($link) {
                $linkArray = $link->toArray();
                
                // Decode currency_object and extract code and symbol
                if (!empty($linkArray['currency_object'])) {
                    $currencyObject = is_array($linkArray['currency_object']) 
                        ? $linkArray['currency_object'] 
                        : json_decode($linkArray['currency_object'], true);
                    
                    $linkArray['currency_symbol'] = $currencyObject['symbol'] ?? '';
                    $linkArray['currency_name'] = $currencyObject['name'] ?? '';
                } else {
                    $linkArray['currency_symbol'] = '';
                    $linkArray['currency_name'] = '';
                }
                
                return $linkArray;
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Payment links retrieved successfully',
                'data' => $transformedData,
                'pagination' => [
                    'total' => $paymentLinks->total(),
                    'per_page' => $paymentLinks->perPage(),
                    'current_page' => $paymentLinks->currentPage(),
                    'last_page' => $paymentLinks->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment links: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment links',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single payment link details
     * 
     * @param PaymentByLink $paymentLink
     * @return JsonResponse
     */
    public function show(PaymentByLink $paymentLink): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            
            // Ensure the payment link belongs to the authenticated merchant
            if ($paymentLink->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment link'
                ], 403);
            }
            
            // Decode currency_object from JSON to array for the response
            $paymentLinkData = $paymentLink->toArray();
            
            // Decode currency_object and extract code and symbol
            if (!empty($paymentLinkData['currency_object'])) {
                $currencyObject = is_array($paymentLinkData['currency_object']) 
                    ? $paymentLinkData['currency_object'] 
                    : json_decode($paymentLinkData['currency_object'], true);
                
                $paymentLinkData['currency_object'] = $currencyObject;
                $paymentLinkData['currency_symbol'] = $currencyObject['symbol'] ?? '';
                $paymentLinkData['currency_name'] = $currencyObject['name'] ?? '';
            } else {
                $paymentLinkData['currency_symbol'] = '';
                $paymentLinkData['currency_name'] = '';
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment link retrieved successfully',
                'data' => $paymentLinkData
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new payment link
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // dd($request->all());
            $merchantId = $this->getMerchantId();
            // dd($merchantId);
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency_id' => 'required|exists:currencies,id',
                'payment_method_types' => 'nullable|array',
                'payment_method_types.*' => 'string|in:card,afterpay_clearpay,alipay,bancontact,eps,giropay,grabpay,ideal,klarna,oxxo,p24,sepa_debit,sofort,us_bank_account,wechat_pay',
                'scheduled_date' => 'nullable|date|after:now',
                'expired_date' => 'nullable|date|after:now',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'customer_email' => 'nullable|email|max:255',
            ]);

            $currency = Currency::findOrFail($validated['currency_id']);
            $currencyData = [
                'id' => $currency->id,
                'name' => $currency->name,
                'symbol' => method_exists($currency, 'getTranslation')
                    ? ($currency->getTranslation('symbol', app()->getLocale(), false)
                        ?? $currency->getTranslation('symbol', 'en', false)
                        ?? '')
                    : ($currency->symbol ?? ''),
                'currency_code' => method_exists($currency, 'getTranslation')
                    ? ($currency->getTranslation('currency_code', app()->getLocale(), false)
                        ?? $currency->getTranslation('currency_code', 'en', false)
                        ?? '')
                    : ($currency->currency_code ?? ''),
                'country' => $currency->country ?? null,
            ];

            // dd($request->all());
            $request->merge([
                'merchant_id' => $merchantId,
                'status' => 'active',
                // 'short_uuid' => Str::random(8),
                'payment_method_types' => $request->payment_method_types ?? ['card'],
                'currency_code' => $currencyData['currency_code'] ?? $currencyData['code'] ?? null,
                'currency_object' => json_encode($currencyData),
            ]);

            $paymentLink = $this->paymentByLinkService->store($request);
            
            // Automatically send email if customer email is provided
            if (!empty($request->customer_email)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($request->customer_email)
                        ->send(new \App\Mail\PaymentMail($paymentLink));
                } catch (\Exception $emailException) {
                    // Log email error but don't fail the payment link creation
                    Log::warning('Payment link created but failed to send email: ' . $emailException->getMessage());
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment link created successfully' . (!empty($request->customer_email) ? ' and email sent' : ''),
                'data' => $paymentLink
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {

            Log::error('Error creating payment link: ' . $e->getMessage() . ' on line ' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment link
     * 
     * @param Request $request
     * @param PaymentByLink $paymentLink
     * @return JsonResponse
     */
    public function update(Request $request, PaymentByLink $paymentLink): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            
            // Ensure the payment link belongs to the authenticated merchant
            if ($paymentLink->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment link'
                ], 403);
            }

            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency_id' => 'required|exists:currencies,id',
                'payment_method_types' => 'nullable|array',
                'payment_method_types.*' => 'string|in:card,afterpay_clearpay,alipay,bancontact,eps,giropay,grabpay,ideal,klarna,oxxo,p24,sepa_debit,sofort,us_bank_account,wechat_pay',
                'scheduled_date' => 'nullable|date|after:now',
                'expired_date' => 'nullable|date|after:now',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'nullable|string|max:20',
                'customer_email' => 'nullable|email|max:255',
            ]);

            $currency = Currency::findOrFail($validated['currency_id']);
            $currencyData = [
                'id' => $currency->id,
                'name' => $currency->name,
                'symbol' => method_exists($currency, 'getTranslation')
                    ? ($currency->getTranslation('symbol', app()->getLocale(), false)
                        ?? $currency->getTranslation('symbol', 'en', false)
                        ?? '')
                    : ($currency->symbol ?? ''),
                'currency_code' => method_exists($currency, 'getTranslation')
                    ? ($currency->getTranslation('currency_code', app()->getLocale(), false)
                        ?? $currency->getTranslation('currency_code', 'en', false)
                        ?? '')
                    : ($currency->currency_code ?? ''),
                'country' => $currency->country ?? null,
            ];

            $request->merge([
                'payment_method_types' => $request->payment_method_types ?? ['card'],
                'currency_code' => $currencyData['currency_code'] ?? $currencyData['code'] ?? null,
                'currency_object' => json_encode($currencyData),
            ]);
            
            $paymentLink = $this->paymentByLinkService->update($request, $paymentLink->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment link updated successfully',
                'data' => $paymentLink
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete payment link
     * 
     * @param PaymentByLink $paymentLink
     * @return JsonResponse
     */
    public function destroy(PaymentByLink $paymentLink): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            
            // Ensure the payment link belongs to the authenticated merchant
            if ($paymentLink->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment link'
                ], 403);
            }
            
            $this->paymentByLinkService->destroy($paymentLink->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment link deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete payment links
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No payment links selected'
                ], 400);
            }
            
            // Ensure all payment links belong to this merchant
            $count = PaymentByLink::where('merchant_id', $merchantId)
                ->whereIn('id', $ids)
                ->count();
            
            if ($count !== count($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some payment links do not belong to this merchant'
                ], 403);
            }
            
            // Delete payment links
            PaymentByLink::whereIn('id', $ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment links deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting payment links: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment links',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update scheduled date for a payment link
     * 
     * @param Request $request
     * @param PaymentByLink $paymentLink
     * @return JsonResponse
     */
    public function updateDate(Request $request, PaymentByLink $paymentLink): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            
            // Ensure the payment link belongs to the authenticated merchant
            if ($paymentLink->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment link'
                ], 403);
            }

            $request->validate([
                'scheduled_date' => 'required|date',
            ]);
            
            $paymentLink->scheduled_date = $request->scheduled_date;
            $paymentLink->status = 'scheduled';
            $paymentLink->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment link rescheduled successfully',
                'data' => $paymentLink
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating payment link date: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment link date',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send payment link via email, WhatsApp, or SMS
     * 
     * @param Request $request
     * @param PaymentByLink $paymentLink
     * @return JsonResponse
     */
    public function send(Request $request, PaymentByLink $paymentLink): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            
            // Ensure the payment link belongs to the authenticated merchant
            if ($paymentLink->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this payment link'
                ], 403);
            }

            $request->validate([
                'send_email' => 'nullable|boolean',
                'send_whatsapp' => 'nullable|boolean',
                'send_sms' => 'nullable|boolean',
            ]);
            
            $methods = [];
            
            if ($request->boolean('send_email')) {
                $methods[] = 'Email';
                $email = $paymentLink->customer_email;
                if (!empty($email)) {
                    \Illuminate\Support\Facades\Mail::to($email)
                        ->send(new \App\Mail\PaymentMail($paymentLink));
                }
            }
            
            if ($request->boolean('send_whatsapp')) {
                $methods[] = 'WhatsApp';
                // Implement actual WhatsApp logic as needed
            }
            
            if ($request->boolean('send_sms')) {
                $methods[] = 'SMS';
                // Implement actual SMS logic as needed
            }
            
            if (empty($methods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No sending method selected.'
                ], 400);
            }
            
            $msg = 'Payment link sent via: ' . implode(' and ', $methods);
            
            return response()->json([
                'success' => true,
                'message' => $msg
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending payment link: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send payment link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export payment links data
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function export(Request $request)
    {
        try {
            $merchantId = $this->getMerchantId();
            
            $query = PaymentByLink::where('merchant_id', $merchantId)
                ->with(['merchant']);

            // Apply the same filters as the index method
            if ($request->filled('customer')) {
                $query->where('customer_name', 'like', '%' . $request->customer . '%');
            }

            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('uuid', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhere('customer_name', 'like', "%{$search}%")
                      ->orWhere('customer_email', 'like', "%{$search}%")
                      ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            }

            $paymentLinks = $query->orderBy('created_at', 'desc')->get();
            
            // Generate CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="payment_links_export_' . date('Y-m-d_H-i-s') . '.csv"',
            ];

            $callback = function() use ($paymentLinks) {
                $file = fopen('php://output', 'w');
                
                // Add headers
                fputcsv($file, [
                    'ID',
                    'UUID',
                    'Merchant',
                    'Customer Name',
                    'Customer Email',
                    'Customer Phone',
                    'Amount',
                    'Currency',
                    'Status',
                    'Created At',
                    'Scheduled Date',
                    'Expired Date'
                ]);
                
                // Add data
                foreach ($paymentLinks as $link) {
                    fputcsv($file, [
                        $link->id,
                        $link->uuid,
                        optional($link->merchant)->name,
                        $link->customer_name,
                        $link->customer_email,
                        $link->customer_phone,
                        $link->amount,
                        $link->currency_code,
                        $link->status,
                        optional($link->created_at)?->format('Y-m-d H:i:s'),
                        $link->scheduled_date,
                        $link->expired_date
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            Log::error('Error exporting payment links: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment link statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            
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

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                    'expired' => $expired,
                    'completed' => $completed
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching payment link statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

