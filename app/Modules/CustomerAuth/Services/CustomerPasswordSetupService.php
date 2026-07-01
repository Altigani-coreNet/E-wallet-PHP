<?php

namespace App\Modules\CustomerAuth\Services;

use App\Mail\CustomerSetPasswordMail;
use App\Models\Customer;
use App\Models\CustomerPasswordSetupToken;
use App\Services\PasswordResetLinkService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerPasswordSetupService
{
    private const TOKEN_TTL_HOURS = 48;

    /**
     * Generate a one-time set-password token for the customer and deliver the
     * invite link via email and SMS. Failures are logged, never thrown, so
     * customer creation is not blocked by a delivery problem.
     */
    public function generateAndSend(Customer $customer): void
    {
        if (! $customer->email && ! $customer->phone) {
            return;
        }

        $plainToken = $this->issueToken($customer);
        $setupUrl = $this->buildSetupUrl($plainToken);

        if ($customer->email) {
            $this->sendEmail($customer, $setupUrl);
        }

        if ($customer->phone) {
            $this->sendSms($customer, $setupUrl);
        }
    }

    /**
     * Issue (or re-issue) a token, invalidating any previous unused tokens.
     */
    public function issueToken(Customer $customer): string
    {
        CustomerPasswordSetupToken::query()
            ->where('customer_id', $customer->id)
            ->whereNull('used_at')
            ->delete();

        $plainToken = Str::random(64);

        CustomerPasswordSetupToken::create([
            'customer_id' => $customer->id,
            'token' => PasswordResetLinkService::hashForStorage($plainToken),
            'expires_at' => now()->addHours(self::TOKEN_TTL_HOURS),
        ]);

        return $plainToken;
    }

    public function findActiveByPlainToken(string $plainToken): ?CustomerPasswordSetupToken
    {
        $plain = PasswordResetLinkService::normalizePlainToken($plainToken);
        if ($plain === '') {
            return null;
        }

        $hash = PasswordResetLinkService::hashForStorage($plain);

        $token = CustomerPasswordSetupToken::query()
            ->where('token', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->first();

        return $token;
    }

    /**
     * Set the customer's password using a valid token. Activates the account
     * if it was pending so the customer can log in immediately.
     */
    public function setPassword(string $plainToken, string $password): Customer
    {
        $token = $this->findActiveByPlainToken($plainToken);

        if (! $token) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid or expired token'
            );
        }

        $customer = $token->customer;

        if (! $customer) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Customer account not found'
            );
        }

        $updates = ['password' => Hash::make($password)];

        if ($customer->status === Customer::STATUS_PENDING) {
            $updates['status'] = Customer::STATUS_ACTIVE;
        }

        if ($customer->status === Customer::STATUS_REJECTED) {
            unset($updates['status']);
        }

        $customer->update($updates);

        $token->forceFill(['used_at' => now()])->save();

        return $customer->fresh();
    }

    private function buildSetupUrl(string $plainToken): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base . '/customer/set-password/' . urlencode($plainToken);
    }

    private function sendEmail(Customer $customer, string $setupUrl): void
    {
        try {
            Mail::to($customer->email)->send(
                new CustomerSetPasswordMail($customer, $setupUrl, self::TOKEN_TTL_HOURS)
            );
        } catch (\Throwable $exception) {
            Log::error('Customer set-password email failed', [
                'customer_id' => $customer->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendSms(Customer $customer, string $setupUrl): void
    {
        // SMS provider is not yet wired; log via the existing stub pattern so
        // delivery starts working automatically once a real sender is added.
        Log::info('[STUB] Sending set-password SMS', [
            'phone' => $customer->phone,
            'url' => $setupUrl,
        ]);
    }
}
