<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAdminResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $status = strtolower(trim((string) $this->status));
        $isActive = in_array($status, ['active', '1'], true) || $this->status === 1 || $this->status === true;

        $regions = $this->whenLoaded('countries', function () {
            return $this->countries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'code' => $country->code,
                    'name' => $country->name,
                ];
            })->values();
        }, []);

        $roles = $this->whenLoaded('roles', function () {
            return $this->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            })->values();
        }, []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_image' => $this->profile_image ? asset($this->profile_image) : null,
            'status' => $isActive ? 'active' : 'inactive',
            'custom_region' => (bool) $this->custom_region,
            'roles' => $roles,
            'regions' => $regions,
            'created_at' => $this->created_at,
            'country' => $this->whenLoaded('country', function () {
                if (!$this->country) {
                    return null;
                }

                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                    'code' => $this->country->code,
                ];
            }),
        ];
    }
}
