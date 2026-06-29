<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminCustomerSoftDeleteTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const TEST_PHONE = '+249987654321';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        $this->seedCountryAndCity();

        $this->admin = Admin::factory()->active()->create();
    }

    public function test_soft_delete_sets_deleted_at_status_and_corrupts_phone_and_email(): void
    {
        $registration = $this->registerCustomer(self::TEST_PHONE, self::VALID_PASSWORD);
        $registration['response']->assertCreated();

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();
        $customer->update(['email' => 'deleted-test@example.com']);

        $response = $this->actingAsAdminApi()
            ->deleteJson("/api/v2/admin/customers/{$customer->id}");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => true,
                'message' => 'Customer deleted successfully',
            ]);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        $trashed = Customer::withTrashed()->whereKey($customer->id)->firstOrFail();
        $this->assertSame(Customer::STATUS_DELETED, $trashed->status);
        $this->assertSame("deleted_{$customer->id}_".self::TEST_PHONE, $trashed->phone);
        $this->assertSame("deleted_{$customer->id}_deleted-test@example.com", $trashed->email);
        $this->assertNotNull($trashed->deleted_at);
    }

    public function test_admin_index_excludes_soft_deleted_customers(): void
    {
        $registration = $this->registerCustomer(self::TEST_PHONE, self::VALID_PASSWORD);
        $registration['response']->assertCreated();

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();

        $this->actingAsAdminApi()
            ->deleteJson("/api/v2/admin/customers/{$customer->id}")
            ->assertOk();

        $indexResponse = $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/customers', [
                'search' => self::TEST_PHONE,
            ]);

        $indexResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.data', []);
    }

    public function test_login_fails_after_soft_delete(): void
    {
        $registration = $this->registerCustomer(self::TEST_PHONE, self::VALID_PASSWORD);
        $registration['response']->assertCreated();

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();
        $customer->update(['status' => Customer::STATUS_ACTIVE]);

        $this->actingAsAdminApi()
            ->deleteJson("/api/v2/admin/customers/{$customer->id}")
            ->assertOk();

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $loginResponse->assertUnauthorized()
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_reregister_with_same_phone_succeeds_after_soft_delete(): void
    {
        $registration = $this->registerCustomer(self::TEST_PHONE, self::VALID_PASSWORD);
        $registration['response']->assertCreated();

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();

        $this->actingAsAdminApi()
            ->deleteJson("/api/v2/admin/customers/{$customer->id}")
            ->assertOk();

        $reRegister = $this->registerCustomer(self::TEST_PHONE, 'NewPass1!');

        $reRegister['response']->assertCreated()
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

    public function test_bulk_delete_corrupts_and_soft_deletes_every_id(): void
    {
        $first = $this->registerCustomer('+249911111111', self::VALID_PASSWORD);
        $second = $this->registerCustomer('+249922222222', self::VALID_PASSWORD);

        $firstCustomer = Customer::query()->where('phone', '+249911111111')->firstOrFail();
        $secondCustomer = Customer::query()->where('phone', '+249922222222')->firstOrFail();

        $response = $this->actingAsAdminApi()
            ->postJson('/api/v2/admin/customers/bulk-delete', [
                'ids' => [$firstCustomer->id, $secondCustomer->id],
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => true,
                'message' => '2 customers deleted successfully',
            ]);

        $this->assertSoftDeleted('customers', ['id' => $firstCustomer->id]);
        $this->assertSoftDeleted('customers', ['id' => $secondCustomer->id]);

        $firstTrashed = Customer::withTrashed()->whereKey($firstCustomer->id)->firstOrFail();
        $secondTrashed = Customer::withTrashed()->whereKey($secondCustomer->id)->firstOrFail();

        $this->assertSame(Customer::STATUS_DELETED, $firstTrashed->status);
        $this->assertSame(Customer::STATUS_DELETED, $secondTrashed->status);
        $this->assertSame("deleted_{$firstCustomer->id}_+249911111111", $firstTrashed->phone);
        $this->assertSame("deleted_{$secondCustomer->id}_+249922222222", $secondTrashed->phone);
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this->withHeaders([
            'Accept' => 'application/json',
        ]);
    }
}
