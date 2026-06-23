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
 *     schema="UserProfileResource",
 *     title="User Profile Resource",
 *     description="User profile resource with additional details",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="merchant", ref="#/components/schemas/MerchantResource"),
 *     @OA\Property(property="branch", type="object"),
 *     @OA\Property(property="current_terminal", type="object"),
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

/**
 * @OA\Schema(
 *     schema="BatchResource",
 *     title="Batch Resource",
 *     description="Batch resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="merchant_id", type="integer", example=1),
 *     @OA\Property(property="batch_number", type="string", example="BATCH20230901001"),
 *     @OA\Property(property="status", type="string", enum={"open", "closed", "settled"}, example="open"),
 *     @OA\Property(property="total_amount", type="number", format="float", example=1250.50),
 *     @OA\Property(property="transaction_count", type="integer", example=5),
 *     @OA\Property(
 *         property="transactions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TransactionResource")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="settled_at", type="string", format="date-time", nullable=true)
 * )
 */

/**
 * @OA\Schema(
 *     schema="TransactionResource",
 *     title="Transaction Resource",
 *     description="Transaction resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="merchant_id", type="integer", example=1),
 *     @OA\Property(property="batch_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="transaction_id", type="string", example="TXN20230901001"),
 *     @OA\Property(property="amount", type="number", format="float", example=150.75),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "declined", "voided", "refunded"}, example="approved"),
 *     @OA\Property(property="payment_method", type="string", example="credit_card"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Batch",
 *     title="Batch",
 *     description="Batch model representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="merchant_id", type="integer", example=1),
 *     @OA\Property(property="batch_number", type="string", example="BATCH20230901001"),
 *     @OA\Property(property="status", type="string", enum={"open", "closed", "settled"}, example="open"),
 *     @OA\Property(property="total_amount", type="number", format="float", example=1250.50),
 *     @OA\Property(property="transaction_count", type="integer", example=5),
 *     @OA\Property(
 *         property="transactions",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Transaction")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="settled_at", type="string", format="date-time", nullable=true)
 * )
 */

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     title="Transaction",
 *     description="Transaction model representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="merchant_id", type="integer", example=1),
 *     @OA\Property(property="batch_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="transaction_id", type="string", example="TXN20230901001"),
 *     @OA\Property(property="amount", type="number", format="float", example=150.75),
 *     @OA\Property(property="status", type="string", enum={"pending", "approved", "declined", "voided", "refunded"}, example="approved"),
 *     @OA\Property(property="payment_method", type="string", example="credit_card"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="BatchSummary",
 *     title="Batch Summary",
 *     description="Summary of batch statistics",
 *     @OA\Property(property="total_batches", type="integer", example=10),
 *     @OA\Property(property="total_amount", type="number", format="float", example=15000.50),
 *     @OA\Property(property="total_transactions", type="integer", example=150),
 *     @OA\Property(
 *         property="status_breakdown",
 *         type="object",
 *         @OA\Property(property="open", type="integer", example=2),
 *         @OA\Property(property="closed", type="integer", example=3),
 *         @OA\Property(property="settled", type="integer", example=5)
 *     ),
 *     @OA\Property(
 *         property="daily_totals",
 *         type="array",
 *         @OA\Items(
 *             @OA\Property(property="date", type="string", format="date", example="2023-09-01"),
 *             @OA\Property(property="total_amount", type="number", format="float", example=2500.75),
 *             @OA\Property(property="batch_count", type="integer", example=3)
 *         )
 *     )
 * )
 */

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
 */

/**
 * @OA\Schema(
 *     schema="ValidationDetailsResponse",
 *     title="Validation Details Response",
 *     description="Response schema for validation details",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Details validated successfully")
 * )
 */

/**
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
 */

/**
 * @OA\Schema(
 *     schema="SendVerificationCodeResponse",
 *     title="Send Verification Code Response",
 *     description="Response schema for sending verification code",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Email verification code sent successfully"),
 *     @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
 * )
 */

/**
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

/**
 * @OA\Schema(
 *     schema="VerifyCodeResponse",
 *     title="Verify Code Response",
 *     description="Response schema for verifying code",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Email verified successfully"),
 *     @OA\Property(property="verified_type", type="string", example="email")
 * )
 */

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
 */

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

/**
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

/**
 * @OA\Schema(
 *     schema="CountryResource",
 *     title="Country Resource",
 *     description="Country resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="object", example={"en": "United States", "ar": "الولايات المتحدة"}),
 *     @OA\Property(property="short_name", type="string", example="US"),
 *     @OA\Property(property="code", type="string", example="+1")
 * )
 */

/**
 * @OA\Schema(
 *     schema="CountrySelectResource",
 *     title="Country Select Resource",
 *     description="Country resource for select dropdown",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="text", type="string", example="United States"),
 *     @OA\Property(property="name", type="object", example={"en": "United States", "ar": "الولايات المتحدة"}),
 *     @OA\Property(property="short_name", type="string", example="US"),
 *     @OA\Property(property="code", type="string", example="+1"),
 *     @OA\Property(property="flag_url", type="string", example="/flags/us.png"),
 *     @OA\Property(property="flag_path", type="string", example="/storage/flags/us.png")
 * )
 */

/**
 * @OA\Schema(
 *     schema="CityResource",
 *     title="City Resource",
 *     description="City resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="object", example={"en": "New York", "ar": "نيويورك"}),
 *     @OA\Property(property="country_id", type="integer", example=1),
 *     @OA\Property(
 *         property="country",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="object", example={"en": "United States", "ar": "الولايات المتحدة"}),
 *         @OA\Property(property="short_name", type="string", example="US"),
 *         @OA\Property(property="code", type="string", example="+1")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="CitySelectResource",
 *     title="City Select Resource",
 *     description="City resource for select dropdown",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="text", type="string", example="New York"),
 *     @OA\Property(property="name", type="object", example={"en": "New York", "ar": "نيويورك"}),
 *     @OA\Property(property="country_id", type="integer", example=1)
 * )
 */

/**
 * @OA\Schema(
 *     schema="BusinessTypeResource",
 *     title="Business Type Resource",
 *     description="Business type resource representation",
 *     @OA\Property(property="value", type="string", example="retail"),
 *     @OA\Property(property="label", type="string", example="Retail Store")
 * )
 */

/**
 * @OA\Schema(
 *     schema="BusinessTypeSelectResource",
 *     title="Business Type Select Resource",
 *     description="Business type resource for select dropdown",
 *     @OA\Property(property="id", type="string", example="retail"),
 *     @OA\Property(property="text", type="string", example="Retail Store"),
 *     @OA\Property(property="value", type="string", example="retail"),
 *     @OA\Property(property="label", type="string", example="Retail Store")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     title="Validation Error Response",
 *     description="Response schema for validation errors",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         @OA\Property(
 *             property="email",
 *             type="array",
 *             @OA\Items(type="string", example="The email field is required.")
 *         ),
 *         @OA\Property(
 *             property="phone",
 *             type="array",
 *             @OA\Items(type="string", example="The phone field is required.")
 *         )
 *     )
 * )
 */