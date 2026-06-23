<?php

namespace App\Enums;

enum ServiceType: string
{
    case DIGITAL = 'digital';
    case IVR = 'ivr';
    case SMS = 'sms';

    /**
     * Get all service types as an array for select options
     */
    public static function toArray(): array
    {
        return [
            self::DIGITAL->value => 'Digital',
            self::IVR->value => 'IVR',
            self::SMS->value => 'SMS',
        ];
    }

    /**
     * Get the display name for a service type
     */
    public function getDisplayName(): string
    {
        return self::toArray()[$this->value] ?? $this->value;
    }

    /**
     * Get service type by value
     */
    public static function fromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
