<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Models\WalletTransferOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WalletTransferOtpService
{
    private const OTP_EXPIRY_MINUTES = 10;

    public function __construct(
        private readonly CustomerEphemeralBroadcastService $ephemeralBroadcastService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{otp_token: string, expires_at: string}
     */
    public function issue(Customer $customer, array $payload): array
    {
        $payload = self::normalizePayload($payload);

        if ($payload['idempotency_key'] !== null) {
            WalletTransferOtp::query()
                ->where('customer_id', $customer->id)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->where('payload->idempotency_key', $payload['idempotency_key'])
                ->delete();
        }

        $code = (int) config('services.otp.mock_code', 111111);
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        WalletTransferOtp::create([
            'customer_id' => $customer->id,
            'token' => $token,
            'code' => $code,
            'payload' => $payload,
            'expires_at' => $expiresAt,
        ]);

        Log::info('[STUB] Sending wallet transfer SMS OTP', [
            'phone' => $customer->phone,
            'code' => $code,
            'recipient_wallet_id' => $payload['recipient_wallet_id'],
            'amount' => $payload['amount'],
        ]);

        $this->ephemeralBroadcastService->notifyCustomer(
            (string) $customer->id,
            'Transfer verification code',
            sprintf(
                'Your transfer verification code is %s for %s to %s.',
                $code,
                number_format($payload['amount'], 2, '.', ''),
                $payload['recipient_wallet_id'],
            ),
            [
                'event_type' => 'transfer_otp',
                'otp_code' => $code,
                'amount' => $payload['amount'],
                'recipient_wallet_id' => $payload['recipient_wallet_id'],
                'description' => $payload['description'],
                'note' => $payload['note'],
            ],
        );

        return [
            'otp_token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verifyAndConsume(Customer $customer, string $otpToken, int $otpCode, array $payload): void
    {
        $payload = self::normalizePayload($payload);
        $otpToken = trim($otpToken);

        if ($otpToken === '') {
            throw new InvalidArgumentException('Invalid or expired OTP.');
        }

        $record = WalletTransferOtp::query()
            ->where('customer_id', $customer->id)
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

        if (! self::payloadsMatch($record->payload ?? [], $payload)) {
            throw new InvalidArgumentException('OTP does not match this transfer.');
        }

        $record->update(['consumed_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     recipient_wallet_id: string,
     *     amount: float,
     *     description: ?string,
     *     note: ?string,
     *     idempotency_key: ?string
     * }
     */
    public static function normalizePayload(array $payload): array
    {
        $description = isset($payload['description']) ? trim((string) $payload['description']) : null;
        $note = isset($payload['note']) ? trim((string) $payload['note']) : null;
        $idempotencyKey = isset($payload['idempotency_key']) ? trim((string) $payload['idempotency_key']) : null;

        return [
            'recipient_wallet_id' => trim((string) ($payload['recipient_wallet_id'] ?? '')),
            'amount' => round((float) ($payload['amount'] ?? 0), 2),
            'description' => $description !== '' ? $description : null,
            'note' => $note !== '' ? $note : null,
            'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $incoming
     */
    public static function payloadsMatch(array $stored, array $incoming): bool
    {
        $stored = self::normalizePayload($stored);
        $incoming = self::normalizePayload($incoming);

        return $stored === $incoming;
    }

    private function codeMatches(WalletTransferOtp $record, int $otpCode): bool
    {
        $mockCode = (int) config('services.otp.mock_code', 111111);

        if ($otpCode === $mockCode) {
            return true;
        }

        return (int) $record->code === $otpCode;
    }
}
