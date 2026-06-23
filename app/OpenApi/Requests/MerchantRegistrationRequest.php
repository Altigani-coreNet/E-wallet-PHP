<?php

namespace App\OpenApi\Requests;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="MerchantRegistrationRequest",
 *     title="Merchant Registration Request",
 *     description="Request schema for merchant registration",
 *     required={"owner_name", "business_name", "business_type", "business_address", "country", "city", "trade_license_number", "trade_license_start_date", "trade_license_expired_date", "tax_number"},
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="first_name", type="string", example="John"),
 *     @OA\Property(property="last_name", type="string", example="Doe"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="nationality", type="string", example="American"),
 *     @OA\Property(property="owner_name", type="string", example="John Doe"),
 *     @OA\Property(property="business_name", type="string", example="Doe's Electronics Store"),
 *     @OA\Property(property="business_type", type="string", example="retail"),
 *     @OA\Property(property="business_phone", type="string", example="+1234567890"),
 *     @OA\Property(property="business_address", type="string", example="123 Main Street, City, State"),
 *     @OA\Property(property="country", type="integer", example=1),
 *     @OA\Property(property="city", type="integer", example=1),
 *     @OA\Property(property="trade_license_number", type="string", example="TL123456789"),
 *     @OA\Property(property="trade_license_start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="trade_license_expired_date", type="string", format="date", example="2024-12-31"),
 *     @OA\Property(property="trade_license_authority", type="string", example="Department of Commerce"),
 *     @OA\Property(property="tax_number", type="string", example="TAX123456789"),
 *     @OA\Property(property="tax_certified_number", type="string", example="TC123456789"),
 *     @OA\Property(property="tax_id_number", type="string", example="TID123456789"),
 *     @OA\Property(property="vat_number", type="string", example="VAT123456789"),
 *     @OA\Property(property="tax_registration_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="tax_authority", type="string", example="Internal Revenue Service"),
 *     @OA\Property(property="annual_turnover", type="number", format="float", example=1000000.00)
 * )
 */
class MerchantRegistrationRequest {}


