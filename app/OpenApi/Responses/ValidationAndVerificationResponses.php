<?php

namespace App\OpenApi\Responses;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ValidationDetailsResponse",
 *     title="Validation Details Response",
 *     description="Response schema for validation details",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Details validated successfully")
 * )
 *
 * @OA\Schema(
 *     schema="SendVerificationCodeResponse",
 *     title="Send Verification Code Response",
 *     description="Response schema for sending verification code",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Email verification code sent successfully"),
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
 * )
 *
 * @OA\Schema(
 *     schema="VerifyCodeResponse",
 *     title="Verify Code Response",
 *     description="Response schema for verifying code",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Email verified successfully"),
 *     @OA\Property(property="verified_type", type="string", example="email")
 * )
 */
class ValidationAndVerificationResponses {}


