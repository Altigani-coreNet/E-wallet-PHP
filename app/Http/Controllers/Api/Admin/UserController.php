<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponse;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $users = $this->userService->getAllUsers($request);
            return $this->SuccessMessage($users);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request);
            return $this->SuccessMessage($user, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        try {
            $userData = $this->userService->show($user);
            return $this->SuccessMessage($userData);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUser($request, $user);
            return $this->SuccessMessage($updatedUser);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);
            return $this->SuccessMessage(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get users data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $data = $this->userService->data($request);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch users data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get users for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $users = $this->userService->getUserInSelect($request);
            // Normalize to include merchant and branch names in API response
            $normalized = collect($users)->map(function ($u) {
                // If repository already returns arrays, keep them; else map model
                if (is_array($u)) return $u;
                return [
                    'id' => $u->id,
                    'text' => $u->name . ($u->email ? " ({$u->email})" : ''),
                    'name' => $u->name,
                    'email' => $u->email,
                    'merchant_id' => $u->merchant_id,
                    'merchant_name' => optional($u->merchant)->name,
                    'branch_id' => $u->branch_id,
                    'branch_name' => optional($u->branch)->name,
                    'phone' => $u->phone,
                ];
            });
            return $this->SuccessMessage($normalized);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch users for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Assign terminals to user
     */
    public function assignTerminals(Request $request): JsonResponse
    {
        try {
            $result = $this->userService->assignTerminals($request);
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to assign terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove terminal from user
     */
    public function removeTerminal(Request $request): JsonResponse
    {
        try {
            $result = $this->userService->removeTerminal($request);
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to remove terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user sections
     */
    public function sections(User $user, string $type = 'overview'): JsonResponse
    {
        try {
            $sections = $this->userService->getUsersSections($user, $type);
            return $this->SuccessMessage($sections);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user sections: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete users
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:users,id'
            ]);

            $this->userService->bulkDelete($request->ids);
            return $this->SuccessMessage(['message' => 'Users deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete users: ' . $e->getMessage(), null, 500);
        }
    }

} 