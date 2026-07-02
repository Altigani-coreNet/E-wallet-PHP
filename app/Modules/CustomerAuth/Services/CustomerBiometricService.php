<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Modules\CustomerAuth\Models\CustomerBiometricDevice;
use App\Modules\CustomerAuth\Resources\CustomerBiometricDeviceResource;
use App\Modules\CustomerAuth\Support\BiometricChallengeCipher;
use App\Modules\CustomerAuth\Support\BiometricSignatureVerifier;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CustomerBiometricService
{
    private const CHALLENGE_CACHE_PREFIX = 'biometric:challenge:';

    private const DEVICE_CHALLENGE_CACHE_PREFIX = 'biometric:device-challenge:';

    public function __construct(
        private readonly CustomerAuthService $authService,
        private readonly CustomerService $customerService,
    ) {}

    public function enroll(Customer $customer, array $data): array
    {
        $publicKey = BiometricSignatureVerifier::normalizePublicKey($data['public_key']);

        if (! BiometricSignatureVerifier::isValidPublicKey($publicKey)) {
            throw new \InvalidArgumentException('Invalid public key');
        }

        $algorithm = BiometricSignatureVerifier::detectAlgorithm($publicKey)
            ?? config('services.biometric.algorithm', CustomerBiometricDevice::ALGORITHM_ES256);

        return DB::transaction(function () use ($customer, $data, $publicKey, $algorithm) {
            $this->enforceMaxDevices($customer, $data['device_id']);

            $device = CustomerBiometricDevice::query()->firstOrNew([
                'customer_id' => $customer->id,
                'device_id' => $data['device_id'],
            ]);

            $wasActive = $device->exists && $device->isActive();

            $device->fill([
                'device_name' => $data['device_name'] ?? null,
                'platform' => $data['platform'],
                'public_key' => $publicKey,
                'algorithm' => $algorithm,
                'status' => CustomerBiometricDevice::STATUS_ACTIVE,
                'enrolled_at' => now(),
                'last_used_at' => null,
                'revoked_at' => null,
            ]);
            $device->save();

            if (! $wasActive) {
                $this->customerService->logCustomerEvent($customer, 'biometric_enrolled', [
                    'message' => 'Biometric login enabled on '.($data['device_name'] ?: $data['platform'].' device'),
                    'device_id' => $data['device_id'],
                    'platform' => $data['platform'],
                    'performed_by' => $customer->name ?: $customer->phone,
                ]);
            }

            return CustomerBiometricDeviceResource::make($device)->resolve();
        });
    }

    public function listDevices(Customer $customer): array
    {
        $devices = CustomerBiometricDevice::query()
            ->where('customer_id', $customer->id)
            ->where('status', CustomerBiometricDevice::STATUS_ACTIVE)
            ->orderByDesc('enrolled_at')
            ->get();

        return CustomerBiometricDeviceResource::collection($devices)->resolve();
    }

    public function revokeDevice(Customer $customer, string $credentialId): void
    {
        $device = CustomerBiometricDevice::query()
            ->where('customer_id', $customer->id)
            ->where('id', $credentialId)
            ->where('status', CustomerBiometricDevice::STATUS_ACTIVE)
            ->first();

        if (! $device) {
            throw new \InvalidArgumentException('Biometric device not found');
        }

        $this->revokeCredential($device, $customer);
    }

    public function disableDevice(Customer $customer, string $deviceId): void
    {
        $device = CustomerBiometricDevice::query()
            ->where('customer_id', $customer->id)
            ->where('device_id', $deviceId)
            ->where('status', CustomerBiometricDevice::STATUS_ACTIVE)
            ->first();

        if (! $device) {
            throw new \InvalidArgumentException('Biometric device not found');
        }

        $this->revokeCredential($device, $customer);
    }

    /**
     * @return array{challenge_token: string, nonce: string, expires_at: string}
     */
    public function issueChallenge(string $deviceId): array
    {
        $this->assertChallengeRateLimit($deviceId);

        $device = $this->findActiveDeviceByDeviceId($deviceId);

        if (! $device) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        $customer = $device->customer;

        if ($reason = $customer?->authLoginBlockReason()) {
            throw new UnauthorizedHttpException('Bearer', $reason);
        }

        $ttl = (int) config('services.biometric.challenge_ttl', 60);
        $nonce = random_bytes(32);
        $jti = (string) Str::uuid();
        $expiresAt = now()->addSeconds($ttl);

        $this->invalidateDeviceChallenge($deviceId);

        Cache::put(self::CHALLENGE_CACHE_PREFIX.$jti, [
            'device_id' => $deviceId,
            'customer_id' => $device->customer_id,
        ], $expiresAt);

        Cache::put(self::DEVICE_CHALLENGE_CACHE_PREFIX.$deviceId, $jti, $expiresAt);

        $challengeToken = BiometricChallengeCipher::encrypt([
            'nonce' => base64_encode($nonce),
            'device_id' => $deviceId,
            'jti' => $jti,
            'exp' => $expiresAt->getTimestamp(),
        ]);

        return [
            'challenge_token' => $challengeToken,
            'nonce' => BiometricSignatureVerifier::base64UrlEncode($nonce),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    public function login(array $data): array
    {
        try {
            $payload = BiometricChallengeCipher::decrypt($data['challenge_token']);
        } catch (\InvalidArgumentException|\JsonException) {
            throw new \InvalidArgumentException('Invalid or expired challenge token');
        }

        if ($payload['device_id'] !== $data['device_id']) {
            throw new \InvalidArgumentException('Invalid or expired challenge token');
        }

        if ($payload['exp'] < now()->getTimestamp()) {
            throw new \InvalidArgumentException('Invalid or expired challenge token');
        }

        $activeJti = Cache::get(self::DEVICE_CHALLENGE_CACHE_PREFIX.$data['device_id']);
        if ($activeJti !== $payload['jti']) {
            throw new \InvalidArgumentException('Invalid or expired challenge token');
        }

        $cached = Cache::pull(self::CHALLENGE_CACHE_PREFIX.$payload['jti']);
        if ($cached === null) {
            throw new \InvalidArgumentException('Invalid or expired challenge token');
        }

        Cache::forget(self::DEVICE_CHALLENGE_CACHE_PREFIX.$data['device_id']);

        $device = $this->findActiveDeviceByDeviceId($data['device_id']);

        if (! $device || $device->customer_id !== $cached['customer_id']) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        $customer = $device->customer;

        if ($reason = $customer?->authLoginBlockReason()) {
            throw new UnauthorizedHttpException('Bearer', $reason);
        }

        $nonce = base64_decode($payload['nonce'], true);
        if ($nonce === false) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        if (! BiometricSignatureVerifier::verify(
            $nonce,
            $data['signature'],
            $device->public_key,
            $device->algorithm,
        )) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid credentials');
        }

        $device->update(['last_used_at' => now()]);

        $customer->load(['country', 'city']);

        $this->customerService->logCustomerEvent($customer, 'biometric_login', [
            'message' => 'Logged in with biometric on '.($device->device_name ?: $device->platform.' device'),
            'device_id' => $device->device_id,
            'platform' => $device->platform,
            'performed_by' => $customer->name ?: $customer->phone,
        ]);

        return $this->authService->refreshToken($customer);
    }

    public function revokeAllForCustomer(Customer $customer): void
    {
        CustomerBiometricDevice::query()
            ->where('customer_id', $customer->id)
            ->where('status', CustomerBiometricDevice::STATUS_ACTIVE)
            ->each(fn (CustomerBiometricDevice $device) => $this->revokeCredential($device, $customer, logEvent: false));

        $this->customerService->logCustomerEvent($customer, 'biometric_revoked', [
            'message' => 'All biometric devices revoked',
            'performed_by' => $customer->name ?: $customer->phone,
        ]);
    }

    private function findActiveDeviceByDeviceId(string $deviceId): ?CustomerBiometricDevice
    {
        return CustomerBiometricDevice::query()
            ->with('customer')
            ->where('device_id', $deviceId)
            ->where('status', CustomerBiometricDevice::STATUS_ACTIVE)
            ->first();
    }

    private function enforceMaxDevices(Customer $customer, string $deviceId): void
    {
        $maxDevices = (int) config('services.biometric.max_devices', 5);
        $strategy = config('services.biometric.max_devices_strategy', 'revoke_oldest');

        $activeDevices = CustomerBiometricDevice::query()
            ->where('customer_id', $customer->id)
            ->where('status', CustomerBiometricDevice::STATUS_ACTIVE)
            ->orderBy('enrolled_at')
            ->get();

        $existing = $activeDevices->firstWhere('device_id', $deviceId);
        $activeCount = $activeDevices->count();

        if ($existing || $activeCount < $maxDevices) {
            return;
        }

        if ($strategy === 'reject') {
            throw new ConflictHttpException('Maximum number of biometric devices reached');
        }

        $oldest = $activeDevices->first();
        if ($oldest) {
            $this->revokeCredential($oldest, $customer);
        }
    }

    private function revokeCredential(
        CustomerBiometricDevice $device,
        Customer $customer,
        bool $logEvent = true,
    ): void {
        $device->markRevoked();
        Cache::forget(self::DEVICE_CHALLENGE_CACHE_PREFIX.$device->device_id);

        if ($logEvent) {
            $this->customerService->logCustomerEvent($customer, 'biometric_revoked', [
                'message' => 'Biometric login disabled on '.($device->device_name ?: $device->platform.' device'),
                'device_id' => $device->device_id,
                'platform' => $device->platform,
                'performed_by' => $customer->name ?: $customer->phone,
            ]);
        }
    }

    private function invalidateDeviceChallenge(string $deviceId): void
    {
        $previousJti = Cache::pull(self::DEVICE_CHALLENGE_CACHE_PREFIX.$deviceId);
        if ($previousJti) {
            Cache::forget(self::CHALLENGE_CACHE_PREFIX.$previousJti);
        }
    }

    private function assertChallengeRateLimit(string $deviceId): void
    {
        $maxAttempts = (int) config('services.biometric.challenge_rate_limit', 10);
        $decaySeconds = (int) config('services.biometric.challenge_rate_decay', 60);
        $key = 'biometric-challenge:'.sha1($deviceId.'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new TooManyRequestsHttpException($decaySeconds, 'Too many challenge requests');
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
