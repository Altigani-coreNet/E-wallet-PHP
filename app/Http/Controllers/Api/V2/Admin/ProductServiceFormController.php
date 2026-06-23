<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductServiceFormsSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductServiceFormController extends Controller
{
    public function index(string $productId): JsonResponse
    {
        $product = Product::query()->findOrFail($productId);
        $forms = ProductServiceFormsSyncService::toResponseArray($product);

        return response()->json([
            'success' => true,
            'data' => $forms,
        ]);
    }

    /**
     * Replace all forms for the product (simple & predictable for the builder).
     */
    public function store(Request $request, string $productId): JsonResponse
    {
        $product = Product::query()->findOrFail($productId);

        $payload = $request->validate([
            'forms' => 'required|array|min:1',
            'forms.*.form_name' => 'nullable|array',
            'forms.*.form_name.en' => 'nullable|string|max:255',
            'forms.*.form_name.ar' => 'nullable|string|max:255',
            'forms.*.form_url' => 'nullable|string|max:2048',
            'forms.*.country_id' => 'nullable|uuid',
            'forms.*.fields' => 'required|array|min:1',
            'forms.*.fields.*.label_en' => 'nullable|string|max:255',
            'forms.*.fields.*.label_ar' => 'nullable|string|max:255',
            'forms.*.fields.*.key' => 'required|string|max:255',
            'forms.*.fields.*.type' => 'required|string|max:255',
            'forms.*.fields.*.options' => 'nullable|array',
            'forms.*.fields.*.customization' => 'nullable|array',
            'forms.*.fields.*.customization.min_length' => 'nullable|numeric|min:0',
            'forms.*.fields.*.customization.max_length' => 'nullable|numeric|min:0',
            'forms.*.fields.*.customization.regex' => 'nullable|string|max:1000',
            'forms.*.fields.*.customization.hint' => 'nullable|string|max:255',
            // For number fields, min/max remain numeric; for date fields they are validated separately in controller logic.
            'forms.*.fields.*.customization.min' => 'nullable',
            'forms.*.fields.*.customization.max' => 'nullable',
            'forms.*.fields.*.sort_order' => 'nullable|integer|min:0',
            'forms.*.fields.*.is_required' => 'nullable|boolean',
            'forms.*.fields.*.status' => 'nullable|boolean',
            'forms.*.fields.*.country_id' => 'nullable|uuid',
        ]);

        app(ProductServiceFormsSyncService::class)->sync($product, $payload['forms']);

        return $this->index($productId);
    }
}

