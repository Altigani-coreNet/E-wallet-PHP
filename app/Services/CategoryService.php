<?php

namespace App\Services;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryService
{
    public function index();

    public function data();

    public function create();

    public function store(CategoryRequest $request);

    public function show(Category $category);

    public function edit(Category $category);

    public function update(CategoryRequest $request, Category $category);

    public function destroy(Category $category);

    public function getCategories();

    public function changeStatus(Category $category);

    public function getAllCategories($category_type);

    public function mapCategoryFromJson(array $categoryData): Category|array|bool;

    public function getCategoriesName(): Collection|array;

    public function CategoriesWithSubCategoriesCount(): Collection|array;

    public function getCategoryWithSubCategories();
}
