<?php

namespace App\Repositories;


use App\Http\Requests\UserRequest;
use App\Http\Requests\VerifiedRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use App\Models\UsersOtp;
use App\Services\UserService;
use App\Traits\HasFiles;
use App\Traits\MessageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PHPUnit\Framework\Exception;
use Yajra\DataTables\DataTables;
use App\Mail\NewUserCredentialsMail;
use App\Models\Merchant;

class UserRepository implements UserService
{
    use MessageManager, HasFiles;

    public function __construct() {}

    public function createUser($request): User
    {
        try {

            $requestData = $request->validated();
            // $requestData = array_merge($requestData, [
            //     "city_id" => $request->city_id,
            //     "country_id" => $request->country_id,
            // ]);
            // dd($request->all());
            // dd($requestData);
            $requestData["country_id"] = Merchant::select('country_id')->find($request->merchant_id)->country_id;

            // dd($requestData);
            $profile_path = $this->uploadImageAndGetFileName($request, "profile_image", "users_profiles");

            $requestData["profile_image"] = $profile_path;
            // Always generate a secure random password for new users
            $plainPassword = $this->generateSecurePassword(14);
            $requestData['password'] = bcrypt($plainPassword);
            //    dd($requestData);
           if($request->has("merchant_id")){
            $requestData["merchant_id"] = $request->merchant_id;
           }

            $user =  User::create($requestData);

            if ($request->has("roles") &&  !is_null($request->roles) && count($request->roles) > 0) {
                // dd($request->roles);
                $roles = \Spatie\Permission\Models\Role::whereIn("id", $request->roles)->pluck("name");

                $user->assignRole($roles);
            }
            // Send credentials email to the newly created user
            if (!empty($user->email)) {
                Mail::to($user->email)->send(new NewUserCredentialsMail($user, $plainPassword));
            }

            // Log user creation activity
            $user->logActivity('created', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                "User created successfully", null, null, [
                    'email_sent' => !empty($user->email),
                    'roles_assigned' => $request->has("roles") && !is_null($request->roles) && count($request->roles) > 0
                ]);

            return $user;
        } catch (\Exception $e) {
            dd($e->getMessage());
            throw $e;
            // dd($e->getMessage());
        }
    }


    public function data(Request $request): JsonResponse
    {
        $query = User::with(["Roles", "merchant", "branch"])->withCountry();

        if ($request->has('search') && !is_array($request->search) && !is_null($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%");
            });
        }

        if ($request->has('status') && !is_null($request->status)) {

            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };

            $query->where('status', $status);
        }



        if ($request->has('merchant_id') && !is_null($request->merchant_id)) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->has('country_id') && !is_null($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('from_date') && $request->from_date && !is_null($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date && !is_null($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // dd($query->toSql(), $request->all());

        return DataTables::of($query)
            ->addColumn('record_select', 'users.data_table.record_select')
            ->addColumn("roles", fn($user) => view('users.data_table.roles', compact('user'))->render())
            ->addColumn('actions', 'users.data_table.actions')
            ->addColumn('user_info', fn($item) => view('users.data_table.user_info', compact('item'))->render())
            ->editColumn("status", fn($item) => $item->getStatusWithSpan())
            ->editColumn("profile_image", fn($item) => $item->getTableImage())
            ->editColumn("name", fn($item) => "<div> $item->name </div>")
            ->editColumn("merchant_id", fn($item) => $item->merchant ? $item->merchant->name : 'N/A')
            ->editColumn("branch_id", fn($item) => $item->branch ? $item->branch->name : 'N/A')
            ->editColumn("country", fn($item) => $item->country ? $item->country->name : 'N/A')
            ->rawColumns(['record_select', 'user_info', 'roles', "user_contact", 'actions', 'name', "profile_image", "status", "merchant_id", "branch_id"])
            ->toJson();
    }

    public function show(User $user): View
    {
        // Load all necessary relationships for the user show page
        $user->load([
            'merchant',
            'branch',

        ]);


        $type = null;
        if (!isset($tab))
            $tab = 'overview';
        return view('users.show', compact("user", "type", "tab"));
    }

    public function getUsersSections(User $user, string $type = 'overview')
    {
        $user->load([
            'merchant',
            'branch',
            'terminals'
        ]);

        $user->tenders_counts = 0;
        $user->logs_counts = 0;
        // dd('section');
        $data = [];
        if ($type == "logs") $data['logs'] = ["jksa"];
        return view('users.' . $type, compact('user', "type", "data"));
    }

    public function getProfile(int $id): User
    {
        return User::find($id)->Load("City:id,name", "Country:id,name", "Nationality:id,name", "Profile");
    }


    public function updateUser(UserRequest $request, User $user): bool
    {
        // dd($request);
        try {

            $requestData = $request->except("password");

            if ($request->profile_image) {
                $profile_path = $this->uploadImageAndGetFileName($request, "profile_image", "users_profiles");
            } else {
                $profile_path = $user->profile_image;
            }



            if ($profile_path) $requestData["profile_image"] = $profile_path;

            if ($request->password) $requestData['password'] = bcrypt($request->password);

            $user->update($requestData);
            
            // Log password change if password was updated
            if ($request->password) {
                $user->logActivity('password_changed', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                    "User password changed", null, null, [
                        'changed_by_admin' => Auth::guard('admin')->check()
                    ]);
            }

            // Log role changes
            if ($request->has("roles")) {
                $roles = \Spatie\Permission\Models\Role::whereIn("id", $request->roles)->pluck("name");
                $oldRoles = $user->roles->pluck('name')->toArray();
                
                $user->syncRoles($roles);
                
                // Log role changes if they actually changed
                if ($oldRoles !== $roles->toArray()) {
                    $user->logActivity('roles_updated', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                        "User roles updated", $oldRoles, $roles->toArray(), [
                            'old_roles' => $oldRoles,
                            'new_roles' => $roles->toArray()
                        ]);
                }
            } else {
                $oldRoles = $user->roles->pluck('name')->toArray();
                $user->syncRoles([]);
                
                // Log role removal if user had roles before
                if (!empty($oldRoles)) {
                    $user->logActivity('roles_removed', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                        "All user roles removed", $oldRoles, [], [
                            'removed_roles' => $oldRoles
                        ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            dd($e->$e->getMessage());
            return false;
            // dd($e->getMessage());
        }
    }

    public function deleteUser(User $user): bool
    {
        // Log user deletion activity before deleting
        $user->logActivity('deleted', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
            "User deleted", $user->toArray(), null, [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'deleted_by_admin' => Auth::guard('admin')->check()
            ]);

        return $user->delete();
    }


    public function changeStatus(User $user): RedirectResponse
    {

        try {
            $oldStatus = $user->status;
            $user->changeStatus();
            $newStatus = $user->status;
            
            // Log status change activity
            $user->logActivity('status_changed', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                "User status changed", ['status' => $oldStatus], ['status' => $newStatus], [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'status_label_old' => $oldStatus ? 'Active' : 'Inactive',
                    'status_label_new' => $newStatus ? 'Active' : 'Inactive'
                ]);

            $this->SuccessMessage(__("translation.User_status_has_been_changed"));

            return redirect()->back();
        } catch (\Exception $exception) {

            $this->ErrorMessage($exception->getMessage());
            return redirect()->back();
        }
    }

    public function CheckIfUserIsExistAndGenerateOtp(string $phone): UsersOtp|bool
    {
        //        $user = User::select("phone")->where("phone", $phone)->first();
        //
        //        if (!$user) {
        //            return false;
        //        }
        return $this->otpService->GenerateOtp($phone);
    }

    public function getUserDataWithOtp(VerifiedRequest $request): ?User
    {
        // If The User Use Testing Code
        if (\Illuminate\Support\Str::startsWith($request->code, '11111')) $identifierRecord = $request->identifier;
        else  $identifierRecord = $this->otpService->getPhoneNumber($request->code, $request->token);

        if (!$identifierRecord) throw new Exception("No Otp Found");

        return User::where("email", $identifierRecord)->orWhere("phone", $identifierRecord)->first();
    }

    public function mapFromJsonToUserData(array $userData): User|array
    {
        //        if ($userData["shop_logo"]) dd($userData);
        try {
            $user = new User();
            $userData["password"] = "as";
            $user->fill($userData);
            $user->last_name = $userData['lastname'];
            if (!$user->name) $user->name = '-';
            $user->type = strtolower($userData['expert']);
            $user->status = $userData['u_active'];
            $user->profile_image = $userData['profile_pic'];
            $user->password = bcrypt($userData['password']);
            $user->old_id = $userData['u_id'];

            $user->nationality = !$userData['nationality'] ? null : Cache::remember("country_{$userData['nationality']}", 3600, function () use ($userData) {
                return Country::where('code', $userData["nationality"])->first()->id ?? null;
            });

            $user->city_id = !$userData['shop_city'] ? null : Cache::remember("city_{$userData['shop_city']}", 3600, function () use ($userData) {
                return City::where('old_id', $userData["shop_city"])->first()->id ?? null;
            });

            $user->country_id = !$userData['shop_country'] ? null : Cache::remember("country_{$userData['shop_country']}", 3600, function () use ($userData) {
                return Country::where('code', $userData["shop_country"])->first()->id ?? null;
            });

            $user->save();

            return $user;
        } catch (\Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }


    public function mapUserLinksFromJson(array $userdata): array
    {

        $links = [];
        $socialPlatforms = ['facebook', 'linkedin', 'instagram', 'twitter', 'shop_website'];

        foreach ($socialPlatforms as $platform) {
            if (!empty($userdata[$platform]) && $userdata[$platform] != "") {
                $links[] = [
                    "link_type" => $platform == 'shop_website' ? 'website' : $platform,
                    "link_url" => $userdata[$platform]
                ];
            }
        }

        return $links;
    }

    public function getCompanyInCategories(array $preference)
    {
        return User::where('type', "company")
            ->whereHas('profile', function ($query) use ($preference) {
                $query->whereIn('category_id', $preference);
            })
            ->get();
    }

    public function GenerateOtpToNewUser(\Illuminate\Http\Request $request): UsersOtp
    {
        return $this->otpService->GenerateOtp($request->phone);
    }

    public function VerifyTheOtp(\Illuminate\Http\Request $request): string|null
    {
        // If The User Use Testing Code
        if ($request->code == "111111") $phone = $request->phone;
        else  $phone = $this->otpService->getPhoneNumber($request->code, $request->token);

        if (!$phone) return null;

        return $phone;
    }

    public function resetUserToNormalType($user_id): bool
    {
        return User::where("id", $user_id)->update(["type" => "user"]);
    }

    public function updateUserProfileImage(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $profile_image = $this->uploadImageAndGetFileName($request, "profile_image", "users_profiles");
        if ($profile_image) {
            $user->profile_image = $profile_image;
            $user->save();
        }
        return true;
    }

    public function updateProfile(Request $request, User $user): bool
    {
        try {
            $data = $request->only(['name', 'email', 'phone']);

            $profileImage = $this->uploadImageAndGetFileName($request, 'profile_image', 'users_profiles');
            if ($profileImage) {
                $data['profile_image'] = $profileImage;
            }

            $user->update($data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        try {
            $user->update([
                'password' => bcrypt($newPassword)
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }



    /**
     * Get all users for API
     */
    public function getAllUsers(Request $request)
    {
        $query = User::with(['roles', 'merchant', 'branch']);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $status = $request->status === 'active' ? 1 : 0;
            $query->where('status', $status);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get users for select dropdown
     */
    public function getUserInSelect(Request $request)
    {
        $query = User::query()
            ->with(['merchant:id,name', 'branch:id,name'])
            ->select('id', 'name', 'email', 'phone', 'merchant_id', 'branch_id');

        // Filter by merchant if provided (common in admin screens)
        if ($request->has('merchant_id') && !empty($request->merchant_id)) {
            $query->where('merchant_id', $request->merchant_id);
        }

        // Filter by branch if provided
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Only active users by default or when requested
        if (!$request->has('status') || $request->status === 'active') {
            $query->where('status', 1);
        } elseif ($request->status === 'inactive') {
            $query->where('status', 0);
        }

        // Search functionality for select2
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }

        $users = $query->orderBy('name')->limit(50)->get();

        // Map to detailed select items with merchant/branch names
        return $users->map(function ($u) {
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
    }

    /**
     * Assign terminals to user
     */
    public function assignTerminals(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'terminals' => 'required|array',
                'terminals.*' => 'exists:terminals,id'
            ]);

            $user = User::findOrFail($request->user_id);

            // Get old terminals for logging
            $oldTerminals = $user->terminals->pluck('id')->toArray();
            
            // Sync terminals
            $user->terminals()->sync($request->terminals);

            // Log terminal assignment activity
            $user->logActivity('terminals_updated', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                "User terminals updated", $oldTerminals, $request->terminals, [
                    'old_terminals' => $oldTerminals,
                    'new_terminals' => $request->terminals,
                    'terminals_added' => array_diff($request->terminals, $oldTerminals),
                    'terminals_removed' => array_diff($oldTerminals, $request->terminals)
                ]);

            return ['message' => 'Terminals assigned successfully'];
        } catch (\Exception $e) {
            throw new \Exception('Failed to assign terminals: ' . $e->getMessage());
        }
    }

    /**
     * Remove terminal from user
     */
    public function removeTerminal(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'terminal_id' => 'required|exists:terminals,id'
            ]);

            $user = User::findOrFail($request->user_id);

            // Get terminal info for logging
            $terminal = \App\Models\Terminal::find($request->terminal_id);
            
            // Detach terminal
            $user->terminals()->detach($request->terminal_id);

            // Log terminal removal activity
            $user->logActivity('terminal_removed', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                "Terminal removed from user", [$request->terminal_id], null, [
                    'terminal_id' => $request->terminal_id,
                    'terminal_name' => $terminal ? $terminal->name : 'Unknown Terminal',
                    'removed_by_admin' => Auth::guard('admin')->check()
                ]);

            return ['message' => 'Terminal removed successfully'];
        } catch (\Exception $e) {
            throw new \Exception('Failed to remove terminal: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete users
     */
    public function bulkDelete(array $ids)
    {
        try {
            $users = User::whereIn('id', $ids)->get();

            foreach ($users as $user) {
                // Log user deletion activity before deleting
                $user->logActivity('deleted', Auth::guard('admin')->check() ? Auth::guard('admin')->user() : Auth::user(), 
                    "User deleted in bulk operation", $user->toArray(), null, [
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'bulk_delete' => true,
                        'deleted_by_admin' => Auth::guard('admin')->check()
                    ]);

                // Delete profile image if exists
                if ($user->profile_image) {
                    // Delete the image file using Storage
                    Storage::delete($user->profile_image);
                }

                // Delete the user
                $user->delete();
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete users: ' . $e->getMessage());
        }
    }

    public function getCityFromGeoLocation(UserRequest $request)
    {
        if ($request->has("location")) {
            [$latitude, $longitude] = explode(",", $request->get("location"));
            $response = Http::withHeaders([
                'User-Agent' => 'Mawj/1.0 (jksa.work.1@gmail.com)'
            ])->get("https://nominatim.openstreetmap.org/reverse", [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'accept-language' => 'en', // Ensure English response
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $cityName = $data["address"]["county"] ?? $data['address']['city'] ?? $data['address']['town'] ?? $data['address']['village'] ?? null;
                return City::where("name->en", 'like', "%{$cityName}%")->first();
            }
        }
        return null;
    }

    public function showWithTab(User $user, string $tab = 'overview'): View
    {
        // Load necessary relationships based on tab
        switch ($tab) {
            case 'transactions':
                // $user->load(['terminals.transactions' => function ($query) {
                //     $query->latest()->limit(100);
                // }]);
                break;
            case 'terminals':
                $user->load(['terminals', 'terminalGroups', 'currentTerminal']);
                break;
            case 'user_groups':
                $user->load(['userGroups']);
                break;
            case 'attachments':
                $user->load(['attachments']);
                break;
            case 'events':
                // For events tab, we don't need to load logs here as DataTables will handle it via AJAX
                break;
            default:
                $user->load(['merchant', 'branch', 'terminals', 'userGroups' , 'LatestLogs']);
                break;
        }

        return view('users.show', compact('user', 'tab'));
    }

    /**
     * Export user template for import
     */
    public function exportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_import_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add headers (password auto-generated, merchant_id selected via dropdown)
            fputcsv($file, [
                'Name',
                'Email',
                'Phone',
                'Status'
            ]);

            // Add sample data rows with examples
            fputcsv($file, [
                'John Doe',
                'john.doe@example.com',
                '+1234567890',
                'active'
            ]);

            fputcsv($file, [
                'Jane Smith',
                'jane.smith@example.com',
                '+1234567891',
                'active'
            ]);

            fputcsv($file, [
                'Bob Johnson',
                'bob.johnson@example.com',
                '+1234567892',
                'inactive'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export users data based on filters
     */
    public function export(Request $request)
    {
        $query = User::with(['roles', 'merchant', 'branch', 'city', 'country']);

        // Apply filters
        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%");
            });
        }

        if ($request->has('status') && $request->status) {
            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };
            $query->where('status', $status);
        }

        if ($request->has('merchant') && $request->merchant) {
            $query->where('merchant_id', $request->merchant);
        }

        if ($request->has('branch') && $request->branch) {
            $query->where('branch_id', $request->branch);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $users = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Status',
                'Merchant',
                'Branch',
                'Roles',
                'City',
                'Country',
                'Created At',
                'Updated At'
            ]);

            // Add data rows
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->status ? 'Active' : 'Inactive',
                    $user->merchant ? $user->merchant->name : 'N/A',
                    $user->branch ? $user->branch->name : 'N/A',
                    $user->roles ? $user->roles->pluck('name')->implode(', ') : 'N/A',
                    $user->city ? $user->city->name : 'N/A',
                    $user->country ? $user->country->name : 'N/A',
                    $user->created_at ? $user->created_at->format('Y-m-d H:i:s') : 'N/A',
                    $user->updated_at ? $user->updated_at->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview users import data
     */
    public function importPreview($file, $merchantId)
    {
        try {
            $extension = $file->getClientOriginalExtension();

            if ($extension === 'csv') {
                $data = $this->parseCsvFile($file);
            } else {
                $data = $this->parseExcelFile($file);
            }

            // Skip header row
            $headers = array_shift($data);
            
            $previewData = [];
            $errors = [];

            foreach ($data as $rowIndex => $row) {
                $userData = $this->mapCsvRowToUserData($row, $headers);
                $rowNum = $rowIndex + 2;
                
                // Validate the row
                $validation = $this->validateUserRow($userData, $merchantId, $rowNum);
                
                $previewData[] = [
                    'name' => $userData['name'] ?? '',
                    'email' => $userData['email'] ?? '',
                    'phone' => $userData['phone'] ?? '',
                    'password' => 'Auto-generated', // Password will be auto-generated
                    'is_active' => isset($userData['status']) && strtolower($userData['status']) === 'active',
                    'is_valid' => $validation['is_valid'],
                    'errors' => $validation['errors']
                ];
                
                if (!$validation['is_valid']) {
                    $errors[] = "Row {$rowNum}: " . $validation['errors'];
                }
            }

            return [
                'data' => $previewData,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to preview file: ' . $e->getMessage());
        }
    }

    /**
     * Validate user row data
     */
    private function validateUserRow($userData, $merchantId, $rowNum)
    {
        $errors = [];
        
        // Check required fields
        if (empty($userData['name'])) {
            $errors[] = 'Missing name';
        }
        
        if (empty($userData['email'])) {
            $errors[] = 'Missing email';
        } elseif (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (User::where('email', $userData['email'])->exists()) {
            $errors[] = 'Duplicate email';
        }
        
        if (empty($userData['phone'])) {
            $errors[] = 'Missing phone';
        } elseif (User::where('phone', $userData['phone'])->exists()) {
            $errors[] = 'Duplicate phone number';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => implode(', ', $errors)
        ];
    }
    
    /**
     * Generate random password
     */
    private function generateRandomPassword($length = 12)
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }

    /**
     * Import users from CSV/Excel file
     */
    public function import($file, $merchantId)
    {
        try {
            $extension = $file->getClientOriginalExtension();

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            if ($extension === 'csv') {
                $data = $this->parseCsvFile($file);
            } else {
                $data = $this->parseExcelFile($file);
            }

            // Skip header row
            $headers = array_shift($data);
            
            // Get merchant's country_id
            $merchant = \App\Models\Merchant::find($merchantId);
            if (!$merchant) {
                throw new \Exception('Merchant not found');
            }

            foreach ($data as $rowIndex => $row) {
                try {
                    $userData = $this->mapCsvRowToUserData($row, $headers);
                    $rowNum = $rowIndex + 2;
                    
                    // Validate the row
                    $validation = $this->validateUserRow($userData, $merchantId, $rowNum);
                    
                    if (!$validation['is_valid']) {
                        $skippedCount++;
                        $errors[] = "Row {$rowNum}: " . $validation['errors'];
                        continue;
                    }

                    if ($userData) {
                        // Generate random password
                        $plainPassword = $this->generateRandomPassword();
                        
                        $userData['merchant_id'] = $merchantId;
                        $userData['country_id'] = $merchant->country_id;
                        $userData['password'] = $plainPassword;
                        
                        $user = $this->createUserFromImport($userData, $merchant, $plainPassword);
                        $importedCount++;
                    }
                } catch (\Exception $e) {
                    $skippedCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            $message = "Import completed. {$importedCount} users imported successfully";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} skipped";
            }

            return [
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors
            ];
        } catch (\Exception $e) {
            throw new \Exception('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Parse CSV file
     */
    private function parseCsvFile($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }

        fclose($handle);
        return $data;
    }

    /**
     * Parse Excel file
     */
    private function parseExcelFile($file)
    {
        // For now, we'll use a simple approach
        // In production, you might want to use a library like PhpSpreadsheet
        $data = [];
        $handle = fopen($file->getPathname(), 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }

        fclose($handle);
        return $data;
    }

    /**
     * Map CSV row to user data
     */
    private function mapCsvRowToUserData($row, $headers)
    {
        $userData = [];

        foreach ($headers as $index => $header) {
            if (isset($row[$index])) {
                $userData[strtolower(trim($header))] = trim($row[$index]);
            }
        }

        return $userData;
    }

    /**
     * Create user from imported data
     */
    private function createUserFromImport($userData, $merchant, $plainPassword)
    {
        // Validate required fields
        $requiredFields = ['name', 'email', 'phone'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Check if user already exists
        if (User::where('email', $userData['email'])->exists()) {
            throw new \Exception("User with email {$userData['email']} already exists");
        }

        // Prepare user data
        $userData['password'] = bcrypt($plainPassword);
        $userData['status'] = isset($userData['status']) && strtolower($userData['status']) === 'active' ? 1 : 0;

        // Create user
        $user = User::create($userData);

        // Send welcome email with credentials
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(
                new \App\Mail\WelcomeMail($user, $plainPassword, $merchant)
            );
        } catch (\Exception $e) {
            // Log email error but don't fail the import
            \Illuminate\Support\Facades\Log::warning("Failed to send welcome email to {$user->email}: " . $e->getMessage());
        }

        return $user;
    }

    private function generateSecurePassword(int $length = 14): string
    {
        $length = max(8, $length);

        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $symbols = '!@#$%&*()-_=+?';
        $allChars = $lower . $upper . $digits . $symbols;

        $passwordChars = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($passwordChars); $i < $length; $i++) {
            $passwordChars[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        for ($i = count($passwordChars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$passwordChars[$i], $passwordChars[$j]] = [$passwordChars[$j], $passwordChars[$i]];
        }

        return implode('', $passwordChars);
    }
}
