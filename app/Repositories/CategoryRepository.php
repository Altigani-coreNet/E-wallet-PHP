<?php

namespace App\Repositories;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\MessageManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class CategoryRepository implements CategoryService
{
    use MessageManager;

    public function index(): View
    {
        return view('categories.index');
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(CategoryRequest $request)
    {
        try {
            $imagePath = $this->uploadImageAndGetFileName($request, 'image', 'categories_images');

            $category = new Category();

            $category
                ->setTranslation('name', 'en', $request->name['en'])
                ->setTranslation('name', 'ar', $request->name['ar'] ?? $request->name['en']);

            $category->status = $request->status;
            $category->image = $imagePath;
            $category->shop_id = auth()->user()->shop_id;
            $category->save();

      } catch (\Exception $exception) {
            throw new \Exception("Some Thing Went Wrong");
        }
    }

    public function show(Category $category): RedirectResponse
    {
        return redirect()->route('subcategories.index', ["type" => $category->type, "parent" => $category->id]);
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $imagePath = $this->uploadImageAndGetFileName($request, 'image', 'categories_images');

        $category
            ->setTranslation('name', 'en', $request->name['en'])
            ->setTranslation('name', 'ar', $request->name['ar'] ?? $request->name['en']);

        $category->status = $request->status;

        if ($imagePath) $category->image = $imagePath;

        $category->save();

        return redirect()->route('categories.index', ['type' => $category->type])->with('success', ___("translation.category_updated_successfully"));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')->with('success', ___("translation.category_deleted_successfully"));
    }

    public function data(): JsonResponse
    {
        $query = Category::withCount("SubCategories")->where("shop_id", auth()->user()->shop_id);

        if (request()->has('type')) {
            $query->where('type', request()->input('type'));
        }

        return DataTables::of($query)
            ->addColumn('record_select', 'users.data_table.record_select')
            ->editColumn("name", fn($item) => $item->name)
            ->editColumn('status', fn($item) => $item->getStatusWithSpan())
            ->editColumn('image', fn($item) => \view('categories.data_table.profile_image', compact('item'))->render())
            ->editColumn('sub_categories_count', 'categories.data_table.sub_categories_count')
            ->addColumn('actions', 'categories.data_table.actions')
            ->rawColumns(['record_select', "sub_categories_count", 'actions', 'status', "image"])
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

    public function getCategories()
    {
        return Category::where("status", 1)->limit(10)->get();
    }

    public function changeStatus(Category $category)
    {
        $category->changeStatus();

        return redirect()->route('categories.index', ["type" => $category->type])->with('success', ___("translation.status_changed_successfully"));
    }

    public function getAllCategories($category_type = null): Collection
    {
        return Category::where("status", 1)
            ->when($category_type, function ($query, $category_type) {
                return $query->where('type', $category_type);
            })->limit(10)->get();
    }

    public function mapCategoryFromJson(array $categoryData): Category|array|bool
    {
        try {
            $category = Category::create(
                [
                    'name' => [
                        "en" => $categoryData["c_title"],
                        "ar" => $categoryData["c_title_arab"],
                    ],
                    "image" => $categoryData["c_image"],
                    "status" => $categoryData["c_active"],
                    "type" => strtolower($categoryData["screen"]),
                    "old_id" => $categoryData["c_id"],
                ]
            );
            return $category;
        } catch (\Exception $exception) {
            return ["error" => $exception->getMessage()];
        }
    }

    public function getCategoriesName(): Collection|array
    {
        $query = Category::select("id", "name")
            ->active();

        if (request()->has('type')) {
            $query->where('type', request()->input('type'));
        }

        return $query->get();
    }

    public function CategoriesWithSubCategoriesCount(): Collection|array
    {
        $query = Category::select("id", "name")
            ->withCount(["SubCategories" => function ($query) {
                $query->where("status", 1);
            }])
            ->active();

        if (request()->has('type')) {
            $query->where('type', request()->input('type'));
        }

        return $query->get();
    }

    public function getCategoryWithSubCategories()
    {
        $query = Category::active()->select("id", "name")->with([
            "SubCategories" => function ($q) {
                return $q->select('id', 'name', 'parent_id')->active();
            },
        ]);

        if (request()->has("type")) {
            $query->where('type', request()->type);
        }

        if (request()->has("id")) {
            $query->where('id', request()->id);
        }

        return $query->get();
    }
}
