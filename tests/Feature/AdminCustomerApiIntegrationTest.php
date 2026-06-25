<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Country;
use App\Models\City;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminCustomerApiIntegrationTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

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

    public function test_admin_can_create_customer_with_pending_status_by_default(): void
    {
        $response = $this->actingAsAdminApi()->postJson('/api/cashier/v2/admin/customers', [
            'name' => 'Pending Customer',
            'email' => 'pending@example.com',
            'phone' => '+249911100001',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Customer::STATUS_PENDING)
            ->assertJsonPath('data.uuid', fn ($uuid) => is_string($uuid) && $uuid !== '');

        $this->assertDatabaseHas('customers', [
            'email' => 'pending@example.com',
            'phone' => '+249911100001',
            'status' => Customer::STATUS_PENDING,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);
    }

    public function test_admin_can_create_customer_with_explicit_active_status(): void
    {
        $response = $this->actingAsAdminApi()->postJson('/api/cashier/v2/admin/customers', [
            'name' => 'Active Customer',
            'email' => 'active@example.com',
            'phone' => '+249911100002',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Customer::STATUS_ACTIVE);

        $this->assertDatabaseHas('customers', [
            'email' => 'active@example.com',
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_show_customer_by_uuid(): void
    {
        $customer = $this->createCustomer([
            'email' => 'show@example.com',
            'phone' => '+249911100003',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAsAdminApi()
            ->getJson("/api/cashier/v2/admin/customers/{$customer->uuid}");

        $response->assertOk()
            ->assertJsonPath('data.uuid', $customer->uuid)
            ->assertJsonPath('data.email', 'show@example.com')
            ->assertJsonPath('data.status', Customer::STATUS_ACTIVE)
            ->assertJsonPath('data.country_name', 'Sudan')
            ->assertJsonPath('data.city_name', 'Khartoum');
    }

    public function test_admin_update_persists_changes_in_database(): void
    {
        $customer = $this->createCustomer([
            'email' => 'update@example.com',
            'phone' => '+249911100004',
            'name' => 'Before Update',
            'status' => Customer::STATUS_PENDING,
        ]);

        $response = $this->actingAsAdminApi()->putJson(
            "/api/cashier/v2/admin/customers/{$customer->uuid}",
            [
                'name' => 'After Update',
                'email' => 'updated@example.com',
                'phone' => '+249911100004',
                'address' => 'New Street',
                'country_id' => $this->country->id,
                'city_id' => $this->city->id,
                'status' => Customer::STATUS_ACTIVE,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.name', 'After Update')
            ->assertJsonPath('data.status', Customer::STATUS_ACTIVE);

        $this->assertDatabaseHas('customers', [
            'uuid' => $customer->uuid,
            'name' => 'After Update',
            'email' => 'updated@example.com',
            'address' => 'New Street',
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_update_status_via_status_endpoint(): void
    {
        $customer = $this->createCustomer([
            'email' => 'status@example.com',
            'phone' => '+249911100005',
            'status' => Customer::STATUS_PENDING,
        ]);

        $response = $this->actingAsAdminApi()->postJson(
            "/api/cashier/v2/admin/customers/{$customer->uuid}/status",
            ['status' => Customer::STATUS_SUSPENDED]
        );

        $response->assertOk()
            ->assertJsonPath('data.status', Customer::STATUS_SUSPENDED);

        $this->assertDatabaseHas('customers', [
            'uuid' => $customer->uuid,
            'status' => Customer::STATUS_SUSPENDED,
        ]);
    }

    public function test_admin_index_filters_by_status(): void
    {
        $this->createCustomer([
            'email' => 'pending-filter@example.com',
            'phone' => '+249911100006',
            'status' => Customer::STATUS_PENDING,
        ]);

        $this->createCustomer([
            'email' => 'active-filter@example.com',
            'phone' => '+249911100007',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAsAdminApi()->getJson(
            '/api/cashier/v2/admin/customers?'.http_build_query([
                'status' => Customer::STATUS_ACTIVE,
                'search' => 'active-filter@example.com',
            ])
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.email', 'active-filter@example.com')
            ->assertJsonPath('data.data.0.status', Customer::STATUS_ACTIVE);
    }

    public function test_wallet_registration_sets_pending_status(): void
    {
        $registration = $this->registerCustomer('+249911100008', self::VALID_PASSWORD);
        $registration['response']->assertCreated();

        $this->assertDatabaseHas('customers', [
            'phone' => '+249911100008',
            'status' => Customer::STATUS_PENDING,
        ]);
    }

    public function test_pending_customer_cannot_login(): void
    {
        $this->createCustomer([
            'email' => 'pending-login@example.com',
            'phone' => '+249911100009',
            'password' => Hash::make(self::VALID_PASSWORD),
            'status' => Customer::STATUS_PENDING,
        ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => '+249911100009',
            'password' => self::VALID_PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_active_customer_can_login(): void
    {
        $this->createCustomer([
            'email' => 'active-login@example.com',
            'phone' => '+249911100010',
            'password' => Hash::make(self::VALID_PASSWORD),
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
            'name' => 'Active Login',
        ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => '+249911100010',
            'password' => self::VALID_PASSWORD,
        ])->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_inactive_customer_cannot_login(): void
    {
        $this->createCustomer([
            'email' => 'inactive-login@example.com',
            'phone' => '+249911100011',
            'password' => Hash::make(self::VALID_PASSWORD),
            'status' => Customer::STATUS_INACTIVE,
        ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => '+249911100011',
            'password' => self::VALID_PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_admin_delete_soft_deletes_and_corrupts_identifiers(): void
    {
        $customer = $this->createCustomer([
            'email' => 'delete@example.com',
            'phone' => '+249911100012',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->actingAsAdminApi()
            ->deleteJson("/api/cashier/v2/admin/customers/{$customer->uuid}")
            ->assertOk();

        $this->assertSoftDeleted('customers', ['uuid' => $customer->uuid]);

        $trashed = Customer::withTrashed()->where('uuid', $customer->uuid)->firstOrFail();
        $this->assertSame(Customer::STATUS_DELETED, $trashed->status);
        $this->assertSame("deleted_{$customer->id}_+249911100012", $trashed->phone);
        $this->assertSame("deleted_{$customer->id}_delete@example.com", $trashed->email);
    }

    public function test_admin_bulk_delete_uses_uuids_and_updates_database(): void
    {
        $first = $this->createCustomer([
            'email' => 'bulk1@example.com',
            'phone' => '+249911100013',
        ]);
        $second = $this->createCustomer([
            'email' => 'bulk2@example.com',
            'phone' => '+249911100014',
        ]);

        $this->actingAsAdminApi()->postJson('/api/cashier/v2/admin/customers/bulk-delete', [
            'ids' => [$first->uuid, $second->uuid],
        ])->assertOk()
            ->assertJsonPath('data.deleted_count', 2);

        $this->assertSoftDeleted('customers', ['uuid' => $first->uuid]);
        $this->assertSoftDeleted('customers', ['uuid' => $second->uuid]);
    }

    public function test_status_endpoint_rejects_invalid_status(): void
    {
        $customer = $this->createCustomer([
            'email' => 'invalid-status@example.com',
            'phone' => '+249911100015',
        ]);

        $this->actingAsAdminApi()->postJson(
            "/api/cashier/v2/admin/customers/{$customer->uuid}/status",
            ['status' => 'deleted']
        )->assertUnprocessable();
    }

    public function test_suspended_customer_cannot_login(): void
    {
        $this->createCustomer([
            'email' => 'suspended-login@example.com',
            'phone' => '+249911100016',
            'password' => Hash::make(self::VALID_PASSWORD),
            'status' => Customer::STATUS_SUSPENDED,
            'profile_completed' => true,
        ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => '+249911100016',
            'password' => self::VALID_PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_full_status_lifecycle_persists_each_transition_in_database(): void
    {
        $customer = $this->createCustomer([
            'email' => 'lifecycle@example.com',
            'phone' => '+249911100017',
            'status' => Customer::STATUS_PENDING,
        ]);

        $transitions = [
            Customer::STATUS_ACTIVE,
            Customer::STATUS_SUSPENDED,
            Customer::STATUS_INACTIVE,
            Customer::STATUS_ACTIVE,
        ];

        foreach ($transitions as $status) {
            $this->actingAsAdminApi()->postJson(
                "/api/cashier/v2/admin/customers/{$customer->uuid}/status",
                ['status' => $status]
            )->assertOk()
                ->assertJsonPath('data.status', $status);

            $this->assertDatabaseHas('customers', [
                'uuid' => $customer->uuid,
                'status' => $status,
            ]);
        }
    }

    public function test_admin_index_returns_all_manageable_statuses(): void
    {
        foreach (Customer::MANAGEABLE_STATUSES as $index => $status) {
            $this->createCustomer([
                'email' => "{$status}-list@example.com",
                'phone' => '+2499112'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
                'status' => $status,
            ]);
        }

        $response = $this->actingAsAdminApi()->getJson('/api/cashier/v2/admin/customers?per_page=50');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $statuses = collect($response->json('data.data'))->pluck('status')->unique()->values()->all();
        $this->assertEqualsCanonicalizing(Customer::MANAGEABLE_STATUSES, $statuses);
    }

    private function createCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Test Customer',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2499'.fake()->unique()->numerify('#######'),
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'status' => Customer::STATUS_PENDING,
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
