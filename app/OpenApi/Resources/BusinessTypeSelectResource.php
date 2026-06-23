<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

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
class BusinessTypeSelectResource {}


