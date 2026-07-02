<?php

namespace App\Modules\CustomerAuth\Requests;

use App\Models\CustomerRejection;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRejectedFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('customer')->check();
    }

    public function rules(): array
    {
        $customer = Auth::guard('customer')->user();
        $rejection = CustomerRejection::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->first();

        if (! $rejection) {
            return [];
        }

        return $this->buildValidationRules($rejection);
    }

    private function buildValidationRules(CustomerRejection $rejection): array
    {
        $validationRules = [];
        $invalidFields = $rejection->invalid_fields ?? [];
        $missingAttachments = $rejection->missing_attachments ?? [];

        $fieldRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'national_id' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'gender' => 'required|string|in:male,female',
            'country' => 'required|uuid|exists:countries,id',
            'city' => 'required|uuid|exists:cities,id',
        ];

        foreach ($invalidFields as $field) {
            if (isset($fieldRules[$field])) {
                $validationRules[$field] = $fieldRules[$field];
            }
        }

        foreach ($missingAttachments as $attachment) {
            $apiKey = CustomerAttachmentService::normalizeMissingAttachmentKey($attachment);

            if ($apiKey === CustomerAttachmentService::MISSING_ATTACHMENT_PICTURE) {
                $validationRules['picture'] = 'required|file|image|max:2048';
            }

            if ($apiKey === CustomerAttachmentService::MISSING_ATTACHMENT_PASSPORT) {
                $validationRules['passport'] = 'required|file|mimes:jpeg,jpg,png,pdf|max:2048';
            }
        }

        return $validationRules;
    }
}
