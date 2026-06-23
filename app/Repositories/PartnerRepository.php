<?php

namespace App\Repositories;

use App\Models\Partner;
use Carbon\Carbon;

class PartnerRepository
{
    protected $model;

    public function __construct(Partner $model)
    {
        $this->model = $model;
    }

    /**
     * Get all partners
     */
    public function all()
    {
        return $this->model->with(['user'])->get();
    }

    /**
     * Create a new partner
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a partner
     */
    public function update(Partner $partner, array $data)
    {
        $partner->update($data);
        return $partner->fresh();
    }

    /**
     * Delete a partner
     */
    public function delete(Partner $partner)
    {
        return $partner->delete();
    }

    /**
     * Find partner by ID
     */
    public function find($id)
    {
        return $this->model->with(['user', 'attachments'])->find($id);
    }

    /**
     * Find partner by ID or fail
     */
    public function findOrFail($id)
    {
        return $this->model->with(['user', 'attachments'])->findOrFail($id);
    }

    /**
     * Get paginated partners with filters
     */
    public function getPaginated(array $filters = [], int $perPage = 15)
    {
        $relations = ['user', 'country', 'partnerCategory'];
        if (! empty($filters['sub_partners_only'])) {
            $relations[] = 'parentPartner';
        }

        $query = $this->model->query()->with($relations)->withCount('subPartners');

        if (! empty($filters['sub_partners_only'])) {
            $query->whereNotNull($this->model->getTable().'.parent_id');
            if (! empty($filters['parent_id'])) {
                $query->where($this->model->getTable().'.parent_id', $filters['parent_id']);
            }
        } elseif (! empty($filters['parent_id'])) {
            $query->where($this->model->getTable().'.parent_id', $filters['parent_id']);
        } else {
            $query->whereNull($this->model->getTable().'.parent_id');
        }

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('phone', 'like', "%{$filters['search']}%")
                  ->orWhere('merchant_code', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['partner_category_id'])) {
            $query->where('partner_category_id', $filters['partner_category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Bulk delete partners by IDs
     */
    public function bulkDelete(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}


