<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/softpos/register/validate-details",
     *     summary="Validate user details",
     *     description="Validates user details before proceeding with registration",
     *     tags={"Company Registration"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ValidationDetailsRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Details validated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationDetailsResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     )
     * )
     */
    public function validateDetails(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('registration.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('registration.details_validated'),
        ]);
    }
}