<?php

namespace App\OpenApi\Requests;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UserRegistrationRequest",
 *     title="User Registration Request",
 *     description="Request schema for user registration",
 *     required={"email", "phone", "first_name", "last_name", "password", "password_confirmation"},
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="password", type="string", format="password", example="SecurePass123!"),
 *     @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!")
 * )
 */
class UserRegistrationRequest {}


