<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

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
class TransactionResource {}


