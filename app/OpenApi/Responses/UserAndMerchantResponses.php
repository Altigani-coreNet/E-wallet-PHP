<?php

namespace App\OpenApi\Responses;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UserRegistrationResponse",
 *     title="User Registration Response",
 *     description="Response schema for user registration",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="User registered successfully"),
 *     @OA\Property(property="data", ref="#/components/schemas/UserResource"),
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
 * )
 *
 * @OA\Schema(
 *     schema="MerchantRegistrationResponse",
 *     title="Merchant Registration Response",
 *     description="Response schema for merchant registration",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Merchant registered successfully"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="merchant_id", type="integer", example=1),
 *         @OA\Property(property="business_name", type="string", example="Doe's Electronics Store"),
 *         @OA\Property(property="status", type="string", example="pending")
 *     )
 * )
 */
class UserAndMerchantResponses {}


