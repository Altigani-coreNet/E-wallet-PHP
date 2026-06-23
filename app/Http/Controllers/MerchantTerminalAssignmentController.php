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
use Illuminate\Support\Facades\Auth;

class MerchantTerminalAssignmentController extends Controller
{
    /**
     * Display the merchant terminal assignment page
     */
    public function index(Request $request)
    {
        $selectedUserId = $request->query('user_id');
        
        if (!$selectedUserId) {
            return redirect()->route('merchant.users.index')->with('error', 'User ID is required');
        }
        
        $user = User::where('id', $selectedUserId)
                   ->where('merchant_id', Auth::user()->merchant_id)
                   ->firstOrFail();
        
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            return redirect()->route('merchant.users.index')->with('error', 'Merchant not found');
        }
        
        // Get branches for this merchant only
        $branches = Branch::where('merchant_id', $merchant->id)->get();
        
        return view('merchant.terminal-assignments.index', compact('user', 'merchant', 'branches', 'selectedUserId'));
    }

    /**
     * Store terminal assignments for merchant
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
            
            // Verify user belongs to current merchant
            $user = User::where('id', $userId)
                       ->where('merchant_id', Auth::user()->merchant_id)
                       ->firstOrFail();

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

            return redirect()->route('merchant.users.index')
                ->with('success', 'Terminals assigned successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error assigning terminals: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign user groups to user (merchant-scoped)
     */
    private function assignUserGroups($userId, $userGroupIds, $branchId)
    {
        $user = User::where('id', $userId)
                   ->where('merchant_id', Auth::user()->merchant_id)
                   ->firstOrFail();
        
        // Verify user groups belong to merchant's branches
        $validUserGroups = UserGroup::whereIn('id', $userGroupIds)
                                   ->whereHas('branch', function($query) {
                                       $query->where('merchant_id', Auth::user()->merchant_id);
                                   })
                                   ->pluck('id')
                                   ->toArray();
        
        // Sync the user groups
        $user->userGroups()->sync($validUserGroups);
        
        // Get all terminal IDs from user groups and their associated terminal groups
        $terminalIds = [];
        
        foreach ($validUserGroups as $userGroupId) {
            $userGroup = UserGroup::find($userGroupId);
            
            if ($userGroup->is_single_terminal && $userGroup->terminal_id) {
                // Single terminal mode - verify terminal belongs to merchant
                $terminal = Terminal::where('id', $userGroup->terminal_id)
                                   ->where('merchant_id', Auth::user()->merchant_id)
                                   ->first();
                if ($terminal) {
                    $terminalIds[] = $userGroup->terminal_id;
                }
            } else {
                // Multiple terminal groups mode
                $userGroupTerminalIds = $userGroup->terminalGroups()
                    ->with(['terminals' => function($query) {
                        $query->where('merchant_id', Auth::user()->merchant_id);
                    }])
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
        
        Log::info("Merchant user {$user->name} (ID: {$userId}) assigned to user groups: " . implode(', ', $validUserGroups) . " with terminals: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Assign terminal groups to user (merchant-scoped)
     */
    private function assignTerminalGroups($userId, $terminalGroupIds, $branchId)
    {
        $user = User::where('id', $userId)
                   ->where('merchant_id', Auth::user()->merchant_id)
                   ->firstOrFail();
        
        // Verify terminal groups belong to merchant's branches
        $validTerminalGroups = TerminalGroup::whereIn('id', $terminalGroupIds)
                                           ->whereHas('branch', function($query) {
                                               $query->where('merchant_id', Auth::user()->merchant_id);
                                           })
                                           ->pluck('id')
                                           ->toArray();
        
        // Sync the terminal groups directly to the user
        $user->terminalGroups()->sync($validTerminalGroups);
        
        // Get all terminal IDs from terminal groups (merchant-scoped)
        $terminalIds = TerminalGroup::whereIn('id', $validTerminalGroups)
            ->with(['terminals' => function($query) {
                $query->where('merchant_id', Auth::user()->merchant_id);
            }])
            ->get()
            ->flatMap(function ($terminalGroup) {
                return $terminalGroup->terminals->pluck('id');
            })
            ->toArray();
        
        // Remove duplicates and add to user's terminals
        $terminalIds = array_unique($terminalIds);
        $user->addTerminalIds($terminalIds);
        
        Log::info("Merchant user {$user->name} (ID: {$userId}) assigned to terminal groups: " . implode(', ', $validTerminalGroups) . " with terminals: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Assign terminals directly to user (merchant-scoped)
     */
    private function assignTerminalsDirectly($userId, $terminalIds, $branchId)
    {
        $user = User::where('id', $userId)
                   ->where('merchant_id', Auth::user()->merchant_id)
                   ->firstOrFail();
        
        // Verify terminals belong to merchant
        $validTerminalIds = Terminal::whereIn('id', $terminalIds)
                                   ->where('merchant_id', Auth::user()->merchant_id)
                                   ->pluck('id')
                                   ->toArray();
        
        // Sync the terminals directly to the user
        $user->terminals()->sync($validTerminalIds);
        
        // Add terminal IDs to user's terminals JSON field
        $user->addTerminalIds($validTerminalIds);
        
        Log::info("Merchant user {$user->name} (ID: {$userId}) assigned to terminals directly: " . implode(', ', $validTerminalIds));
        
        return true;
    }

    /**
     * Assign all terminals to user (merchant-scoped)
     */
    private function assignAllTerminals($userId)
    {
        $user = User::where('id', $userId)
                   ->where('merchant_id', Auth::user()->merchant_id)
                   ->firstOrFail();
        
        // Get all terminals from the user's merchant
        $terminalIds = Terminal::where('merchant_id', Auth::user()->merchant_id)
            ->pluck('id')
            ->toArray();
        
        // Sync all terminals to the user
        $user->terminals()->sync($terminalIds);
        
        // Set all terminal IDs in user's terminals JSON field
        $user->setTerminalIds($terminalIds);
        
        Log::info("Merchant user {$user->name} (ID: {$userId}) assigned to all terminals in merchant: {$user->merchant_id} with terminals: " . implode(', ', $terminalIds));
        
        return true;
    }

    /**
     * Get user groups by branch (merchant-scoped)
     */
    public function getUserGroupsByBranch($branchId)
    {
        $userGroups = UserGroup::where('branch_id', $branchId)
                              ->whereHas('branch', function($query) {
                                  $query->where('merchant_id', Auth::user()->merchant_id);
                              })
                              ->get();
        return response()->json($userGroups);
    }

    /**
     * Get terminal groups by branch (merchant-scoped)
     */
    public function getTerminalGroupsByBranch($branchId)
    {
        $terminalGroups = TerminalGroup::where('branch_id', $branchId)
                                      ->whereHas('branch', function($query) {
                                          $query->where('merchant_id', Auth::user()->merchant_id);
                                      })
                                      ->get();
        return response()->json($terminalGroups);
    }

    /**
     * Get terminals by branch (merchant-scoped)
     */
    public function getTerminalsByBranch($branchId)
    {
        $terminals = Terminal::where('branch_id', $branchId)
                            ->where('merchant_id', Auth::user()->merchant_id)
                            ->get();
        return response()->json($terminals);
    }
} 