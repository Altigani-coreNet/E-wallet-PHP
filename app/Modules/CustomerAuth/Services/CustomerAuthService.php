<?php

namespace App\Modules\CustomerAuth\Services;

use App\Mail\CustomerWelcomeMail;
use App\Models\ChangeRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Modules\AdminKyc\Services\AdminKycNotificationService;
use App\Modules\AdminKyc\Notifications\AdminKycNotificationType;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Modules\CustomerAuth\Support\OtpTokenCipher;
use App\Services\CustomerService;
use App\Services\WalletService;
use App\Support\CustomerEventMessageBuilder;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        private readonly CustomerActionOtpService $actionOtpService,
        private readonly CustomerJwtService $jwtService,
        private readonly WalletService $walletService,
        private readonly CustomerSystemNotificationService $customerSystemNotificationService,
        private readonly CustomerService $customerService,
        private readonly CustomerProfileService $customerProfileService,
        private readonly CustomerAttachmentService $customerAttachmentService,
        private readonly AdminKycNotificationService $adminKycNotificationService,
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
            'phone_verified_at' => now(),
        ]);

        $this->otpService->consumeOtpById($otp->id);

        $customer->load(['country', 'city']);

        $this->customerService->logCustomerEvent($customer, 'registered', [
            'message' => CustomerEventMessageBuilder::registered($customer),
            'phone' => $customer->phone,
            'performed_by' => $customer->phone ?: 'Customer',
        ]);

        return $this->buildAuthResponse($customer);
    }

    public function login(array $data): array
    {
        $customer = Customer::query()
            ->where('phone', $data['phone'])
            ->whereNull('deleted_at')
            ->first();

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
        $result = $this->otpService->generateAndSendSmsOtp($phone);

        $customer = Customer::query()
            ->where('phone', $phone)
            ->whereNull('deleted_at')
            ->first();

        if ($customer) {
            $this->customerService->logCustomerEvent($customer, 'password_reset_requested', [
                'message' => CustomerEventMessageBuilder::passwordResetRequested(),
                'performed_by' => $customer->name ?: $customer->phone ?: 'Customer',
            ]);
        }

        return $result;
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

        $this->customerService->logCustomerEvent($customer->fresh(['country', 'city']), 'password_reset', [
            'message' => CustomerEventMessageBuilder::passwordReset($customer),
            'performed_by' => $customer->phone ?: 'Customer',
        ]);

        return $this->buildAuthResponse($customer->fresh(['country', 'city']));
    }

    public function logout(): array
    {
        return ['message' => 'Logged out successfully'];
    }

    public function deleteAccount(Customer $customer, string $password): array
    {
        if (! $customer->password || ! Hash::check($password, $customer->password)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Password is incorrect'
            );
        }

        app(CustomerBiometricService::class)->revokeAllForCustomer($customer);
        $this->customerService->softDeleteWithCorruption($customer);

        return ['message' => 'Account deleted successfully'];
    }

    public function refreshToken(Customer $customer): array
    {
        $customer->load(['country', 'city']);

        return $this->buildAuthResponse($customer);
    }

    /**
     * @return array{otp_token: string, expires_at: string}
     */
    public function requestPasswordChange(Customer $customer, array $data): array
    {
        $this->assertPasswordChangeValid($customer, $data);

        $fingerprint = CustomerActionOtpService::fingerprintPassword($data['password']);

        return $this->actionOtpService->issuePasswordChange($customer, $fingerprint);
    }

    public function confirmPasswordChange(Customer $customer, array $data): array
    {
        $this->assertPasswordChangeValid($customer, $data);

        $fingerprint = CustomerActionOtpService::fingerprintPassword($data['password']);

        return DB::transaction(function () use ($customer, $data, $fingerprint) {
            $this->actionOtpService->verifyPasswordChangeAndConsume(
                $customer,
                $data['otp_token'],
                (int) $data['otp'],
                $fingerprint,
            );

            return $this->applyPasswordChange($customer, $data['password']);
        });
    }

    private function assertPasswordChangeValid(Customer $customer, array $data): void
    {
        if (! Hash::check($data['current_password'], $customer->password)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'Current password is incorrect'
            );
        }

        if (Hash::check($data['password'], $customer->password)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'New password must be different from the current password'
            );
        }
    }

    private function applyPasswordChange(Customer $customer, string $newPassword): array
    {
        $customer->update([
            'password' => Hash::make($newPassword),
        ]);

        app(CustomerBiometricService::class)->revokeAllForCustomer($customer->fresh());

        $customer->load(['country', 'city']);

        $this->customerSystemNotificationService->send(
            $customer->fresh(),
            CustomerNotificationType::PasswordChanged,
        );

        $this->customerService->logCustomerEvent($customer->fresh(['country', 'city']), 'password_changed', [
            'message' => CustomerEventMessageBuilder::passwordChanged($customer->name ?: $customer->phone),
            'performed_by' => $customer->name ?: $customer->phone,
        ]);

        return $this->buildAuthResponse($customer->fresh(['country', 'city']));
    }

    public function profile(Customer $customer): array
    {
        $customer->load(['country', 'city', 'wallet', 'attachments']);

        $rejection = $this->customerProfileService->latestRejection($customer);

        return [
            'profile_completed' => $customer->profile_completed,
            'has_pending_change' => $this->customerProfileService->hasPendingChangeRequest($customer),
            'rejection' => $rejection ? [
                'id' => $rejection->id,
                'rejection_reason' => $rejection->rejection_reason,
                'invalid_fields' => $rejection->invalid_fields ?? [],
                'missing_attachments' => $rejection->missing_attachments ?? [],
                'created_at' => $rejection->created_at?->toIso8601String(),
            ] : null,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ];
    }

    public function completeProfile(Customer $customer, array $data, Request $request): array
    {
        $wasAlreadyCompleted = (bool) $customer->profile_completed;

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

        return DB::transaction(function () use ($customer, $data, $request, $wasAlreadyCompleted, $country) {
            $updateData = [
                'name' => $data['firstName'],
                'national_id' => $data['nationalId'],
                'email' => $data['email'],
                'birth_date' => $data['birthDate'],
                'gender' => $data['gender'],
                'city_id' => $data['cityId'],
                'country_id' => $country->id,
                'merchant_country_id' => $country->id,
                'profile_completed' => true,
            ];

            $customer->update($updateData);

            $this->customerAttachmentService->uploadProfileImageFromRequest($request, $customer);
            $this->customerAttachmentService->uploadPassportDocumentFromRequest($request, $customer);

            $this->walletService->createForCustomer($customer);

            $customer->load(['country', 'city', 'wallet', 'attachments']);

            $this->sendWelcomeEmail(
                $data['email'],
                $data['firstName'],
                $customer->phone,
            );

            if (! $wasAlreadyCompleted) {
                $this->customerSystemNotificationService->send(
                    $customer->fresh(['country', 'city', 'wallet', 'attachments']),
                    CustomerNotificationType::ProfileCompleted,
                );

                $freshCustomer = $customer->fresh(['country', 'city', 'wallet', 'attachments']);

                $this->customerService->logCustomerEvent(
                    $freshCustomer,
                    'profile_completed',
                    [
                        'message' => CustomerEventMessageBuilder::profileCompleted($freshCustomer),
                        'performed_by' => $freshCustomer->name ?: $freshCustomer->phone,
                    ],
                    null,
                    $freshCustomer->getAttributes(),
                );

                $this->adminKycNotificationService->send(
                    $freshCustomer,
                    AdminKycNotificationType::CustomerProfileCompleted,
                );
            }

            return [
                'profile_completed' => true,
                'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city', 'wallet', 'attachments']))->resolve(),
            ];
        });
    }

    public function updateProfile(Customer $customer, array $data, Request $request): array
    {
        if ($this->customerProfileService->hasPendingChangeRequest($customer)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException(
                'A change request is already pending for this account'
            );
        }

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
            'merchant_country_id' => $country->id,
        ];

        if (array_key_exists('email', $data)) {
            $requestedEmail = trim((string) $data['email']);
            $currentEmail = trim((string) ($customer->email ?? ''));

            if ($requestedEmail !== '' && strcasecmp($requestedEmail, $currentEmail) !== 0) {
                if (Customer::query()
                    ->where('email', $requestedEmail)
                    ->where('id', '!=', $customer->id)
                    ->exists()) {
                    throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                        'Email is already in use'
                    );
                }

                $updateData['email'] = $requestedEmail;
            }
        }

        if ($request->hasFile('picture')) {
            $updateData['profile_image'] = $this->storeProfilePicture($request, $customer);
        }

        if ($request->hasFile('passport') && $customer->status === Customer::STATUS_ACTIVE) {
            $updateData['passport_document'] = $this->customerAttachmentService->storePassportForChangeRequest(
                $customer,
                $request->file('passport'),
            );
        }

        if (array_key_exists('phone', $data)) {
            $requestedPhone = trim((string) $data['phone']);
            $currentPhone = trim((string) ($customer->phone ?? ''));

            if ($requestedPhone !== '' && $requestedPhone !== $currentPhone) {
                if (Customer::query()
                    ->where('phone', $requestedPhone)
                    ->where('id', '!=', $customer->id)
                    ->exists()) {
                    throw new \Symfony\Component\HttpKernel\Exception\ConflictHttpException(
                        'Phone is already in use'
                    );
                }

                $updateData['phone'] = $requestedPhone;
            }
        }

        if ($customer->status === Customer::STATUS_REJECTED) {
            $beforeValues = $customer->only(array_keys($updateData));

            $customer->update($updateData);
            $customer->update(['status' => Customer::STATUS_PENDING]);
            $customer->load(['country', 'city', 'wallet']);

            $this->customerService->logCustomerEvent(
                $customer->fresh(['country', 'city', 'wallet']),
                'profile_resubmitted',
                [
                    'message' => CustomerEventMessageBuilder::profileResubmitted($beforeValues, $updateData),
                    'performed_by' => $customer->name ?: $customer->phone,
                ],
                $beforeValues,
                $updateData,
            );

            $this->adminKycNotificationService->send(
                $customer->fresh(['country', 'city', 'wallet']),
                AdminKycNotificationType::CustomerProfileResubmitted,
            );

            return [
                'profile_completed' => (bool) $customer->profile_completed,
                'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city', 'wallet']))->resolve(),
            ];
        }

        if ($customer->status === Customer::STATUS_ACTIVE) {
            return DB::transaction(function () use ($customer, $updateData) {
                $previousStatus = $customer->status;
                $beforeValues = $customer->only(array_keys($updateData));
                $payload = $updateData;
                $payload['__meta'] = [
                    'previous_status' => $previousStatus,
                ];

                $changeRequest = ChangeRequest::create([
                    'changeable_type' => Customer::class,
                    'changeable_id' => $customer->id,
                    'requester_type' => Customer::class,
                    'requester_id' => $customer->id,
                    'payload' => $payload,
                    'reason' => null,
                    'status' => 'pending',
                    'has_file' => isset($updateData['profile_image']) || isset($updateData['passport_document']),
                ]);

                $customer->update(['status' => Customer::STATUS_REQUESTING_UPDATED]);
                $customer->load(['country', 'city', 'wallet']);

                $this->customerService->logCustomerEvent(
                    $customer->fresh(['country', 'city', 'wallet']),
                    'change_request_submitted',
                    [
                        'message' => CustomerEventMessageBuilder::changeRequestSubmitted($customer, $updateData),
                        'change_request_id' => $changeRequest->id,
                        'performed_by' => $customer->name ?: $customer->phone,
                    ],
                    $beforeValues,
                    $updateData,
                );

                $this->adminKycNotificationService->send(
                    $customer->fresh(['country', 'city', 'wallet']),
                    AdminKycNotificationType::CustomerChangeRequestSubmitted,
                    ['change_request_id' => $changeRequest->id],
                );

                return [
                    'profile_completed' => (bool) $customer->profile_completed,
                    'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city', 'wallet']))->resolve(),
                ];
            });
        }

        $customer->update($updateData);
        $customer->load(['country', 'city', 'wallet']);

        return [
            'profile_completed' => (bool) $customer->profile_completed,
            'customer' => CustomerAuthResource::make($customer->fresh(['country', 'city', 'wallet']))->resolve(),
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

        $refresh = $this->jwtService->createRefreshToken(
            $customer->id,
            $customer->email ?: $customer->phone,
            'customer',
        );

        return [
            'token' => $auth['token'],
            'token_type' => $auth['tokenType'],
            'expires_in' => $auth['expiresIn'],
            'refresh_token' => $refresh['token'],
            'refresh_token_expires_in' => $refresh['expiresIn'],
            'profile_completed' => (bool) $customer->profile_completed,
            'customer' => CustomerAuthResource::make($customer)->resolve(),
        ];
    }
}
