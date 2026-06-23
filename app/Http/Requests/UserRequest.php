<?php

namespace App\Http\Requests;

use http\Env\Request;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed $password
 */
class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
//        dd($this->all());
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = request('user')?->id;
        return [
            "name" => !$this->isMethod("post") ? "string" : "required|string",
            "email" => !$this->isMethod("post") ? "email" : "required|email|unique:users,email",
            "password" => !$this->isMethod("post") ? "" : "nullable|string",
            "phone" => $this->isMethod("post") ? "required|numeric|digits_between:1,12|unique:users,phone" : 'required|numeric|digits_between:1,12|unique:users,phone,' . $id,
            "city_id" => "exists:cities,id",
            "last_name" => !$this->isMethod("post") ? "nullable" : "",
            "brith_date" => "nullable|date",
            "country_id" => "exists:countries,id",
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            "nationality" => $this->is('api/*') ? "" : "exists:countries,id",
            "gender" => $this->is('api/*') ? "" : "string",
            "type" => "nullable|string",
            "merchant_id" => "nullable|exists:merchants,id",
            "branch_id" => "nullable|exists:branches,id",
        ];
    }

    // UserRequest.php
 


    public function autoMap($request): UserRequest
    {
        $this->replace($request->all());

        if ($request->file('profile_image') instanceof \Illuminate\Http\UploadedFile) {
            $this->files->set('profile_image', $request->file('profile_image'));
        }

        return $this;
    }
}
