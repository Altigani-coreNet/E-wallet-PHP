<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripePaymentLinkWebhooks;
use App\Models\PaymentByLink;
use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Models\Customer;
use App\Models\Batch;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use App\Services\PaymentByLinkService;
use App\Services\TransactionService;
use App\Http\Requests\Api\PosTransactionRequest;
use App\Traits\MessageManager;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Mail\PaymentMail;
use Symfony\Component\HttpFoundation\Response;

class PaymentByLinkController extends Controller
{
    use HandlesStripePaymentLinkWebhooks;
    use MessageManager;

    protected $paymentByLinkService;
    protected $transactionService;

    public function __construct(PaymentByLinkService $paymentByLinkService, TransactionService $transactionService)
    {
        $this->paymentByLinkService = $paymentByLinkService;
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)  
    {
        return view('admin.payment-links.index');
    }

    public function pay($uuid)
    {
        $link = $this->paymentByLinkService->findByUuid($uuid);
        // dd();
        if ($link->expired_date && now()->isAfter($link->expired_date)) {
            $link->update(['status' => 'expired']);
            return redirect()->route('payments.error', $uuid);
        }else{
            return redirect($link->link);
        };


        // return view('payment-links.pay', compact('link'));
    }

    public function create()
    {
        return view('admin.payment-links.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'payment_method_types' => 'required|array',
            'customer_id' => 'required|exists:customers,id',
            'scheduled_date' => 'nullable|date',
            'expired_date' => 'nullable|date|after:now',
        ]);

       try {
        $link = $this->paymentByLinkService->store($request);

        $this->successMessage('Payment link created successfully');

        return redirect()->route('admin.payment-links.index');

       }catch(\Exception $e){
        // dd($e->getMessage());
         $this->ErrorMessage($e->getMessage());

         return redirect()->back();
       }

        // return response()->json(['success' => true, 'data' => $link], 201);
    }

    public function show($id)
    {
        $link = $this->paymentByLinkService->show($id);
        return response()->json(['success' => true, 'data' => $link]);
    }

    public function showByUuid($uuid)
    {
        try {
            $link = $this->paymentByLinkService->findByUuid($uuid);
            return response()->json(['success' => true, 'data' => $link]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Payment link not found'], 404);
        }
    }

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

    public function edit(PaymentByLink $paymentLink)
    {
        return view('admin.payment-links.edit', compact('paymentLink'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'payment_method_types' => 'required|array',
            'customer_id' => 'required|exists:customers,id',
            'scheduled_date' => 'nullable|date',
            'expired_date' => 'nullable|date|after:now',
        ]);
        try {
            $link = $this->paymentByLinkService->update($request, $id);
            $this->successMessage('Payment link updated successfully');
            return redirect()->route('admin.payment-links.index');
        } catch (\Exception $e) {
            $this->errorMessage('Payment link update failed');
            return redirect()->route('admin.payment-links.index');
        }
    }

    public function destroy($id)
    {
        $this->paymentByLinkService->destroy($id);
        $this->successMessage('Payment link deleted successfully');
        return redirect()->route('admin.payment-links.index');
        // return response()->json(['success' => true, 'message' => 'Payment link deleted successfully']);
    }

    public function updateDate(Request $request, $payment_link)
    {
        $request->validate([
            'scheduled_date' => 'required|date',
        ]);
        $link = PaymentByLink::findOrFail($payment_link);
        $link->scheduled_date = $request->scheduled_date;
        $link->status = 'scheduled';
        $link->save();
        return response()->json(['success' => true, 'message' => 'Payment link rescheduled successfully']);
    }

    public function data(Request $request)
    {
        $query = PaymentByLink::with(['merchant', 'country', 'customer', 'currency']);

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Created at range
        if ($request->filled('created_from') && $request->filled('created_to')) {
            $query->whereBetween('created_at', [
                $request->date('created_from')->startOfDay(),
                $request->date('created_to')->endOfDay(),
            ]);
        } elseif ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->date('created_from')->startOfDay());
        } elseif ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->date('created_to')->endOfDay());
        }

        // Updated at range
        if ($request->filled('updated_from') && $request->filled('updated_to')) {
            $query->whereBetween('updated_at', [
                $request->date('updated_from')->startOfDay(),
                $request->date('updated_to')->endOfDay(),
            ]);
        } elseif ($request->filled('updated_from')) {
            $query->where('updated_at', '>=', $request->date('updated_from')->startOfDay());
        } elseif ($request->filled('updated_to')) {
            $query->where('updated_at', '<=', $request->date('updated_to')->endOfDay());
        }

        // Text search (supports both DataTables search[value] and custom search string)
        $textSearch = is_array($request->search) ? ($request->search['value'] ?? null) : $request->get('search');
        if (!empty($textSearch)) {
            $query->where(function ($q) use ($textSearch) {
                $q->where('uuid', 'like', "%{$textSearch}%")
                  ->orWhere('amount', 'like', "%{$textSearch}%")
                  ->orWhere('status', 'like', "%{$textSearch}%")
                  ->orWhereHas('merchant', function ($mq) use ($textSearch) {
                      $mq->where('name', 'like', "%{$textSearch}%");
                  })
                  ->orWhereHas('customer', function ($cq) use ($textSearch) {
                      $cq->where('name', 'like', "%{$textSearch}%");
                  });
            });
        }

        return DataTables::of($query)
                ->addColumn('merchant', function($row) {
                    return $row->merchant ? $row->merchant->name : '';
                })
                ->addColumn('customer', function($row) {
                    return $row->customer ? $row->customer->name : '';
                })
                ->addColumn('currency', function($row) {
                    return $row->currency ? $row->currency->currency_code : '';
                })
                ->editColumn('amount', function($row) {
                    return $row->currency ? $row->currency?->symbol . ' ' . $row->amount : '';
                })
                ->editColumn('status', fn($link) => $link->getStatusSpan())
                ->editColumn('created_at', fn($link) => $link->created_at->format('d-m-Y H:i:s'))
                ->editColumn('scheduled_date', fn($link) => $link->scheduled_date ? Carbon::parse($link->scheduled_date)->diffForHumans() : '')
                ->addColumn('country', function($row) {
                    return $row->country ? $row->country->name : 'N/A';
                })
            ->addColumn('actions', function($row) {
                $showUrl = route('admin.payment-links.show', $row->id);
                $editUrl = route('admin.payment-links.edit', $row->id);
                $deleteUrl = route('admin.payment-links.destroy', $row->id);
                return view('admin.payment-links.partials.actions', compact('showUrl','row',  'editUrl', 'deleteUrl'))->render();
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

    public function export(Request $request)
    {
        $query = PaymentByLink::with(['merchant', 'country', 'customer', 'currency']);

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->filled('created_from') && $request->filled('created_to')) {
            $query->whereBetween('created_at', [
                $request->date('created_from')->startOfDay(),
                $request->date('created_to')->endOfDay(),
            ]);
        } elseif ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->date('created_from')->startOfDay());
        } elseif ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->date('created_to')->endOfDay());
        }

        if ($request->filled('updated_from') && $request->filled('updated_to')) {
            $query->whereBetween('updated_at', [
                $request->date('updated_from')->startOfDay(),
                $request->date('updated_to')->endOfDay(),
            ]);
        } elseif ($request->filled('updated_from')) {
            $query->where('updated_at', '>=', $request->date('updated_from')->startOfDay());
        } elseif ($request->filled('updated_to')) {
            $query->where('updated_at', '<=', $request->date('updated_to')->endOfDay());
        }

        $textSearch = $request->get('search');
        if (!empty($textSearch)) {
            $query->where(function ($q) use ($textSearch) {
                $q->where('uuid', 'like', "%{$textSearch}%")
                  ->orWhere('amount', 'like', "%{$textSearch}%")
                  ->orWhere('status', 'like', "%{$textSearch}%")
                  ->orWhereHas('merchant', function ($mq) use ($textSearch) {
                      $mq->where('name', 'like', "%{$textSearch}%");
                  })
                  ->orWhereHas('customer', function ($cq) use ($textSearch) {
                      $cq->where('name', 'like', "%{$textSearch}%");
                  });
            });
        }

        $fileName = 'payment_links_export_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'UUID', 'Merchant', 'Customer', 'Amount', 'Currency', 'Status', 'Created At', 'Scheduled Date', 'Country']);
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
                        optional($row->country)->name,
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
     * Generate a Stripe payment link based on form fields (mock implementation)
     */
    public function generateUrl(Request $request)
    {
        // Here you would use the Stripe API to generate a payment link using the form data
        // For now, just return a mock URL with query params for demonstration
        $params = http_build_query($request->all());
        $demoUrl = 'https://checkout.stripe.com/pay/demo?' . $params;
        return response()->json(['url' => $demoUrl]);
    }

    /**
     * Generate a Stripe Checkout Session URL for a one-time payment
     * Expects 'price' and 'currency' in the request
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
     * (same verification + link finalization as {@see \App\Http\Controllers\Api\PaymentLinkController::handleWebhook}).
     */
    public function handleWebhook(Request $request)
    {
        try {
            $parsed = $this->parseStripeWebhook($request);
            if ($parsed instanceof Response) {
                return $parsed;
            }
            $event = $parsed;

            Log::info('Stripe webhook received object', ['event' => $event->data->object]);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->markPaymentLinkFailedFromStripeIntent($event->data->object);
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.succeeded':
                    $this->handleChargeSucceeded($event->data->object);
                    break;

                case 'charge.failed':
                    $this->handleChargeFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event: ' . $event->type, [
                        'event_id' => $event->id,
                        'event_type' => $event->type,
                    ]);
                    break;
            }

            return response()->json(['success' => true, 'message' => 'Webhook handled'], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Handle successful payment intent (shared finalization with API webhook via trait).
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $link = $this->resolvePaymentLinkFromStripeIntent($paymentIntent);

        if ($link) {
            $beforeStatus = $link->status;
            $this->finalizePaymentLinkFromStripeIntent($link, $paymentIntent);

            if (! in_array($beforeStatus, ['completed', 'canceled'], true)) {
                Log::info('Payment link webhook: finalized payment link', [
                    'payment_link_id' => $link->id,
                    'stripe_payment_intent_id' => $paymentIntent->id ?? null,
                ]);

                $link->refresh();
                $customerEmail = $this->getCustomerEmail($paymentIntent, $link);

                if ($customerEmail) {
                    Log::info('Attempting to send success email', [
                        'payment_link_id' => $link->id,
                        'customer_email' => $customerEmail,
                        'stripe_payment_intent_id' => $paymentIntent->id ?? null,
                    ]);

                    try {
                        Mail::raw('Your payment was successful. Thank you!', function ($message) use ($customerEmail) {
                            $message->to($customerEmail)
                                ->subject('Payment Successful');
                        });

                        Log::info('Success email sent successfully', [
                            'payment_link_id' => $link->id,
                            'customer_email' => $customerEmail,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send success email', [
                            'payment_link_id' => $link->id,
                            'customer_email' => $customerEmail,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::warning('No customer email available for success notification', [
                        'payment_link_id' => $link->id,
                        'stripe_payment_intent_id' => $paymentIntent->id ?? null,
                    ]);
                }
            }
        } else {
            Log::warning('payment_intent.succeeded without resolvable payment link', [
                'stripe_payment_intent_id' => $paymentIntent->id ?? null,
                'metadata' => (array) ($paymentIntent->metadata ?? []),
            ]);
        }

        $this->notifyPosSalePayment($paymentIntent);
    }

    /**
     * Notify POS application that a payment link was paid so it can update the sale.
     */
    private function notifyPosSalePayment($paymentIntent): void
    {
        try {
            $saleId = $paymentIntent->metadata->sale_id ?? null;
            if (!$saleId) {
                Log::warning('Skipping POS sale update: sale_id missing from payment intent metadata', [
                    'payment_intent' => $paymentIntent->id ?? null,
                ]);
                return;
            }

            $posBaseUrl = rtrim(config('services.pos_service_url'), '/');
            if (empty($posBaseUrl)) {
                Log::warning('Skipping POS sale update: pos_service_url not configured');
                return;
            }

            $webhookSecret = config('services.webhook_secret', env('WEBHOOK_SECRET'));

            $payload = [
                'sale_id' => (int) $saleId,
                'paid_amount' => ($paymentIntent->amount_received ?? 0) / 100,
                'payment_status' => 1, // mark as paid
                'payment_method' => $paymentIntent->metadata->payment_method ?? 'Payment Link',
                'currency' => $paymentIntent->currency ?? null,
                'payment_link_id' => $paymentIntent->metadata->payment_link_id ?? null,
                'transaction_id' => $paymentIntent->metadata->transaction_id ?? null,
                'stripe_payment_intent_id' => $paymentIntent->id ?? null,
                'stripe_charge_id' => $paymentIntent->latest_charge ?? null,
                'payment_metadata' => [
                    'source' => $paymentIntent->metadata->source ?? null,
                    'sale_type' => $paymentIntent->metadata->sale_type ?? null,
                    'created_at' => $paymentIntent->metadata->created_at ?? null,
                ],
            ];

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Webhook-Secret' => $webhookSecret,
            ])
            ->timeout(15)
            ->post($posBaseUrl . '/webhooks/payment-link/paid', $payload);

            if (!$response->successful()) {
                Log::error('POS sale update failed', [
                    'sale_id' => $saleId,
                    'payment_intent' => $paymentIntent->id ?? null,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            } else {
                Log::info('POS sale updated successfully after payment link payment', [
                    'sale_id' => $saleId,
                    'payment_intent' => $paymentIntent->id ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error notifying POS about paid payment link', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_intent' => $paymentIntent->id ?? null,
            ]);
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
        // This event is usually handled by payment_intent.succeeded
        // But we can log it for additional tracking
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
        \Log::info('Starting to process successful transaction', [
            'transaction_id' => $transactionId,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100, // Convert from cents
            'currency' => $paymentIntent->currency
        ]);

        $transaction = Transaction::find($transactionId);
        if (!$transaction || $transaction->status !== 'pending') {
            \Log::warning('Transaction not found or not in pending status', [
                'transaction_id' => $transactionId,
                'transaction_exists' => $transaction ? true : false,
                'status' => $transaction ? $transaction->status : 'not_found'
            ]);
            return;
        }

        \Log::info('Transaction found and valid for processing', [
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
                
                \Log::info('Charge data retrieved', [
                    'charge_id' => $charge->id,
                    'rrn' => $rrn,
                    'invoice_id' => $invoiceId
                ]);
            } catch (\Exception $e) {
                \Log::error('Error retrieving charge data: ' . $e->getMessage());
            }
        }
        
        // Check PaymentIntent for invoice if not found in charge
        if (!$invoiceId && $paymentIntent->invoice) {
            $invoiceId = $paymentIntent->invoice;
        }

        // Get or create batch for the merchant
        $batch = $this->getOrCreateBatch($transaction->merchant_id);
        \Log::info('Batch retrieved/created for merchant', [
            'batch_id' => $batch->id,
            'merchant_id' => $transaction->merchant_id,
            'batch_status' => $batch->status ?? 'N/A'
        ]);
        
        // Create or update payment method
        $paymentMethod = $this->createOrUpdatePaymentMethod($paymentIntent);
        \Log::info('Payment method processed', [
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

        \Log::info('Transaction updated successfully with RRN and Invoice ID', [
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
        \Log::info('Batch totals updated', [
            'batch_id' => $batch->id,
            'merchant_id' => $transaction->merchant_id
        ]);
        
        // // Log the successful payment
        // TransactionLog::logApproval(
        //     $transaction->id,
        //     $transaction->amount,
        //     'Payment completed via Stripe payment link',
        //     [
        //         'stripe_payment_intent_id' => $paymentIntent->id,
        //         'stripe_payment_method_id' => $paymentIntent->payment_method,
        //         'batch_id' => $batch->id,
        //         'payment_method_id' => $paymentMethod->id,
        //         'payment_method' => 'stripe',
        //         'rrn' => $rrn,
        //         'invoice_id' => $invoiceId
        //     ]
        // );

        \Log::info('Transaction processing completed successfully', [
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

    public function send(Request $request, $payment_link)
    {
        $request->validate([
            'send_email' => 'nullable|boolean',
            'send_whatsapp' => 'nullable|boolean',
        ]);
        $link = PaymentByLink::findOrFail($payment_link);
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
            // Simulate sending WhatsApp (implement actual logic as needed)
        }
        if (empty($methods)) {
            return response()->json(['success' => false, 'message' => 'No sending method selected.'], 400);
        }
        $msg = 'Payment link sent via: ' . implode(' and ', $methods);
        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function success()
    {
        return view('payments.success');
    }

    public function error()
    {
        return view('payments.error');
    }
}
