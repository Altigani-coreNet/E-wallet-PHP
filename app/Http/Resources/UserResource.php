<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     title="User Resource",
 *     description="User resource schema"
 * )
 */

class UserResource extends JsonResource
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
             * @OA\Property(property="id", type="integer", example=1, description="User ID")
             */
            'id' => $this->id,
            
            /**
             * @OA\Property(property="name", type="string", example="John Doe", description="User's full name")
             */
            'name' => $this->name,
            
            /**
             * @OA\Property(property="email", type="string", format="email", example="admin@example.com", description="User's email address")
             */
            'email' => $this->email,
            
            /**
             * @OA\Property(property="mobile", type="string", example="+971512345678", description="User's mobile number")
             */
            'mobile' => $this->mobile,
            
            /**
             * @OA\Property(property="phone", type="string", nullable=true, example=null, description="User's phone number")
             */
            'phone' => $this->phone,
            
            /**
             * @OA\Property(property="gender", type="string", nullable=true, example=null, description="User's gender")
             */
            'gender' => $this->gender,
            
            /**
             * @OA\Property(property="profile_image", type="string", example="http://localhost:8000/assets/media/avatars/300-1.jpg", description="User's profile image URL")
             */
            'profile_image' => $this->getProfileImageApi(),
            
            /**
             * @OA\Property(property="is_approved", type="boolean", example=true, description="Whether the user account is approved")
             */
            // 'is_approved' => $this->is_approved,
            
            /**
             * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Account creation timestamp")
             */
            'created_at' => $this->created_at,
            
            /**
             * @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Last update timestamp")
             */
            'updated_at' => $this->updated_at,

            /**
             * @OA\Property(property="onboarding_completed", type="boolean", example=true, description="Whether the user has completed merchant onboarding (has a merchant record)")
             */
            'onboarding_completed' => !is_null($this->merchant_id),
        ];
    }
}
