<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

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
class CityResource {}


