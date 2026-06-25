<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Notifications\TestUserNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminCustomerNotificationApiTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private Admin $admin;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        [$this->country, $this->city] = $this->seedCountryAndCity();
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_admin_can_send_notification_to_customer(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Notify Me',
            'email' => 'notify@example.com',
            'phone' => '+249911100099',
        ]);

        $response = $this->actingAsAdminApi()->postJson('/api/v2/admin/notifications', [
            'topic' => 'alert',
            'target_type' => 'customer',
            'customer_id' => $customer->id,
            'title' => 'Account Update',
            'description' => 'Your account has been updated.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.target_type', 'customer')
            ->assertJsonPath('data.users_notified', 1);

        $this->assertDatabaseHas('notifications', [
            'type' => TestUserNotification::class,
            'notifiable_type' => Customer::class,
            'notifiable_id' => (string) $customer->id,
            'target_type' => 'customer',
            'title' => 'Account Update',
            'description' => 'Your account has been updated.',
            'source' => 'admin_management',
        ]);
    }

    public function test_customer_id_is_required_for_customer_target(): void
    {
        $response = $this->actingAsAdminApi()->postJson('/api/v2/admin/notifications', [
            'topic' => 'alert',
            'target_type' => 'customer',
            'title' => 'Missing Customer',
            'description' => 'No customer selected.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'customer_id is required for customer target');
    }

    public function test_customer_receives_admin_notification(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Inbox Customer',
            'email' => 'inbox@example.com',
            'phone' => '+249911100100',
        ]);

        $this->actingAsAdminApi()->postJson('/api/v2/admin/notifications', [
            'topic' => 'service_updates',
            'target_type' => 'customer',
            'customer_id' => $customer->id,
            'title' => 'New Feature',
            'description' => 'Check out our latest update.',
        ])->assertCreated();

        $listResponse = $this->withHeaders($this->customerAuthHeaders($customer))
            ->getJson('/api/v1/customer/notifications');

        $listResponse->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', 'New Feature')
            ->assertJsonPath('data.data.0.description', 'Check out our latest update.');

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->getJson('/api/v1/customer/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data', 1);
    }

    public function test_customer_can_mark_notification_as_read_and_delete(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Action Customer',
            'email' => 'action@example.com',
            'phone' => '+249911100101',
        ]);

        $this->actingAsAdminApi()->postJson('/api/v2/admin/notifications', [
            'topic' => 'payments',
            'target_type' => 'customer',
            'customer_id' => $customer->id,
            'title' => 'Payment Reminder',
            'description' => 'Please complete your payment.',
        ])->assertCreated();

        $notificationId = DatabaseNotification::query()
            ->where('notifiable_type', Customer::class)
            ->where('notifiable_id', (string) $customer->id)
            ->value('id');

        $this->assertNotNull($notificationId);

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson("/api/v1/customer/notifications/{$notificationId}/mark-as-read")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(
            DatabaseNotification::query()->find($notificationId)?->read_at
        );

        $this->withHeaders($this->customerAuthHeaders($customer))
            ->deleteJson("/api/v1/customer/notifications/{$notificationId}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
    }

    public function test_admin_can_lookup_customers_for_notification_target(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Lookup Customer',
            'email' => 'lookup@example.com',
            'phone' => '+249911100102',
        ]);

        $response = $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/notifications/lookups/customers/select');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => 'Lookup Customer',
                'email' => 'lookup@example.com',
            ]);

        $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/notifications/lookups/customers/select?search=Lookup')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $customer->id,
                'name' => 'Lookup Customer',
            ]);

        $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/notifications/lookups/customers/select?search=nonexistent-customer')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_admin_can_resend_customer_notification(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Resend Customer',
            'email' => 'resend@example.com',
            'phone' => '+249911100103',
        ]);

        $createResponse = $this->actingAsAdminApi()->postJson('/api/v2/admin/notifications', [
            'topic' => 'logs',
            'target_type' => 'customer',
            'customer_id' => $customer->id,
            'title' => 'System Log',
            'description' => 'Important system update.',
        ]);

        $createResponse->assertCreated();

        $notificationId = DatabaseNotification::query()
            ->where('notifiable_type', Customer::class)
            ->where('notifiable_id', (string) $customer->id)
            ->value('id');

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/notifications/{$notificationId}/resend")
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.resent', true);
    }

    public function test_admin_show_includes_customer_id_for_customer_target(): void
    {
        $customer = $this->createCustomer([
            'name' => 'Show Customer',
            'email' => 'show@example.com',
            'phone' => '+249911100104',
        ]);

        $this->actingAsAdminApi()->postJson('/api/v2/admin/notifications', [
            'topic' => 'alert',
            'target_type' => 'customer',
            'customer_id' => $customer->id,
            'title' => 'Show Me',
            'description' => 'Details for admin view.',
        ])->assertCreated();

        $notificationId = DatabaseNotification::query()
            ->where('notifiable_type', Customer::class)
            ->where('notifiable_id', (string) $customer->id)
            ->value('id');

        $this->actingAsAdminApi()
            ->getJson("/api/v2/admin/notifications/{$notificationId}")
            ->assertOk()
            ->assertJsonPath('data.target_type', 'customer')
            ->assertJsonPath('data.customer_id', (string) $customer->id);
    }

    private function createCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Test Customer',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2499'.fake()->unique()->numerify('#######'),
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
        ], $attributes));
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this->withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'X-App-Locale' => 'en',
        ]);
    }
}
