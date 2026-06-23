<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="PaymentLinkResource",
 *     type="object",
 *     title="Payment Link Resource",
 *     description="Payment link resource with all details",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Payment link ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="merchant_id",
 *         type="integer",
 *         description="Merchant ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="customer_id",
 *         type="integer",
 *         description="Customer ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="number",
 *         format="float",
 *         description="Payment amount",
 *         example=99.99
 *     ),
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         description="Currency code",
 *         enum={"USD","AED","SUD","EUR"},
 *         example="USD"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Payment link status",
 *         enum={"active","inactive","expired","completed","scheduled"},
 *         example="active"
 *     ),
 *     @OA\Property(
 *         property="payment_status",
 *         type="string",
 *         description="Payment status",
 *         enum={"pending","paid","failed"},
 *         example="pending"
 *     ),
 *     @OA\Property(
 *         property="payment_method_types",
 *         type="array",
 *         description="Allowed payment methods",
 *         @OA\Items(
 *             type="string",
 *             enum={"card","afterpay_clearpay","alipay","bancontact","eps","giropay","grabpay","ideal","klarna","oxxo","p24","sepa_debit","sofort","us_bank_account","wechat_pay"}
 *         ),
 *         example={"card","alipay"}
 *     ),
 *     @OA\Property(
 *         property="short_uuid",
 *         type="string",
 *         description="Short UUID for payment link",
 *         example="abc12345"
 *     ),
 *     @OA\Property(
 *         property="link",
 *         type="string",
 *         description="Payment link URL",
 *         example="https://example.com/pay/abc12345"
 *     ),
 *     @OA\Property(
 *         property="scheduled_date",
 *         type="string",
 *         format="date-time",
 *         description="Scheduled payment date",
 *         example="2024-01-15T10:00:00Z",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="expired_date",
 *         type="string",
 *         format="date-time",
 *         description="Payment link expiration date",
 *         example="2024-12-31T23:59:59Z",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         description="Additional metadata",
 *         example={"source":"api","created_by":"merchant"}
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2024-01-01T00:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2024-01-01T00:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="merchant",
 *         type="object",
 *         description="Merchant information",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Test Merchant"),
 *         @OA\Property(property="email", type="string", format="email", example="merchant@example.com")
 *     ),
 *     @OA\Property(
 *         property="customer",
 *         type="object",
 *         description="Customer information",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="customer@example.com"),
 *         @OA\Property(property="phone", type="string", example="+1234567890")
 *     )
 * )
 */
class PaymentLinkResource
{
    // This class is used only for OpenAPI documentation
}
