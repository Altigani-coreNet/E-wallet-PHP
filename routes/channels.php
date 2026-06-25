<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('public-notifications', function ($user) {
    return !is_null($user);
});

Broadcast::channel('group-notifications.{groupName}', function ($user, string $groupName) {
    // TODO: Replace with real membership check against your groups table/pivot.
    return true;
});

Broadcast::channel('user-notifications.{targetUserId}', function ($user, string $targetUserId) {
    return (string) $user->id === (string) $targetUserId;
});

Broadcast::channel('customer-notifications.{targetCustomerId}', function ($user, string $targetCustomerId) {
    return (string) $user->id === (string) $targetCustomerId;
});


Broadcast::channel('merchant-notifications.{targetMerchantId}', function ($user, string $targetMerchantId) {
    return (string) ($user->merchant_id ?? '') === (string) $targetMerchantId;
});

Broadcast::channel('services_updates', function ($user) {
    return !is_null($user);
});
