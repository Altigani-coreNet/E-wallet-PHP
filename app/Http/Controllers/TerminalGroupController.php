<?php

namespace App\Http\Controllers;

use App\Models\TerminalGroup;
use App\Models\Merchant;
use App\Models\Terminal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TerminalGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $terminalGroups = TerminalGroup::with(['merchant', 'terminals'])
        //     ->orderBy('created_at', 'desc')
        //     ->paginate(10);

        return view('terminal_groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('terminal_groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:terminal_groups,id',
            'description' => 'nullable|string',
            'terminal_ids' => 'required|array|min:1',
            'terminal_ids.*' => 'exists:terminals,id',
            'user_group_ids' => 'required|array|min:1',
            'user_group_ids.*' => 'exists:user_groups,id',
            'merchant_id' => 'nullable|exists:merchants,id',
        ]);

        try {
            DB::beginTransaction();

            // Determine merchant_id: prefer provided merchant_id (admin), fallback to first user group's merchant
            $merchantId = $request->merchant_id;
            if (!$merchantId) {
                $firstUserGroup = \App\Models\UserGroup::find($request->user_group_ids[0]);
                $merchantId = $firstUserGroup ? $firstUserGroup->merchant_id : null;
            }

            // Create the terminal group
            $terminalGroup = TerminalGroup::create([
                'name' => $request->name,
                'group_id' => TerminalGroup::generateGroupId(),
                'parent_id' => $request->parent_id,
                'merchant_id' => $merchantId,
                'description' => $request->description,
                'is_active' => true,
                'country_id' => $merchantId ? Merchant::select('country_id')->find($merchantId)->country_id : null,
            ]);

            // Attach terminals to the group
            if ($request->has('terminal_ids')) {
                $terminalGroup->terminals()->attach($request->terminal_ids);
                
                // Log terminal assignment to group for each terminal
                $admin = auth()->user();
                foreach ($request->terminal_ids as $terminalId) {
                    $terminal = Terminal::find($terminalId);
                    if ($terminal) {
                        $terminal->logActivity('group_assigned', $admin, "Terminal assigned to group '{$terminalGroup->name}'");
                    }
                }
            }

            // Attach user groups to the terminal group
            if ($request->has('user_group_ids')) {
                $terminalGroup->userGroups()->attach($request->user_group_ids);
            }

            // Update terminal merchant_ids to match chosen merchant_id
            if ($merchantId) {
                Terminal::whereIn('id', $request->terminal_ids)->update([
                    'merchant_id' => $merchantId,
                    'is_assigned_to_group' => true
                ]);
            } else {
                Terminal::whereIn('id', $request->terminal_ids)->update([
                    'is_assigned_to_group' => true
                ]);
            }


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Terminal group created successfully.',
                'data' => $terminalGroup->load('terminals')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create terminal group: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TerminalGroup $terminalGroup)
    {
        $terminalGroup->load(['parent', 'children', 'terminals']);
        return view('terminal_groups.show', compact('terminalGroup'));
    }

    /**
     * Display the specified resource for API.
     */
    public function showApi(TerminalGroup $terminalGroup)
    {
        $terminalGroup->load(['parent', 'children', 'terminals']);
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
        $terminalGroup->load(['parent', 'children', 'terminals']);
        
        // Get all active terminals
        $terminals = Terminal::active()->get();

        return view('terminal_groups.edit', compact('terminalGroup', 'terminals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TerminalGroup $terminalGroup)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:terminal_groups,id',
            'description' => 'nullable|string',
            'terminal_ids' => 'required|array|min:1',
            'terminal_ids.*' => 'exists:terminals,id',
        ]);

        try {
            DB::beginTransaction();

            $oldTerminalIds = $terminalGroup->terminals()->pluck('terminals.id')->toArray();
            $newTerminalIds = $request->terminal_ids;

            $terminalsToRemove = array_diff($oldTerminalIds, $newTerminalIds);

            $terminalsToAdd = array_diff($newTerminalIds, $oldTerminalIds);

            Terminal::whereIn('id', $terminalsToRemove)->update([
                'is_assigned_to_group' => false
            ]);

            Terminal::whereIn('id', $terminalsToAdd)->update([
                'is_assigned_to_group' => true
            ]);

            // Log terminal removals from group
            $admin = auth()->user();
            foreach ($terminalsToRemove as $terminalId) {
                $terminal = Terminal::find($terminalId);
                if ($terminal) {
                    $terminal->logActivity('group_removed', $admin, "Terminal removed from group '{$terminalGroup->name}'");
                }
            }

            // Log terminal additions to group
            foreach ($terminalsToAdd as $terminalId) {
                $terminal = Terminal::find($terminalId);
                if ($terminal) {
                    $terminal->logActivity('group_assigned', $admin, "Terminal assigned to group '{$terminalGroup->name}'");
                }
            }

            // Update the terminal group
            $terminalGroup->update([
                'name' => $request->name,
                'parent_id' => $request->parent_id,
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
            dd($e->getMessage(), $e->getLine());
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
        try {
            $terminalGroup->delete();
            return redirect()->route('terminal-groups.index')
                ->with('success', 'Terminal group deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete terminal group: ' . $e->getMessage());
        }
    }

    /**
     * Get terminals for a specific merchant (AJAX)
     */
    public function getMerchantTerminals(Request $request)
    {
        $merchantId = $request->merchant_id;
        
        $terminals = Terminal::where('merchant_id', $merchantId)
            ->active()
            ->get(['id', 'name', 'terminal_id']);

        return response()->json($terminals);
    }

    /**
     * Toggle the active status of a terminal group
     */
    public function toggleStatus(TerminalGroup $terminalGroup)
    {
        try {
            $terminalGroup->update([
                'is_active' => !$terminalGroup->is_active
            ]);

            $status = $terminalGroup->is_active ? 'activated' : 'deactivated';
            return redirect()->route('terminal-groups.index')
                ->with('success', "Terminal group {$status} successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update terminal group status: ' . $e->getMessage());
        }
    }

    /**
     * Get parent terminal groups for select dropdown
     */
    public function getParentGroups()
    {
        $parentGroups = TerminalGroup::parentGroups()
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
     * Get terminal groups for select dropdown
     */
    public function select(Request $request)
    {
        $query = TerminalGroup::query();
        
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
     * Get data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        $query = TerminalGroup::withCountry()->with(['parent', 'terminals']);

        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('group_id', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%")
                    ->orWhereHas('parent', function ($parentQuery) use ($search) {
                        $parentQuery->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($request->has('status')) {
            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };

            $query->where('is_active', $status);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', request()->branch_id);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        return DataTables::of($query)
            ->addColumn('record_select', 'terminal_groups.data_table.record_select')
            ->addColumn('actions', function ($group) {
                return view('terminal_groups.data_table.actions', compact('group'));
            })
            ->editColumn("status", fn($item) => $item->getStatusWithSpan())
            ->editColumn("name", fn($item) => "<div> $item->name <br/> {$item->group_id}</div>")
            ->editColumn("parent_id", fn($item) => $item->parent ? $item->parent->name : 'Parent Group')
            ->editColumn("merchant_name", fn($item) => $item->merchant ? $item->merchant->name : 'N/A')
            // ->editColumn("branch_id", fn($item) => $item->branch ? $item->branch->name : 'N/A')
            ->editColumn("terminals_count", fn($item) => "<span class='badge badge-light-primary '>{$item->terminals->count()}</span>")
            ->editColumn("created_at", fn($item) => $item->created_at->format('M d, Y H:i'))
            ->rawColumns(['record_select', 'actions', 'name', 'status', 'parent_id', 'branch_id', 'terminals_count', 'created_at'])
            ->toJson();

    }

    /**
     * Bulk delete terminal groups
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

            // Delete terminal groups
            $deletedCount = TerminalGroup::whereIn('id', $ids)->delete();

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

    /**
     * Remove a terminal from the terminal group
     */
    public function removeTerminal(Request $request, TerminalGroup $terminalGroup)
    {
        try {
            $request->validate([
                'terminal_id' => 'required|exists:terminals,id'
            ]);

            $terminalId = $request->terminal_id;

          
            // Check if terminal is actually in this group
            if (!$terminalGroup->terminals()->where('terminal_group_terminal.terminal_id', $terminalId)->exists()) {
                return redirect()->back()->with('error', 'Terminal is not in this group.');
            }

            // Remove terminal from group
            $terminalGroup->terminals()->detach($terminalId);

            // Log terminal removal from group
            $terminal = Terminal::find($terminalId);
            if ($terminal) {
                $admin = auth()->user();
                $terminal->logActivity('group_removed', $admin, "Terminal removed from group '{$terminalGroup->name}'");
            }

            Terminal::where('id', $terminalId)->update([
                'is_assigned_to_group' => false
            ]);

            return redirect()->back()->with('success', 'Terminal removed from group successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to remove terminal from group: ' . $e->getMessage());
        }
    }

    /**
     * Export terminal groups with current filters
     */
    public function export(Request $request)
    {
        $query = TerminalGroup::with(['merchant', 'terminals']);

        // Apply filters
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('merchant') && !empty($request->merchant)) {
            $query->where('merchant_id', $request->merchant);
        }

        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->whereHas('merchant', function($q) use ($request) {
                $q->where('country_id', $request->country_id);
            });
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $terminalGroups = $query->get();

        // Create new Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator('Fast POS System')
            ->setTitle('Terminal Groups Export')
            ->setSubject('Terminal Groups')
            ->setDescription('Export of terminal groups data');

        // Set header row style
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ];

        // Add headers
        $headers = ['ID', 'Name', 'Merchant', 'Terminals Count', 'Status', 'Created At'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add data rows
        $row = 2;
        foreach ($terminalGroups as $group) {
            $sheet->setCellValue('A' . $row, $group->id);
            $sheet->setCellValue('B' . $row, $group->name);
            $sheet->setCellValue('C' . $row, $group->merchant->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $group->terminals->count());
            $sheet->setCellValue('E' . $row, $group->is_active ? 'Active' : 'Inactive');
            $sheet->setCellValue('F' . $row, $group->created_at->format('Y-m-d H:i:s'));
            
            // Apply alternating row colors
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F2F2F2');
            }
            
            $row++;
        }

        // Add borders to all cells
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:F' . ($row - 1))->applyFromArray($styleArray);

        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'terminal_groups_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
