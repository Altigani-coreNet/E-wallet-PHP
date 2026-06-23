<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;
use App\Models\UserGroup;
use App\Models\TerminalGroup;
use App\Models\Terminal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminalAssignmentController extends Controller
{
    /**
     * Display the terminal assignment page
     */
    public function index(Request $request)
    {
        $selectedUserId = $request->query('user_id');
        
        if (!$selectedUserId) {
            return redirect()->route('users.index')->with('error', 'User ID is required');
        }
        
        $user = User::findOrFail($selectedUserId);
        $merchant = $user->merchant; // Assuming user has merchant relationship
        
        if (!$merchant) {
            return redirect()->route('users.index')->with('error', 'User does not have an associated merchant');
        }
        
        // Get branches for this merchant only
        $branches = Branch::where('merchant_id', $merchant->id)->get();
        
        return view('terminal-assignments.index', compact('user', 'merchant', 'branches', 'selectedUserId'));
    }

    /**
     * Store terminal assignments
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'assignment_type' => 'required|in:user_groups,terminal_groups,individual_terminals,all_terminals',
            'branch_id' => 'required_if:assignment_type,user_groups,terminal_groups,individual_terminals|exists:branches,id',
            'user_group_ids' => 'required_if:assignment_type,user_groups|array',
            'user_group_ids.*' => 'exists:user_groups,id',
            'terminal_group_ids' => 'required_if:assignment_type,terminal_groups|array',
            'terminal_group_ids.*' => 'exists:terminal_groups,id',
            'terminal_ids' => 'required_if:assignment_type,individual_terminals|array',
            'terminal_ids.*' => 'exists:terminals,id',
        ]);

        try {
            DB::beginTransaction();

            $userId = $request->user_id;
            $assignmentType = $request->assignment_type;

            switch ($assignmentType) {
                case 'user_groups':
                    $this->assignUserGroups($userId, $request->user_group_ids, $request->branch_id);
                    break;
                    
                case 'terminal_groups':
                    $this->assignTerminalGroups($userId, $request->terminal_group_ids, $request->branch_id);
                    break;
                    
                case 'individual_terminals':
                    $this->assignTerminalsDirectly($userId, $request->terminal_ids, $request->branch_id);
                    break;
                    
                case 'all_terminals':
                    $this->assignAllTerminals($userId);
                    break;
            }

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'Terminals assigned successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error assigning terminals: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign user groups to user
     */
    private function assignUserGroups($userId, $userGroupIds, $branchId)
    {
        $user = User::findOrFail($userId);
        
        // Sync the user groups - this will add new relationships and remove old ones
        $user->userGroups()->sync($userGroupIds);
        
        // Get all terminal IDs from user groups and their associated terminal groups
        $terminalIds = [];
        
        foreach ($userGroupIds as $userGroupId) {
            $userGroup = UserGroup::find($userGroupId);
            
            if ($userGroup->is_single_terminal && $userGroup->terminal_id) {
                // Single terminal mode
                $terminalIds[] = $userGroup->terminal_id;
            } else {
                // Multiple terminal groups mode
                $userGroupTerminalIds = $userGroup->terminalGroups()
                    ->with('terminals')
                    ->get()
                    ->flatMap(function ($terminalGroup) {
                        return $terminalGroup->terminals->pluck('id');
                    })
                    ->toArray();
                
                $terminalIds = array_merge($terminalIds, $userGroupTerminalIds);
            }
        }
        
        // Remove duplicates and add to user's terminals
        $terminalIds = array_unique($terminalIds);
        $user->addTerminalIds($terminalIds);
        
        // Log the assignment for audit purposes
        Log::info("User {$user->name} (ID: {$userId}) assigned to user groups: " . implode(', ', $userGroupIds) . " with terminals: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Assign terminal groups to user
     */
    private function assignTerminalGroups($userId, $terminalGroupIds, $branchId)
    {
        $user = User::findOrFail($userId);
        
        // Sync the terminal groups directly to the user
        $user->terminalGroups()->sync($terminalGroupIds);
        
        // Get all terminal IDs from terminal groups
        $terminalIds = TerminalGroup::whereIn('id', $terminalGroupIds)
            ->with('terminals')
            ->get()
            ->flatMap(function ($terminalGroup) {
                return $terminalGroup->terminals->pluck('id');
            })
            ->toArray();
        
        // Remove duplicates and add to user's terminals
        $terminalIds = array_unique($terminalIds);
        $user->addTerminalIds($terminalIds);
        
        Log::info("User {$user->name} (ID: {$userId}) assigned to terminal groups: " . implode(', ', $terminalGroupIds) . " with terminals: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Assign terminals directly to user
     */
    private function assignTerminalsDirectly($userId, $terminalIds, $branchId)
    {
        $user = User::findOrFail($userId);
        
        // Sync the terminals directly to the user
        $user->terminals()->sync($terminalIds);
        
        // Add terminal IDs to user's terminals JSON field
        $user->addTerminalIds($terminalIds);
        
        Log::info("User {$user->name} (ID: {$userId}) assigned to terminals directly: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Assign all terminals to user
     */
    private function assignAllTerminals($userId)
    {
        $user = User::findOrFail($userId);
        
        // Get all terminals from the user's merchant
        $terminalIds = Terminal::pluck('id')
            ->toArray();
        
        // Sync all terminals to the user
        $user->terminals()->sync($terminalIds);
        
        // Set all terminal IDs in user's terminals JSON field
        $user->setTerminalIds($terminalIds);
        
        Log::info("User {$user->name} (ID: {$userId}) assigned to all terminals in merchant: {$user->merchant_id} with terminals: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Get user groups by branch
     */
    public function getUserGroupsByBranch($branchId)
    {
        $userGroups = UserGroup::where('branch_id', $branchId)->get();
        return response()->json($userGroups);
    }

    /**
     * Get terminal groups by branch
     */
    public function getTerminalGroupsByBranch($branchId)
    {
        $terminalGroups = TerminalGroup::where('branch_id', $branchId)->get();
        return response()->json($terminalGroups);
    }

    /**
     * Get terminals by branch
     */
    public function getTerminalsByBranch($branchId)
    {
        $terminals = Terminal::where('branch_id', $branchId)->get();
        return response()->json($terminals);
    }
} 