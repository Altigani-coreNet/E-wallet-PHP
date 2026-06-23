<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Settlement",
 *     title="Settlement",
 *     description="Settlement model representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="merchant_id", type="integer", example=1),
 *     @OA\Property(property="batch_id", type="integer", example=10),
 *     @OA\Property(property="amount", type="number", format="float", example=1234.56),
 *     @OA\Property(property="status", type="string", example="pending"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="settled_at", type="string", format="date-time", nullable=true)
 * )
 */
class Settlement {}


