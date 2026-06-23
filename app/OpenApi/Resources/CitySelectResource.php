<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

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
class CitySelectResource {}


