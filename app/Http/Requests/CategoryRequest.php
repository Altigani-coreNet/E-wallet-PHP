<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property array $name
 * @property string $image
 * @property bool $status
 * @property bool $active
 * @property int $id
 * @property mixed $type
 * @property mixed $url
 */
class CategoryRequest extends FormRequest
{

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
        return [
            "image" =>
                'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // For updating

            "name" => "required",
        ];
    }
}
