<?php

namespace App\Modules\CustomerAuth\Services;

use App\Mail\CustomerEmailVerificationMail;
use App\Mail\VerificationCode;
use App\Models\Customer;
use App\Modules\CustomerAuth\Repositories\CustomerOtpRepository;
use App\Modules\CustomerAuth\Support\OtpTokenCipher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerOtpService
{
    private const EMAIL_LINK_EXPIRY_HOURS = 48;

    public function __construct(
        private readonly CustomerOtpRepository $otpRepository,
    ) {}

    public function generateAndSendEmailOtp(string $email): array
    {
        $otp = $this->otpRepository->store($email, 'email');

        Mail::to($email)->send(new VerificationCode($otp->code));

        return ['token' => $otp->token];
    }

    public function generateAndSendSmsOtp(string $phone): array
    {
        $otp = $this->otpRepository->store($phone, 'sms');

        Log::info('[STUB] Sending SMS OTP', [
            'phone' => $phone,
            'code' => $otp->code,
        ]);

        return ['token' => $otp->token];
    }

    public function verifyAndResolveAccount(string $token, int $code): array
    {
        $otp = $this->otpRepository->verifyCode($token, $code);

        if (! $otp) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid or expired OTP code'
            );
        }

        $customer = $otp->channel === 'email'
            ? Customer::query()->where('email', $otp->identifier)->first()
            : Customer::query()->where('phone', $otp->identifier)->first();

        return [
            'verified' => true,
            'has_account' => $customer !== null,
            'otp_token' => OtpTokenCipher::encrypt($otp->token),
        ];
    }

    public function findVerifiedSmsOtp(string $token, string $phone): \App\Models\CustomerOtp
    {
        $otp = $this->otpRepository->findVerifiedSmsOtp($token, $phone);

        if (! $otp) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Phone not verified or OTP expired'
            );
        }

        return $otp;
    }

    public function consumeOtpById(int $id): void
    {
        $this->otpRepository->deleteById($id);
    }

    public function sendEmailVerificationOtp(Customer $customer): array
    {
        $email = trim((string) $customer->email);

        if ($email === '') {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(
                'Customer has no email address on file'
            );
        }

        return $this->generateAndSendEmailOtp($email);
    }

    public function confirmEmailVerification(Customer $customer, string $token, int $code): Customer
    {
        if ($customer->hasVerifiedEmail()) {
            return $customer;
        }

        $email = trim((string) $customer->email);

        if ($email === '') {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(
                'Customer has no email address on file'
            );
        }

        $otp = $this->otpRepository->verifyCode($token, $code);

        if (! $otp || $otp->channel !== 'email' || $otp->identifier !== $email) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid or expired OTP code'
            );
        }

        $customer->email_verified_at = now();
        $customer->save();

        $this->consumeOtpById($otp->id);

        return $customer->fresh(['country', 'city', 'wallet', 'attachments']);
    }

    public function sendEmailVerificationLink(Customer $customer): array
    {
        if ($customer->hasVerifiedEmail()) {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(
                'Customer email is already verified'
            );
        }

        $email = trim((string) $customer->email);

        if ($email === '') {
            throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(
                'Customer has no email address on file'
            );
        }

        $otp = $this->otpRepository->storeEmailVerificationLink($email);
        $verifyUrl = $this->buildEmailVerificationUrl($otp->token);

        Mail::to($email)->send(
            new CustomerEmailVerificationMail($customer, $verifyUrl, self::EMAIL_LINK_EXPIRY_HOURS)
        );

        return ['sent' => true];
    }

    public function verifyEmailByLink(string $token): Customer
    {
        $otp = $this->otpRepository->findValidEmailVerificationLink($token);

        if (! $otp) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid or expired verification link'
            );
        }

        $customer = Customer::query()->where('email', $otp->identifier)->first();

        if (! $customer) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Customer account not found for this verification link'
            );
        }

        if ($customer->hasVerifiedEmail()) {
            $this->consumeOtpById($otp->id);

            return $customer->fresh(['country', 'city', 'wallet', 'attachments']);
        }

        $otp->is_verified = true;
        $otp->save();

        $customer->email_verified_at = now();
        $customer->save();

        $this->consumeOtpById($otp->id);

        return $customer->fresh(['country', 'city', 'wallet', 'attachments']);
    }

    private function buildEmailVerificationUrl(string $plainToken): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $base.'/customer/verify-email/'.urlencode($plainToken);
    }
}
