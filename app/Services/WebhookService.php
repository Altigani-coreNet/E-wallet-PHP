<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookEvent;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Trigger webhook for a specific event
     *
     * @param string $eventName e.g., "transaction.created"
     * @param string $merchantId
     * @param array $payload Data to send in webhook
     * @return void
     */
    public function trigger(string $eventName, string $merchantId, array $payload): void
    {
        try {
            // Find the event
            $event = WebhookEvent::where('name', $eventName)
                ->where('is_active', true)
                ->first();

            if (!$event) {
                Log::warning('Webhook event not found', ['event' => $eventName]);
                return;
            }

            // Find all active webhooks for this merchant that are subscribed to this event
            $webhooks = Webhook::where('merchant_id', $merchantId)
                ->where('is_active', true)
                ->whereHas('events', function ($query) use ($event) {
                    $query->where('webhook_event_id', $event->id);
                })
                ->get();

            if ($webhooks->isEmpty()) {
                Log::info('No active webhooks found for event', [
                    'event' => $eventName,
                    'merchant_id' => $merchantId
                ]);
                return;
            }

            // Prepare the webhook payload
            $webhookPayload = [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'event' => $eventName,
                'created_at' => now()->toIso8601String(),
                'data' => $payload,
            ];

            // Trigger each webhook
            foreach ($webhooks as $webhook) {
                $this->sendWebhook($webhook, $event, $webhookPayload);
            }

        } catch (\Exception $e) {
            Log::error('Failed to trigger webhook', [
                'event' => $eventName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send webhook to endpoint
     *
     * @param Webhook $webhook
     * @param WebhookEvent $event
     * @param array $payload
     * @return void
     */
    protected function sendWebhook(Webhook $webhook, WebhookEvent $event, array $payload): void
    {
        try {
            // Add signature to payload
            $timestamp = time();
            $signedPayload = $this->signPayload($payload, $webhook->secret, $timestamp);

            Log::info('Sending webhook', [
                'webhook_id' => $webhook->id,
                'endpoint' => $webhook->endpoint_url,
                'event' => $event->name,
            ]);

            // Send HTTP POST request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signedPayload['signature'],
                    'X-Webhook-Timestamp' => $timestamp,
                    'X-Webhook-Event' => $event->name,
                ])
                ->post($webhook->endpoint_url, $payload);

            $statusCode = $response->status();
            $isSuccess = $response->successful();

            // Log the webhook attempt
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'webhook_event_id' => $event->id,
                'event_name' => $event->name,
                'payload' => json_encode($payload),
                'status' => $isSuccess ? 'success' : 'failed',
                'http_status_code' => $statusCode,
                'response' => $response->body(),
                'error_message' => $isSuccess ? null : 'HTTP ' . $statusCode,
            ]);

            // Update webhook statistics
            if ($isSuccess) {
                $webhook->incrementSuccess();
            } else {
                $webhook->incrementFailure();
            }

            Log::info('Webhook sent', [
                'webhook_id' => $webhook->id,
                'status' => $isSuccess ? 'success' : 'failed',
                'http_status' => $statusCode,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Connection error - schedule retry
            $this->handleWebhookFailure($webhook, $event, $payload, 'Connection error: ' . $e->getMessage());

        } catch (\Exception $e) {
            // Other errors
            $this->handleWebhookFailure($webhook, $event, $payload, 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle webhook failure and schedule retry
     *
     * @param Webhook $webhook
     * @param WebhookEvent $event
     * @param array $payload
     * @param string $errorMessage
     * @return void
     */
    protected function handleWebhookFailure(Webhook $webhook, WebhookEvent $event, array $payload, string $errorMessage): void
    {
        Log::error('Webhook failed', [
            'webhook_id' => $webhook->id,
            'endpoint' => $webhook->endpoint_url,
            'error' => $errorMessage,
        ]);

        // Create log entry with retry scheduled
        WebhookLog::create([
            'webhook_id' => $webhook->id,
            'webhook_event_id' => $event->id,
            'event_name' => $event->name,
            'payload' => json_encode($payload),
            'status' => 'failed',
            'http_status_code' => null,
            'response' => null,
            'error_message' => $errorMessage,
            'retry_count' => 0,
            'next_retry_at' => now()->addMinutes(5), // Retry in 5 minutes
        ]);

        $webhook->incrementFailure();
    }

    /**
     * Sign the webhook payload
     *
     * @param array $payload
     * @param string $secret
     * @param int $timestamp
     * @return array
     */
    protected function signPayload(array $payload, string $secret, int $timestamp): array
    {
        $payloadString = json_encode($payload);
        $signedPayload = $timestamp . '.' . $payloadString;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return [
            'signature' => $signature,
            'payload' => $payloadString,
        ];
    }

    /**
     * Verify webhook signature (for receiving webhooks from external sources)
     *
     * @param string $payload
     * @param string $signature
     * @param string $timestamp
     * @param string $secret
     * @return bool
     */
    public function verifySignature(string $payload, string $signature, string $timestamp, string $secret): bool
    {
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Retry failed webhooks
     *
     * @return void
     */
    public function retryFailedWebhooks(): void
    {
        // Get logs that are pending retry
        $failedLogs = WebhookLog::pendingRetry()
            ->where('retry_count', '<', 3) // Max 3 retries
            ->with(['webhook', 'webhookEvent'])
            ->get();

        foreach ($failedLogs as $log) {
            if (!$log->webhook || !$log->webhook->is_active) {
                continue;
            }

            // Attempt to resend
            $payload = json_decode($log->payload, true);
            $this->sendWebhook($log->webhook, $log->webhookEvent, $payload);

            // Update retry count
            $log->increment('retry_count');
            
            // Schedule next retry (exponential backoff)
            if ($log->retry_count < 3) {
                $nextRetry = now()->addMinutes(5 * pow(2, $log->retry_count)); // 5, 10, 20 minutes
                $log->update(['next_retry_at' => $nextRetry]);
            }
        }
    }
}

