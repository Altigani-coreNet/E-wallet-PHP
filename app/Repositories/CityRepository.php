<?php

namespace App\Repositories;

use App\Models\City;

class CityRepository
{
    protected $model;

    public function __construct(City $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new city
     */
    public function create(array $data): City
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing city
     */
    public function update(City $city, array $data): City
    {
        $city->update($data);
        return $city->fresh();
    }

    /**
     * Delete a city
     */
    public function delete(City $city): bool
    {
        return $city->delete();
    }

    /**
     * Find city by ID
     */
    public function findById(int $id): ?City
    {
        return $this->model->find($id);
    }

    /**
     * Get cities by country
     */
    public function getCitiesByCountry(int $countryId)
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * Get active cities
     */
    public function getActiveCities()
    {
        return $this->model->where('status', 1)->get();
    }
}


