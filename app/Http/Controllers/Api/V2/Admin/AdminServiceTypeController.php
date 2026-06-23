<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminServiceTypeController extends Controller
{
    /**
     * Service types for admin dropdowns (filters, service wizard, etc.).
     */
    public function select(Request $request): JsonResponse
    {
        $query = ServiceType::query()->where('is_active', true);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $limit = $request->has('limit') ? max(1, (int) $request->limit) : 100;

        $rows = $query->orderBy('name_en')->limit($limit)->get(['id', 'name_en', 'name_ar', 'code']);

        return response()->json([
            'success' => true,
            'status' => true,
            'data' => $rows,
        ]);
    }
}
