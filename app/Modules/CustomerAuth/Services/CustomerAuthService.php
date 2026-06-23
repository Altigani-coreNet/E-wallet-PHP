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
    public function __construct(
        private readonly CustomerOtpService $otpService,
        private readonly CustomerJwtService $jwtService,
    ) {
    }

    public function register(array $data): array
    {
        if ($data['password'] !== $data['password_confirmation']) {
            throw new \InvalidArgumentException('Password confirmation does not match');
        }

        try {
            $rawOtpToken = OtpTokenCipher::decrypt($data['otp_token']);
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Invalid OTP token');
        }

        $otp = $this->otpService->findVerifiedSmsOtp($rawOtpToken, $data['phone']);

        if (Customer::query()->where('phone', $data['phone'])->exists()) {
            throw new \DomainException('A customer with this phone already exists');
        }

        $customer = Customer::create([
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'profile_completed' => false,
        ]);

        $this->otpService->consumeOtpById($otp->id);

        $customer->load(['country', 'city']);

        $auth = $this->jwtService->createToken(
            $customer->id,
            $customer->email ?? $customer->phone,
        );

        return [
            'token' => $auth['token'],
            'token_type' => $auth['tokenType'],
            'profile_completed' => false,
            'customer' => (new CustomerAuthResource($customer))->resolve(),
        ];
    }

    public function profile(Customer $customer): array
    {
        $customer->load(['country', 'city']);

        return [
            'profile_completed' => (bool) $customer->profile_completed,
            'customer' => (new CustomerAuthResource($customer))->resolve(),
        ];
    }

    public function completeProfile(Customer $customer, array $data): array
    {
        $dialCode = $this->normalizeDialCode($data['country_code'] ?? config('customer_auth.default_country_dial_code'));
        $country = $this->findCountryByDialCode($dialCode);

        if (!$country) {
            throw new \InvalidArgumentException('Invalid country code');
        }

        $this->assertCityBelongsToCountry($data['cityId'], $country->id);

        $emailTaken = Customer::query()
            ->where('email', $data['email'])
            ->where('id', '!=', $customer->id)
            ->exists();

        if ($emailTaken) {
            throw new \DomainException('Email is already in use');
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
            'customer' => (new CustomerAuthResource($customer))->resolve(),
        ];
    }

    private function normalizeDialCode(string $dialCode): string
    {
        return ltrim(trim($dialCode), '+');
    }

    private function findCountryByDialCode(string $dialCode): ?Country
    {
        return Country::query()
            ->where('code', $dialCode)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->first();
    }

    private function assertCityBelongsToCountry(string $cityId, string $countryId): City
    {
        $city = City::query()
            ->where('id', $cityId)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->first();

        if (!$city) {
            throw new \InvalidArgumentException('Invalid city');
        }

        if ($city->country_id !== $countryId) {
            throw new \InvalidArgumentException('City does not belong to the selected country');
        }

        return $city;
    }
}
