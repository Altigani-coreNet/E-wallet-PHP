<?php

namespace App\Http\Controllers;

use App\Models\TerminalGroup;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\Select2Trait;
use App\Traits\MessageManager;

class MerchantTerminalGroupController extends Controller
{
    use Select2Trait, MessageManager;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('merchant.terminal_groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('merchant.terminal_groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'terminal_ids' => 'required|array|min:1',
            'terminal_ids.*' => 'exists:terminals,id',
            'user_group_ids' => 'required|array|min:1',
            'user_group_ids.*' => 'exists:user_groups,id',
        ]);

        try {
            DB::beginTransaction();

            // Create the terminal group with merchant_id from authenticated user
            $terminalGroup = TerminalGroup::create([
                'name' => $request->name,
                'group_id' => TerminalGroup::generateGroupId(),
                'merchant_id' => auth()->user()->merchant_id,
                'branch_id' => $request->branch_id,
                'description' => $request->description,
                'is_active' => true,
            ]);

            // Attach terminals to the group
            if ($request->has('terminal_ids')) {
                $terminalGroup->terminals()->attach($request->terminal_ids);
            }

            // Attach user groups to the terminal group
            if ($request->has('user_group_ids')) {
                $terminalGroup->userGroups()->attach($request->user_group_ids);
            }

            // Update terminal merchant_ids to match user group merchant_id
            Terminal::whereIn('id', $request->terminal_ids)->update([
                'merchant_id' => auth()->user()->merchant_id,
                'is_assigned_to_group' => true
            ]);

            DB::commit();

            return redirect()->route('merchant.terminal-groups.index')
                ->with('success', 'Terminal group created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to create terminal group: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TerminalGroup $terminalGroup)
    {
        // Check if the terminal group belongs to the authenticated merchant
        if ($terminalGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal group.');
        }

        $terminalGroup->load(['merchant', 'terminals']);
        return view('merchant.terminal_groups.show', compact('terminalGroup'));
    }

    /**
     * Display the specified resource for API.
     */
    public function showApi(TerminalGroup $terminalGroup)
    {
        // Check if the terminal group belongs to the authenticated merchant
        if ($terminalGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal group.');
        }

        $terminalGroup->load(['merchant', 'terminals']);
        return response()->json([
            'success' => true,
            'data' => $terminalGroup
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TerminalGroup $terminalGroup)
    {
        // Check if the terminal group belongs to the authenticated merchant
        if ($terminalGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal group.');
        }

        $terminalGroup->load(['merchant', 'terminals']);
        
        // Get terminals for the authenticated merchant
        $merchantTerminals = Terminal::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get();

        return view('merchant.terminal_groups.edit', compact('terminalGroup', 'merchantTerminals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TerminalGroup $terminalGroup)
    {
        // Check if the terminal group belongs to the authenticated merchant
        if ($terminalGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal group.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'terminal_ids' => 'required|array|min:1',
            'terminal_ids.*' => 'exists:terminals,id',
        ]);

        try {
            DB::beginTransaction();

            // Update the terminal group
            $terminalGroup->update([
                'name' => $request->name,
                'branch_id' => $request->branch_id,
                'description' => $request->description,
            ]);

            // Sync terminals to the group
            if ($request->has('terminal_ids')) {
                $terminalGroup->terminals()->sync($request->terminal_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Terminal group updated successfully.',
                'data' => $terminalGroup->load('terminals')
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update terminal group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TerminalGroup $terminalGroup)
    {
        // Check if the terminal group belongs to the authenticated merchant
        if ($terminalGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal group.');
        }

        try {
            $terminalGroup->delete();
            return redirect()->route('merchant.terminal-groups.index')
                ->with('success', 'Terminal group deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete terminal group: ' . $e->getMessage());
        }
    }

    /**
     * Get terminals for the authenticated merchant (AJAX)
     */
    public function getMerchantTerminals(Request $request)
    {
        $terminals = Terminal::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get(['id', 'name', 'terminal_id']);

        return response()->json($terminals);
    }

    /**
     * Get parent terminal groups for select dropdown (merchant-specific)
     */
    public function getParentGroups()
    {
        $parentGroups = TerminalGroup::where('merchant_id', auth()->user()->merchant_id)
            ->parentGroups()
            ->active()
            ->select('id', 'name', 'group_id')
            ->orderBy('name')
            ->get()
            ->map(function ($terminalGroup) {
                return [
                    'id' => $terminalGroup->id,
                    'text' => $terminalGroup->name . ' (' . $terminalGroup->group_id . ')'
                ];
            });

        return response()->json($parentGroups);
    }

    /**
     * Toggle the active status of a terminal group
     */
    public function toggleStatus(TerminalGroup $terminalGroup)
    {
        // Check if the terminal group belongs to the authenticated merchant
        if ($terminalGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal group.');
        }

        try {
            $terminalGroup->update([
                'is_active' => !$terminalGroup->is_active
            ]);

            $status = $terminalGroup->is_active ? 'activated' : 'deactivated';
            return redirect()->route('merchant.terminal-groups.index')
                ->with('success', "Terminal group {$status} successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update terminal group status: ' . $e->getMessage());
        }
    }

    /**
     * Get terminal groups for select dropdown (merchant-specific)
     */
    public function select(Request $request): JsonResponse
    {
        $query = TerminalGroup::where('merchant_id', auth()->user()->merchant_id);
        
        // Filter by branch if provided
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('group_id', 'like', "%{$search}%");
            });
        }
        
        $terminalGroups = $query->select('id', 'name', 'group_id')
                               ->orderBy('name')
                               ->limit(10)
                               ->get()
                               ->map(function ($terminalGroup) {
                                   return [
                                       'id' => $terminalGroup->id,
                                       'text' => $terminalGroup->name . ' (' . $terminalGroup->group_id . ')'
                                   ];
                               });
        
        return response()->json($terminalGroups);
    }

    /**
     * Get data for DataTables (merchant-specific)
     */
    public function data(Request $request): JsonResponse
    {
        $query = TerminalGroup::with(['merchant', 'terminals'])
            ->where('merchant_id', auth()->user()->merchant_id);

        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('group_id', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%");
            });
        }

        if ($request->has('status')) {
            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };

            $query->where('is_active', $status);
        }

        return DataTables::of($query)
            ->addColumn('record_select', fn($group) => view('merchant.terminal_groups.data_table.record_select', compact('group')))
            ->addColumn('actions', function ($group) {
                return view('merchant.terminal_groups.data_table.actions', compact('group'));
            })
            ->editColumn("status", fn($item) => $item->getStatusWithSpan())
            ->editColumn("name", fn($item) => "<div> $item->name <br/> {$item->group_id}</div>")
            ->editColumn("branch_id", fn($item) => $item->branch ? $item->branch->name : 'N/A')
            ->editColumn("terminals_count", fn($item) => "<span class='badge badge-light-primary badge-sm'>{$item->terminals->count()}</span>")
            ->editColumn("created_at", fn($item) => $item->created_at->format('M d, Y H:i'))
            ->rawColumns(['record_select', 'actions', 'name', 'status', 'branch_id', 'terminals_count', 'created_at'])
            ->toJson();
    }

    /**
     * Bulk delete terminal groups (merchant-specific)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|string'
            ]);

            $ids = explode(',', $request->ids);
            $ids = array_filter($ids); // Remove empty values

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No terminal groups selected for deletion.'
                ], 400);
            }

            DB::beginTransaction();

            // Delete terminal groups that belong to the authenticated merchant
            $deletedCount = TerminalGroup::whereIn('id', $ids)
                ->where('merchant_id', auth()->user()->merchant_id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} terminal group(s)."
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete terminal groups: ' . $e->getMessage()
            ], 500);
        }
    }
} 