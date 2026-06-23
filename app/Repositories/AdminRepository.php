<?php

namespace App\Repositories;

use App\Models\Admin;

class AdminRepository
{
    protected $model;

    public function __construct(Admin $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new admin
     */
    public function create(array $data): Admin
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing admin
     */
    public function update(Admin $admin, array $data): Admin
    {
        $admin->update($data);
        return $admin->fresh();
    }

    /**
     * Delete an admin
     */
    public function delete(Admin $admin): bool
    {
        return $admin->delete();
    }

    /**
     * Find admin by ID
     */
    public function findById(int $id): ?Admin
    {
        return $this->model->find($id);
    }

    /**
     * Find admin by email
     */
    public function findByEmail(string $email): ?Admin
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get all admins with pagination
     */
    public function getAllPaginated(int $perPage = 10)
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Get active admins
     */
    public function getActiveAdmins()
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Search admins
     */
    public function search(string $query)
    {
        return $this->model->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%")
                          ->orWhere('phone', 'like', "%{$query}%")
                          ->get();
    }
}


