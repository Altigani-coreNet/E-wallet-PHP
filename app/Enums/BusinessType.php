<?php

namespace App\Enums;

enum BusinessType: string
{
    case RETAIL = 'retail';
    case ELECTRONICS = 'electronics';
    case PHARMACY = 'pharmacy';
    case SERVICES = 'services';
    case RESTAURANT = 'restaurant';

    /**
     * Get all business types as an array for select options
     */
    public static function toArray(): array
    {
        return [
            self::RETAIL->value => 'Retail',
            self::ELECTRONICS->value => 'Electronics',
            self::PHARMACY->value => 'Pharmacy',
            self::SERVICES->value => 'Services',
            self::RESTAURANT->value => 'Restaurant',
        ];
    }

    /**
     * Get the display name for a business type
     */
    public function getDisplayName(): string
    {
        return self::toArray()[$this->value] ?? $this->value;
    }

    /**
     * Get business type by value
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }
}

