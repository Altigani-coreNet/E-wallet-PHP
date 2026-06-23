<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="BusinessTypeResource",
 *     title="Business Type Resource",
 *     description="Business type resource representation",
 *     @OA\Property(property="value", type="string", example="retail"),
 *     @OA\Property(property="label", type="string", example="Retail Store")
 * )
 */
class BusinessTypeResource {}


