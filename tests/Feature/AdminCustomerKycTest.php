<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ChangeHistory;
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

    public function test_change_history_index_returns_applied_customer_changes(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'history-'.uniqid().'@example.com',
            'phone' => '+24997'.random_int(1000000, 9999999),
            'name' => 'Before Name',
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        $changeRequest = ChangeRequest::query()->create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => ['name' => 'After Name'],
            'status' => 'pending',
        ]);

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/change-requests/{$changeRequest->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('change_histories', [
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'change_request_id' => $changeRequest->id,
        ]);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/customers/kyc/change-history');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['data' => ['data', 'total']]);

        $row = collect($response->json('data.data'))->first();
        $this->assertNotNull($row);
        $this->assertSame('After Name', $customer->fresh()->name);
        $this->assertContains('Name', $row['changed_fields'] ?? []);
    }

    public function test_change_history_show_returns_before_and_after(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'history-detail-'.uniqid().'@example.com',
            'phone' => '+24998'.random_int(1000000, 9999999),
            'name' => 'Original Name',
            'status' => Customer::STATUS_REQUESTING_UPDATED,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
        ]);

        $changeRequest = ChangeRequest::query()->create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => ['name' => 'Updated Name'],
            'status' => 'pending',
        ]);

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/change-requests/{$changeRequest->id}/approve")
            ->assertOk();

        $history = ChangeHistory::query()
            ->where('change_request_id', $changeRequest->id)
            ->firstOrFail();

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/kyc/change-history/{$history->id}");

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.change_request_id', $changeRequest->id)
            ->assertJsonPath('data.changes.0.field', 'name')
            ->assertJsonPath('data.changes.0.before', 'Original Name')
            ->assertJsonPath('data.changes.0.after', 'Updated Name');
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

    public function test_admin_customer_show_includes_verification_and_counts(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'admin-show-'.uniqid().'@example.com',
            'phone' => '+24998'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_PENDING,
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'password' => Hash::make('Password1!'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $customer->logs()->create([
            'action' => 'registered',
            'metadata' => ['message' => 'Registered'],
            'user_id' => $customer->id,
            'user_type' => Customer::class,
        ]);

        ChangeRequest::query()->create([
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'requester_type' => Customer::class,
            'requester_id' => $customer->id,
            'payload' => ['name' => 'Updated Name'],
            'status' => 'pending',
        ]);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/customers/'.$customer->id);

        $response->assertOk()
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('data.phone_verified', true)
            ->assertJsonPath('data.events_count', 1)
            ->assertJsonPath('data.change_requests_count', 1)
            ->assertJsonStructure([
                'data' => [
                    'email_verified_at',
                    'phone_verified_at',
                    'transactions_count',
                    'profile_completion' => [
                        'completion',
                        'missing',
                        'status',
                        'documents',
                    ],
                ],
            ])
            ->assertJsonPath('data.profile_completion.completion', fn ($value) => is_int($value) && $value >= 0 && $value <= 100);
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this;
    }
}
