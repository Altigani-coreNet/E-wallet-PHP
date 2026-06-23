<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserIndexResource extends JsonResource
{
    /**
     * Transform user model to minimal payload for admin users index table.
     *
     * Frontend expects: status badge, merchant/branch names, and activate/deactivate checks.
     */
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $isActive = (
            $status === 1 ||
            $status === '1' ||
            $status === true ||
            strtolower((string) $status) === 'active'
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'merchant_id' => $this->merchant_id,
            'branch_id' => $this->branch_id,

            // Keep DB-native status (0/1) for existing frontend code.
            'status' => $status,
            // Extra derived field for consistency across UI screens.
            'is_active' => $isActive,
            // Rule: admin is derived from the user's `type`.
            'is_admin' => ($this->type === 'admin'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'merchant' => $this->whenLoaded('merchant', function () {
                $merchant = $this->merchant;

                return [
                    'business_name' => $merchant->business_name,
                    'name' => $merchant->name,
                    'email' => $merchant->email,
                    'country' => $merchant->country
                        ? [
                            'code' => $merchant->country->code,
                            'name' => $merchant->country->name,
                        ]
                        : null,
                ];
            }),

            'branch' => $this->whenLoaded('branch', function () {
                $branch = $this->branch;

                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                ];
            }),

            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles
                    ? $this->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values()->toArray()
                    : [];
            }),
        ];
    }
}

