<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Http\Resources\Select2Response;
use App\Models\Brand;
use App\Services\BrandService;
use App\Traits\MessageManager;
use App\Traits\Select2Trait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use Select2Trait, MessageManager;

    public function __construct(private BrandService $brandService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->brandService->index();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->brandService->create();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrandRequest $request)
    {
        try {
            $this->brandService->store($request);
            $this->SuccessMessage(___("translation.brand_added_successfully"));
            return redirect()->route('brands.index');
        } catch (\Exception $e) {
            $this->ErrorMessage(___("translation.something_went_worng"));
            return redirect()->route('brands.index');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        if (\request()->has('status')) {
            return $this->brandService->changeStatus($brand);
        }
        return $this->brandService->show($brand);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand)
    {
        return $this->brandService->edit($brand);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BrandRequest $request, Brand $brand)
    {
        return $this->brandService->update($request, $brand);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        return $this->brandService->destroy($brand);
    }

    public function data(): JsonResponse
    {
        return $this->brandService->data();
    }

    public function getSelectData(Request $request): JsonResponse
    {
        return $this->getSelect2Data($request, Brand::class, ["name"]);
    }

    public function getBrandName(Request $request): JsonResponse
    {
        $ids = (explode(',', $request->ids));
        $response = Brand::select('name', "id")->whereIn("id", $ids)->get();
        return response()->json(Select2Response::collection($response));
    }
}
