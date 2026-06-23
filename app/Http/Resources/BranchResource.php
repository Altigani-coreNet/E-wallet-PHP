<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="BranchResource",
 *     title="Branch Resource",
 *     description="Branch resource schema"
 * )
 */

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * @OA\Property(property="id", type="integer", example=1, description="Branch ID")
             */
            'id' => $this->id,
            
            /**
             * @OA\Property(property="name", type="string", example="Dubai Mall Branch", description="Branch name")
             */
            'name' => $this->name,
            
            /**
             * @OA\Property(property="address", type="string", example="Dubai Mall, Level 2, Dubai, UAE", description="Branch address")
             */
            'address' => $this->address,
            
            /**
             * @OA\Property(property="is_active", type="boolean", example=true, description="Whether the branch is active")
             */
            'is_active' => $this->is_active,
            
            /**
             * @OA\Property(property="merchant_id", type="integer", example=1, description="Associated merchant ID")
             */
            'merchant_id' => $this->merchant_id,
            
            /**
             * @OA\Property(property="status_display", type="string", example="Active", description="Status display name")
             */
            'status_display' => $this->status_display_name,
            
            
            
            /**
             * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Branch creation timestamp")
             */
            'created_at' => $this->created_at,
            
            /**
             * @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Last update timestamp")
             */
            'updated_at' => $this->updated_at,
        ];
    }
} 