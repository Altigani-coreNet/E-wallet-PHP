<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminProviderSettlementService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminProviderSettlementController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AdminProviderSettlementService $settlementService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            return $this->SuccessMessage([
                'data' => $this->settlementService->index([
                    'search' => $request->input('search'),
                ]),
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to fetch provider payables: '.$e->getMessage(), null, 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'string', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $admin = $request->user('admin-api');
            $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
            $idempotencyKey = $idempotencyKey !== '' ? $idempotencyKey : null;

            $result = $this->settlementService->settle(
                (int) $admin->id,
                $validated['partner_id'],
                (float) $validated['amount'],
                $validated['description'] ?? null,
                $idempotencyKey,
            );

            return $this->SuccessMessage($result);
        } catch (InvalidArgumentException $e) {
            return $this->ErrorMessage($e->getMessage(), null, 422);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to settle provider payable: '.$e->getMessage(), null, 500);
        }
    }
}
