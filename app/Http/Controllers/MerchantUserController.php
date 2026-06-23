<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\UserService;
use App\Traits\MessageManager;
use App\Traits\Select2Trait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class MerchantUserController extends Controller
{
    use AuthorizesRequests, MessageManager, Select2Trait;

    public function __construct(public UserService $userService)
    {
    }

    public function index(): View
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('view_users')) {
            abort(403, 'Unauthorized access to users.');
        }
        return view('merchant.users.index');
    }

    public function data(Request $request): JsonResponse
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('view_users')) {
            abort(403, 'Unauthorized access to users.');
        }
        $query = User::with(["Roles", "merchant", "branch"])
            ->where('merchant_id', auth()->user()->merchant_id);

        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%");
            });
        }

        if ($request->has('status')) {
            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };
            $query->where('status', $status);
        }

        if ($request->has('city_id')) {
            $query->where('city_id', request()->city_id);
        }

        return DataTables::of($query)
            ->addColumn('record_select', 'merchant.users.data_table.record_select')
            ->addColumn("roles", fn($user) => view('merchant.users.data_table.roles', compact('user'))->render())
            ->addColumn('actions', 'merchant.users.data_table.actions')
            ->editColumn("status", fn($item) => $item->getStatusWithSpan())
            ->editColumn("profile_image", fn($item) => $item->getTableImage())
            ->editColumn("name", fn($item) => "<div> $item->name </div>")
            ->editColumn("branch_id", fn($item) => $item->branch ? $item->branch->name : 'N/A')
            ->rawColumns(['record_select', 'roles', "user_contact", 'actions', 'name', "profile_image", "status", "branch_id"])
            ->toJson();
    }

    public function create(): View
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('create_users')) {
            abort(403, 'Unauthorized access to create users.');
        }
        return view('merchant.users.create');
    }

    public function store(UserRequest $request)
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('create_users')) {
            abort(403, 'Unauthorized access to create users.');
        }
        // Set merchant_id from authenticated user
        $request->merge(['merchant_id' => auth()->user()->merchant_id]);
        // dd($request->all());
        $this->userService->createUser($request);

        session()->flash('success', __('translation.added_successfully'));
        return Redirect()->route('merchant.users.index');
    }

    public function show(User $user): RedirectResponse|View
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('view_users')) {
            abort(403, 'Unauthorized access to users.');
        }
        // Check if the user belongs to the authenticated merchant
        if ($user->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user.');
        }

        if (request()->status) {
            return $this->userService->changeStatus($user);
        }
        return $this->userService->show($user);
    }

    public function usersSections(User $user, string $type = 'overview'): View
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('view_users')) {
            abort(403, 'Unauthorized access to users.');
        }
        // Check if the user belongs to the authenticated merchant
        if ($user->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user.');
        }

        return $this->userService->getUsersSections($user, $type);
    }

    public function edit($id): View
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('edit_users')) {
            abort(403, 'Unauthorized access to edit users.');
        }
        $user = User::where('id', $id)
            ->where('merchant_id', auth()->user()->merchant_id)
            ->firstOrFail();

        return view('merchant.users.edit', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('edit_users')) {
            abort(403, 'Unauthorized access to edit users.');
        }
        // Check if the user belongs to the authenticated merchant
        if ($user->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user.');
        }

        // Set merchant_id from authenticated user
        $request->merge(['merchant_id' => auth()->user()->merchant_id]);

        $this->userService->updateUser($request, $user);
        $this->SuccessMessage(__("translation.users_updated_successfully"));
        return redirect()->route('merchant.users.index');
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('delete_users')) {
            abort(403, 'Unauthorized access to delete users.');
        }
        try {
            $user = User::where('id', $id)
                ->where('merchant_id', auth()->user()->merchant_id)
                ->firstOrFail();

          
            $this->userService->deleteUser($user);
            $this->SuccessMessage(__('translation.deleted_successfully'));
            return redirect()->back();
        } catch (\Exception $exception) {
            $this->ErrorMessage($exception->getMessage());
            return redirect()->back();
        }
    }

    public function bulkDelete()
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('delete_users')) {
            abort(403, 'Unauthorized access to delete users.');
        }
        foreach (json_decode(request()->record_ids) as $recordId) {
            $user = User::where('id', $recordId)
                ->where('merchant_id', auth()->user()->merchant_id)
                ->first();

            if ($user) {
                $this->delete($user);
            }
        }

        session()->flash('success', __('translation.deleted_successfully'));
        return response(__('translation.deleted_successfully'));
    }

    private function delete(User $user)
    {
        $user->delete();
    }


    public function getUserInSelect(Request $request)
    {
        if (!auth()->user()->can('users') && !auth()->user()->can('view_users')) {
            abort(403, 'Unauthorized access to users.');
        }
        // Only return users for the authenticated merchant
        $query = User::where('merchant_id', auth()->user()->merchant_id);
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->select('id', 'name', 'email', 'phone')
                      ->orderBy('name')
                      ->limit(10)
                      ->get()
                      ->map(function ($user) {
                          return [
                              'id' => $user->id,
                              'text' => $user->name . ' (' . $user->email . ')'
                          ];
                      });

        return response()->json($users);
    }
} 