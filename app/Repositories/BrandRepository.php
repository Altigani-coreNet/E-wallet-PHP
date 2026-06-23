<?php

namespace App\Repositories;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use App\Traits\MessageManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class BrandRepository implements BrandService
{
    use MessageManager;

    public function index(): View
    {
        return view('brands.index');
    }

    public function create(): View
    {
        return view('brands.create');
    }

    public function store(BrandRequest $request)
    {
        try {
            $imagePath = $this->uploadImageAndGetFileName($request, 'image', 'brands_images');

            $brand = new Brand();
            $brand
                ->setTranslation('name', 'en', $request->name['en'])
                ->setTranslation('name', 'ar', $request->name['ar'] ?? $request->name['en']);

            $brand->status = $request->status;
            $brand->image = $imagePath;
            $brand->shop_id = auth()->user()->shop_id;
            $brand->save();

        } catch (\Exception $exception) {
            throw new \Exception("Something Went Wrong");
        }
    }

    public function show(Brand $brand): RedirectResponse
    {
        return redirect()->route('brands.index');
    }

    public function edit(Brand $brand): View
    {
        return view('brands.edit', compact('brand'));
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $imagePath = $this->uploadImageAndGetFileName($request, 'image', 'brands_images');

        $brand
            ->setTranslation('name', 'en', $request->name['en'])
            ->setTranslation('name', 'ar', $request->name['ar'] ?? $request->name['en']);

        $brand->status = $request->status;

        if ($imagePath) $brand->image = $imagePath;

        $brand->save();

        return redirect()->route('brands.index')->with('success', ___("translation.brand_updated_successfully"));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();
        return redirect()->route('brands.index')->with('success', ___("translation.brand_deleted_successfully"));
    }

    public function data(): JsonResponse
    {
//        dd('bor');
        $query = Brand::where("shop_id", auth()->user()->shop_id);

        return DataTables::of($query)
            ->addColumn('record_select', 'users.data_table.record_select')
            ->editColumn("name", fn($item) => $item->name)
            ->editColumn('status', fn($item) => $item->getStatusWithSpan())
            ->editColumn('image', fn($item) => \view('brands.data_table.profile_image', compact('item'))->render())
            ->addColumn('actions', 'brands.data_table.actions')
            ->rawColumns(['record_select', 'actions', 'status', "image"])
            ->toJson();
    }

    public function uploadImageAndGetFileName($request, string $property, string $filename): ?string
    {
        $imagePath = null;
        if ($request->hasFile($property)) {
            $image = $request->file($property);
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path($filename), $imageName);
            $imagePath = $filename . '/' . $imageName;
        }
        return $imagePath;
    }

    public function getBrands()
    {
        return Brand::where("status", 1)->limit(10)->get();
    }

    public function changeStatus(Brand $brand)
    {
        $brand->changeStatus();
        return redirect()->route('brands.index')->with('success', ___("translation.status_changed_successfully"));
    }

    public function getAllBrands(): Collection
    {
        return Brand::where("status", 1)->limit(10)->get();
    }

    public function getBrandsName(): Collection|array
    {
        return Brand::select("id", "name")
            ->active()
            ->get();
    }
}
