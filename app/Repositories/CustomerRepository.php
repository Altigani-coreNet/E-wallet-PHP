<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\PaymentByLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CustomerRepository
{
    public function queryByMerchant(int $merchantId): Builder
    {
        return Customer::query()->where('merchant_id', $merchantId);
    }

    public function paginateByMerchant(int $merchantId, int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = $this->queryByMerchant($merchantId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findByIdAndMerchant(int $customerId, int $merchantId): ?Customer
    {
        return Customer::where('id', $customerId)
            ->where('merchant_id', $merchantId)
            ->first();
    }

    public function emailExistsForMerchant(string $email, int $merchantId, ?int $exceptCustomerId = null): bool
    {
        $query = Customer::where('email', $email)->where('merchant_id', $merchantId);
        if ($exceptCustomerId) {
            $query->where('id', '!=', $exceptCustomerId);
        }
        return $query->exists();
    }

    public function emailExistsGlobally(string $email, ?int $exceptCustomerId = null): bool
    {
        $query = Customer::where('email', $email);
        if ($exceptCustomerId) {
            $query->where('id', '!=', $exceptCustomerId);
        }

        return $query->exists();
    }

    public function create(array $attributes): Customer
    {
        return Customer::create($attributes);
    }

    public function update(Customer $customer, array $attributes): Customer
    {
        $customer->update($attributes);
        return $customer;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function latestPaymentLinkForCustomer(int $merchantId, int $customerId): ?PaymentByLink
    {
        return PaymentByLink::where('merchant_id', $merchantId)
            ->where('customer_id', $customerId)
            ->latest()
            ->first();
    }
}


