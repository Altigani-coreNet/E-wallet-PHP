<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Models\CustomerActionOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CustomerActionOtpService
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function __construct(
        private readonly CustomerEphemeralBroadcastService $ephemeralBroadcastService,
    ) {
    }

    /**
     * @return array{otp_token: string, expires_at: string}
     */
    public function issuePasswordChange(Customer $customer, string $passwordFingerprint): array
    {
        CustomerActionOtp::query()
            ->where('customer_id', $customer->id)
            ->where('purpose', CustomerActionOtp::PURPOSE_PASSWORD_CHANGE)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->delete();

        $code = (int) config('services.otp.mock_code', 111111);
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        CustomerActionOtp::create([
            'customer_id' => $customer->id,
            'purpose' => CustomerActionOtp::PURPOSE_PASSWORD_CHANGE,
            'token' => $token,
            'code' => $code,
            'payload' => [
                'password_fingerprint' => $passwordFingerprint,
            ],
            'expires_at' => $expiresAt,
        ]);

        Log::info('[STUB] Sending password change SMS OTP', [
            'phone' => $customer->phone,
            'code' => $code,
            'customer_id' => $customer->id,
        ]);

        $this->ephemeralBroadcastService->notifyCustomer(
            (string) $customer->id,
            'Password change verification code',
            sprintf('Your password change verification code is %s.', $code),
            [
                'event_type' => 'password_change_otp',
                'otp_code' => $code,
            ],
        );

        return [
            'otp_token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function verifyPasswordChangeAndConsume(
        Customer $customer,
        string $otpToken,
        int $otpCode,
        string $passwordFingerprint,
    ): void {
        $otpToken = trim($otpToken);

        if ($otpToken === '') {
            throw new InvalidArgumentException('Invalid or expired OTP.');
        }

        $record = CustomerActionOtp::query()
            ->where('customer_id', $customer->id)
            ->where('purpose', CustomerActionOtp::PURPOSE_PASSWORD_CHANGE)
            ->where('token', $otpToken)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->lockForUpdate()
            ->first();

        if (! $record) {
            throw new InvalidArgumentException('Invalid or expired OTP.');
        }

        if (! $this->codeMatches($record, $otpCode)) {
            throw new InvalidArgumentException('Invalid or expired OTP.');
        }

        $storedFingerprint = (string) ($record->payload['password_fingerprint'] ?? '');

        if ($storedFingerprint === '' || ! hash_equals($storedFingerprint, $passwordFingerprint)) {
            throw new InvalidArgumentException('OTP does not match this password change request.');
        }

        $record->update(['consumed_at' => now()]);
    }

    public static function fingerprintPassword(string $password): string
    {
        return hash_hmac('sha256', $password, (string) config('app.key'));
    }

    private function codeMatches(CustomerActionOtp $record, int $otpCode): bool
    {
        $mockCode = (int) config('services.otp.mock_code', 111111);

        if ($otpCode === $mockCode) {
            return true;
        }

        return (int) $record->code === $otpCode;
    }
}
