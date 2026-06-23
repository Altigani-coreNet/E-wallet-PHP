<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanStoreRequest;
use App\Models\Plan;
use App\Repositories\PlanRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};

class AdminPlanController extends Controller
{
    use ApiResponse;

    protected $planRepository;

    public function __construct(PlanRepository $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    /**
     * Get all plans with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'plan_type' => $request->get('plan_type'),
                'status' => $request->get('status'),
                'has_discount' => $request->get('has_discount'),
            ];

            $perPage = $request->get('per_page', 15);
            $plans = $this->planRepository->getPaginated($filters, $perPage);

            return $this->SuccessMessage($plans);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch plans: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get plans for select dropdown (simplified list)
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $query = Plan::select('id', 'name', 'price', 'status');

            // Add search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Only active plans by default
            if (!$request->has('include_inactive')) {
                $query->where('status', true);
            }

            $plans = $query->orderBy('name')
                ->limit(100)
                ->get()
                ->map(function($plan) {
                    return [
                        'id' => $plan->id,
                        'text' => $plan->name,
                        'name' => $plan->name,
                        'price' => $plan->price,
                        'status' => $plan->status,
                    ];
                });

            return $this->SuccessMessage($plans);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch plans: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get plan details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $plan = $this->planRepository->findOrFail($id);
            
            // Ensure planPrices are included in the response
            // Load the relationship if not already loaded
            if (!$plan->relationLoaded('planPrices')) {
                $plan->load(['planPrices.currency', 'planPrices.country']);
            }
            
            // Convert to array to ensure relationships are included
            $planData = $plan->toArray();
            
            // Explicitly include planPrices in the response
            $planData['plan_prices'] = $plan->planPrices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'plan_id' => $price->plan_id,
                    'currency_id' => $price->currency_id,
                    'country_id' => $price->country_id,
                    'price' => $price->price,
                    'current_price' => $price->current_price,
                    'is_default' => $price->is_default,
                    'currency' => $price->currency ? [
                        'id' => $price->currency->id,
                        'name' => $price->currency->name,
                        'currency_code' => $price->currency->currency_code,
                        'symbol' => $price->currency->symbol,
                    ] : null,
                    'country' => $price->country ? [
                        'id' => $price->country->id,
                        'name' => $price->country->name,
                        'short_name' => $price->country->short_name,
                        'code' => $price->country->code,
                    ] : null,
                ];
            })->toArray();
            
            return $this->SuccessMessage($planData);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch plan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new plan
     */
    public function store(PlanStoreRequest $request): JsonResponse
    {
        try {
            $plan = $this->planRepository->store($request);
            return $this->SuccessMessage($plan, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create plan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update a plan
     */
    public function update(PlanStoreRequest $request, string $id): JsonResponse
    {
        try {
            $plan = $this->planRepository->findOrFail($id);
            $updatedPlan = $this->planRepository->update($request, $plan);
            return $this->SuccessMessage($updatedPlan);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update plan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete a plan
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $plan = $this->planRepository->findOrFail($id);
            $this->planRepository->destroy($plan);
            return $this->SuccessMessage(null);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete plan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change plan status
     */
    public function changeStatus(string $id): JsonResponse
    {
        try {
            $plan = $this->planRepository->findOrFail($id);
            $this->planRepository->changeStatus($plan);
            return $this->SuccessMessage($plan->fresh());
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change plan status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get plan report data
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'plan_type' => $request->get('plan_type'),
                'status' => $request->get('status'),
                'has_discount' => $request->get('has_discount'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $query = Plan::with(['features', 'scopes']);

            // Apply filters
            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('description', 'like', "%{$filters['search']}%");
                });
            }

            if (!empty($filters['plan_type'])) {
                $query->where('plan_type', $filters['plan_type']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['has_discount'])) {
                $query->where('has_discount', $filters['has_discount']);
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $plans = $query->get();

            // Calculate statistics
            $stats = [
                'total_plans' => $plans->count(),
                'active_plans' => $plans->where('status', true)->count(),
                'inactive_plans' => $plans->where('status', false)->count(),
                'plans_with_discount' => $plans->where('has_discount', true)->count(),
                'total_revenue_potential' => $plans->sum('price'),
            ];

            return $this->SuccessMessage([
                'plans' => $plans,
                'statistics' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to generate report: ' . $e->getMessage(), null, 500);
        }
    }
}
