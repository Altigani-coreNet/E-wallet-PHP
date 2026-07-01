<?php

namespace Tests\Feature;

use App\Events\AdminKycQueueUpdatedEvent;
use App\Events\AdminNotificationEvent;
use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\AdminKyc\Notifications\AdminKycNotificationType;
use App\Modules\AdminKyc\Services\AdminKycNotificationService;
use App\Modules\AdminKyc\Services\PrimaryAdminResolver;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Notifications\TestUserNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminKycNotificationTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private Country $country;

    private City $city;

    private Admin $primaryAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        [$this->country, $this->city] = $this->seedCountryAndCity();

        $this->primaryAdmin = Admin::factory()->active()->create([
            'created_at' => now()->subDays(5),
        ]);
    }

    public function test_profile_complete_notifies_primary_admin(): void
    {
        Mail::fake();
        Event::fake([AdminNotificationEvent::class, AdminKycQueueUpdatedEvent::class]);

        $customer = Customer::factory()->create([
            'phone' => '+249977'.random_int(100000, 999999),
            'profile_completed' => false,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->withCustomerToken($customer)
            ->post('/api/v1/customer/profile/complete', $this->profilePayload($this->city), [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'type' => TestUserNotification::class,
            'notifiable_type' => Admin::class,
            'notifiable_id' => (string) $this->primaryAdmin->id,
            'target_type' => 'admin',
            'topic' => 'kyc',
            'source' => 'system',
            'is_admin' => true,
        ]);

        Event::assertDispatched(AdminNotificationEvent::class, function (AdminNotificationEvent $event) {
            return $event->targetAdminId === (string) $this->primaryAdmin->id;
        });

        Event::assertDispatched(AdminKycQueueUpdatedEvent::class);
    }

    public function test_duplicate_profile_complete_does_not_double_notify_admin(): void
    {
        Mail::fake();
        Event::fake([AdminNotificationEvent::class, AdminKycQueueUpdatedEvent::class]);

        $customer = Customer::factory()->create([
            'phone' => '+249978'.random_int(100000, 999999),
            'profile_completed' => true,
            'status' => Customer::STATUS_PENDING,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->withCustomerToken($customer)
            ->post('/api/v1/customer/profile/complete', $this->profilePayload($this->city), [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => Admin::class,
            'topic' => 'kyc',
        ]);
        Event::assertNotDispatched(AdminNotificationEvent::class);
    }

    public function test_admin_kyc_notification_service_uses_primary_admin_resolver(): void
    {
        Event::fake([AdminNotificationEvent::class, AdminKycQueueUpdatedEvent::class]);

        $customer = Customer::factory()->create([
            'name' => 'KYC Test Customer',
            'phone' => '+249979'.random_int(100000, 999999),
            'status' => Customer::STATUS_PENDING,
        ]);

        app(AdminKycNotificationService::class)->send(
            $customer,
            AdminKycNotificationType::CustomerProfileCompleted,
        );

        $resolved = PrimaryAdminResolver::resolve();
        $this->assertNotNull($resolved);
        $this->assertSame((string) $this->primaryAdmin->id, (string) $resolved->id);
    }

    public function test_approve_customer_broadcasts_queue_update(): void
    {
        Event::fake([AdminKycQueueUpdatedEvent::class]);

        $customer = Customer::factory()->create([
            'email' => 'approve-queue-'.uniqid().'@example.com',
            'phone' => '+249980'.random_int(100000, 999999),
            'status' => Customer::STATUS_PENDING,
            'profile_completed' => true,
            'password' => bcrypt('Password1!'),
        ]);

        Passport::actingAs($this->primaryAdmin, [], 'admin-api');

        $this->postJson("/api/v2/admin/customers/{$customer->id}/approve")
            ->assertOk();

        Event::assertDispatched(AdminKycQueueUpdatedEvent::class);
    }

    private function withCustomerToken(Customer $customer): self
    {
        $auth = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
            'Accept' => 'application/json',
        ]);
    }

    private function profilePayload(City $city): array
    {
        return [
            'firstName' => 'Ahmed',
            'nationalId' => 'NID-ADMIN-KYC-'.random_int(1000, 9999),
            'email' => 'admin-kyc-'.uniqid().'@example.com',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'cityId' => $city->id,
            'country_code' => '249',
            'picture' => UploadedFile::fake()->image('avatar.jpg'),
            'passport' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
        ];
    }
}
