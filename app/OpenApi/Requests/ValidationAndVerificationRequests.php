<?php

namespace App\OpenApi\Requests;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ValidationDetailsRequest",
 *     title="Validation Details Request",
 *     description="Request schema for validating user details",
 *     required={"email", "phone", "first_name", "last_name"},
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="type", type="string", enum={"email"}, example="email")
 * )
 *
 * @OA\Schema(
 *     schema="SendVerificationCodeRequest",
 *     title="Send Verification Code Request",
 *     description="Request schema for sending verification code",
 *     required={"email", "phone", "first_name", "last_name", "type"},
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="type", type="string", enum={"email", "phone"}, example="email")
 * )
 *
 * @OA\Schema(
 *     schema="VerifyCodeRequest",
 *     title="Verify Code Request",
 *     description="Request schema for verifying code",
 *     required={"code", "token", "type"},
 *     @OA\Property(property="code", type="string", example="123456"),
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *     @OA\Property(property="type", type="string", enum={"email", "phone"}, example="email")
 * )
 */
class ValidationAndVerificationRequests {}


