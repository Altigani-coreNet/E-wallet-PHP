<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\ChangeRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class ChangeRequestFormatter
{
    protected array $cityCache = [];
    protected array $countryCache = [];
    protected array $userCache = [];

    /**
     * Build a summary payload for list views.
     */
    public function formatSummary(ChangeRequest $changeRequest): array
    {
        $changeRequest->loadMissing(['requester', 'approver', 'changeable']);

        return [
            'id' => $changeRequest->id,
            'status' => $changeRequest->status,
            'reason' => $changeRequest->reason,
            'changeable_id' => $changeRequest->changeable_id,
            'changeable_type' => $this->getTypeSlug($changeRequest),
            'changeable_label' => $this->getTypeLabel($changeRequest),
            'changeable_name' => $this->getChangeableName($changeRequest),
            'has_file' => $changeRequest->has_file,
            'requester' => $this->formatActor($changeRequest->requester),
            'approver' => $this->formatActor($changeRequest->approver),
            'created_at' => optional($changeRequest->created_at)->toISOString(),
            'approved_at' => optional($changeRequest->approved_at)->toISOString(),
            'rejected_at' => optional($changeRequest->rejected_at)->toISOString(),
        ];
    }

    /**
     * Build the detailed payload including comparison data.
     */
    public function formatDetail(ChangeRequest $changeRequest): array
    {
        $changeRequest->loadMissing(['requester', 'approver', 'changeable']);

        $payload = $changeRequest->payload ?? [];
        $mappedPayload = $this->mapPayloadFieldsWithComparison($payload, $changeRequest->changeable);

        return [
            'id' => $changeRequest->id,
            'request_type' => $this->getRequestType($payload),
            'reason' => $changeRequest->reason,
            'status' => $changeRequest->status,
            'changeable_id' => $changeRequest->changeable_id,
            'changeable_type' => $this->getTypeSlug($changeRequest),
            'changeable_label' => $this->getTypeLabel($changeRequest),
            'changeable_name' => $this->getChangeableName($changeRequest),
            'requester' => $this->formatActor($changeRequest->requester),
            'approver' => $this->formatActor($changeRequest->approver),
            'moderation_note' => $changeRequest->moderation_note,
            'created_at' => optional($changeRequest->created_at)->toISOString(),
            'approved_at' => optional($changeRequest->approved_at)->toISOString(),
            'rejected_at' => optional($changeRequest->rejected_at)->toISOString(),
            'changes' => $mappedPayload,
            'has_file' => $changeRequest->has_file,
        ];
    }

    protected function formatActor(?Model $actor): ?array
    {
        if (!$actor) {
            return null;
        }

        return [
            'id' => $actor->getKey(),
            'type' => get_class($actor),
            'name' => $actor->name ?? null,
            'email' => $actor->email ?? null,
        ];
    }

    protected function getChangeableName(ChangeRequest $changeRequest): ?string
    {
        $changeable = $changeRequest->changeable;

        if (!$changeable) {
            return null;
        }

        if ($changeable instanceof Merchant) {
            return $changeable->business_name ?? $changeable->name ?? null;
        }

        if ($changeable instanceof Branch) {
            return $changeable->name;
        }

        return $changeable->name ?? $changeable->title ?? null;
    }

    protected function getTypeLabel(ChangeRequest $changeRequest): string
    {
        $map = [
            Merchant::class => 'Merchant',
            Branch::class => 'Branch',
        ];

        return $map[$changeRequest->changeable_type] ?? class_basename($changeRequest->changeable_type ?? '');
    }

    protected function getTypeSlug(ChangeRequest $changeRequest): string
    {
        $map = [
            Merchant::class => 'merchant',
            Branch::class => 'branch',
        ];

        return $map[$changeRequest->changeable_type] ?? strtolower(class_basename($changeRequest->changeable_type ?? 'unknown'));
    }

    protected function mapPayloadFieldsWithComparison(array $payload, ?Model $model): array
    {
        $mappedPayload = [];
        $attachmentsByType = [];

        if ($model && method_exists($model, 'attachments')) {
            $model->loadMissing('attachments');
            $attachmentsByType = $model->attachments->keyBy('url_type');
        }

        foreach ($payload as $key => $requestedValue) {
            if (Str::startsWith($key, '__')) {
                continue;
            }

            $mappedKey = $this->mapFieldName($key);

            $currentValue = $model ? $model->getAttribute($key) : null;
            $currentRaw = $currentValue;

            if ($this->isAttachmentField($key)) {
                if ($attachmentsByType && $attachmentsByType->has($key)) {
                    $currentRaw = $attachmentsByType->get($key)->url;
                } elseif ($model && $key === 'company_logo') {
                    $currentRaw = $model->getAttribute('logo');
                }
            }

            $currentMappedValue = $this->mapFieldValue($key, $currentRaw ?? $currentValue);
            $requestedMappedValue = $this->mapFieldValue($key, $requestedValue);

            if ($currentMappedValue !== $requestedMappedValue) {
                $isAttachment = $this->isAttachmentField($key);
                $mappedPayload[$mappedKey] = [
                    'current' => $currentMappedValue,
                    'requested' => $requestedMappedValue,
                    'field' => $key,
                    'current_raw' => $currentRaw,
                    'requested_raw' => $requestedValue,
                    'current_url' => $isAttachment ? $this->resolveAttachmentUrl($currentRaw) : null,
                    'requested_url' => $isAttachment ? $this->resolveAttachmentUrl($requestedValue) : null,
                    'is_attachment' => $isAttachment,
                ];
            }
        }

        return $mappedPayload;
    }

    protected function mapFieldName(string $fieldName): string
    {
        $fieldMappings = [
            'name' => 'Name',
            'business_name' => 'Business Name',
            'owner_name' => 'Owner Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'business_type' => 'Business Type',
            'country' => 'Country',
            'city' => 'City',
            'country_id' => 'Country',
            'city_id' => 'City',
            'trade_license_number' => 'Trade License Number',
            'tax_certified_number' => 'Tax Number',
            'tax_number' => 'Tax Number',
            'trade_license_start_date' => 'Trade License Start Date',
            'trade_license_expired_date' => 'Trade License Expiry Date',
            'logo' => 'Logo',
            'merchant_code' => 'Merchant Code',
            'user_id' => 'User',
            'status' => 'Status',
            'is_active' => 'Active Status',
            'company_logo' => 'Company Logo',
            'tax_certification' => 'Tax Certificate',
            'trade_license' => 'Trade License',
            'user_id_document' => 'ID Document',
        ];

        return $fieldMappings[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName));
    }

    protected function mapFieldValue(string $fieldName, $value)
    {
        if (is_null($value)) {
            return 'Not provided';
        }

        if ($this->isAttachmentField($fieldName) || $fieldName === 'logo') {
            return $value ? 'File uploaded' : 'No file';
        }

        if ($fieldName === 'is_active') {
            return $value ? 'Yes' : 'No';
        }

        switch ($fieldName) {
            case 'city_id':
                return $this->resolveCityName($value);
            case 'country_id':
                return $this->resolveCountryName($value);
            case 'user_id':
                return $this->resolveUserName($value);
        }

        if ($fieldName === 'status') {
            $statusLabels = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'suspended' => 'Suspended',
                'viewed' => 'Viewed',
                'deleted' => 'Deleted',
                'requesting_updated' => 'Requesting Update',
            ];
            return $statusLabels[$value] ?? $value;
        }

        return $value;
    }

    protected function getRequestType(array $payload): string
    {
        if (isset($payload['company_logo']) || isset($payload['tax_certification']) ||
            isset($payload['trade_license']) || isset($payload['user_id_document'])) {
            return 'Attachments';
        }

        return 'Profile';
    }

    protected function resolveCityName($value): string
    {
        if (!$value) {
            return 'Not provided';
        }

        if (!isset($this->cityCache[$value])) {
            $city = City::find($value);
            if ($city) {
                $name = $city->name ?? $city->getAttribute('name');
                if (is_array($name)) {
                    $name = reset($name) ?: 'Unknown';
                }
                $this->cityCache[$value] = (string) ($name ?? 'Unknown');
            } else {
                $this->cityCache[$value] = 'Unknown';
            }
        }

        return $this->cityCache[$value] ?? 'Unknown';
    }

    protected function resolveCountryName($value): string
    {
        if (!$value) {
            return 'Not provided';
        }

        if (!isset($this->countryCache[$value])) {
            $country = Country::find($value);
            if ($country) {
                $name = $country->name ?? $country->getAttribute('name');
                if (is_array($name)) {
                    $name = reset($name) ?: 'Unknown';
                }
                $this->countryCache[$value] = (string) ($name ?? 'Unknown');
            } else {
                $this->countryCache[$value] = 'Unknown';
            }
        }

        return $this->countryCache[$value] ?? 'Unknown';
    }

    protected function resolveUserName($value): string
    {
        if (!$value) {
            return 'Not provided';
        }

        if (!isset($this->userCache[$value])) {
            $user = User::find($value);
            $this->userCache[$value] = $user ? ($user->name ?? $user->email ?? 'Unknown') : 'Unknown';
        }

        return $this->userCache[$value] ?? 'Unknown';
    }
    protected function isAttachmentField(string $fieldName): bool
    {
        return in_array($fieldName, ['company_logo', 'tax_certification', 'trade_license', 'user_id_document'], true);
    }

    protected function resolveAttachmentUrl($value): ?string
    {
        if (!$value) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $path = (string) $value;

        if (Str::startsWith($path, ['storage/', '/storage/'])) {
            return asset(Str::start($path, '/'));
        }

        return asset(Storage::url($path));
    }
}


