<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\Select2Response;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\MessageManager;
use App\Traits\Select2Trait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use Select2Trait, MessageManager;

    public function __construct(private CategoryService $categoryService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->categoryService->index();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->categoryService->create();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        try {
            $this->categoryService->store($request);
            $this->SuccessMessage(___("translation.category_added_successfully"));
            return redirect()->route('categories.index', ['type' => $request->type]);
        } catch (\Exception $e) {
            $this->ErrorMessage(___("translation.something_went_worng"));
            return redirect()->route('categories.index', ['type' => $request->type]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        if (\request()->has('status')) {
            return $this->categoryService->changeStatus($category);
        }
        return $this->categoryService->show($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return $this->categoryService->edit($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category)
    {
        return $this->categoryService->update($request, $category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        return $this->categoryService->destroy($category);
    }

    public function data(): JsonResponse
    {
        return $this->categoryService->data();
    }

    public function getSelectData(Request $request): JsonResponse
    {
        return $this->getSelect2Data($request, Category::class, ["name"]);
    }

    public function getCategoryName(Request $request): JsonResponse
    {
        $ids = (explode(',', $request->ids));
        $response = Category::select('name', "id")->whereIn("id", $ids)->get();
        return response()->json(Select2Response::collection($response));
    }

}
