<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('unit')?->id;
        return [
            'name' => 'required|array',
            'name.*' => 'required|string',
            'code' => 'required|string|unique:units,code,' . $id,
            'shop_id' => 'nullable|exists:shops,id',
            'base_unit_id' => 'nullable|exists:units,id',
//            'operator' => 'nullable|string|in:*,/,+,-',
            'operation_value' => 'nullable|numeric',
            'status' => 'boolean'
        ];
    }
}
