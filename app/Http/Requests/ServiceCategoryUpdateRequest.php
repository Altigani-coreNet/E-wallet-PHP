<?php

namespace App\Http\Requests;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceCategoryUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        $name = $this->input('name');
        $payload = [];

        if ($name && !$this->filled('name_en')) {
            $payload['name_en'] = $name;
        }

        if ($name && !$this->filled('name_ar')) {
            $payload['name_ar'] = $name;
        }

        if ($status !== null && !$this->has('is_active')) {
            $payload['is_active'] = in_array((string) $status, ['1', 'true', 'on'], true);
        }

        if (!empty($payload)) {
            $this->merge($payload);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('id');
        $existingType = ServiceCategory::query()->whereKey($categoryId)->value('type');
        $effectiveType = $this->input('type') ?? $existingType ?? 'service';

        return [
            'type' => ['sometimes', 'in:service,partner'],
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('service_categories', 'code')
                    ->ignore($categoryId)
                    ->where(fn ($q) => $q->where('type', $effectiveType)),
            ],
            'parent_id' => 'nullable|exists:service_categories,id',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:5120',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = ServiceCategory::find($this->route('id'));
            if (!$category) {
                return;
            }
            $type = $this->input('type', $category->type);
            $parentId = $this->input('parent_id', $category->parent_id);
            if (!$parentId) {
                return;
            }
            $parent = ServiceCategory::find($parentId);
            if (!$parent || $parent->type !== $type) {
                $validator->errors()->add('parent_id', 'Parent category must be of the same type.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name_en.required' => 'The category name in English is required.',
            'name_ar.required' => 'The category name in Arabic is required.',
            'code.required' => 'The category code is required.',
            'code.unique' => 'This category code already exists for this type.',
        ];
    }
}
