<?php

namespace App\Support;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Support\Str;

class CustomerEventMessageBuilder
{
    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'national_id' => 'National ID',
        'birth_date' => 'Date of Birth',
        'gender' => 'Gender',
        'country' => 'Country',
        'city' => 'City',
        'country_id' => 'Country',
        'city_id' => 'City',
        'profile_image' => 'Profile Photo',
        'passport_document' => 'Passport Document',
        'profile_completed' => 'Profile Completed',
        'status' => 'Status',
    ];

    /** @var array<string, string> */
    private const ATTACHMENT_LABELS = [
        'profile_image' => 'Profile Photo',
        'passport_document' => 'Passport Document',
    ];

    public static function registered(Customer $customer): string
    {
        $phone = $customer->phone ?: 'unknown phone';

        return "Registered account with phone number {$phone}.";
    }

    public static function profileCompleted(Customer $customer): string
    {
        $customer->loadMissing(['city', 'country']);

        $parts = array_filter([
            self::formatFieldPart('name', $customer->name),
            self::formatFieldPart('email', $customer->email),
            self::formatFieldPart('national_id', $customer->national_id),
            self::formatFieldPart('birth_date', self::formatDate($customer->birth_date)),
            self::formatFieldPart('gender', self::formatGender($customer->gender)),
            self::formatFieldPart('city_id', self::resolveCityName($customer->city_id)),
            self::formatFieldPart('country_id', self::resolveCountryName($customer->country_id)),
        ]);

        if ($parts === []) {
            return 'Submitted KYC profile for admin review.';
        }

        return 'Submitted KYC profile for admin review: '.implode(', ', $parts).'.';
    }

    public static function approved(Customer $customer, ?string $adminName = null): string
    {
        $who = self::actorLabel($adminName);
        $name = trim((string) $customer->name);

        if ($name !== '') {
            return "{$who} approved KYC profile for {$name}. Customer account is now active.";
        }

        return "{$who} approved KYC profile. Customer account is now active.";
    }

    /**
     * @param  list<string>  $invalidFields
     * @param  list<string>  $missingAttachments
     */
    public static function rejected(
        string $reason,
        array $invalidFields = [],
        array $missingAttachments = [],
        ?string $adminName = null,
    ): string {
        $who = self::actorLabel($adminName);
        $segments = ["{$who} rejected KYC profile."];

        if ($invalidFields !== []) {
            $segments[] = 'Incorrect fields: '.self::labelsForKeys($invalidFields).'.';
        }

        if ($missingAttachments !== []) {
            $segments[] = 'Missing documents: '.self::labelsForKeys($missingAttachments, self::ATTACHMENT_LABELS).'.';
        }

        $segments[] = 'Reason: '.trim($reason).'.';

        return implode(' ', $segments);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function changeRequestSubmitted(Customer $customer, array $payload): string
    {
        $changes = self::describePayloadChanges($customer, $payload);

        if ($changes === []) {
            return 'Customer requested a profile update pending admin approval.';
        }

        return 'Customer requested to update profile: '.implode(', ', $changes).'.';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function changeRequestApproved(
        array $payload,
        array $before,
        array $after,
        ?string $moderationNote = null,
        ?string $adminName = null,
    ): string {
        $who = self::actorLabel($adminName);
        $applied = self::describeAppliedChanges($payload, $before, $after);

        $message = $applied === []
            ? "{$who} approved the profile update request."
            : "{$who} approved profile update: ".implode(', ', $applied).'.';

        if ($moderationNote && trim($moderationNote) !== '') {
            $message .= ' Note: '.trim($moderationNote).'.';
        }

        return $message;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function changeRequestRejected(
        array $payload,
        ?string $moderationNote = null,
        ?string $adminName = null,
    ): string {
        $who = self::actorLabel($adminName);
        $fields = self::payloadFieldLabels($payload);

        $message = $fields === []
            ? "{$who} rejected the profile update request."
            : "{$who} rejected profile update request for: {$fields}.";

        if ($moderationNote && trim($moderationNote) !== '') {
            $message .= ' Note: '.trim($moderationNote).'.';
        }

        return $message;
    }

    public static function passwordChanged(?string $customerName = null): string
    {
        $who = $customerName ? $customerName : 'Customer';

        return "{$who} changed account password.";
    }

    public static function passwordReset(Customer $customer): string
    {
        $phone = $customer->phone ?: 'account';

        return "Password reset completed for {$phone}.";
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $uploadedAttachments
     */
    public static function profileResubmitted(
        array $before,
        array $after,
        array $uploadedAttachments = [],
    ): string {
        $changes = [];

        foreach ($after as $column => $newValue) {
            $key = self::columnToFieldKey((string) $column);
            $oldValue = $before[$column] ?? null;
            $changes[] = self::formatTransition(
                self::fieldLabel($key),
                self::formatDisplayValue($key, $oldValue),
                self::formatDisplayValue($key, $newValue),
            );
        }

        foreach ($uploadedAttachments as $attachmentKey) {
            $changes[] = self::fieldLabel((string) $attachmentKey).' uploaded';
        }

        if ($changes === []) {
            return 'Customer corrected rejected profile details and resubmitted for review.';
        }

        return 'Customer corrected rejected fields and resubmitted for review: '.implode(', ', $changes).'.';
    }

    /**
     * @param  list<string>  $keys
     */
    public static function labelsForKeys(array $keys, ?array $labelMap = null): string
    {
        $labels = array_map(
            fn (string $key) => self::fieldLabel($key, $labelMap),
            $keys,
        );

        return self::humanList($labels);
    }

    public static function fieldLabel(string $key, ?array $labelMap = null): string
    {
        $map = $labelMap ?? self::FIELD_LABELS;

        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    public static function describePayloadChanges(Customer $customer, array $payload): array
    {
        $changes = [];

        foreach ($payload as $key => $newValue) {
            if (Str::startsWith((string) $key, '__')) {
                continue;
            }

            $currentValue = $customer->getAttribute($key);
            $label = self::fieldLabel((string) $key);
            $from = self::formatDisplayValue((string) $key, $currentValue);
            $to = self::formatDisplayValue((string) $key, $newValue);

            if ($key === 'profile_image') {
                $changes[] = "{$label} update requested";
                continue;
            }

            $changes[] = self::formatTransition($label, $from, $to);
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    public static function describeAppliedChanges(array $payload, array $before, array $after): array
    {
        $applied = [];

        foreach ($payload as $key => $value) {
            if (Str::startsWith((string) $key, '__')) {
                continue;
            }

            $label = self::fieldLabel((string) $key);
            $newValue = self::formatDisplayValue((string) $key, $after[$key] ?? $value);
            $oldValue = self::formatDisplayValue((string) $key, $before[$key] ?? null);

            if ($key === 'profile_image') {
                $applied[] = "{$label} updated";
                continue;
            }

            if ($oldValue === $newValue) {
                $applied[] = "{$label} set to {$newValue}";
                continue;
            }

            $applied[] = self::formatTransition($label, $oldValue, $newValue);
        }

        return $applied;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function payloadFieldLabels(array $payload): string
    {
        $labels = [];

        foreach ($payload as $key => $value) {
            if (Str::startsWith((string) $key, '__')) {
                continue;
            }

            $labels[] = self::fieldLabel((string) $key);
        }

        return self::humanList($labels);
    }

    public static function formatDisplayValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'empty';
        }

        return match ($key) {
            'birth_date' => self::formatDate($value),
            'gender' => self::formatGender((string) $value),
            'city_id' => self::resolveCityName($value),
            'country_id' => self::resolveCountryName($value),
            'profile_image' => 'new photo',
            default => (string) $value,
        };
    }

    protected static function formatFieldPart(string $key, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::fieldLabel($key).' ('.self::formatDisplayValue($key, $value).')';
    }

    protected static function formatTransition(string $label, string $from, string $to): string
    {
        if ($from === $to) {
            return "{$label} ({$to})";
        }

        return "{$label} ({$from} → {$to})";
    }

    protected static function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'empty';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $string = (string) $value;

        if (str_contains($string, 'T')) {
            return substr($string, 0, 10);
        }

        if (str_contains($string, ' ')) {
            return substr($string, 0, 10);
        }

        return $string;
    }

    protected static function formatGender(?string $gender): string
    {
        return match (strtolower((string) $gender)) {
            'male' => 'Male',
            'female' => 'Female',
            default => ucfirst((string) $gender),
        };
    }

    protected static function resolveCityName(mixed $cityId): string
    {
        if (! $cityId) {
            return 'empty';
        }

        $city = City::query()->find($cityId);
        if (! $city) {
            return 'Unknown city';
        }

        $name = $city->name ?? $city->getAttribute('name');
        if (is_array($name)) {
            $name = reset($name) ?: 'Unknown city';
        }

        return (string) $name;
    }

    protected static function resolveCountryName(mixed $countryId): string
    {
        if (! $countryId) {
            return 'empty';
        }

        $country = Country::query()->find($countryId);
        if (! $country) {
            return 'Unknown country';
        }

        $name = $country->name ?? $country->getAttribute('name');
        if (is_array($name)) {
            $name = reset($name) ?: 'Unknown country';
        }

        return (string) $name;
    }

    protected static function columnToFieldKey(string $column): string
    {
        return match ($column) {
            'country_id' => 'country_id',
            'city_id' => 'city_id',
            default => $column,
        };
    }

    protected static function actorLabel(?string $name): string
    {
        $trimmed = trim((string) $name);

        return $trimmed !== '' ? $trimmed : 'Admin';
    }

    /**
     * @param  list<string>  $items
     */
    protected static function humanList(array $items): string
    {
        $items = array_values(array_filter($items, fn ($item) => $item !== ''));

        return match (count($items)) {
            0 => '',
            1 => $items[0],
            2 => "{$items[0]} and {$items[1]}",
            default => implode(', ', array_slice($items, 0, -1)).', and '.end($items),
        };
    }
}
