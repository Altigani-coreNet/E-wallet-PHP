<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Modules\CustomerAuth\Services\CustomerAuthService;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\CustomerAuthTestCase;

class CustomerAuthServiceDeleteAccountTest extends CustomerAuthTestCase
{
    private const VALID_PASSWORD = 'DeleteAcct1!';

    public function test_delete_account_soft_deletes_with_corruption_when_password_is_correct(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+249912340001',
            'email' => 'self-delete@example.com',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $result = app(CustomerAuthService::class)->deleteAccount($customer, self::VALID_PASSWORD);

        $this->assertSame(['message' => 'Account deleted successfully'], $result);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        $trashed = Customer::withTrashed()->whereKey($customer->id)->firstOrFail();
        $this->assertSame(Customer::STATUS_DELETED, $trashed->status);
        $this->assertSame("deleted_{$customer->id}_+249912340001", $trashed->phone);
        $this->assertSame("deleted_{$customer->id}_self-delete@example.com", $trashed->email);
    }

    public function test_delete_account_rejects_incorrect_password(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+249912340002',
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Password is incorrect');

        app(CustomerAuthService::class)->deleteAccount($customer, 'WrongPass1!');
    }
}
