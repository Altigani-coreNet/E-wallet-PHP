<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TerminalGroup;
use App\Models\Terminal;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class AdminTerminalGroupController extends Controller
{
    use ApiResponse;

    /**
     * Get all terminal groups with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = TerminalGroup::query()
                ->withCount(['terminals', 'userGroups', 'children']);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('group_id', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Status filter (is_active)
            if ($status = $request->input('status')) {
                $isActive = $status === 'active' ? 1 : 0;
                $query->where('is_active', $isActive);
            }

            // Merchant filter
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Branch filter
            if ($branchId = $request->input('branch_id')) {
                $query->where('branch_id', $branchId);
            }

            // Country filter
            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            // Parent/Subgroup filter
            if ($request->has('is_subgroup')) {
                if ($request->input('is_subgroup') === 'yes') {
                    $query->whereNotNull('parent_id');
                } else if ($request->input('is_subgroup') === 'no') {
                    $query->whereNull('parent_id');
                }
            }

            // Date range filter
            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $terminalGroups = $query->latest()->paginate($perPage);

            return $this->SuccessMessage($terminalGroups);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminal groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminal group details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $terminalGroup = TerminalGroup::with(['parent', 'children', 'terminals', 'userGroups'])
                ->withCount(['terminals', 'userGroups', 'children'])
                ->findOrFail($id);

            return $this->SuccessMessage($terminalGroup);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminal group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new terminal group
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:terminal_groups,id',
                'merchant_id' => 'required|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'description' => 'nullable|string',
                'terminal_ids' => 'required|array|min:1',
                'terminal_ids.*' => 'exists:terminals,id',
                'user_group_ids' => 'required|array|min:1',
                'user_group_ids.*' => 'exists:user_groups,id',
                'is_active' => 'boolean',
            ]);

            DB::beginTransaction();

            // Create the terminal group
            $terminalGroup = TerminalGroup::create([
                'name' => $validated['name'],
                'group_id' => TerminalGroup::generateGroupId(),
                'parent_id' => $validated['parent_id'] ?? null,
                'merchant_id' => $validated['merchant_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Attach terminals
            if (isset($validated['terminal_ids'])) {
                $terminalGroup->terminals()->attach($validated['terminal_ids']);
            }

            // Attach user groups
            if (isset($validated['user_group_ids'])) {
                $terminalGroup->userGroups()->attach($validated['user_group_ids']);
            }

            DB::commit();

            return $this->SuccessMessage($terminalGroup->load(['terminals', 'userGroups']), 201);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->ErrorMessage('Failed to create terminal group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update terminal group
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $terminalGroup = TerminalGroup::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'parent_id' => 'nullable|exists:terminal_groups,id',
                'merchant_id' => 'sometimes|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'description' => 'nullable|string',
                'terminal_ids' => 'sometimes|array',
                'terminal_ids.*' => 'exists:terminals,id',
                'user_group_ids' => 'sometimes|array',
                'user_group_ids.*' => 'exists:user_groups,id',
                'is_active' => 'boolean',
            ]);

            DB::beginTransaction();

            // Update terminal group
            $terminalGroup->update($validated);

            // Sync terminals if provided
            if (isset($validated['terminal_ids'])) {
                $terminalGroup->terminals()->sync($validated['terminal_ids']);
            }

            // Sync user groups if provided
            if (isset($validated['user_group_ids'])) {
                $terminalGroup->userGroups()->sync($validated['user_group_ids']);
            }

            DB::commit();

            return $this->SuccessMessage($terminalGroup->load(['terminals', 'userGroups']));

        } catch (\Exception $e) {
            DB::rollback();
            return $this->ErrorMessage('Failed to update terminal group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete terminal group
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $terminalGroup = TerminalGroup::findOrFail($id);
            
            DB::beginTransaction();
            
            // Detach all relationships
            $terminalGroup->terminals()->detach();
            $terminalGroup->userGroups()->detach();
            
            // Delete the group
            $terminalGroup->delete();
            
            DB::commit();

            return $this->SuccessMessage(['message' => 'Terminal group deleted successfully']);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->ErrorMessage('Failed to delete terminal group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminal group statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_groups' => TerminalGroup::count(),
                'active_groups' => TerminalGroup::where('is_active', true)->count(),
                'inactive_groups' => TerminalGroup::where('is_active', false)->count(),
                'parent_groups' => TerminalGroup::whereNull('parent_id')->count(),
                'subgroups' => TerminalGroup::whereNotNull('parent_id')->count(),
                'groups_this_month' => TerminalGroup::whereMonth('created_at', now()->month)->count(),
                'groups_today' => TerminalGroup::whereDate('created_at', now())->count(),
            ];

            return $this->SuccessMessage($stats);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export terminal groups
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = TerminalGroup::query()
                ->withCount(['terminals', 'userGroups', 'children']);

            // Apply same filters as index
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('group_id', 'like', "%{$search}%");
                });
            }

            if ($status = $request->input('status')) {
                $isActive = $status === 'active' ? 1 : 0;
                $query->where('is_active', $isActive);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($branchId = $request->input('branch_id')) {
                $query->where('branch_id', $branchId);
            }

            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            $terminalGroups = $query->get();

            $exportData = $terminalGroups->map(function ($group) {
                return [
                    'ID' => $group->id,
                    'Group ID' => $group->group_id,
                    'Name' => $group->name,
                    'Description' => $group->description ?? 'N/A',
                    'Merchant ID' => $group->merchant_id ?? 'N/A',
                    'Branch ID' => $group->branch_id ?? 'N/A',
                    'Country ID' => $group->country_id ?? 'N/A',
                    'Parent Group' => $group->parent_id ?? 'N/A',
                    'Terminals Count' => $group->terminals_count,
                    'User Groups Count' => $group->user_groups_count,
                    'Subgroups Count' => $group->children_count,
                    'Status' => $group->is_active ? 'Active' : 'Inactive',
                    'Created At' => $group->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->SuccessMessage([
                'data' => $exportData,
                'filename' => 'terminal_groups_export_' . date('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export terminal groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete terminal groups
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:terminal_groups,id'
            ]);

            DB::beginTransaction();
            
            $groups = TerminalGroup::whereIn('id', $validated['ids'])->get();
            
            foreach ($groups as $group) {
                // Detach relationships
                $group->terminals()->detach();
                $group->userGroups()->detach();
                // Delete
                $group->delete();
            }
            
            DB::commit();

            $count = count($validated['ids']);

            return $this->SuccessMessage([
                'message' => "{$count} terminal group(s) deleted successfully",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->ErrorMessage('Failed to delete terminal groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get parent groups for dropdown (groups without parent)
     */
    public function parentGroups(Request $request): JsonResponse
    {
        try {
            $query = TerminalGroup::query()
                ->whereNull('parent_id')
                ->where('is_active', true);

            // Filter by merchant if provided
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            $parentGroups = $query->select('id', 'name', 'group_id')->get();

            return $this->SuccessMessage($parentGroups);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch parent groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Activate terminal group
     */
    public function activate(string $id): JsonResponse
    {
        try {
            $terminalGroup = TerminalGroup::findOrFail($id);
            $terminalGroup->update(['is_active' => true]);

            return $this->SuccessMessage([
                'message' => 'Terminal group activated successfully',
                'data' => $terminalGroup
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to activate terminal group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Deactivate terminal group
     */
    public function deactivate(string $id): JsonResponse
    {
        try {
            $terminalGroup = TerminalGroup::findOrFail($id);
            $terminalGroup->update(['is_active' => false]);

            return $this->SuccessMessage([
                'message' => 'Terminal group deactivated successfully',
                'data' => $terminalGroup
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to deactivate terminal group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Toggle terminal group status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $terminalGroup = TerminalGroup::findOrFail($id);
            $terminalGroup->update(['is_active' => !$terminalGroup->is_active]);

            $message = $terminalGroup->is_active 
                ? 'Terminal group activated successfully' 
                : 'Terminal group deactivated successfully';

            return $this->SuccessMessage([
                'message' => $message,
                'data' => $terminalGroup
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to toggle terminal group status: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove terminal from group
     */
    public function removeTerminal(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'terminal_id' => 'required|exists:terminals,id'
            ]);

            $terminalGroup = TerminalGroup::findOrFail($id);
            $terminalGroup->terminals()->detach($validated['terminal_id']);

            return $this->SuccessMessage([
                'message' => 'Terminal removed from group successfully'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to remove terminal: ' . $e->getMessage(), null, 500);
        }
    }
}

