<?php

namespace App\Services;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Collection;

interface BrandService
{
    public function index();
    public function data();
    public function create();
    public function store(BrandRequest $request);
    public function show(Brand $brand);
    public function edit(Brand $brand);
    public function update(BrandRequest $request, Brand $brand);
    public function destroy(Brand $brand);
    public function getBrands();
    public function changeStatus(Brand $brand);
    public function getAllBrands();
    public function getBrandsName(): Collection|array;
} 