<?php

namespace App\Modules\CustomerAuth\Resources;

use App\Modules\CustomerAuth\Models\CustomerBiometricDevice;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerBiometricDeviceResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var CustomerBiometricDevice $device */
        $device = $this->resource;

        return [
            'id' => $device->id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'platform' => $device->platform,
            'algorithm' => $device->algorithm,
            'enrolled_at' => $device->enrolled_at?->toIso8601String(),
            'last_used_at' => $device->last_used_at?->toIso8601String(),
            'biometric_enabled' => $device->isActive(),
        ];
    }
}
