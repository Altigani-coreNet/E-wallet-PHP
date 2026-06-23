<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChangeRequest;
use App\Models\Merchant;
use App\Services\ChangeRequestFormatter;
use App\Services\ChangeRequestService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminChangeRequestController extends Controller
{
    use ApiResponse;

    protected ChangeRequestFormatter $formatter;
    protected ChangeRequestService $changeRequestService;

    public function __construct(ChangeRequestFormatter $formatter, ChangeRequestService $changeRequestService)
    {
        $this->formatter = $formatter;
        $this->changeRequestService = $changeRequestService;
    }

    /**
     * Get pending counts grouped by changeable type.
     */
    public function statistics(): JsonResponse
    {
        try {
            $counts = ChangeRequest::query()
                ->selectRaw('changeable_type, COUNT(*) as aggregate')
                ->where('status', 'pending')
                ->whereIn('changeable_type', [Merchant::class, Branch::class])
                ->groupBy('changeable_type')
                ->pluck('aggregate', 'changeable_type')
                ->map(fn ($count) => (int) $count)
                ->toArray();

            $merchantCount = $counts[Merchant::class] ?? 0;
            $branchCount = $counts[Branch::class] ?? 0;
            $total = $merchantCount + $branchCount;

            return $this->SuccessMessage([
                'pending' => [
                    'merchant' => $merchantCount,
                    'branch' => $branchCount,
                    'total' => $total,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage(
                'Failed to fetch change request statistics: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * List change requests with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $perPage = max(1, min($perPage, 100));

            $query = ChangeRequest::query()
                ->with(['requester', 'approver', 'changeable']);

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($type = $this->resolveChangeableType($request->input('type') ?? $request->input('changeable_type'))) {
                $query->where('changeable_type', $type);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('changeable_type', Merchant::class)
                    ->where('changeable_id', $merchantId);
            }

            if ($branchId = $request->input('branch_id')) {
                $query->where('changeable_type', Branch::class)
                    ->where('changeable_id', $branchId);
            }

            if ($request->filled('changeable_id')) {
                $query->where('changeable_id', $request->input('changeable_id'));
            }

            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->input('from_date'));
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->input('to_date'));
            }

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%")
                        ->orWhere('moderation_note', 'like', "%{$search}%")
                        ->orWhereHasMorph(
                            'changeable',
                            [Merchant::class, Branch::class],
                            function ($changeableQuery, $type) use ($search) {
                                if ($type === Merchant::class) {
                                    $changeableQuery->where(function ($merchantQuery) use ($search) {
                                        $merchantQuery->where('business_name', 'like', "%{$search}%")
                                            ->orWhere('name', 'like', "%{$search}%");
                                    });
                                } elseif ($type === Branch::class) {
                                    $changeableQuery->where('name', 'like', "%{$search}%");
                                } else {
                                    $changeableQuery->where('name', 'like', "%{$search}%");
                                }
                            }
                        );
                });
            }

            $changeRequests = $query->orderByDesc('created_at')->paginate($perPage);

            $changeRequests->getCollection()->transform(function ($changeRequest) {
                return $this->formatter->formatSummary($changeRequest);
            });

            return $this->SuccessMessage($changeRequests);
        } catch (\Exception $e) {
            return $this->ErrorMessage(
                'Failed to fetch change requests: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Show a single change request with details.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $changeRequest = ChangeRequest::with(['requester', 'approver', 'changeable'])
                ->findOrFail($id);

            $data = $this->formatter->formatDetail($changeRequest);

            return $this->SuccessMessage($data);
        } catch (ModelNotFoundException $e) {
            return $this->ErrorMessage('Change request not found', null, 404);
        } catch (\Exception $e) {
            return $this->ErrorMessage(
                'Failed to fetch change request: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $changeRequest = ChangeRequest::with(['changeable', 'requester', 'approver'])->findOrFail($id);
            $approver = auth('admin-api')->user();

            if (!$approver) {
                return $this->ErrorMessage('Unauthorized', null, 401);
            }

            $result = $this->changeRequestService->approve($changeRequest, $approver, $request->input('moderation_note'));
            $updated = ChangeRequest::with(['requester', 'approver', 'changeable'])->findOrFail($id);

            return $this->SuccessMessage([
                'message' => 'Change request approved successfully',
                'change_request' => $this->formatter->formatDetail($updated),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->ErrorMessage('Change request not found', null, 404);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to approve change request: ' . $e->getMessage(), null, 400);
        }
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'moderation_note' => 'nullable|string|max:2000',
            ]);

            $changeRequest = ChangeRequest::with(['changeable', 'requester', 'approver'])->findOrFail($id);
            $approver = auth('admin-api')->user();

            if (!$approver) {
                return $this->ErrorMessage('Unauthorized', null, 401);
            }

            $updatedRequest = $this->changeRequestService->reject($changeRequest, $approver, $request->input('moderation_note'));

            return $this->SuccessMessage([
                'message' => 'Change request rejected successfully',
                'change_request' => $this->formatter->formatDetail($updatedRequest->loadMissing(['requester', 'approver', 'changeable'])),
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->ErrorMessage('Change request not found', null, 404);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Failed to reject change request: ' . $e->getMessage(), null, 400);
        }
    }

    protected function resolveChangeableType(?string $type): ?string
    {
        if (!$type) {
            return null;
        }

        $normalized = strtolower(trim($type));

        $map = [
            'merchant' => Merchant::class,
            'merchants' => Merchant::class,
            'branch' => Branch::class,
            'branches' => Branch::class,
            Merchant::class => Merchant::class,
            Branch::class => Branch::class,
        ];

        return $map[$normalized] ?? ($map[$type] ?? null);
    }
}


