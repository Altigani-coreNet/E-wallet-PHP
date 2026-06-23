<?php

namespace App\OpenApi\Resources;

use OpenApi\Annotations as OA;

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
class CountrySelectResource {}


