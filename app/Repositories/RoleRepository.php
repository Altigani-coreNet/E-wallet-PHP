<?php

namespace App\Repositories;

use Spatie\Permission\Models\Role;

class RoleRepository
{
    protected $model;

    public function __construct(Role $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new role
     */
    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing role
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);
        return $role->fresh();
    }

    /**
     * Delete a role
     */
    public function delete(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * Find role by ID
     */
    public function findById(int $id): ?Role
    {
        return $this->model->find($id);
    }

    /**
     * Get roles by guard
     */
    public function getRolesByGuard(string $guardName)
    {
        return $this->model->where('guard_name', $guardName)->get();
    }

    /**
     * Find role by name and guard
     */
    public function findByNameAndGuard(string $name, string $guardName): ?Role
    {
        return $this->model->where('name', $name)
                          ->where('guard_name', $guardName)
                          ->first();
    }
}


