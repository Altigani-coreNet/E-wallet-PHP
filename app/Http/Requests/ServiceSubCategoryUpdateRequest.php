<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ServiceSubCategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'category_id' => 'sometimes|required|exists:service_categories,id',
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:service_sub_categories,code,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
