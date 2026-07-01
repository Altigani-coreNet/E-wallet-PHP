<?php

namespace App\Modules\AdminKyc\Services;

use App\Models\Admin;

class PrimaryAdminResolver
{
    public static function resolve(): ?Admin
    {
        $recipient = config('notifications.admin_kyc.recipient', 'first_admin');

        if ($recipient !== 'first_admin') {
            return Admin::query()
                ->where('status', 'active')
                ->whereKey($recipient)
                ->first();
        }

        return Admin::query()
            ->where('status', 'active')
            ->orderBy('created_at')
            ->first();
    }
}
