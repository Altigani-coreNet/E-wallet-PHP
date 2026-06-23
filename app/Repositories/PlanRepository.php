<?php

namespace App\Repositories;

use App\Http\Requests\PlanStoreRequest;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanScope;
use App\Services\PlanService;
use App\Traits\MessageManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class PlanRepository implements PlanService
{
    use MessageManager;

    public function index(): View
    {
        return view('plans.index');
    }

    public function data(): JsonResponse
    {
        $plans = Plan::query();

        return DataTables::of($plans)
            ->addIndexColumn()
            ->addColumn('record_select','plans.data_table.record_select')  
            ->addColumn('plan_type', function ($plan) {
                return ucfirst(strtolower($plan->plan_type->value));
            })
           
            ->editColumn('description', function ($plan) {
                return \Illuminate\Support\Str::limit($plan->description, 50);
            })
            ->addColumn('price', function ($plan) {
                return number_format($plan->price, 2);
            })
            ->addColumn('current_price', function ($plan) {
                return $plan->current_price ? number_format($plan->current_price, 2) : '-';
            })
            ->addColumn('has_discount', function ($plan) {
                return $plan->has_discount ? 
                    '<span class="badge badge-success">Yes</span>' : 
                    '<span class="badge badge-secondary">No</span>';
            })
            ->addColumn('status', function ($plan) {
                return $plan->status ? 
                    '<span class="badge badge-success">Active</span>' : 
                    '<span class="badge badge-danger">Inactive</span>';
            })
            ->addColumn('actions', function ($plan) {
                return view('plans.data_table.actions', compact('plan'));
            })
            ->rawColumns(['has_discount', 'record_select', 'status', 'actions'])
            ->make(true);
    }

    public function create(): View
    {
        $types = [
            ['id' => 1, 'name' => 'Yes'],
            ['id' => 0, 'name' => 'No']
        ];
        
        $plansType = collect(\App\Enums\RecurringType::cases())->map(function ($case) {
            return [
                'id' => $case->value,
                'name' => ucfirst(strtolower($case->value))
            ];
        })->toArray();

        return view('plans.create', compact('types', 'plansType'));
    }

    public function store(PlanStoreRequest $request)
    {
        try {
            $plan = Plan::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'plan_type' => $request->plan_type,
                'current_price' => $request->current_price,
                'has_discount' => $request->has_discount ?? false,
                'status' => $request->status ?? true,
            ]);

            // Handle features if provided
            if ($request->has('features') && is_array($request->features)) {
                foreach ($request->features as $feature) {
                    if (!empty($feature['name'])) {
                        Feature::create([
                            'plan_id' => $plan->id,
                            'name' => $feature['name'],
                            'is_enabled' => isset($feature['is_enabled']) ? true : false
                        ]);
                    }
                }
            }

            // Handle scopes if provided
            if ($request->has('scopes') && is_array($request->scopes)) {
                foreach ($request->scopes as $scope) {
                    if (isset($scope['scope_type'])) {
                        PlanScope::updateOrCreate(
                            [
                                'plan_id' => $plan->id,
                                'scope_type' => $scope['scope_type']
                            ],
                            [
                                'module' => $scope['module'] ?? null,
                                'is_enabled' => isset($scope['is_enabled']) ? (bool)$scope['is_enabled'] : false,
                                'max_count' => isset($scope['max_count']) && $scope['max_count'] !== '' ? (int)$scope['max_count'] : null
                            ]
                        );
                    }
                }
            }

            // Handle plan prices if provided
            if ($request->has('prices') && is_array($request->prices)) {
                // Ensure only one default price
                $hasDefault = false;
                foreach ($request->prices as $priceData) {
                    if (isset($priceData['is_default']) && $priceData['is_default']) {
                        if ($hasDefault) {
                            $priceData['is_default'] = false; // Only first default is kept
                        } else {
                            $hasDefault = true;
                        }
                    }
                }

                // If no default is set, make the first one default
                if (!$hasDefault && count($request->prices) > 0) {
                    $request->prices[0]['is_default'] = true;
                }

                // Delete existing prices and create new ones
                $plan->planPrices()->delete();
                
                foreach ($request->prices as $priceData) {
                    PlanPrice::create([
                        'plan_id' => $plan->id,
                        'currency_id' => $priceData['currency_id'] ?? null,
                        'country_id' => $priceData['country_id'] ?? null,
                        'price' => $priceData['price'],
                        'current_price' => $priceData['current_price'] ?? null,
                        'is_default' => $priceData['is_default'] ?? false,
                    ]);
                }
            }

            return $plan->load(['features', 'scopes', 'planPrices.currency', 'planPrices.country']);
            
        } catch (\Exception $e) {
            Log::error('Error creating plan: ' . $e->getMessage());
            throw $e;
        }
    }

    public function show(Plan $plan): View
    {
        return view('plans.show', compact('plan'));
    }

    public function edit(Plan $plan): View
    {
        $plan->load('features');
        $types = [
            ['id' => 1, 'name' => 'Yes'],
            ['id' => 0, 'name' => 'No']
        ];
        
        $plansType = collect(\App\Enums\RecurringType::cases())->map(function ($case) {
            return [
                'id' => $case->value,
                'name' => ucfirst(strtolower($case->value))
            ];
        })->toArray();

        return view('plans.edit', compact('plan', 'types', 'plansType'));
    }

    public function update(PlanStoreRequest $request, Plan $plan)
    {
        try {
            $plan->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'plan_type' => $request->plan_type,
                'current_price' => $request->current_price,
                'has_discount' => $request->has_discount ?? false,
                'status' => $request->status ?? true,
            ]);

            // Update features
            $plan->features()->delete();
            
            if ($request->has('features') && is_array($request->features)) {
                foreach ($request->features as $feature) {
                    if (!empty($feature['name'])) {
                        Feature::create([
                            'plan_id' => $plan->id,
                            'name' => $feature['name'],
                            'is_enabled' => isset($feature['is_enabled']) ? true : false
                        ]);
                    }
                }
            }

            // Update scopes
            if ($request->has('scopes') && is_array($request->scopes)) {
                // Delete existing scopes not in the request
                $requestScopeTypes = array_column($request->scopes, 'scope_type');
                $plan->scopes()->whereNotIn('scope_type', $requestScopeTypes)->delete();
                
                foreach ($request->scopes as $scope) {
                    if (isset($scope['scope_type'])) {
                        PlanScope::updateOrCreate(
                            [
                                'plan_id' => $plan->id,
                                'scope_type' => $scope['scope_type']
                            ],
                            [
                                'module' => $scope['module'] ?? null,
                                'is_enabled' => isset($scope['is_enabled']) ? (bool)$scope['is_enabled'] : false,
                                'max_count' => isset($scope['max_count']) && $scope['max_count'] !== '' ? (int)$scope['max_count'] : null
                            ]
                        );
                    }
                }
            } else {
                // If no scopes provided, delete all existing scopes
                $plan->scopes()->delete();
            }

            // Handle plan prices if provided
            if ($request->has('prices') && is_array($request->prices)) {
                // Ensure only one default price
                $hasDefault = false;
                foreach ($request->prices as $priceData) {
                    if (isset($priceData['is_default']) && $priceData['is_default']) {
                        if ($hasDefault) {
                            $priceData['is_default'] = false; // Only first default is kept
                        } else {
                            $hasDefault = true;
                        }
                    }
                }

                // If no default is set, make the first one default
                if (!$hasDefault && count($request->prices) > 0) {
                    $request->prices[0]['is_default'] = true;
                }

                // Delete existing prices and create new ones
                $plan->planPrices()->delete();
                
                foreach ($request->prices as $priceData) {
                    PlanPrice::create([
                        'plan_id' => $plan->id,
                        'currency_id' => $priceData['currency_id'] ?? null,
                        'country_id' => $priceData['country_id'] ?? null,
                        'price' => $priceData['price'],
                        'current_price' => $priceData['current_price'] ?? null,
                        'is_default' => $priceData['is_default'] ?? false,
                    ]);
                }
            }

            return $plan->fresh()->load(['features', 'scopes', 'planPrices.currency', 'planPrices.country']);
            
        } catch (\Exception $e) {
            Log::error('Error updating plan: ' . $e->getMessage());
            throw $e;
        }
    }

    public function destroy(Plan $plan)
    {
        try {
            $plan->features()->delete();
            $plan->scopes()->delete();
            $plan->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting plan: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPlans(): Collection
    {
        return Plan::where('status', true)->get();
    }

    public function changeStatus(Plan $plan)
    {
        try {
            $plan->update(['status' => !$plan->status]);
            return $plan->fresh();
        } catch (\Exception $e) {
            Log::error('Error changing plan status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAllPlans(): Collection
    {
        return Plan::all();
    }

    public function getPlanName(): Collection|array
    {
        return Plan::where('status', true)->pluck('name', 'id');
    }

    /**
     * Get paginated plans with filters for API
     */
    public function getPaginated(array $filters = [], int $perPage = 15)
    {
        $query = Plan::query()->with(['features', 'scopes', 'planPrices.currency', 'planPrices.country']);

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

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find plan by ID
     */
    public function find($id)
    {
        return Plan::with(['features', 'scopes', 'planPrices.currency', 'planPrices.country'])->find($id);
    }

    /**
     * Find plan by ID or fail
     */
    public function findOrFail($id)
    {
        return Plan::with(['features', 'scopes', 'planPrices.currency', 'planPrices.country'])->findOrFail($id);
    }
}
