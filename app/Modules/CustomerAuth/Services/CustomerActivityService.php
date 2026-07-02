<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Support\CustomerActivityActions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerActivityService
{
    private const MAX_PER_PAGE = 50;

    private const DEFAULT_PER_PAGE = 15;

    public function paginateForCustomer(Customer $customer, ?string $actionFilter, int $perPage): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), self::MAX_PER_PAGE);

        $query = $customer->logs()->latest('created_at')->latest('id');

        $actions = ($actionFilter !== null && $actionFilter !== '')
            ? [$actionFilter]
            : CustomerActivityActions::ALL;

        $query->whereIn('action', $actions);

        return $query->paginate($perPage);
    }
}
