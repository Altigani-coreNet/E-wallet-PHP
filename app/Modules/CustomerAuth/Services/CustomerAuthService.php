<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Modules\CustomerAuth\Support\OtpTokenCipher;
use Illuminate\Support\Facades\Hash;

class CustomerAuthService
{
    private const DEFAULT_COUNTRY_DIAL_CODE = '249';

    public function __construct(
        private readonly CustomerOtpService $otpService,
        private readonly CustomerJwtService $jwtService,
    ) {}

    public function register(array $data): array
    {
        if ($data['password'] !== $data['password_confirmation']) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Password confirmation does not match'
            );
        }

        try {
            $rawOtpToken = OtpTokenCipher::decrypt($data['otp_token']);
        } catch (\Throwable) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid OTP token'
            );
        }

        $otp = $this->otpService->findVerifiedSmsOtp($rawOtpToken, $data['phone']);

        if (Customer::query()->where('phone', $data['phone'])->exists()) {
            throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                'A customer with this phone already exists'
            );
        }

        $customer = Customer::create([
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'profile_completed' => false,
            'name' => '',
            'email' => '',
        ]);

        $this->otpService->consumeOtpById($otp->id);

        $customer->load(['country', 'city']);

        return $this->buildAuthResponse($customer);
    }

    public function login(array $data): array
    {
        $customer = Customer::query()->where('phone', $data['phone'])->first();

        if (! $customer || ! $customer->password || ! Hash::check($data['password'], $customer->password)) {
            throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException(
                'Bearer',
                'Invalid credentials'
            );
        }

        $customer->load(['country', 'city']);

        return $this->buildAuthResponse($customer);
    }

    public function forgotPassword(string $phone): array
    {
        // Always issue an OTP token — do not reveal whether the account exists.
        return $this->otpService->generateAndSendSmsOtp($phone);
    }

    public function resetPassword(array $data): array
    {
        if ($data['password'] !== $data['password_confirmation']) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Password confirmation does not match'
            );
        }

        try {
            $rawOtpToken = OtpTokenCipher::decrypt($data['otp_token']);
        } catch (\Throwable) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid OTP token'
            );
        }

        $otp = $this->otpService->findVerifiedSmsOtp($rawOtpToken, $data['phone']);

        $customer = Customer::query()->where('phone', $data['phone'])->first();

        if (! $customer) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'No customer account found for this phone number'
            );
        }

        $customer->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->otpService->consumeOtpById($otp->id);

        $customer->load(['country', 'city']);

        return $this->buildAuthResponse($customer->fresh(['country', 'city']));
    }

    public function logout(): array
    {
        return ['message' => 'Logged out successfully'];
    }

    public function profile(Customer $customer): array
    {
        $customer->load(['country', 'city']);

        return [
            'profile_completed' => $customer->profile_completed,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ];
    }

    public function completeProfile(Customer $customer, array $data): array
    {
        $dialCode = $this->normalizeDialCode($data['country_code'] ?? self::DEFAULT_COUNTRY_DIAL_CODE);
        $country = Country::query()
            ->where('dial_code', $dialCode)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $country) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid country code'
            );
        }

        $this->assertCityBelongsToCountry($data['cityId'], $country->id);

        if (Customer::query()
            ->where('email', $data['email'])
            ->where('id', '!=', $customer->id)
            ->exists()) {
            throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                'Email is already in use'
            );
        }

        $customer->update([
            'name' => $data['firstName'],
            'email' => $data['email'],
            'birth_date' => $data['birthDate'],
            'gender' => $data['gender'],
            'city_id' => $data['cityId'],
            'country_id' => $country->id,
            'profile_completed' => true,
        ]);

        $customer->load(['country', 'city']);

        return [
            'profile_completed' => true,
            'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city']))->resolve(),
        ];
    }

    private function normalizeDialCode(string $dialCode): string
    {
        return ltrim(trim($dialCode), '+');
    }

    private function assertCityBelongsToCountry(string $cityId, string $countryId): City
    {
        $city = City::query()
            ->where('id', $cityId)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $city) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid or inactive city'
            );
        }

        if ($city->country_id !== $countryId) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'City does not belong to the selected country'
            );
        }

        return $city;
    }

    private function buildAuthResponse(Customer $customer): array
    {
        $auth = $this->jwtService->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
            'customer',
        );

        return [
            'token' => $auth['token'],
            'token_type' => $auth['tokenType'],
            'profile_completed' => (bool) $customer->profile_completed,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ];
    }
}
