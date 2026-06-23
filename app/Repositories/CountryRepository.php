<?php

namespace App\Repositories;

use App\Models\Country;

class CountryRepository
{
    protected $model;

    public function __construct(Country $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new country
     */
    public function create(array $data): Country
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing country
     */
    public function update(Country $country, array $data): Country
    {
        $country->update($data);
        return $country->fresh();
    }

    /**
     * Delete a country
     */
    public function delete(Country $country): bool
    {
        return $country->delete();
    }

    /**
     * Find country by ID
     */
    public function findById(int $id): ?Country
    {
        return $this->model->find($id);
    }

    /**
     * Get all countries
     */
    public function getAll()
    {
        return $this->model->all();
    }

    /**
     * Get active countries
     */
    public function getActiveCountries()
    {
        return $this->model->where('status', 1)->get();
    }
}


