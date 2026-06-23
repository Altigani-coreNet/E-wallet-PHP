<?php

namespace App\Http\Controllers;

use App\Models\PaymentByLink;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Models\Customer;
use App\Models\Batch;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Services\PaymentByLinkService;
use App\Traits\MessageManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentMail;

class MerchantPaymentLinkController extends Controller
{
    use AuthorizesRequests, MessageManager;

    protected $paymentByLinkService;

    public function __construct(PaymentByLinkService $paymentByLinkService)
    {
        $this->paymentByLinkService = $paymentByLinkService;
    }

    public function index()
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('view_payment_links')) {
            abort(403, 'Unauthorized access to payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        $statistics = $this->getStatistics($merchant->id);
        
        return view('merchant.payment-links.index', [ 'statistics' => $statistics, 'merchant' => $merchant  , 'has_toolbar' => true]);
    }

    public function create()
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('create_payment_links')) {
            abort(403, 'Unauthorized access to create payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        return view('merchant.payment-links.create', [ 'merchant' => $merchant , 'has_toolbar' => true]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('create_payment_links')) {
            abort(403, 'Unauthorized access to create payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
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
                // ->where('merchant_id', $merchant->id)
                ->first();
            
            if (!$customer) {
                return redirect()->back()
                    ->withErrors(['customer_id' => 'Invalid customer selected'])
                    ->withInput();
            }
        }

        $validated['merchant_id'] = $merchant->id;
        $validated['short_uuid'] = Str::random(8);
        $validated['status'] = 'active'; // Default status
        $validated['payment_method_types'] = $request->payment_method_types ?? ['card'];

        try {
            $paymentLink = $this->paymentByLinkService->store($request);
            
            $this->SuccessMessage('Payment link created successfully');
            return redirect()->route('merchant.payment-links.index');
        } catch (\Exception $e) {
            dd($e->getMessage());
            $this->ErrorMessage($e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function show(PaymentByLink $paymentLink)
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('view_payment_links')) {
            abort(403, 'Unauthorized access to payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to payment link');
        }

        return view('merchant.payment-links.show', compact('paymentLink', 'merchant'));
    }

    public function edit(PaymentByLink $paymentLink)
    {
        // dd($paymentLink);
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('edit_payment_links')) {
            abort(403, 'Unauthorized access to edit payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to payment link');
        }

        return view('merchant.payment-links.edit', [ 'paymentLink' => $paymentLink, 'merchant' => $merchant , 'has_toolbar' => true]);
    }

    public function update(Request $request, PaymentByLink $paymentLink)
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('edit_payment_links')) {
            abort(403, 'Unauthorized access to edit payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to payment link');
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
                return redirect()->back()
                    ->withErrors(['customer_id' => 'Invalid customer selected'])
                    ->withInput();
                }
        }

        $validated['payment_method_types'] = $request->payment_method_types ?? ['card'];

        try {
            $this->paymentByLinkService->update($request, $paymentLink->id);
            $this->SuccessMessage('Payment link updated successfully');
            return redirect()->route('merchant.payment-links.index');
        } catch (\Exception $e) {
            $this->ErrorMessage($e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    public function destroy(PaymentByLink $paymentLink)
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('delete_payment_links')) {
            abort(403, 'Unauthorized access to delete payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant || $paymentLink->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to payment link');
        }

        try {
            $this->paymentByLinkService->destroy($paymentLink->id);
            $this->SuccessMessage('Payment link deleted successfully');
            return redirect()->route('merchant.payment-links.index');
        } catch (\Exception $e) {
            $this->ErrorMessage($e->getMessage());
            return redirect()->back();
        }
    }

    public function data(Request $request)
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('view_payment_links')) {
            abort(403, 'Unauthorized access to payment links.');
        }
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $query = PaymentByLink::where('merchant_id', $merchant->id)
            ->with(['merchant', 'customer']); // currency is a UUID, not a relationship

        // Apply filters
        if ($request->filled('customer')) {
            $query->where('customer_id', $request->customer);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search filter
        if ($request->filled('search_text')) {
            $searchText = $request->search_text;
            $query->where(function ($q) use ($searchText) {
                $q->where('uuid', 'like', "%{$searchText}%")
                  ->orWhere('amount', 'like', "%{$searchText}%")
                  ->orWhere('status', 'like', "%{$searchText}%")
                  ->orWhereHas('customer', function ($cq) use ($searchText) {
                      $cq->where('name', 'like', "%{$searchText}%")
                         ->orWhere('email', 'like', "%{$searchText}%");
                  });
            });
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($paymentLink) {
                return view('merchant.payment-links.partials.record_select', ['id' => $paymentLink->id])->render();
            })
            ->addColumn('merchant', function ($paymentLink) {
                return $paymentLink->merchant->name ?? 'N/A';
            })
            ->addColumn('customer', function ($paymentLink) {
                return $paymentLink->customer ? $paymentLink->customer->name : 'N/A';
            })
            ->addColumn('currency', function ($paymentLink) {
                return $paymentLink->currency ? $paymentLink->currency->currency_code : 'N/A';
            })
            ->addColumn('amount', function ($paymentLink) {
                return ($paymentLink->currency?->symbol ?? '$') . ' ' . number_format($paymentLink->amount ?? 0, 2);
            })
            ->addColumn('status', function ($paymentLink) {
                $statusClass = '';
                $statusText = ucfirst($paymentLink->status ?? 'active');
                
                switch (strtolower($paymentLink->status)) {
                    case 'active':
                        $statusClass = 'badge badge-light-success';
                        break;
                    case 'inactive':
                        $statusClass = 'badge badge-light-danger';
                        break;
                    case 'expired':
                        $statusClass = 'badge badge-light-warning';
                        break;
                    case 'completed':
                        $statusClass = 'badge badge-light-info';
                        break;
                    default:
                        $statusClass = 'badge badge-light-secondary';
                }
                
                return '<span class="' . $statusClass . '">' . $statusText . '</span>';
            })
            ->addColumn('expiry_date', function ($paymentLink) {
                return $paymentLink->expired_date ? Carbon::parse($paymentLink->expired_date)->format('M d, Y') : 'No Expiry';
            })
            ->editColumn('scheduled_date', fn($link) => $link->scheduled_date ? Carbon::parse($link->scheduled_date)->diffForHumans() : '')
            ->addColumn('created_at', function ($paymentLink) {
                return $paymentLink->created_at ? $paymentLink->created_at->format('M d, Y H:i:s') : 'N/A';
            })
            ->addColumn('actions', function($row) {
                $showUrl = route('merchant.payment-links.show', $row->id);
                $editUrl = route('merchant.payment-links.edit', $row->id);
                $deleteUrl =  'url';#route('merchant.payment-links.destroy', $row->id);
                return view('merchant.payment-links.partials.actions', compact('showUrl','row',  'editUrl', 'deleteUrl'))->render();
            })
            ->rawColumns(['record_select', 'status', 'actions'])
            ->make(true);
    }

    public function statistics($merchantId)
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

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'expired' => $expired,
            'completed' => $completed
        ];
    }

    private function getStatistics($merchantId)
    {
        return $this->statistics($merchantId);
    }

    /**
     * Pay using a payment link UUID
     */
    public function pay($uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);
            
            if ($link->expired_date && now()->isAfter($link->expired_date)) {
                $link->update(['status' => 'expired']);
                return redirect()->route('payments.error', $uuid);
            }
            
            return redirect($link->link);
        } catch (\Exception $e) {
            abort(404, 'Payment link not found');
        }
    }

    /**
     * Show public payment link view
     */
    public function showPublic($uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);
            
            // Check if payment link is expired
            if ($link->expired_date && now()->isAfter($link->expired_date)) {
                $link->update(['status' => 'expired']);
            }
            
            return view('payment-links.public', compact('link'));
        } catch (\Exception $e) {
            abort(404, 'Payment link not found');
        }
    }

    /**
     * Export payment links to CSV
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('payment_links') && !auth()->user()->can('view_payment_links')) {
            abort(403, 'Unauthorized access to payment links.');
        }
        
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        $query = PaymentByLink::where('merchant_id', $merchant->id)
            ->with(['merchant', 'customer']); // currency is a UUID, not a relationship

        // Apply the same filters as the data method
        if ($request->filled('customer')) {
            $query->where('customer_id', $request->customer);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search filter
        if ($request->filled('search_text')) {
            $searchText = $request->search_text;
            $query->where(function ($q) use ($searchText) {
                $q->where('uuid', 'like', "%{$searchText}%")
                  ->orWhere('amount', 'like', "%{$searchText}%")
                  ->orWhere('status', 'like', "%{$searchText}%")
                  ->orWhereHas('customer', function ($cq) use ($searchText) {
                      $cq->where('name', 'like', "%{$searchText}%")
                         ->orWhere('email', 'like', "%{$searchText}%");
                  });
            });
        }

        $fileName = 'payment_links_export_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'UUID', 'Merchant', 'Customer', 'Amount', 'Currency', 'Status', 'Created At', 'Scheduled Date']);
            
            $query->chunk(1000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->uuid,
                        optional($row->merchant)->name,
                        optional($row->customer)->name,
                        $row->amount,
                        optional($row->currency)->currency_code,
                        $row->status,
                        optional($row->created_at)?->format('Y-m-d H:i:s'),
                        $row->scheduled_date,
                    ]);
                }
            });
            
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Update scheduled date for a payment link
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
     * Send payment link via email, WhatsApp, or SMS
     */
    public function send(Request $request, $payment_link)
    {
        $request->validate([
            'send_email' => 'nullable|boolean',
            'send_whatsapp' => 'nullable|boolean',
            'send_sms' => 'nullable|boolean',
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
        
        if ($request->boolean('send_sms')) {
            $methods[] = 'SMS';
            // Implement actual SMS logic as needed
        }
        
        if (empty($methods)) {
            return response()->json(['success' => false, 'message' => 'No sending method selected.'], 400);
        }
        
        $msg = 'Payment link sent via: ' . implode(' and ', $methods);
        return response()->json(['success' => true, 'message' => $msg]);
    }

    /**
     * Show payment success page
     */
    public function success()
    {
        return view('payments.success');
    }

    /**
     * Show payment error page
     */
    public function error()
    {
        return view('payments.error');
    }

    /**
     * Generate Stripe Checkout Session URL
     */
    public function generateStripeSessionUrl(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|max:10',
                'customer_email' => 'nullable|email',
            ]);

            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => $request->currency,
                        'product_data' => [
                            'name' => 'Payment Link',
                        ],
                        'unit_amount' => intval($request->amount * 100), // Stripe expects amount in cents
                    ],
                    'quantity' => 1,
                ]],
                'customer_email' => $request->customer_email,
                'success_url' => url('/payment-link/success?session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/payment-link/cancel'),
            ]);

            return response()->json(['url' => $session->url], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle Stripe webhook for payment link completion
     */
    public function handleWebhook(Request $request)
    {
        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
            $payload = $request->getContent();
            $sigHeader = $request->header('Stripe-Signature');
            $endpointSecret = env('STRIPE_WEBHOOK_SECRET', 'whsec_ieOFGnWD9C5MuaZ2YOmRN7YnovLn3tJ7');

            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            } catch (\UnexpectedValueException $e) {
                return response('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                return response('Invalid signature', 400);
            }

            Log::info('Stripe webhook received object', ['event' => $event->data->object]);

            // Handle different Stripe webhook events
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;
                    
                case 'charge.succeeded':
                    $this->handleChargeSucceeded($event->data->object);
                    break;
                    
                case 'charge.failed':
                    $this->handleChargeFailed($event->data->object);
                    break;
                    
                default:
                    // Log unhandled event types for debugging
                    Log::info('Unhandled Stripe webhook event: ' . $event->type, [
                        'event_id' => $event->id,
                        'event_type' => $event->type
                    ]);
                    break;
            }
            
            return response()->json(['success' => true, 'message' => 'Webhook handled'], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Handle successful payment intent
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        // Get transaction ID and payment link ID from metadata
        $transactionId = $paymentIntent->metadata->transaction_id ?? null;
        Log::info('Transaction ID', ['transactionId' => $transactionId]);

        if ($transactionId) {
            $this->processSuccessfulTransaction($paymentIntent, $transactionId);
        }
        
        Log::info('Payment_Link ', ['transaction' => Transaction::find($transactionId)->metadata]);
        Log::info('payment', [
            'Link For Payment intent Id' => Transaction::find($transactionId)->metadata['payment_link_id'],
        ]);
        
        if (Transaction::find($transactionId)->metadata) {
            $this->processSuccessfulPaymentLink($paymentIntent, Transaction::find($transactionId)->metadata['payment_link_id']);
        }
    }

    /**
     * Handle failed payment intent
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        $transactionId = $paymentIntent->metadata->transaction_id ?? null;
        if ($transactionId) {
            $transaction = Transaction::find($transactionId);
            if ($transaction && $transaction->status === 'pending') {
                $transaction->update([
                    'status' => 'failed',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'stripe_payment_intent_id' => $paymentIntent->id,
                        'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                        'failed_at' => now()->toISOString()
                    ])
                ]);
                
                Log::warning('Payment failed for transaction: ' . $transactionId, [
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'failure_reason' => $paymentIntent->last_payment_error->message ?? 'Unknown'
                ]);
            }
        }
    }

    /**
     * Handle successful charge
     */
    private function handleChargeSucceeded($charge)
    {
        Log::info('Charge succeeded', [
            'charge_id' => $charge->id,
            'payment_intent_id' => $charge->payment_intent,
            'amount' => $charge->amount / 100, // Convert from cents
            'currency' => $charge->currency
        ]);
    }

    /**
     * Handle failed charge
     */
    private function handleChargeFailed($charge)
    {
        Log::warning('Charge failed', [
            'charge_id' => $charge->id,
            'payment_intent_id' => $charge->payment_intent,
            'failure_reason' => $charge->failure_message ?? 'Unknown',
            'failure_code' => $charge->failure_code ?? 'Unknown'
        ]);
    }

    /**
     * Process successful transaction from webhook
     */
    private function processSuccessfulTransaction($paymentIntent, $transactionId)
    {
        Log::info('Starting to process successful transaction', [
            'transaction_id' => $transactionId,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100, // Convert from cents
            'currency' => $paymentIntent->currency
        ]);

        $transaction = Transaction::find($transactionId);
        if (!$transaction || $transaction->status !== 'pending') {
            Log::warning('Transaction not found or not in pending status', [
                'transaction_id' => $transactionId,
                'transaction_exists' => $transaction ? true : false,
                'status' => $transaction ? $transaction->status : 'not_found'
            ]);
            return;
        }

        Log::info('Transaction found and valid for processing', [
            'transaction_id' => $transaction->id,
            'merchant_id' => $transaction->merchant_id,
            'amount' => $transaction->amount,
            'current_status' => $transaction->status
        ]);

        // Get RRN and Invoice ID from Stripe
        $rrn = null;
        $invoiceId = null;
        
        if ($paymentIntent->latest_charge) {
            try {
                $charge = \Stripe\Charge::retrieve($paymentIntent->latest_charge);
                
                // Get RRN from charge
                $rrn = $charge->payment_method_details->card->reference_number ?? 
                       $charge->metadata->rrn ?? 
                       $charge->id;
                
                // Get invoice ID from charge
                if ($charge->invoice) {
                    $invoiceId = $charge->invoice;
                }
                
                Log::info('Charge data retrieved', [
                    'charge_id' => $charge->id,
                    'rrn' => $rrn,
                    'invoice_id' => $invoiceId
                ]);
            } catch (\Exception $e) {
                Log::error('Error retrieving charge data: ' . $e->getMessage());
            }
        }
        
        // Check PaymentIntent for invoice if not found in charge
        if (!$invoiceId && $paymentIntent->invoice) {
            $invoiceId = $paymentIntent->invoice;
        }

        // Get or create batch for the merchant
        $batch = $this->getOrCreateBatch($transaction->merchant_id);
        Log::info('Batch retrieved/created for merchant', [
            'batch_id' => $batch->id,
            'merchant_id' => $transaction->merchant_id,
            'batch_status' => $batch->status ?? 'N/A'
        ]);
        
        // Create or update payment method
        $paymentMethod = $this->createOrUpdatePaymentMethod($paymentIntent);
        Log::info('Payment method processed', [
            'payment_method_id' => $paymentMethod->id,
            'stripe_payment_method_id' => $paymentIntent->payment_method,
            'payment_method_type' => $paymentMethod->type ?? 'N/A'
        ]);
        
        // Update transaction with batch, payment method, RRN, and invoice ID
        $transaction->update([
            'status' => 'approved',
            'processed_at' => now(),
            'batch_id' => $batch->id,
            'rrn' => $rrn,
            'invoice_no' => $invoiceId,
            'payment_method_id' => $paymentMethod->id,
            'metadata' => array_merge($transaction->metadata ?? [], [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_payment_method_id' => $paymentIntent->payment_method,
                'stripe_customer_id' => $paymentIntent->customer,
                'stripe_charge_id' => $paymentIntent->latest_charge,
                'rrn' => $rrn,
                'invoice_no' => $invoiceId,
                'completed_at' => now()->toISOString(),
                'webhook_processed_at' => now()->toISOString()
            ])
        ]);

        Log::info('Transaction updated successfully with RRN and Invoice ID', [
            'transaction_id' => $transaction->id,
            'new_status' => 'approved',
            'batch_id' => $batch->id,
            'payment_method_id' => $paymentMethod->id,
            'rrn' => $rrn,
            'invoice_id' => $invoiceId,
            'processed_at' => now()->toISOString()
        ]);
        
        // Update batch totals
        $batch->updateTotals();
        Log::info('Batch totals updated', [
            'batch_id' => $batch->id,
            'merchant_id' => $transaction->merchant_id
        ]);

        Log::info('Transaction processing completed successfully', [
            'transaction_id' => $transaction->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'batch_id' => $batch->id,
            'payment_method_id' => $paymentMethod->id,
            'rrn' => $rrn,
            'invoice_id' => $invoiceId,
            'total_processing_time' => now()->diffInMilliseconds($transaction->created_at) . 'ms'
        ]);
    }

    /**
     * Process successful payment link from webhook
     */
    private function processSuccessfulPaymentLink($paymentIntent, $paymentLinkId)
    {
        Log::info('Processing successful payment link', [
            'payment_link_id' => $paymentLinkId,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'status' => $paymentIntent->status
        ]);

        $paymentLink = PaymentByLink::find($paymentLinkId);
        
        if (!$paymentLink) {
            Log::error('Payment link not found', [
                'payment_link_id' => $paymentLinkId,
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);
            return;
        }

        if ($paymentLink->status === 'completed') {
            Log::warning('Payment link already completed', [
                'payment_link_id' => $paymentLinkId,
                'current_status' => $paymentLink->status,
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);
            return;
        }

        Log::info('Payment link found and ready for processing', [
            'payment_link_id' => $paymentLinkId,
            'current_status' => $paymentLink->status,
            'current_payment_status' => $paymentLink->payment_status,
            'amount' => $paymentLink->amount,
            'currency' => $paymentLink->currency
        ]);

        // Update payment link status
        try {
            $metadata = array_merge($paymentLink->metadata ?? [], [
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_payment_method_id' => $paymentIntent->payment_method,
                'stripe_customer_id' => $paymentIntent->customer,
                'completed_at' => now()->toISOString(),
                'webhook_processed_at' => now()->toISOString()
            ]);

            $paymentLink->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'metadata' => $metadata
            ]);

            Log::info('Payment link status updated successfully', [
                'payment_link_id' => $paymentLinkId,
                'new_status' => 'completed',
                'new_payment_status' => 'paid',
                'metadata_updated' => true,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_payment_method_id' => $paymentIntent->payment_method,
                'stripe_customer_id' => $paymentIntent->customer
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update payment link status', [
                'payment_link_id' => $paymentLinkId,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        // Send success email if customer email is available
        $customerEmail = $this->getCustomerEmail($paymentIntent, $paymentLink);
        
        if ($customerEmail) {
            Log::info('Attempting to send success email', [
                'payment_link_id' => $paymentLinkId,
                'customer_email' => $customerEmail,
                'stripe_payment_intent_id' => $paymentIntent->id
            ]);

            try {
                Mail::raw('Your payment was successful. Thank you!', function ($message) use ($customerEmail) {
                    $message->to($customerEmail)
                        ->subject('Payment Successful');
                });

                Log::info('Success email sent successfully', [
                    'payment_link_id' => $paymentLinkId,
                    'customer_email' => $customerEmail,
                    'email_subject' => 'Payment Successful'
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send success email', [
                    'payment_link_id' => $paymentLinkId,
                    'customer_email' => $customerEmail,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw here as email failure shouldn't fail the entire process
            }
        } else {
            Log::warning('No customer email available for success notification', [
                'payment_link_id' => $paymentLinkId,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_customer_id' => $paymentIntent->customer
            ]);
        }

        Log::info('Payment link processing completed successfully', [
            'payment_link_id' => $paymentLinkId,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'final_status' => 'completed',
            'final_payment_status' => 'paid'
        ]);
    }

    /**
     * Get or create batch for merchant
     */
    private function getOrCreateBatch($merchantId)
    {
        // Try to find an existing pending batch for today
        $batch = Batch::where('merchant_id', $merchantId)
            ->where('status', Batch::STATUS_PENDING)
            ->whereDate('created_at', today())
            ->first();

        if (!$batch) {
            // Create new batch for today
            $batch = Batch::create([
                'merchant_id' => $merchantId,
                'status' => Batch::STATUS_PENDING,
                'batch_number' => $this->generateBatchNumber($merchantId),
                'total_amount' => 0,
                'transaction_count' => 0,
            ]);
        }

        return $batch;
    }

    /**
     * Generate unique batch number
     */
    private function generateBatchNumber($merchantId)
    {
        $prefix = 'BATCH';
        $date = now()->format('Ymd');
        $merchantCode = str_pad($merchantId, 4, '0', STR_PAD_LEFT);
        $sequence = Batch::where('merchant_id', $merchantId)
            ->whereDate('created_at', today())
            ->count() + 1;
        
        return $prefix . $date . $merchantCode . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create or update payment method from Stripe data
     */
    private function createOrUpdatePaymentMethod($paymentIntent)
    {
        // Get payment method details from Stripe
        $stripePaymentMethod = null;
        if ($paymentIntent->payment_method) {
            try {
                $stripePaymentMethod = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve Stripe payment method: ' . $e->getMessage());
            }
        }

        if (!$stripePaymentMethod) {
            // Create a basic payment method record
            return PaymentMethod::create([
                'entry_mode' => 'online',
                'card_type' => 'unknown',
                'card_brand' => 'unknown',
                'metadata' => [
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'source' => 'stripe_webhook'
                ]
            ]);
        }

        // Extract card details from Stripe payment method
        $card = $stripePaymentMethod->card ?? null;
        
        return PaymentMethod::create([
            'entry_mode' => 'online',
            'pan_token' => $card ? $this->maskCardNumber($card->last4) : null,
            'cardholder_name' => $stripePaymentMethod->billing_details->name ?? null,
            'expiry_month' => $card ? $card->exp_month : null,
            'expiry_year' => $card ? $card->exp_year : null,
            'card_type' => $card ? strtoupper($card->brand) : null,
            'card_brand' => $card ? ($card->funding === 'credit' ? 'Credit' : 'Debit') : null,
            'issuer_bank' => $card ? $card->bank_name : null,
            'cvv_present' => $stripePaymentMethod->card->checks->cvc_check === 'pass' ? 'yes' : 'no',
            'pin_present' => 'no', // Stripe doesn't provide PIN info
            'metadata' => [
                'stripe_payment_method_id' => $stripePaymentMethod->id,
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_customer_id' => $paymentIntent->customer,
                'source' => 'stripe_webhook'
            ]
        ]);
    }

    /**
     * Mask card number for security
     */
    private function maskCardNumber($last4)
    {
        return '**** **** **** ' . $last4;
    }

    /**
     * Get customer email from payment intent or payment link
     */
    private function getCustomerEmail($paymentIntent, $paymentLink)
    {
        // Try to get email from Stripe customer
        if ($paymentIntent->customer) {
            try {
                $stripeCustomer = \Stripe\Customer::retrieve($paymentIntent->customer);
                if ($stripeCustomer->email) {
                    return $stripeCustomer->email;
                }
            } catch (\Exception $e) {
                Log::error('Failed to retrieve Stripe customer: ' . $e->getMessage());
            }
        }

        // Fallback to payment link customer
        if ($paymentLink->customer_id) {
            $customer = Customer::find($paymentLink->customer_id);
            return $customer ? $customer->email : null;
        }

        return null;
    }
}
