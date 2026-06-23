<?php

namespace App\Repositories;

use App\Models\Advertisement;

class AdvertisementRepository
{
    protected $model;

    public function __construct(Advertisement $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new advertisement
     */
    public function create(array $data): Advertisement
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing advertisement
     */
    public function update(Advertisement $advertisement, array $data): Advertisement
    {
        $advertisement->update($data);
        return $advertisement->fresh();
    }

    /**
     * Delete an advertisement
     */
    public function delete(Advertisement $advertisement): bool
    {
        return $advertisement->delete();
    }

    /**
     * Find advertisement by ID
     */
    public function findById(int $id): ?Advertisement
    {
        return $this->model->find($id);
    }

    /**
     * Get advertisements by country
     */
    public function getAdvertisementsByCountry(int $countryId)
    {
        return $this->model->where('country_id', $countryId)->get();
    }

    /**
     * Get active advertisements
     */
    public function getActiveAdvertisements()
    {
        return $this->model->where('status', 'active')->get();
    }
}


