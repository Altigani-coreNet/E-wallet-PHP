<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ChangeRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminCustomerLogsAndChangeRequestsTest extends CustomerAuthTestCase
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

    public function test_admin_can_list_customer_logs(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'logs-'.uniqid().'@example.com',
            'phone' => '+24995'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_PENDING,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $customer->logs()->create([
            'action' => 'approved',
            'metadata' => ['message' => 'Customer profile approved by Admin'],
            'user_id' => $this->admin->id,
            'user_type' => Admin::class,
        ]);

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$customer->id}/logs");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data.data.0.action', 'approved');
    }

    public function test_admin_can_list_formatted_customer_change_requests(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'changes-'.uniqid().'@example.com',
            'phone' => '+24996'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'name' => 'Original Name',
        ]);

        ChangeRequest::create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => [
                'name' => 'Updated Name',
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'city_id' => $this->city->id,
                'country_id' => $this->country->id,
                '__meta' => ['previous_status' => Customer::STATUS_ACTIVE],
            ],
            'status' => 'pending',
            'has_file' => false,
        ]);

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$customer->id}/change-requests");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonPath('data.0.changeable_type', 'customer')
            ->assertJsonPath('data.0.changed_fields', fn ($fields) => in_array('Name', $fields, true));
    }

    public function test_admin_can_view_customer_change_request_detail(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'detail-'.uniqid().'@example.com',
            'phone' => '+24997'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'name' => 'Original Name',
        ]);

        $changeRequest = ChangeRequest::create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => [
                'name' => 'Updated Name',
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'city_id' => $this->city->id,
                'country_id' => $this->country->id,
                '__meta' => ['previous_status' => Customer::STATUS_ACTIVE],
            ],
            'status' => 'pending',
            'has_file' => false,
        ]);

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$customer->id}/change-requests/{$changeRequest->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $changeRequest->id)
            ->assertJsonPath('data.changes.Name.current', 'Original Name')
            ->assertJsonPath('data.changes.Name.requested', 'Updated Name');
    }

    public function test_approving_customer_change_request_writes_event_log(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'approve-log-'.uniqid().'@example.com',
            'phone' => '+24998'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'name' => 'Original Name',
        ]);

        $changeRequest = ChangeRequest::create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => [
                'name' => 'Updated Name',
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'city_id' => $this->city->id,
                'country_id' => $this->country->id,
                '__meta' => ['previous_status' => Customer::STATUS_ACTIVE],
            ],
            'status' => 'pending',
            'has_file' => false,
        ]);

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/change-requests/{$changeRequest->id}/approve",
            ['moderation_note' => 'Looks good']
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Customer::STATUS_ACTIVE);

        $this->assertDatabaseHas('logs', [
            'loggable_type' => Customer::class,
            'loggable_id' => $customer->id,
            'action' => 'change_request_approved',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this;
    }
}
