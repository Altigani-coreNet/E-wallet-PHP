<?php

namespace App\Repositories;

use App\Models\Merchant;

class MerchantRepository extends MerchantRepov2
{
    protected $model;

    public function __construct(Merchant $model)
    {
        $this->model = $model;
    }

    /**
     * Get all merchants
     */
    public function all()
    {
        return $this->model->with(['country', 'city', 'currency', 'plan'])->get();
    }

   

    /**
     * Update a merchant
     */
    

    /**
     * Delete a merchant
     */
   

    /**
     * Find merchant by ID
     */
    public function find($id)
    {
        return $this->model->with(['country', 'city', 'currency', 'user', 'users', 'branches', 'terminals', 'attachments', 'plan'])->find($id);
    }

    /**
     * Find merchant by ID or fail
     */
    public function findOrFail($id)
    {
        return $this->model->with(['country', 'city', 'currency', 'user', 'users', 'branches', 'terminals', 'attachments', 'plan'])->findOrFail($id);
    }

    /**
     * Get paginated merchants with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15)
    {
        // Only fetch what the Merchants Management list needs
        $query = $this->model->query()
            ->select([
                'id',
                'user_id',
                'name',
                'owner_name',
                'business_name',
                'email',
                'phone',
                'logo',
                'business_type',
                'status',
                'is_active',
                'country_id',
                'city_id',
                'plan_id',
                'created_at',
                'updated_at',
            ])
            ->with([
                'country:id,name',
                'city:id,name',
                'plan:id,name',
            ]);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('business_name', 'like', "%{$filters['search']}%")
                  ->orWhere('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Bulk delete merchants by IDs
     */
    public function bulkDelete(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    public function getDashboardStatisticsV2(?string $merchantId = null): array
    {
        return [
            'totalTerminals' => 0,
            'activeTerminals' => 0,
            'totalUsers' => 0,
            'totalBranches' => 0,
            'inactiveTerminals' => 0,
            'onlineTerminals' => 0,
            'offlineTerminals' => 0,
            'merchant' => $merchantId,
        ];
    }


    

}


