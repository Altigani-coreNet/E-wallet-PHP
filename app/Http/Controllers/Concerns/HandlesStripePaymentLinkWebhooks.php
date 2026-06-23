<?php

namespace App\Http\Controllers\Concerns;

use App\Models\PaymentByLink;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared Stripe payment-link webhook verification and completion logic used by
 * {@see \App\Http\Controllers\Api\PaymentLinkController} and {@see \App\Http\Controllers\PaymentByLinkController}.
 *
 * @property TransactionService $transactionService
 */
trait HandlesStripePaymentLinkWebhooks
{
    /**
     * Verify Stripe signature (or parse unsigned in local dev) and return the Event,
     * or an HTTP error Response.
     *
     * @return Response|Event
     */
    protected function parseStripeWebhook(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = env('STRIPE_WEBHOOK_SECRET');

        try {
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            if ($secret) {
                $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
            } else {
                $event = \Stripe\Event::constructFrom(json_decode($payload, true));
            }
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook: invalid payload', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook: invalid signature', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return $event;
    }

    /**
     * Resolve a payment link from PaymentIntent metadata (uuid, id, or legacy transaction hop).
     */
    protected function resolvePaymentLinkFromStripeIntent(object $intent): ?PaymentByLink
    {
        $meta = $intent->metadata ?? null;

        $uuid = is_object($meta) ? ($meta->payment_link_uuid ?? null) : null;
        if (is_string($uuid) && $uuid !== '') {
            $link = PaymentByLink::where('uuid', $uuid)->first();
            if ($link) {
                return $link;
            }
        }

        $linkId = is_object($meta) ? ($meta->payment_link_id ?? null) : null;
        if ($linkId !== null && $linkId !== '') {
            $link = PaymentByLink::find($linkId);
            if ($link) {
                return $link;
            }
        }

        $transactionId = is_object($meta) ? ($meta->transaction_id ?? null) : null;
        if ($transactionId !== null && $transactionId !== '') {
            $transaction = Transaction::find($transactionId);
            $plId        = $transaction?->metadata['payment_link_id'] ?? null;
            if ($plId) {
                return PaymentByLink::find($plId);
            }
        }

        return null;
    }

    /**
     * Map Stripe PaymentIntent + PaymentMethod into the POS-shaped payload for
     * {@see TransactionService::recordStripePaymentLinkTransaction}.
     */
    protected function buildPosPayloadFromStripeIntent(PaymentByLink $link, object $intent): array
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $intentExpanded = \Stripe\PaymentIntent::retrieve([
                'id' => $intent->id,
                'expand' => ['payment_method'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('buildPosPayloadFromStripeIntent: PaymentIntent retrieve failed', [
                'intent_id' => $intent->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $intentExpanded = $intent;
        }

        $pmObj = $intentExpanded->payment_method ?? null;
        $stripePmId = is_object($pmObj) ? ($pmObj->id ?? null) : $pmObj;

        $card = null;
        $billingName = null;
        $walletChannel = null;
        $cardBrand = null;

        if ($stripePmId) {
            try {
                $pm = (is_object($pmObj) && isset($pmObj->card))
                    ? $pmObj
                    : \Stripe\PaymentMethod::retrieve($stripePmId);
                $card = $pm->card ?? null;
                $billingName = $pm->billing_details->name ?? null;
                if ($card) {
                    $cardBrand = $card->brand ?? null;
                    $wallet = $card->wallet ?? null;
                    if (is_object($wallet) && ! empty($wallet->type ?? null)) {
                        $type = (string) $wallet->type;
                        $walletChannel = match ($type) {
                            'apple_pay' => 'Apple Pay',
                            'google_pay' => 'Google Pay',
                            'samsung_pay' => 'Samsung Pay',
                            'link' => 'Link',
                            default => ucfirst(str_replace('_', ' ', $type)),
                        };
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('buildPosPayloadFromStripeIntent: PaymentMethod retrieve failed', [
                    'payment_method_id' => $stripePmId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $last4 = $card->last4 ?? null;
        $brandLabel = $cardBrand ? strtoupper((string) $cardBrand) : 'CARD';
        $panToken = $last4
            ? sprintf('%s **** %s', $brandLabel, $last4)
            : sprintf('%s **** ????', $brandLabel);

        $expMonth = ($card && ! empty($card->exp_month))
            ? str_pad((string) $card->exp_month, 2, '0', STR_PAD_LEFT)
            : '01';
        $expYear = ($card && ! empty($card->exp_year))
            ? (string) $card->exp_year
            : (string) now()->year;

        $cardholderName = $billingName ?: (($link->customer_name ?: null) ?: 'Cardholder');

        return [
            'merchant_id' => $link->merchant_id,
            'country_id' => $link->country_id,
            'terminal_id' => null,
            'user_id' => null,
            'description' => 'Payment Link Transaction',
            'transaction_metadata' => [
                'source' => 'payment_link',
                'payment_link_id' => $link->id,
                'payment_link_uuid' => $link->uuid,
                'stripe_payment_intent_id' => $intent->id,
                'stripe_charge_id' => $intent->latest_charge ?? null,
                'stripe_customer' => $intent->customer ?? null,
                'stripe_amount_received' => $intent->amount_received ?? null,
                'stripe_currency' => $intent->currency ?? null,
                'stripe_payment_method_id' => $stripePmId,
            ],
            'transactionDetails' => [
                'amount' => (float) ($link->amount ?? 0),
                'currency' => $link->currency_id,
                'timestamp' => now()->toIso8601String(),
                'transactionType' => 'sale',
            ],
            'paymentMethod' => [
                'entryMode' => 'CNP',
                'panToken' => $panToken,
                'cardholderName' => $cardholderName,
                'expiryMonth' => $expMonth,
                'expiryYear' => $expYear,
                'paymentChannel' => $walletChannel,
                'cardBrand' => $cardBrand,
            ],
            'securityData' => [
                'emvData' => null,
                'pinBlock' => null,
                'ksn' => null,
            ],
            'merchantDetails' => [
                'merchantId' => $link->merchant_id,
                'terminalId' => null,
            ],
        ];
    }

    /**
     * Create merchant transaction (once) and mark the payment link completed.
     * Used by confirm-intent, Stripe webhooks, and legacy web routes.
     */
    protected function finalizePaymentLinkFromStripeIntent(PaymentByLink $link, object $intent): void
    {
        $link->refresh();

        if (in_array($link->status, ['completed', 'canceled'], true)) {
            return;
        }

        $existingTx = Transaction::where('merchant_id', $link->merchant_id)
            ->where('transaction_type', 'payment_link')
            ->where('metadata->stripe_payment_intent_id', $intent->id)
            ->first();

        if (! $existingTx) {
            try {
                $posPayload = $this->buildPosPayloadFromStripeIntent($link, $intent);
                $this->transactionService->recordStripePaymentLinkTransaction($posPayload);
            } catch (\Throwable $e) {
                Log::error('finalizePaymentLinkFromStripeIntent: POS-style transaction record failed, falling back to minimal transaction', [
                    'payment_link_id' => $link->id,
                    'intent_id' => $intent->id ?? null,
                    'error' => $e->getMessage(),
                ]);

                $currencyObject = is_array($link->currency_object)
                    ? $link->currency_object
                    : (json_decode($link->currency_object ?? '{}', true) ?: []);

                $transactionPayload = [
                    'amount'               => (float) ($link->amount ?? 0),
                    'currency_id'          => $link->currency_id,
                    'currency_symbol'      => $currencyObject['symbol'] ?? null,
                    'currency_object'      => $currencyObject ? json_encode($currencyObject) : null,
                    'transaction_id'       => Transaction::generateTransactionId(),
                    'status'               => 'approved',
                    'transaction_type'     => 'payment_link',
                    'method'               => 'card',
                    'merchant_id'          => $link->merchant_id,
                    'description'          => 'Payment Link Transaction',
                    'transaction_datetime' => now(),
                    'sdk'                  => 'stripe_sdk',
                    'country_id'           => $link->country_id,
                    'metadata'             => [
                        'source'                   => 'payment_link',
                        'payment_link_id'          => $link->id,
                        'payment_link_uuid'        => $link->uuid,
                        'stripe_payment_intent_id' => $intent->id,
                        'stripe_charge_id'         => $intent->latest_charge ?? null,
                        'stripe_customer'          => $intent->customer ?? null,
                        'stripe_amount_received'   => $intent->amount_received ?? null,
                        'stripe_currency'          => $intent->currency ?? null,
                    ],
                ];

                $this->transactionService->createTransaction($transactionPayload);
            }
        }

        $link->update([
            'status'         => 'completed',
            'payment_status' => 'paid',
            'metadata'       => array_merge((array) ($link->metadata ?? []), [
                'stripe_payment_intent_id' => $intent->id,
                'stripe_amount_received'   => $intent->amount_received ?? null,
                'stripe_currency'          => $intent->currency ?? null,
                'paid_at'                  => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Mark payment link failed when Stripe sends payment_intent.payment_failed.
     */
    protected function markPaymentLinkFailedFromStripeIntent(object $intent): void
    {
        $link = $this->resolvePaymentLinkFromStripeIntent($intent);
        if ($link && ! in_array($link->status, ['completed', 'canceled'], true)) {
            $link->update([
                'status'         => 'failed',
                'payment_status' => 'failed',
                'metadata'       => array_merge((array) ($link->metadata ?? []), [
                    'stripe_payment_intent_id' => $intent->id,
                    'failed_at'                => now()->toISOString(),
                    'failure_message'          => $intent->last_payment_error->message ?? 'Payment failed',
                ]),
            ]);
        }
    }
}
