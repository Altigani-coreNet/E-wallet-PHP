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

class AdminCustomerKycTest extends CustomerAuthTestCase
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

    public function test_pending_list_returns_only_pending_customers(): void
    {
        $pending = Customer::factory()->create([
            'email' => 'pending-'.uniqid().'@example.com',
            'phone' => '+24991'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_PENDING,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        Customer::factory()->create([
            'email' => 'rejected-'.uniqid().'@example.com',
            'phone' => '+24992'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REJECTED,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/customers/kyc/pending');

        $response->assertOk()
            ->assertJsonPath('status', true);

        $ids = collect($response->json('data.data'))->pluck('id')->all();
        $this->assertContains((string) $pending->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_global_events_index_returns_customer_logs(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'events-'.uniqid().'@example.com',
            'phone' => '+24993'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_PENDING,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        $customer->logs()->create([
            'action' => 'profile_completed',
            'metadata' => ['message' => 'Submitted KYC profile for review'],
            'user_id' => $customer->id,
            'user_type' => Customer::class,
        ]);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/customers/kyc/events');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['data', 'total']]);

        $this->assertTrue(
            collect($response->json('data.data'))->contains(fn ($row) => $row['action'] === 'profile_completed')
        );
    }

    public function test_queue_counts_endpoint_returns_pending_totals(): void
    {
        Customer::factory()->create([
            'email' => 'queue-'.uniqid().'@example.com',
            'phone' => '+24994'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_PENDING,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        $customer = Customer::factory()->create([
            'email' => 'queue-cr-'.uniqid().'@example.com',
            'phone' => '+24995'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        ChangeRequest::query()->create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => ['name' => 'Updated Name'],
            'status' => 'pending',
        ]);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/customers/kyc/queue-counts');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.pending_customers', 1)
            ->assertJsonPath('data.pending_change_requests', 1);
    }

    public function test_change_request_statistics_includes_customer_count(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'stats-'.uniqid().'@example.com',
            'phone' => '+24996'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        ChangeRequest::query()->create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => ['name' => 'Updated'],
            'status' => 'pending',
        ]);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/change-requests/statistics');

        $response->assertOk()
            ->assertJsonPath('data.pending.customer', 1);
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this;
    }
}
