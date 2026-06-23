<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdvertisementStoreRequest;
use App\Http\Requests\AdvertisementUpdateRequest;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use App\Support\AdvertisementBroadcast;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminAdvertisementController extends Controller
{
    use ApiResponse;
    
    protected $advertisementService;

    public function __construct(AdvertisementService $advertisementService)
    {
        $this->advertisementService = $advertisementService;
    }

    /**
     * Display a listing of advertisements
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $advertisements = $this->advertisementService->getAllAdvertisements($request);
            return $this->SuccessMessage($advertisements);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch advertisements: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get advertisements data for DataTable
     */
    public function data(Request $request)
    {
        try {
            return $this->advertisementService->getDataTableData($request);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch advertisements data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created advertisement
     */
    public function store(AdvertisementStoreRequest $request): JsonResponse
    {
        try {
            $advertisement = $this->advertisementService->create($request->validated());
            AdvertisementBroadcast::broadcastFullListing();
            return $this->SuccessMessage($advertisement, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified advertisement
     */
    public function show($id): JsonResponse
    {
        try {
            $advertisement = Advertisement::with('country')->findOrFail($id);
            return $this->SuccessMessage($advertisement);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified advertisement
     */
    public function update(AdvertisementUpdateRequest $request, $id): JsonResponse
    {
        try {
            $advertisement = Advertisement::findOrFail($id);
            $advertisement = $this->advertisementService->update($advertisement, $request->validated());
            AdvertisementBroadcast::broadcastFullListing();

            return $this->SuccessMessage($advertisement, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified advertisement
     */
    public function destroy($id): JsonResponse
    {
        try {
            $advertisement = Advertisement::findOrFail($id);
            $this->advertisementService->delete($advertisement);
            AdvertisementBroadcast::broadcastFullListing();

            return $this->SuccessMessage( 'Advertisement deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete advertisement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete advertisements
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = explode(',', $request->ids);
            $this->advertisementService->bulkDelete($ids);
            AdvertisementBroadcast::broadcastFullListing();

            return $this->SuccessMessage('Advertisements deleted successfully');
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete advertisements: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change advertisement status
     */
    public function changeStatus($id): JsonResponse
    {
        try {
            $advertisement = Advertisement::findOrFail($id);
            $advertisement->status = $advertisement->status === 'active' ? 'inactive' : 'active';
            $advertisement->save();
            AdvertisementBroadcast::broadcastFullListing();

            return $this->SuccessMessage($advertisement, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change advertisement status: ' . $e->getMessage(), null, 500);
        }
    }
}

