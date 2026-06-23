<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MerchantResource;
use App\Http\Resources\BranchResource;

/**
 * @OA\Schema(
 *     schema="UserProfileResource",
 *     title="User Profile Resource",
 *     description="Enhanced user profile resource with merchant and branch information"
 * )
 */

class UserProfileResource extends JsonResource
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
             * @OA\Property(property="merchant", type="object", nullable=true, description="Associated merchant information")
             */
            'merchant' => $this->when($this->merchant, function () {
                return new MerchantResource($this->merchant);
            }),
            
            /**
             * @OA\Property(property="branch", type="object", nullable=true, description="Associated branch information")
             */
            'branch' => $this->when($this->branch, function () {
                return new BranchResource($this->branch);
            }),
            
            /**
             * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Account creation timestamp")
             */
            'created_at' => $this->created_at,
            
            /**
             * @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Last update timestamp")
             */
            'updated_at' => $this->updated_at,

            /**	
             * @OA\Property(property="terminal", type="object", nullable=true, description="Current terminal information")
             */
            'terminal' => $this->when($this->currentTerminal, function () {
                return new TerminalResource($this->currentTerminal);
            }),
        ];
    }
} 