<?php

namespace Tests\Unit;

use App\Models\Customer;
use Tests\TestCase;

class CustomerStatusTest extends TestCase
{
    public function test_manageable_statuses_are_defined(): void
    {
        $this->assertSame(
            ['pending', 'active', 'suspended', 'inactive'],
            Customer::MANAGEABLE_STATUSES
        );
    }

    public function test_wallet_access_is_only_allowed_for_active_customers(): void
    {
        foreach (Customer::MANAGEABLE_STATUSES as $status) {
            $customer = new Customer(['status' => $status]);
            $this->assertSame($status === Customer::STATUS_ACTIVE, $customer->canAccessWallet());
        }
    }

    public function test_wallet_login_block_reason_matches_status(): void
    {
        $pending = new Customer(['status' => Customer::STATUS_PENDING]);
        $this->assertSame('Your account is pending approval.', $pending->walletLoginBlockReason());

        $active = new Customer(['status' => Customer::STATUS_ACTIVE]);
        $this->assertNull($active->walletLoginBlockReason());

        $suspended = new Customer(['status' => Customer::STATUS_SUSPENDED]);
        $this->assertStringContainsString('suspended', strtolower((string) $suspended->walletLoginBlockReason()));

        $inactive = new Customer(['status' => Customer::STATUS_INACTIVE]);
        $this->assertStringContainsString('inactive', strtolower((string) $inactive->walletLoginBlockReason()));
    }

    public function test_new_customer_defaults_to_pending_on_create(): void
    {
        $customer = Customer::factory()->make(['status' => null]);
        $customer->save();

        $this->assertSame(Customer::STATUS_PENDING, $customer->fresh()->status);
    }
}
