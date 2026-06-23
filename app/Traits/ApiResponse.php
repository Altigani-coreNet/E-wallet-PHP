<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponse
{
    public function SuccessMessage($data, $status = 200): JsonResponse
    {
        // dd($data);
        return response()->json([
            'status' => true,
            'data' => $data
        ], $status);
    }

    public function ErrorMessage($data = 'some thing went worng', $errorCode = null, $HttpStatusCode = 500): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $data,
            'Error_Code' => $errorCode,
        ], $HttpStatusCode);
    }


}
