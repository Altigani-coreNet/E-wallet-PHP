<?php

namespace App\Support;

final class CustomerActivityActions
{
    public const PASSWORD_CHANGED = 'password_changed';

    public const PASSWORD_RESET = 'password_reset';

    public const PASSWORD_RESET_REQUESTED = 'password_reset_requested';

    public const CHANGE_REQUEST_SUBMITTED = 'change_request_submitted';

    public const PROFILE_RESUBMITTED = 'profile_resubmitted';

    /** @var list<string> */
    public const ALL = [
        self::PASSWORD_CHANGED,
        self::PASSWORD_RESET,
        self::PASSWORD_RESET_REQUESTED,
        self::CHANGE_REQUEST_SUBMITTED,
        self::PROFILE_RESUBMITTED,
    ];

    public static function isAllowed(string $action): bool
    {
        return in_array($action, self::ALL, true);
    }

    public static function allowedListForMessage(): string
    {
        return implode(', ', self::ALL);
    }
}
