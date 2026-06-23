<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;

class BranchRepository
{
    protected $model;

    public function __construct(Branch $model)
    {
        $this->model = $model;
    }

    public function query(array $filters = []): Builder
    {
        $query = $this->model->query()->with(['merchant', 'country', 'city']);

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('address', 'like', "%{$filters['search']}%")
                  ->orWhereHas('merchant', function($mq) use ($filters) {
                      $mq->where('business_name', 'like', "%{$filters['search']}%");
                  });
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (isset($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function paginate(array $filters, int $perPage = 15)
    {
        return $this->query($filters)->latest()->paginate($perPage);
    }

    public function all(array $filters = [])
    {
        return $this->query($filters)->latest()->get();
    }

    public function find(string $id)
    {
        return $this->model->with(['merchant', 'country', 'city'])->findOrFail($id);
    }

    public function create(array $data): Branch
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Branch
    {
        $branch = $this->model->findOrFail($id);
        $branch->update($data);
        return $branch->fresh();
    }

    public function delete(string $id): bool
    {
        $branch = $this->model->findOrFail($id);
        return $branch->delete();
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}
