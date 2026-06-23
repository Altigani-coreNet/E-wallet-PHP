<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BatchResource;
use App\Models\Batch;
use App\Models\Transaction;
use App\Services\BatchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BatchController extends Controller
{

    public function __construct(public BatchService $batchService)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/batches",
     *     summary="Get all batches for the authenticated merchant",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of batches per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of batches",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/BatchResource")),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Merchant ID required"),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $merchantId = $request->user()->merchant_id ?? $request->merchant_id;
        
        if (!$merchantId) {
            return response()->json(['error' => 'Merchant ID required'], 400);
        }

        $userId = $request->user()->id ?? $request->user_id;
        $batches = $this->batchService->getBatchesByMerchant($merchantId, $userId, $request->limit ?? 15);
        
        return response()->json([
            'success' => true,
            'data' => BatchResource::collection($batches),
            'pagination' => [
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
                'per_page' => $batches->perPage(),
                'total' => $batches->total(),
            ]
        ]);
    }

   
    public function show(Batch $batch): JsonResponse
    {
        // Check if user has access to this batch
        $merchantId = request()->user()->merchant_id ?? request()->merchant_id;
        
        if ($batch->merchant_id != $merchantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new BatchResource($batch->load('transactions'))
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
        ]);

        $batch = $this->batchService->createBatch($request->merchant_id);
        
        return response()->json([
            'success' => true,
            'message' => 'Batch created successfully',
            'data' => new BatchResource($batch)
        ], 201);
    }

    public function addTransaction(Request $request, Batch $batch): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        // Check if user has access to this batch
        $merchantId = request()->user()->merchant_id ?? request()->merchant_id;
        
        if ($batch->merchant_id != $merchantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transaction = Transaction::findOrFail($request->transaction_id);
        
        // Check if transaction belongs to the same merchant
        if ($transaction->merchant_id != $merchantId) {
            return response()->json(['error' => 'Transaction does not belong to this merchant'], 400);
        }

        try {
            $batch->addTransaction($transaction);
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction added to batch successfully',
                'data' => new BatchResource($batch->load('transactions'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/batches/{batch}/remove-transaction",
     *     summary="Remove a transaction from a batch",
     *     tags={"Batch"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="batch",
     *         in="path",
     *         required=true,
     *         description="Batch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_id"},
     *             @OA\Property(property="transaction_id", type="integer", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction removed from batch successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/BatchResource")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error or unauthorized"),
     * )
     */
    public function removeTransaction(Request $request, Batch $batch): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
        ]);

        // Check if user has access to this batch
        $merchantId = request()->user()->merchant_id ?? request()->merchant_id;
        
        if ($batch->merchant_id != $merchantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $transaction = Transaction::findOrFail($request->transaction_id);
        
        try {
            $batch->removeTransaction($transaction);
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction removed from batch successfully',
                'data' => new BatchResource($batch->load('transactions'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function settle(Batch $batch): JsonResponse
    {
        // Check if user has access to this batch
        $merchantId = request()->user()->merchant_id ?? request()->merchant_id;
        
        if ($batch->merchant_id != $merchantId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            if (!$batch->canBeSettled()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Batch cannot be settled. All transactions must be in final states.'
                ], 400);
            }

            $this->batchService->settleBatch($batch);
            
            return response()->json([
                'success' => true,
                'message' => 'Batch settled successfully',
                'data' => new BatchResource($batch->load('transactions'))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function summary(Request $request): JsonResponse
    {
        
        $merchantId = $request->user()->merchant_id ?? $request->merchant_id;
        
        if (!$merchantId) {
            return response()->json(['error' => 'Merchant ID required'], 400);
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        // dd('hi');
        
        $summary = $this->batchService->getBatchSummary($merchantId, $startDate, $endDate);
        
        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
