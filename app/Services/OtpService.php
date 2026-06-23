<?php

namespace App\Services;

use App\Repositories\OtpRepository;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCode;
use Illuminate\Support\Facades\Log;

class OtpService
{
    
    protected $otpRepository;

    public function __construct(OtpRepository $otpRepository)
    {
        $this->otpRepository = $otpRepository;
    }

    public function generateAndSendEmailOtp($email)
    {
        // Store and get OTP details
        $otp = $this->otpRepository->store([
            'email' => $email,
            'type' => 'email'
        ]);

        // Send email
        Mail::to($email)->send(new VerificationCode($otp->code));

        return [
            'token' => $otp->token,
            'code' => $otp->code // Remove this in production, only for testing
        ];
    }

    public function generateEmailOtpWithoutSending(string $email): array
    {
        $otp = $this->otpRepository->store([
            'email' => $email,
            'type' => 'email'
        ]);

        return [
            'token' => $otp->token,
            'code' => $otp->code // Remove in production
        ];
    }

    public function generateAndSendSmsOtp($phone)
    {
        // Store and get OTP details
        $otp = $this->otpRepository->store([
            'phone' => $phone,
            'type' => 'phone'
        ]);

        // Send SMS using your SMS service
        try {
         $this->sendSmsm($phone,$otp->code);

        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
            throw $e;
        }

        return [
            'token' => $otp->token,
            'code' => $otp->code // Remove this in production, only for testing
        ];
    }

    public function verifyCode($token, $code)
    {
        return $this->otpRepository->verifyCode($token, $code);
    }

    public function findValidOtp(string $token, string $code): ?\App\Models\UsersOtp
    {
        return $this->otpRepository->findValidOtp($token, $code);
    }

    public function consumeOtpById(int $id): void
    {
        $this->otpRepository->deleteById($id);
    }

    public function findValidOtpByToken(string $token): ?\App\Models\UsersOtp
    {
        return $this->otpRepository->findValidOtpByToken($token);
    }

    public function sendSmsm($phone, $code)
    {
        return 'done';
    }
}