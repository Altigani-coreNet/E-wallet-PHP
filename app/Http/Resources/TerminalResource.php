<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TerminalResource",
 *     title="Terminal Resource",
 *     description="Terminal resource schema"
 * )
 */

class TerminalResource extends JsonResource
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
             * @OA\Property(property="id", type="integer", example=1, description="Terminal ID")
             */
            'id' => $this->id,
            
            /**
             * @OA\Property(property="name", type="string", example="Terminal 001", description="Terminal name")
             */
            'name' => $this->name,
            
            /**
             * @OA\Property(property="terminal_id", type="string", example="TERM12345678", description="Unique terminal identifier")
             */
            'terminal_id' => $this->terminal_id,
            
            /**
             * @OA\Property(property="brand", type="string", nullable=true, example="Verifone", description="Terminal brand")
             */
            'brand' => $this->brand,
            
            /**
             * @OA\Property(property="model", type="string", nullable=true, example="VX520", description="Terminal model")
             */
            'model' => $this->model,
            
            /**
             * @OA\Property(property="manufacturer", type="string", nullable=true, example="Verifone Inc.", description="Terminal manufacturer")
             */
            'manufacturer' => $this->manufacturer,
            
            /**
             * @OA\Property(property="serial_no", type="string", nullable=true, example="SN123456789", description="Terminal serial number")
             */
            'serial_no' => $this->serial_no,
            
            /**
             * @OA\Property(property="sdk_id", type="string", nullable=true, example="SDK001", description="SDK identifier")
             */
            'sdk_id' => $this->sdk_id,
            
            /**
             * @OA\Property(property="sdk_version", type="string", nullable=true, example="2.1.0", description="SDK version")
             */
            'sdk_version' => $this->sdk_version,
            
            /**
             * @OA\Property(property="android_os", type="string", nullable=true, example="Android 11", description="Android OS version")
             */
            'android_os' => $this->android_os,
            
            /**
             * @OA\Property(property="add_type", type="string", nullable=true, example="manual", description="Terminal addition type")
             */
            'add_type' => $this->add_type,
            
            /**
             * @OA\Property(property="is_active", type="boolean", example=true, description="Whether the terminal is active")
             */
            'is_active' => $this->is_active,
            
            /**
             * @OA\Property(property="device_id", type="string", nullable=true, example="DEV123456", description="Device identifier")
             */
            'device_id' => $this->device_id,
            
            /**
             * @OA\Property(property="terminal_status", type="string", example="online", description="Current terminal status")
             */
            'terminal_status' => $this->terminal_status,

            'merchant_id' => $this->merchant_id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'branch' => $this->whenLoaded('branch', function () {
                if (!$this->branch) {
                    return null;
                }

                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),
            
            /**
             * @OA\Property(property="current_user_id", type="integer", nullable=true, example=1, description="ID of user currently using the terminal")
             */
            'current_user_id' => $this->current_user_id,
            
            /**
             * @OA\Property(property="status_display", type="string", example="Active", description="Status display name")
             */
            'status_display' => $this->status_display_name,
            
            /**
             * @OA\Property(property="status_text", type="string", example="Online", description="Terminal status text")
             */
            'status_text' => $this->status_text,
            
            /**
             * @OA\Property(property="status_badge_class", type="string", example="badge-success", description="Status badge CSS class")
             */
            'status_badge_class' => $this->status_badge_class,
            
            /**
             * @OA\Property(property="full_info", type="string", example="Terminal 001 (Verifone) - VX520 - Verifone Inc.", description="Full terminal information")
             */
            'full_info' => $this->full_info,
            
            /**
             * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Terminal creation timestamp")
             */
            'created_at' => $this->created_at,
            
            /**
             * @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Last update timestamp")
             */
            'updated_at' => $this->updated_at,
        ];
    }
}
