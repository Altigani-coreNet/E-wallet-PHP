<?php

namespace Tests\Feature;

use App\Mail\CustomerWelcomeMail;
use App\Mail\VerificationCode;
use App\Models\Advertisement;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\CustomerAuthTestCase;

class CustomerAuthApiTest extends CustomerAuthTestCase
{
    private const VALID_PASSWORD = 'Password1!';

    private const TEST_PHONE = '+249912345678';

    private const TEST_NATIONAL_ID = 'NID-123456789';

    public function test_can_send_sms_otp(): void
    {
        $response = $this->postJson('/api/v1/customer/otp/sms', [
            'phone' => self::TEST_PHONE,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent via SMS',
            ])
            ->assertJsonStructure([
                'data' => ['token'],
            ]);

        $this->assertDatabaseHas('customers_otp', [
            'identifier' => self::TEST_PHONE,
            'channel' => 'sms',
        ]);
    }

    public function test_can_send_email_otp(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/customer/otp/email', [
            'email' => 'customer@example.com',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent to email',
            ])
            ->assertJsonStructure([
                'data' => ['token'],
            ]);

        Mail::assertSent(VerificationCode::class, function (VerificationCode $mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    public function test_can_verify_sms_otp(): void
    {
        $smsResponse = $this->postJson('/api/v1/customer/otp/sms', [
            'phone' => self::TEST_PHONE,
        ]);

        $token = $smsResponse->json('data.token');

        $response = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $token,
            'code' => 111111,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'OTP verified successfully',
                'data' => [
                    'verified' => true,
                    'has_account' => false,
                ],
            ])
            ->assertJsonStructure([
                'data' => ['otp_token'],
            ]);
    }

    public function test_verify_otp_fails_with_invalid_code(): void
    {
        $smsResponse = $this->postJson('/api/v1/customer/otp/sms', [
            'phone' => self::TEST_PHONE,
        ]);

        $response = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $smsResponse->json('data.token'),
            'code' => 999999,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired OTP code',
            ]);
    }

    public function test_can_register_customer(): void
    {
        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $response = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'otp_token' => $otpToken,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'token_type' => 'Bearer',
                    'profile_completed' => false,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'refresh_token',
                    'expires_in',
                    'refresh_token_expires_in',
                    'customer' => [
                        'id',
                        'phone',
                        'profileCompleted',
                        'balance',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
            'status' => Customer::STATUS_PENDING,
        ]);

        $customerId = $response->json('data.customer.id');
        $this->assertNotNull($customerId);
        $this->assertTrue(Str::isUuid((string) $customerId));
        $this->assertSame($customerId, Customer::query()->where('phone', self::TEST_PHONE)->value('id'));
        $this->assertSame(Customer::STATUS_PENDING, $response->json('data.customer.status'));
        $this->assertCustomerAuthPayloadExcludesMerchantFields($response->json('data.customer'));

        $this->assertDatabaseHas('logs', [
            'loggable_type' => Customer::class,
            'loggable_id' => $customerId,
            'action' => 'registered',
        ]);
    }

    public function test_register_fails_without_verified_otp(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'otp_token' => 'invalid-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_register_fails_when_phone_already_exists(): void
    {
        Customer::factory()->create(['phone' => self::TEST_PHONE]);

        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $response = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'otp_token' => $otpToken,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'A customer with this phone already exists',
            ]);
    }

    public function test_register_fails_when_password_confirmation_mismatch(): void
    {
        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $response = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => 'Different1!',
            'otp_token' => $otpToken,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_register_validation_requires_strong_password(): void
    {
        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $response = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
            'otp_token' => $otpToken,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_login(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
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
                        'status' => Customer::STATUS_ACTIVE,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => ['token', 'refresh_token'],
            ]);

        $this->assertCustomerAuthPayloadExcludesMerchantFields($response->json('data.customer'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => 'WrongPass1!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_can_forgot_password(): void
    {
        $response = $this->postJson('/api/v1/customer/password/forgot', [
            'phone' => self::TEST_PHONE,
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'If an account exists, an OTP has been sent via SMS',
            ])
            ->assertJsonStructure([
                'data' => ['token'],
            ]);
    }

    public function test_can_reset_password(): void
    {
        Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make('OldPass1!'),
        ]);

        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $response = $this->postJson('/api/v1/customer/password/reset', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'otp_token' => $otpToken,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'refresh_token'],
            ]);

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->first();
        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $customer->password));
    }

    public function test_can_refresh_token_with_refresh_token_body(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');
        $this->assertNotEmpty($refreshToken);

        $response = $this->postJson('/api/v1/customer/auth/refresh-token', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'refresh_token',
                    'expires_in',
                    'refresh_token_expires_in',
                    'customer' => ['id'],
                ],
            ]);

        $this->assertNotSame($refreshToken, $response->json('data.refresh_token'));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_can_refresh_token_with_bearer_fallback(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('data.refresh_token'),
        ])->postJson('/api/v1/customer/auth/refresh-token');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully',
            ]);
    }

    public function test_refresh_token_rejects_access_token(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->postJson('/api/v1/customer/auth/refresh-token', [
            'refresh_token' => $loginResponse->json('data.token'),
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_refresh_token_requires_valid_token(): void
    {
        $response = $this->postJson('/api/v1/customer/auth/refresh-token');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_refresh_token_cannot_access_protected_routes(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $refresh = app(CustomerJwtService::class)->createRefreshToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        $this->withHeaders([
            'Authorization' => 'Bearer '.$refresh['token'],
        ])->getJson('/api/v1/customer/profile')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_can_change_password(): void
    {
        $newPassword = 'NewSecure1!';

        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
        ])->postJson('/api/v1/customer/password/change', [
            'current_password' => self::VALID_PASSWORD,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'refresh_token'],
            ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ])->assertStatus(401);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => $newPassword,
        ])->assertOk();
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
        ])->postJson('/api/v1/customer/password/change', [
            'current_password' => 'WrongPass1!',
            'password' => 'NewSecure1!',
            'password_confirmation' => 'NewSecure1!',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect',
            ]);
    }

    public function test_change_password_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/customer/password/change', [
            'current_password' => self::VALID_PASSWORD,
            'password' => 'NewSecure1!',
            'password_confirmation' => 'NewSecure1!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_change_password_validation_requires_strong_password(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
        ])->postJson('/api/v1/customer/password/change', [
            'current_password' => self::VALID_PASSWORD,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_get_profile(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/profile');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'profile_completed' => false,
                    'customer' => [
                        'id' => $customer->id,
                        'phone' => self::TEST_PHONE,
                        'status' => Customer::STATUS_PENDING,
                        'walletId' => null,
                    ],
                ],
            ]);

        $this->assertCustomerAuthPayloadExcludesMerchantFields($response->json('data.customer'));
    }

    public function test_profile_returns_wallet_id_and_balance_when_wallet_exists(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $wallet = app(\App\Services\WalletService::class)->createForCustomer($customer);
        $wallet->update(['balance' => '500.00', 'available_balance' => '450.00']);

        $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.customer.walletId', '249912345678@fastpay')
            ->assertJsonPath('data.customer.balance', '500.00')
            ->assertJsonPath('data.customer.availableBalance', '450.00');
    }

    public function test_can_complete_profile(): void
    {
        Mail::fake();

        [$country, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $response = $this->withCustomerToken($customer)
            ->post('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'nationalId' => self::TEST_NATIONAL_ID,
                'email' => 'ahmed@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('avatar.jpg'),
                'passport' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile completed successfully',
                'data' => [
                    'profile_completed' => true,
                    'customer' => [
                        'name' => 'Ahmed',
                        'email' => 'ahmed@example.com',
                        'nationalId' => self::TEST_NATIONAL_ID,
                        'profileCompleted' => true,
                        'countryId' => $country->id,
                        'cityId' => $city->id,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'customer' => ['profileImage'],
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'national_id' => self::TEST_NATIONAL_ID,
            'profile_completed' => true,
        ]);

        $customer->refresh();
        $this->assertNotNull($customer->profile_image);
        $this->assertFileExists(public_path($customer->profile_image));

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Customer::class,
            'attachable_id' => $customer->id,
            'url_type' => 'profile_image',
        ]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Customer::class,
            'attachable_id' => $customer->id,
            'url_type' => 'passport_document',
        ]);

        Mail::assertSent(CustomerWelcomeMail::class, function (CustomerWelcomeMail $mail) {
            return $mail->hasTo('ahmed@example.com')
                && $mail->customerName === 'Ahmed'
                && $mail->phone === self::TEST_PHONE;
        });

        $this->assertDatabaseHas('logs', [
            'loggable_type' => Customer::class,
            'loggable_id' => $customer->id,
            'action' => 'profile_completed',
        ]);
    }

    public function test_complete_profile_requires_passport(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $response = $this->withCustomerToken($customer)
            ->post('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'nationalId' => 'NID-NO-PASSPORT-001',
                'email' => 'ahmed@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('avatar.jpg'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['passport']);

        Mail::assertNothingSent();
    }

    public function test_can_update_profile_without_changing_email_or_phone(): void
    {
        Mail::fake();

        [$country, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'email' => 'existing@example.com',
            'name' => 'Old Name',
            'national_id' => self::TEST_NATIONAL_ID,
            'profile_completed' => true,
        ]);

        $response = $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/update', [
                'firstName' => 'Updated Name',
                'nationalId' => 'NID-SHOULD-NOT-CHANGE',
                'birthDate' => '1992-03-20',
                'gender' => 'female',
                'cityId' => $city->id,
                'country_code' => '249',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'profile_completed' => true,
                    'customer' => [
                        'name' => 'Updated Name',
                        'email' => 'existing@example.com',
                        'phone' => self::TEST_PHONE,
                        'nationalId' => self::TEST_NATIONAL_ID,
                        'gender' => 'female',
                        'countryId' => $country->id,
                        'cityId' => $city->id,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'email' => 'existing@example.com',
            'phone' => self::TEST_PHONE,
            'national_id' => self::TEST_NATIONAL_ID,
            'gender' => 'female',
        ]);

        Mail::assertNothingSent();
    }

    public function test_complete_profile_fails_with_duplicate_email(): void
    {
        [, $city] = $this->createCountryAndCity();

        Customer::factory()->create(['email' => 'taken@example.com']);

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'email' => '',
        ]);

        $response = $this->withCustomerToken($customer)
            ->post('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'nationalId' => 'NID-TAKEN-001',
                'email' => 'taken@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('avatar.jpg'),
                'passport' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Email is already in use',
            ]);
    }

    public function test_complete_profile_requires_national_id(): void
    {
        [, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $response = $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'email' => 'ahmed@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nationalId']);
    }

    public function test_complete_profile_rejects_duplicate_national_id(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        Customer::factory()->create([
            'national_id' => self::TEST_NATIONAL_ID,
        ]);

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $response = $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'nationalId' => self::TEST_NATIONAL_ID,
                'email' => 'another@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nationalId']);
    }

    public function test_can_logout(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $response = $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/auth/logout');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
                'data' => [
                    'message' => 'Logged out successfully',
                ],
            ]);
    }

    public function test_can_delete_account_soft_deletes_and_corrupts_identifiers(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'email' => 'delete-me@example.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->withCustomerToken($customer)
            ->deleteJson('/api/v1/customer/account', [
                'password' => self::VALID_PASSWORD,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Account deleted successfully',
                'data' => [
                    'message' => 'Account deleted successfully',
                ],
            ]);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        $trashed = Customer::withTrashed()->whereKey($customer->id)->firstOrFail();
        $this->assertSame(Customer::STATUS_DELETED, $trashed->status);
        $this->assertSame("deleted_{$customer->id}_".self::TEST_PHONE, $trashed->phone);
        $this->assertSame("deleted_{$customer->id}_delete-me@example.com", $trashed->email);
    }

    public function test_delete_account_fails_with_wrong_password(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $this->withCustomerToken($customer)
            ->deleteJson('/api/v1/customer/account', [
                'password' => 'WrongPass1!',
            ])
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Password is incorrect',
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => self::TEST_PHONE,
            'deleted_at' => null,
        ]);
    }

    public function test_delete_account_requires_password(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $this->withCustomerToken($customer)
            ->deleteJson('/api/v1/customer/account', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_fails_after_self_delete(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $this->withCustomerToken($customer)
            ->deleteJson('/api/v1/customer/account', [
                'password' => self::VALID_PASSWORD,
            ])
            ->assertOk();

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ])
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_reregister_with_same_phone_succeeds_after_self_delete(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $this->withCustomerToken($customer)
            ->deleteJson('/api/v1/customer/account', [
                'password' => self::VALID_PASSWORD,
            ])
            ->assertOk();

        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
            'otp_token' => $otpToken,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customers', [
            'phone' => self::TEST_PHONE,
            'deleted_at' => null,
            'status' => Customer::STATUS_PENDING,
        ]);

        $newCustomer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();
        $this->assertNotSame($customer->id, $newCustomer->id);
        $this->assertTrue(Hash::check('NewPass1!', $newCustomer->password));
    }

    public function test_protected_routes_require_auth(): void
    {
        $this->getJson('/api/v1/customer/profile')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);

        $this->postJson('/api/v1/customer/profile/complete')
            ->assertStatus(401);

        $this->postJson('/api/v1/customer/profile/update')
            ->assertStatus(401);

        $this->postJson('/api/v1/customer/auth/logout')
            ->assertStatus(401);

        $this->deleteJson('/api/v1/customer/account')
            ->assertStatus(401);

        $this->postJson('/api/v1/customer/password/change')
            ->assertStatus(401);

        $this->getJson('/api/v1/customer/banners')
            ->assertStatus(401);

        $this->getJson('/api/v1/customer/notifications')
            ->assertStatus(401);

        $this->getJson('/api/v1/customer/services/catalog')
            ->assertStatus(401);

        $this->getJson('/api/v1/customer/services/home')
            ->assertStatus(401);

        $this->getJson('/api/v1/customer/partners/00000000-0000-4000-8000-000000000099')
            ->assertStatus(401);
    }

    public function test_can_list_countries(): void
    {
        $this->createCountryAndCity();

        $response = $this->getJson('/api/v1/countries');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Countries retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'shortName', 'code', 'name'],
                ],
            ]);
    }

    public function test_can_list_cities_by_dial_code(): void
    {
        [$country, $city] = $this->createCountryAndCity();

        $response = $this->getJson('/api/v1/countries/249/cities');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Cities retrieved successfully',
                'data' => [
                    [
                        'id' => $city->id,
                        'countryId' => $country->id,
                    ],
                ],
            ]);
    }

    public function test_list_cities_returns_not_found_for_unknown_dial_code(): void
    {
        $response = $this->getJson('/api/v1/countries/999/cities');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Country not found',
            ]);
    }

    public function test_can_list_banners_for_customer_country(): void
    {
        [$country] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'country_id' => $country->id,
        ]);

        Advertisement::query()->create([
            'name' => 'Summer Promo',
            'image' => 'promo.jpg',
            'country_id' => $country->id,
            'status' => 'active',
        ]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/banners');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'image_url', 'country_id'],
                ],
            ]);
    }

    public function test_can_list_notifications(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/notifications');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    public function test_can_get_notification_unread_count(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/notifications/unread-count');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => 0,
            ]);
    }

    private function verifiedSmsOtpToken(string $phone): string
    {
        $smsResponse = $this->postJson('/api/v1/customer/otp/sms', [
            'phone' => $phone,
        ]);

        $smsResponse->assertCreated();

        $verifyResponse = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $smsResponse->json('data.token'),
            'code' => 111111,
        ]);

        $verifyResponse->assertCreated();

        return $verifyResponse->json('data.otp_token');
    }

    private function withCustomerToken(Customer $customer): self
    {
        $auth = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $customerPayload
     */
    private function assertCustomerAuthPayloadExcludesMerchantFields(?array $customerPayload): void
    {
        $this->assertIsArray($customerPayload);
        $this->assertArrayHasKey('status', $customerPayload);
        $this->assertArrayNotHasKey('merchantId', $customerPayload);
        $this->assertArrayNotHasKey('merchantCountryId', $customerPayload);
    }

    /**
     * @return array{0: Country, 1: City}
     */
    private function createCountryAndCity(): array
    {
        $country = Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $city = City::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Khartoum'],
            'country_id' => $country->id,
            'status' => true,
        ]);

        return [$country, $city];
    }
}
