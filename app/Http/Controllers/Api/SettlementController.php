<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Http\Resources\SettlementResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettlementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/settlements",
     *     summary="Get all settlements",
     *     tags={"Settlements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settlements retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Settlement")),
     *             @OA\Property(property="message", type="string", example="Settlements retrieved successfully"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // You can add filters/pagination as needed
        $settlements = Settlement::where('merchant_id', $request->user()->merchant_id)
            ->where('user_id', $request->user()->id)
            ->with(['batch'])
            ->latest()
            ->paginate($request->get('limit', 15));
        return response()->json([
            'success' => true,
            'data' => SettlementResource::collection($settlements),
            'message' => 'Settlements retrieved successfully',
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'per_page' => $settlements->perPage(),
                'total' => $settlements->total(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settlements/{id}",
     *     summary="Get settlement details",
     *     tags={"Settlements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Settlement ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Settlement details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Settlement"),
     *             @OA\Property(property="message", type="string", example="Settlement details retrieved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Settlement not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Settlement not found")
     *         )
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        $settlement = Settlement::with(['batch', 'merchant', 'transactions'])->find($id);

        if (!$settlement) {
            return response()->json([
                'success' => false,
                'message' => 'Settlement not found',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => new SettlementResource($settlement),
            'message' => 'Settlement details retrieved successfully',
        ]);
    }
}
