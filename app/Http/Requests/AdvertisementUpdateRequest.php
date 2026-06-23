<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementUpdateRequest extends FormRequest
{
    private const BANNER_IMAGE_DIMENSIONS = 'dimensions:min_width=300,min_height=300,max_width=2000,max_height=2000';

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
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',  //.self::BANNER_IMAGE_DIMENSIONS,
            'country_id' => 'required|exists:countries,id',
            'status' => 'required|in:active,inactive',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The advertisement image may not be greater than 2MB.',
            'image.dimensions' => 'The banner image must be between 300×300 and 2000×2000 pixels.',
        ];
    }

    // public function withValidator($validator): void
    // {
    //     $validator->after(function ($validator) {
    //         $image = $this->file('image');
    //         if (!$image) {
    //             return;
    //         }

    //         $size = @getimagesize($image->getPathname());
    //         if (!$size) {
    //             return;
    //         }

    //         [$width, $height] = $size;
    //         if ($height <= $width) {
    //             $validator->errors()->add('image', 'The banner image must have height greater than width.');
    //         }
    //     });
    // }
}
