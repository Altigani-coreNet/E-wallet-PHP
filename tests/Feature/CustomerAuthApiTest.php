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
        $customer = Customer::factory()->create([
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
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => ['token'],
            ]);
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
                'data' => ['token'],
            ]);

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->first();
        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $customer->password));
    }

    public function test_can_refresh_token(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
        ])->postJson('/api/v1/customer/auth/refresh-token');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Token refreshed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'customer' => ['id'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
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
                    ],
                ],
            ]);
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
            ->patch('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'email' => 'ahmed@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('avatar.jpg'),
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
            'profile_completed' => true,
        ]);

        $customer->refresh();
        $this->assertNotNull($customer->profile_image);
        $this->assertFileExists(public_path($customer->profile_image));

        Mail::assertSent(CustomerWelcomeMail::class, function (CustomerWelcomeMail $mail) {
            return $mail->hasTo('ahmed@example.com')
                && $mail->customerName === 'Ahmed'
                && $mail->phone === self::TEST_PHONE;
        });
    }

    public function test_can_complete_profile_without_picture(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $response = $this->withCustomerToken($customer)
            ->patchJson('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'email' => 'ahmed@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.profile_completed', true)
            ->assertJsonPath('data.customer.profileCompleted', true)
            ->assertJsonPath('data.customer.profileImage', null);

        $customer->refresh();
        $this->assertNull($customer->profile_image);
        Mail::assertSent(CustomerWelcomeMail::class);
    }

    public function test_can_update_profile_without_changing_email_or_phone(): void
    {
        Mail::fake();

        [$country, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'email' => 'existing@example.com',
            'name' => 'Old Name',
            'profile_completed' => true,
        ]);

        $response = $this->withCustomerToken($customer)
            ->patch('/api/v1/customer/profile/update', [
                'firstName' => 'Updated Name',
                'birthDate' => '1992-03-20',
                'gender' => 'female',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('new-avatar.jpg'),
            ], [
                'Accept' => 'application/json',
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
            ->patch('/api/v1/customer/profile/complete', [
                'firstName' => 'Ahmed',
                'email' => 'taken@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('avatar.jpg'),
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Email is already in use',
            ]);
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

    public function test_protected_routes_require_auth(): void
    {
        $this->getJson('/api/v1/customer/profile')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);

        $this->patchJson('/api/v1/customer/profile/complete')
            ->assertStatus(401);

        $this->patchJson('/api/v1/customer/profile/update')
            ->assertStatus(401);

        $this->postJson('/api/v1/customer/auth/logout')
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
                    '*' => ['id', 'shortName', 'code', 'dialCode', 'name'],
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
     * @return array{0: Country, 1: City}
     */
    private function createCountryAndCity(): array
    {
        $country = Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan'],
            'short_name' => 'SD',
            'code' => 'SD',
            'dial_code' => '249',
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
