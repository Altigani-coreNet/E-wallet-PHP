<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\BusinessType;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class BusinessTypeController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Get(
     *     path="/api/business-types",
     *     summary="Get all business types",
     *     description="Retrieves a list of all available business types",
     *     tags={"Company Registration"},
     *     @OA\Response(
     *         response=200,
     *         description="Business types retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business types retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BusinessTypeResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch business types",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch business types: Error message")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $businessTypes = collect(BusinessType::cases())->map(function ($type) {
                return [
                    'value' => $type->value,
                    'label' => $type->getDisplayName(),
                ];
            });

            return $this->SuccessMessage($businessTypes);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch business types: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/business-types/select",
     *     summary="Get business types for select dropdown",
     *     description="Retrieves business types formatted for select dropdown",
     *     tags={"Company Registration"},
     *     @OA\Response(
     *         response=200,
     *         description="Business types for select retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business types for select retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BusinessTypeSelectResource")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to fetch business types for select",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch business types for select: Error message")
     *         )
     *     )
     * )
     */
    public function select(Request $request)
    {
        try {
            $businessTypes = collect(BusinessType::cases())->map(function ($type) {
                return [
                    'id' => $type->value,
                    'text' => $type->getDisplayName(),
                    'value' => $type->value,
                    'label' => $type->getDisplayName(),
                ];
            });

            return $this->SuccessMessage($businessTypes);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch business types for select: ' . $e->getMessage(), null, 500);
        }
    }
}
