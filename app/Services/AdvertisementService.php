<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Repositories\AdvertisementRepository;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\Facades\DataTables;

class AdvertisementService
{
    use HasFiles;
    
    protected $advertisementRepository;

    public function __construct(AdvertisementRepository $advertisementRepository)
    {
        $this->advertisementRepository = $advertisementRepository;
    }

    /**
     * Create a new advertisement
     */
    public function create(array $data): Advertisement
    {
        // Handle image upload
        if (isset($data['image'])) {
            $imagePath = $this->uploadImageAndGetFileName(
                request(),
                'image',
                'advertisements'
            );
            $data['image'] = $imagePath;
        }

        return $this->advertisementRepository->create($data);
    }

    /**
     * Update an existing advertisement
     */
    public function update(Advertisement $advertisement, array $data): Advertisement
    {
        // Handle image upload
        if (isset($data['image']) && request()->hasFile('image')) {
            // Delete old image
            if ($advertisement->image && File::exists(public_path($advertisement->image))) {
                File::delete(public_path($advertisement->image));
            }

            $imagePath = $this->uploadImageAndGetFileName(
                request(),
                'image',
                'advertisements'
            );
            $data['image'] = $imagePath;
        } else {
            // Keep the old image if no new image is uploaded
            unset($data['image']);
        }

        return $this->advertisementRepository->update($advertisement, $data);
    }

    /**
     * Delete an advertisement
     */
    public function delete(Advertisement $advertisement): bool
    {
        // Delete the image file
        if ($advertisement->image && File::exists(public_path($advertisement->image))) {
            File::delete(public_path($advertisement->image));
        }

        return $this->advertisementRepository->delete($advertisement);
    }

    /**
     * Bulk delete advertisements
     */
    public function bulkDelete(array $ids): bool
    {
        $advertisements = Advertisement::whereIn('id', $ids)->get();
        
        foreach ($advertisements as $advertisement) {
            // Delete image file
            if ($advertisement->image && File::exists(public_path($advertisement->image))) {
                File::delete(public_path($advertisement->image));
            }
            
            $advertisement->delete();
        }
        
        return true;
    }

    /**
     * Get all advertisements for API
     */
    public function getAllAdvertisements(Request $request)
    {
        $query = Advertisement::with('country');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhereHas('country', function ($countryQuery) use ($searchValue) {
                      $countryQuery->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Country filter
        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get DataTable data for advertisements
     */
    public function getDataTableData(Request $request)
    {
        $query = Advertisement::with('country');

        // Country filter
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filters
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date_from . ' 00:00:00',
                $request->date_to . ' 23:59:59',
            ]);
        } elseif ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        } elseif ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Search functionality
        $textSearch = is_array($request->search) ? ($request->search['value'] ?? null) : $request->get('search');
        if (!empty($textSearch)) {
            $query->where(function ($q) use ($textSearch) {
                $q->where('name', 'like', "%{$textSearch}%")
                  ->orWhereHas('country', function ($countryQuery) use ($textSearch) {
                      $countryQuery->where('name', 'like', "%{$textSearch}%");
                  });
            });
        }

        return DataTables::of($query)
            ->addColumn('id', fn($advertisement) => $advertisement->id)
            ->addColumn('name', fn($advertisement) => $advertisement->name)
            ->addColumn('image', fn($advertisement) => $advertisement->image ? (function_exists('coreservice_asset') ? coreservice_asset($advertisement->image) : asset($advertisement->image)) : null)
            ->addColumn('country_name', fn($advertisement) => $advertisement->country->name ?? '-')
            ->addColumn('country_id', fn($advertisement) => $advertisement->country_id)
            ->addColumn('status', fn($advertisement) => $advertisement->status)
            ->addColumn('start_date', fn($advertisement) => $advertisement->start_date ? $advertisement->start_date->format('Y-m-d') : '-')
            ->addColumn('end_date', fn($advertisement) => $advertisement->end_date ? $advertisement->end_date->format('Y-m-d') : '-')
            ->addColumn('created_at', fn($advertisement) => $advertisement->created_at->format('Y-m-d H:i:s'))
            ->make(true);
    }
}


