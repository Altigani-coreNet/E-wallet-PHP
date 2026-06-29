<?php

namespace Tests\Feature;

use App\Events\CustomerNotificationEvent;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Modules\CustomerAuth\Services\CustomerSystemNotificationService;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Notifications\TestUserNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\CustomerAuthTestCase;

class CustomerSystemNotificationTest extends CustomerAuthTestCase
{
    private const TEST_PHONE = '+249912345679';

    public function test_profile_completion_creates_system_notification(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/complete', $this->profilePayload($city))
            ->assertOk()
            ->assertJsonPath('data.profile_completed', true);

        $this->assertDatabaseHas('notifications', [
            'type' => TestUserNotification::class,
            'notifiable_type' => Customer::class,
            'notifiable_id' => (string) $customer->id,
            'target_type' => 'customer',
            'topic' => 'alert',
            'title' => 'Application received - we\'re on it',
            'description' => 'Thanks for completing your profile. We\'ve received your application and our team is reviewing it now. We\'ll let you know the moment your account is approved - no action needed from you.',
            'source' => 'system',
            'is_admin' => false,
        ]);
    }

    public function test_profile_completion_notification_appears_in_inbox(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/complete', $this->profilePayload($city))
            ->assertOk();

        $this->withCustomerToken($customer->fresh())
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', 'Application received - we\'re on it')
            ->assertJsonPath('data.data.0.description', 'Thanks for completing your profile. We\'ve received your application and our team is reviewing it now. We\'ll let you know the moment your account is approved - no action needed from you.')
            ->assertJsonPath('data.data.0.topic', 'alert');

        $this->withCustomerToken($customer->fresh())
            ->getJson('/api/v1/customer/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data', 1);
    }

    public function test_profile_completion_succeeds_when_notification_fails(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $mock = $this->partialMock(CustomerSystemNotificationService::class, function (MockInterface $mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('persistAndBroadcast')
                ->andThrow(new \RuntimeException('Notification dispatch failed'));
        });

        $this->app->instance(CustomerSystemNotificationService::class, $mock);

        $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/complete', $this->profilePayload($city))
            ->assertOk()
            ->assertJsonPath('data.profile_completed', true);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'profile_completed' => true,
        ]);

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_profile_completion_does_not_duplicate_notification(): void
    {
        Mail::fake();

        [, $city] = $this->createCountryAndCity();

        $notificationMock = $this->mock(CustomerSystemNotificationService::class);
        $notificationMock->shouldReceive('send')->once();

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/profile/complete', $this->profilePayload($city))
            ->assertOk();

        $this->assertEquals(0, DatabaseNotification::query()->count());
    }

    public function test_service_dispatches_customer_notification_event(): void
    {
        Event::fake([CustomerNotificationEvent::class]);

        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
            'profile_completed' => false,
        ]);

        app(CustomerSystemNotificationService::class)->send(
            $customer,
            CustomerNotificationType::ProfileCompleted,
        );

        Event::assertDispatched(CustomerNotificationEvent::class, function (CustomerNotificationEvent $event) use ($customer) {
            return $event->targetCustomerId === (string) $customer->id
                && $event->title === 'Application received - we\'re on it'
                && str_contains($event->message, 'Thanks for completing your profile');
        });

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => (string) $customer->id,
            'source' => 'system',
        ]);
    }

    public function test_service_send_does_not_throw_when_persist_fails(): void
    {
        $customer = Customer::factory()->create([
            'phone' => self::TEST_PHONE,
        ]);

        $mock = $this->partialMock(CustomerSystemNotificationService::class, function (MockInterface $mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('persistAndBroadcast')
                ->andThrow(new \RuntimeException('DB unavailable'));
        });

        $mock->send($customer, CustomerNotificationType::ProfileCompleted);

        $this->assertDatabaseCount('notifications', 0);
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

    private function profilePayload(City $city): array
    {
        return [
            'firstName' => 'Ahmed',
            'nationalId' => 'NID-SYSTEM-NOTIF-001',
            'email' => 'system-notif@example.com',
            'birthDate' => '1990-05-15',
            'gender' => 'male',
            'cityId' => $city->id,
            'country_code' => '249',
        ];
    }
}
