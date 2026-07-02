<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Modules\CustomerAuth\Models\CustomerBiometricDevice;
use App\Modules\CustomerAuth\Support\BiometricSignatureVerifier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\CustomerAuthTestCase;
use Tests\Support\BiometricTestKeyPair;
use Tests\Support\CustomerAuthTestHelper;

class CustomerBiometricAuthApiTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const TEST_PHONE = '+249912345678';

    private const DEVICE_ID = '550e8400-e29b-41d4-a716-446655440000';

    protected function setUp(): void
    {
        parent::setUp();

        BiometricTestKeyPair::reset();
        $this->configureCustomerAuthTesting();
    }

    public function test_can_enroll_biometric_device(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/biometric/enroll', [
                'device_id' => self::DEVICE_ID,
                'device_name' => 'Test Pixel',
                'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
                'public_key' => BiometricTestKeyPair::publicKey(),
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Biometric device enrolled successfully',
                'data' => [
                    'device_id' => self::DEVICE_ID,
                    'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
                    'biometric_enabled' => true,
                ],
            ]);

        $this->assertDatabaseHas('customer_biometric_devices', [
            'customer_id' => $customer->id,
            'device_id' => self::DEVICE_ID,
            'status' => CustomerBiometricDevice::STATUS_ACTIVE,
        ]);
    }

    public function test_can_issue_biometric_challenge(): void
    {
        $this->enrollTestDevice();

        $response = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['challenge_token', 'nonce', 'expires_at'],
            ]);
    }

    public function test_can_login_with_biometric_signature(): void
    {
        $customer = $this->enrollTestDevice();

        $challenge = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ])->assertOk()->json('data');

        $response = $this->postJson('/api/v1/customer/auth/biometric/login', [
            'device_id' => self::DEVICE_ID,
            'challenge_token' => $challenge['challenge_token'],
            'signature' => BiometricTestKeyPair::signNonceFromResponse($challenge['nonce']),
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token_type' => 'Bearer',
                    'customer' => [
                        'id' => $customer->id,
                        'phone' => self::TEST_PHONE,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => ['token', 'refresh_token'],
            ]);

        $this->assertNotNull(
            CustomerBiometricDevice::query()
                ->where('device_id', self::DEVICE_ID)
                ->value('last_used_at')
        );
    }

    public function test_biometric_login_rejects_invalid_signature(): void
    {
        $this->enrollTestDevice();

        $challenge = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ])->json('data');

        $response = $this->postJson('/api/v1/customer/auth/biometric/login', [
            'device_id' => self::DEVICE_ID,
            'challenge_token' => $challenge['challenge_token'],
            'signature' => BiometricSignatureVerifier::base64UrlEncode('not-a-valid-signature'),
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_biometric_login_rejects_expired_challenge(): void
    {
        $this->enrollTestDevice();

        $challenge = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ])->json('data');

        $this->travel(120)->seconds();

        $response = $this->postJson('/api/v1/customer/auth/biometric/login', [
            'device_id' => self::DEVICE_ID,
            'challenge_token' => $challenge['challenge_token'],
            'signature' => BiometricTestKeyPair::signNonceFromResponse($challenge['nonce']),
        ]);

        $response->assertStatus(422);

        $this->travelBack();
    }

    public function test_biometric_login_rejects_replayed_challenge(): void
    {
        $this->enrollTestDevice();

        $challenge = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ])->json('data');

        $payload = [
            'device_id' => self::DEVICE_ID,
            'challenge_token' => $challenge['challenge_token'],
            'signature' => BiometricTestKeyPair::signNonceFromResponse($challenge['nonce']),
        ];

        $this->postJson('/api/v1/customer/auth/biometric/login', $payload)->assertOk();

        $replay = $this->postJson('/api/v1/customer/auth/biometric/login', $payload);

        $replay->assertStatus(422);
    }

    public function test_revoked_device_cannot_request_challenge(): void
    {
        $customer = $this->enrollTestDevice();

        $credentialId = CustomerBiometricDevice::query()
            ->where('device_id', self::DEVICE_ID)
            ->value('id');

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->deleteJson('/api/v1/customer/biometric/devices/'.$credentialId)
            ->assertOk();

        $response = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ]);

        $response->assertStatus(401);
    }

    public function test_enroll_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/customer/biometric/enroll', [
            'device_id' => self::DEVICE_ID,
            'device_name' => 'Test Pixel',
            'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
            'public_key' => BiometricTestKeyPair::publicKey(),
        ]);

        $response->assertStatus(401);
    }

    public function test_password_change_revokes_biometric_devices(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');
        $newPassword = 'NewSecure1!';

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/biometric/enroll', [
                'device_id' => self::DEVICE_ID,
                'device_name' => 'Test Pixel',
                'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
                'public_key' => BiometricTestKeyPair::publicKey(),
            ])->assertCreated();

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            111111,
            self::VALID_PASSWORD,
            $newPassword,
        )->assertOk();

        $this->assertDatabaseHas('customer_biometric_devices', [
            'device_id' => self::DEVICE_ID,
            'status' => CustomerBiometricDevice::STATUS_REVOKED,
        ]);

        $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ])->assertStatus(401);
    }

    public function test_max_devices_revokes_oldest_device(): void
    {
        config(['services.biometric.max_devices' => 2]);

        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $headers = $this->customerAuthHeaders($customer);
        $oldestDeviceId = '11111111-1111-4111-8111-111111111111';

        foreach ([$oldestDeviceId, '22222222-2222-4222-8222-222222222222'] as $index => $deviceId) {
            $this->withHeaders($headers)->postJson('/api/v1/customer/biometric/enroll', [
                'device_id' => $deviceId,
                'device_name' => 'Device '.$index,
                'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
                'public_key' => BiometricTestKeyPair::publicKey(),
            ])->assertCreated();
        }

        $this->withHeaders($headers)->postJson('/api/v1/customer/biometric/enroll', [
            'device_id' => '33333333-3333-4333-8333-333333333333',
            'device_name' => 'Device 3',
            'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
            'public_key' => BiometricTestKeyPair::publicKey(),
        ])->assertCreated();

        $this->assertDatabaseHas('customer_biometric_devices', [
            'device_id' => $oldestDeviceId,
            'status' => CustomerBiometricDevice::STATUS_REVOKED,
        ]);

        $this->assertDatabaseHas('customer_biometric_devices', [
            'device_id' => '33333333-3333-4333-8333-333333333333',
            'status' => CustomerBiometricDevice::STATUS_ACTIVE,
        ]);
    }

    public function test_suspended_customer_cannot_biometric_login(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
            'status' => Customer::STATUS_SUSPENDED,
        ]);

        CustomerBiometricDevice::query()->create([
            'customer_id' => $customer->id,
            'device_id' => self::DEVICE_ID,
            'device_name' => 'Test Pixel',
            'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
            'public_key' => BiometricTestKeyPair::publicKey(),
            'algorithm' => CustomerBiometricDevice::ALGORITHM_ES256,
            'status' => CustomerBiometricDevice::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => self::DEVICE_ID,
        ])->assertStatus(401);
    }

    public function test_signature_verifier_accepts_valid_signature(): void
    {
        $nonce = random_bytes(32);
        $signature = BiometricTestKeyPair::sign($nonce);

        $this->assertTrue(BiometricSignatureVerifier::verify(
            $nonce,
            $signature,
            BiometricTestKeyPair::publicKey(),
            BiometricTestKeyPair::ALGORITHM,
        ));
    }

    public function test_can_list_and_disable_biometric_devices(): void
    {
        $customer = $this->enrollTestDevice();

        $listResponse = $this->withHeaders($this->customerAuthHeaders($customer))
            ->getJson('/api/v1/customer/biometric/devices');

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/biometric/disable', [
                'device_id' => self::DEVICE_ID,
            ])
            ->assertOk();

        $this->assertDatabaseHas('customer_biometric_devices', [
            'device_id' => self::DEVICE_ID,
            'status' => CustomerBiometricDevice::STATUS_REVOKED,
        ]);
    }

    public function test_challenge_fails_for_unknown_device(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/biometric/challenge', [
            'device_id' => (string) Str::uuid(),
        ]);

        $response->assertStatus(401);
    }

    private function enrollTestDevice(): Customer
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/biometric/enroll', [
                'device_id' => self::DEVICE_ID,
                'device_name' => 'Test Pixel',
                'platform' => CustomerBiometricDevice::PLATFORM_ANDROID,
                'public_key' => BiometricTestKeyPair::publicKey(),
            ])
            ->assertCreated();

        return $customer;
    }
}
