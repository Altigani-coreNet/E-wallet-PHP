<?php

namespace Database\Factories;

/**
 * Mock Data for Profile Completion
 * Based on Merchant::calculateProfileCompletion() function
 */
class ProfileCompletionMocks
{
    /**
     * Scenario 1: Low Completion (28%) - New Merchant
     * Missing: documents, approval, users, terminals
     */
    public static function lowCompletion(): array
    {
        return [
            'completion' => 28,
            'missing' => [
                'Company Logo is required.',
                'User Id Document is required.',
                'Tax Certification is required.',
                'Trade License is required.',
                'Account is pending approval.',
                'Add at least one user to your account.',
                'Add at least one terminal to your account.',
            ],
            'status' => 'pending',
            'documents' => [
                'total_required' => 4,
                'uploaded' => 0
            ],
            'users_count' => 0,
            'terminals_count' => 0,
        ];
    }

    /**
     * Scenario 2: Medium Completion (64%) - In Progress
     * Has profile + some documents, pending approval
     */
    public static function mediumCompletion(): array
    {
        return [
            'completion' => 64,
            'missing' => [
                'Tax Certification is required.',
                'Trade License is required.',
                'Account is pending approval.',
                'Add at least one terminal to your account.',
            ],
            'status' => 'pending',
            'documents' => [
                'total_required' => 4,
                'uploaded' => 2
            ],
            'users_count' => 3,
            'terminals_count' => 0,
        ];
    }

    /**
     * Scenario 3: High Completion (82%) - Almost Complete
     * Missing only terminals
     */
    public static function highCompletion(): array
    {
        return [
            'completion' => 82,
            'missing' => [
                'Add at least one terminal to your account.',
            ],
            'status' => 'approved',
            'documents' => [
                'total_required' => 4,
                'uploaded' => 4
            ],
            'users_count' => 5,
            'terminals_count' => 0,
        ];
    }

    /**
     * Scenario 4: Complete (100%) - Fully Setup
     * No missing fields
     */
    public static function fullCompletion(): array
    {
        return [
            'completion' => 100,
            'missing' => [],
            'status' => 'approved',
            'documents' => [
                'total_required' => 4,
                'uploaded' => 4
            ],
            'users_count' => 8,
            'terminals_count' => 3,
        ];
    }

    /**
     * Scenario 5: Rejected Account (46%)
     * Has some progress but account rejected
     */
    public static function rejectedAccount(): array
    {
        return [
            'completion' => 46,
            'missing' => [
                'Tax Certification is required.',
                'Account approval was rejected. Reason: Invalid business license information.',
                'Add at least one terminal to your account.',
            ],
            'status' => 'rejected',
            'documents' => [
                'total_required' => 4,
                'uploaded' => 3
            ],
            'users_count' => 2,
            'terminals_count' => 0,
        ];
    }

    /**
     * Scenario 6: Profile Only (28%)
     * Only basic profile filled, nothing else
     */
    public static function profileOnly(): array
    {
        return [
            'completion' => 28,
            'missing' => [
                'Company Logo is required.',
                'User Id Document is required.',
                'Tax Certification is required.',
                'Trade License is required.',
                'Account is pending approval.',
                'Add at least one user to your account.',
                'Add at least one terminal to your account.',
            ],
            'status' => 'pending',
            'documents' => [
                'total_required' => 4,
                'uploaded' => 0
            ],
            'users_count' => 0,
            'terminals_count' => 0,
        ];
    }

    /**
     * Get all scenarios as array
     */
    public static function all(): array
    {
        return [
            'low' => self::lowCompletion(),
            'medium' => self::mediumCompletion(),
            'high' => self::highCompletion(),
            'full' => self::fullCompletion(),
            'rejected' => self::rejectedAccount(),
            'profile_only' => self::profileOnly(),
        ];
    }

    /**
     * Get random scenario for testing
     */
    public static function random(): array
    {
        $scenarios = [
            self::lowCompletion(),
            self::mediumCompletion(),
            self::highCompletion(),
            self::fullCompletion(),
        ];

        return $scenarios[array_rand($scenarios)];
    }
}

