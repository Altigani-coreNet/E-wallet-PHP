<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

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
class BatchResource {}


