<?php

namespace App\Jobs;

use App\Mail\MerchantRegistrationConfirmationMail;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessMerchantPostRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The merchant instance.
     *
     * @var \App\Models\Merchant
     */
    protected $merchant;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Merchant $merchant
     */
    public function __construct(User $user, Merchant $merchant)
    {
        $this->user = $user;
        $this->merchant = $merchant;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Send merchant registration confirmation email
        try {
            Mail::to($this->user->email)->send(
                new MerchantRegistrationConfirmationMail($this->user, $this->merchant)
            );
        } catch (\Throwable $mailError) {
            Log::warning('Failed to send MerchantRegistrationConfirmationMail from job', [
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'error' => $mailError->getMessage(),
            ]);
        }

        // Call POS service to setup merchant configuration
        try {
            $posServiceUrl = config('services.pos_service_url');
            $webhookSecret = config('services.webhook_secret', env('WEBHOOK_SECRET'));

            if (!$posServiceUrl) {
                Log::warning('POS service URL not configured, skipping merchant configuration setup from job', [
                    'merchant_id' => $this->merchant->id,
                ]);
                return;
            }

            $configureUrl = rtrim($posServiceUrl, '/') . '/v1/merchant/configure';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Webhook-Secret' => $webhookSecret,
                ])
                ->post($configureUrl, [
                    'merchant_id' => $this->merchant->id,
                ]);

            if ($response->successful()) {
                Log::info('Merchant configuration setup successful from job', [
                    'merchant_id' => $this->merchant->id,
                    'response' => $response->json(),
                ]);
            } else {
                Log::warning('Merchant configuration setup failed from job', [
                    'merchant_id' => $this->merchant->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to setup merchant configuration in POS service from job', [
                'merchant_id' => $this->merchant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

