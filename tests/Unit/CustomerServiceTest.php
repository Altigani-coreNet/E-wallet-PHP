<?php

namespace Tests\Unit;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class CustomerServiceTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private CustomerService $customerService;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        [$this->country, $this->city] = $this->seedCountryAndCity();
        $this->customerService = app(CustomerService::class);
    }

    public function test_create_defaults_status_to_pending(): void
    {
        $customer = $this->customerService->create([
            'name' => 'Service Create',
            'email' => 'service-create@example.com',
            'phone' => '+249911200001',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->assertSame(Customer::STATUS_PENDING, $customer->status);
        $this->assertDatabaseHas('customers', [
            'email' => 'service-create@example.com',
            'status' => Customer::STATUS_PENDING,
        ]);
    }

    public function test_update_persists_changes(): void
    {
        $customer = $this->createCustomer([
            'email' => 'service-update@example.com',
            'phone' => '+249911200002',
            'name' => 'Before Service Update',
        ]);

        $updated = $this->customerService->update($customer, [
            'name' => 'After Service Update',
            'email' => 'service-updated@example.com',
            'phone' => '+249911200002',
            'address' => 'Service Street',
        ]);

        $this->assertSame('After Service Update', $updated->name);
        $this->assertSame('service-updated@example.com', $updated->email);
        $this->assertSame('Service Street', $updated->address);
    }

    public function test_update_rejects_duplicate_email(): void
    {
        $this->createCustomer([
            'email' => 'existing@example.com',
            'phone' => '+249911200003',
        ]);

        $customer = $this->createCustomer([
            'email' => 'service-update-dup@example.com',
            'phone' => '+249911200004',
        ]);

        $this->expectException(ValidationException::class);

        $this->customerService->update($customer, [
            'name' => 'Duplicate Email Attempt',
            'email' => 'existing@example.com',
            'phone' => '+249911200004',
        ]);
    }

    public function test_update_status_changes_status(): void
    {
        $customer = $this->createCustomer([
            'email' => 'status@example.com',
            'phone' => '+249911200005',
            'status' => Customer::STATUS_PENDING,
        ]);

        $updated = $this->customerService->updateStatus($customer, Customer::STATUS_SUSPENDED);

        $this->assertSame(Customer::STATUS_SUSPENDED, $updated->status);
    }

    public function test_toggle_status_suspends_active_customer(): void
    {
        $customer = $this->createCustomer([
            'email' => 'toggle@example.com',
            'phone' => '+249911200006',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $updated = $this->customerService->toggleStatus($customer);

        $this->assertSame(Customer::STATUS_SUSPENDED, $updated->status);
    }

    public function test_delete_soft_deletes_and_corrupts_identifiers(): void
    {
        $customer = $this->createCustomer([
            'email' => 'delete-service@example.com',
            'phone' => '+249911200007',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->customerService->delete($customer);

        $trashed = Customer::withTrashed()->where('uuid', $customer->uuid)->firstOrFail();
        $this->assertSame(Customer::STATUS_DELETED, $trashed->status);
        $this->assertSame("deleted_{$customer->id}_+249911200007", $trashed->phone);
        $this->assertSame("deleted_{$customer->id}_delete-service@example.com", $trashed->email);
        $this->assertNotNull($trashed->deleted_at);
    }

    public function test_bulk_delete_by_uuid_returns_deleted_count(): void
    {
        $first = $this->createCustomer([
            'email' => 'bulk-service-1@example.com',
            'phone' => '+249911200008',
        ]);
        $second = $this->createCustomer([
            'email' => 'bulk-service-2@example.com',
            'phone' => '+249911200009',
        ]);

        $deletedCount = $this->customerService->bulkDeleteByUuid([$first->uuid, $second->uuid]);

        $this->assertSame(2, $deletedCount);
        $this->assertSoftDeleted('customers', ['uuid' => $first->uuid]);
        $this->assertSoftDeleted('customers', ['uuid' => $second->uuid]);
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
            'status' => Customer::STATUS_PENDING,
            'profile_completed' => true,
        ], $attributes));
    }
}
