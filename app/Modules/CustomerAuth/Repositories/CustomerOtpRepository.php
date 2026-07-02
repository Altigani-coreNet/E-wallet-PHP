<?php

namespace App\Modules\CustomerAuth\Repositories;

use App\Models\CustomerOtp;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CustomerOtpRepository
{
    private const OTP_EXPIRY_MINUTES = 10;

    private const EMAIL_LINK_EXPIRY_HOURS = 48;

    public function store(string $identifier, string $channel): CustomerOtp
    {
        return CustomerOtp::create([
            'identifier' => $identifier,
            'channel' => $channel,
            'code' => (int) config('services.otp.mock_code', 111111),
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);
    }

    public function findValidOtp(string $token, int $code): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('code', $code)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function findValidOtpByToken(string $token): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function verifyCode(string $token, int $code): ?CustomerOtp
    {
        $mockCode = (int) config('services.otp.mock_code', 111111);
        $otp = $code === $mockCode
            ? $this->findValidOtpByToken($token)
            : $this->findValidOtp($token, $code);

        if (! $otp) {
            return null;
        }

        $otp->is_verified = true;
        $otp->save();

        return $otp->fresh();
    }

    public function findVerifiedSmsOtp(string $token, string $phone): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('identifier', $phone)
            ->where('channel', 'sms')
            ->where('is_verified', true)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function findVerifiedEmailOtp(string $token, string $email): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('identifier', $email)
            ->where('channel', 'email')
            ->where('is_verified', true)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function deleteById(int $id): void
    {
        CustomerOtp::query()->whereKey($id)->delete();
    }

    public function storeEmailVerificationLink(string $email): CustomerOtp
    {
        CustomerOtp::query()
            ->where('identifier', $email)
            ->where('channel', 'email')
            ->where('is_verified', false)
            ->delete();

        return CustomerOtp::create([
            'identifier' => $email,
            'channel' => 'email',
            'code' => (int) config('services.otp.mock_code', 111111),
            'token' => Str::random(64),
            'expires_at' => Carbon::now()->addHours(self::EMAIL_LINK_EXPIRY_HOURS),
        ]);
    }

    public function findValidEmailVerificationLink(string $token): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('channel', 'email')
            ->where('is_verified', false)
            ->where('expires_at', '>', now())
            ->first();
    }
}
