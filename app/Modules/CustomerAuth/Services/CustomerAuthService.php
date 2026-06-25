<?php

namespace App\Modules\CustomerAuth\Services;

use App\Mail\CustomerWelcomeMail;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Modules\CustomerAuth\Support\OtpTokenCipher;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerAuthService
{
    use HasFiles;

    private const DEFAULT_COUNTRY_CODE = '249';

    private const PROFILE_IMAGE_DIR = 'customer_profile_images';

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
            'status' => Customer::STATUS_PENDING,
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

        if ($reason = $customer->authLoginBlockReason()) {
            throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException(
                'Bearer',
                $reason
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

    public function refreshToken(Customer $customer): array
    {
        $customer->load(['country', 'city']);

        return $this->buildAuthResponse($customer);
    }

    public function profile(Customer $customer): array
    {
        $customer->load(['country', 'city']);

        return [
            'profile_completed' => $customer->profile_completed,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ];
    }

    public function completeProfile(Customer $customer, array $data, Request $request): array
    {
        $code = $this->normalizeCode($data['country_code'] ?? self::DEFAULT_COUNTRY_CODE);
        $country = Country::query()
            ->where('code', $code)
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

        $updateData = [
            'name' => $data['firstName'],
            'national_id' => $data['nationalId'],
            'email' => $data['email'],
            'birth_date' => $data['birthDate'],
            'gender' => $data['gender'],
            'city_id' => $data['cityId'],
            'country_id' => $country->id,
            'profile_completed' => true,
        ];

        if ($request->hasFile('picture')) {
            $updateData['profile_image'] = $this->storeProfilePicture($request, $customer);
        }

        $customer->update($updateData);

        $customer->load(['country', 'city']);

        $this->sendWelcomeEmail(
            $data['email'],
            $data['firstName'],
            $customer->phone,
        );

        return [
            'profile_completed' => true,
            'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city']))->resolve(),
        ];
    }

    public function updateProfile(Customer $customer, array $data, Request $request): array
    {
        $code = $this->normalizeCode($data['country_code'] ?? self::DEFAULT_COUNTRY_CODE);
        $country = Country::query()
            ->where('code', $code)
            ->where('status', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $country) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Invalid country code'
            );
        }

        $this->assertCityBelongsToCountry($data['cityId'], $country->id);

        $updateData = [
            'name' => $data['firstName'],
            'birth_date' => $data['birthDate'],
            'gender' => $data['gender'],
            'city_id' => $data['cityId'],
            'country_id' => $country->id,
        ];

        if ($request->hasFile('picture')) {
            $updateData['profile_image'] = $this->storeProfilePicture($request, $customer);
        }

        $customer->update($updateData);
        $customer->load(['country', 'city']);

        return [
            'profile_completed' => (bool) $customer->profile_completed,
            'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city']))->resolve(),
        ];
    }

    private function storeProfilePicture(Request $request, Customer $customer): ?string
    {
        if (! $request->hasFile('picture')) {
            return null;
        }

        if ($customer->profile_image) {
            $oldImagePath = public_path($customer->profile_image);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $directory = public_path(self::PROFILE_IMAGE_DIR);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return $this->uploadImageAndGetFileName($request, 'picture', self::PROFILE_IMAGE_DIR);
    }

    private function sendWelcomeEmail(string $email, string $customerName, string $phone): void
    {
        try {
            Mail::to($email)->send(new CustomerWelcomeMail($customerName, $email, $phone));
        } catch (\Throwable $exception) {
            Log::error('Customer welcome email failed', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function normalizeCode(string $code): string
    {
        return ltrim(trim($code), '+');
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
