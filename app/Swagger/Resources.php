<?php

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     title="User Resource",
 *     description="User resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="MerchantResource",
 *     title="Merchant Resource",
 *     description="Merchant resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="My Business"),
 *     @OA\Property(property="merchant_code", type="string", example="MERCH001"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="business@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="business_type", type="string", example="retail"),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="is_active", type="boolean", example=false),
 *     @OA\Property(property="address", type="string", example="123 Business St"),
 *     @OA\Property(property="latitude", type="number", format="float", example=12.345678),
 *     @OA\Property(property="longitude", type="number", format="float", example=98.765432),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

