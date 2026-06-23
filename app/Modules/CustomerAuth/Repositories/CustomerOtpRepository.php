<?php

namespace App\Modules\CustomerAuth\Repositories;

use App\Models\CustomerOtp;
use Carbon\Carbon;

class CustomerOtpRepository
{
    public function store(string $identifier, string $channel): CustomerOtp
    {
        return CustomerOtp::create([
            'identifier' => $identifier,
            'channel' => $channel,
            'code' => config('customer_auth.otp_mock_code'),
            'token' => bin2hex(random_bytes(32)),
            'is_verified' => false,
            'expires_at' => Carbon::now()->addMinutes(config('customer_auth.otp_expiry_minutes')),
        ]);
    }

    public function findValidOtp(string $token, int $code): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('code', $code)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function findValidOtpByToken(string $token): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('is_verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function verifyCode(string $token, int $code): ?CustomerOtp
    {
        $mockCode = config('customer_auth.otp_mock_code');
        $otp = $code === $mockCode
            ? $this->findValidOtpByToken($token)
            : $this->findValidOtp($token, $code);

        if (!$otp) {
            return null;
        }

        $otp->update(['is_verified' => true]);

        return $otp->fresh();
    }

    public function findVerifiedSmsOtp(string $token, string $phone): ?CustomerOtp
    {
        return CustomerOtp::query()
            ->where('token', $token)
            ->where('identifier', $phone)
            ->where('channel', 'sms')
            ->where('is_verified', true)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function deleteById(int $id): void
    {
        CustomerOtp::query()->where('id', $id)->delete();
    }
}
