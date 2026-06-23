<?php

namespace App\Services;

use App\Mail\MerchantApprovalMail;
use App\Mail\MerchantRejectionMail;
use App\Mail\WelcomeMail;
use App\Models\Partner as ContentProvider;
use App\Models\PartnerRejection as ContentProviderRejection;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PartnerRepository as ContentProviderRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Http\Resources\ContentProviderListResource;
use App\Http\Resources\ServiceResource;

/**
 * ContentProviderService
 * 
 * Main service class for managing content providers (formerly merchants)
 */
class ContentProviderService 
{
    protected $merchantRepository;

    public function __construct(ContentProviderRepository $contentProviderRepository)
    {
        $this->merchantRepository = $contentProviderRepository;
    }

    /**
     * Get paginated content providers list with filters
     */
    public function index(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'country_id' => $request->input('country_id'),
            'partner_category_id' => $request->input('partner_category_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'parent_id' => $request->input('parent_id'),
            'sub_partners_only' => $request->boolean('sub_partners_only'),
        ];

        $perPage = (int) $request->input('per_page', 15);

        $paginator = $this->merchantRepository->getPaginated($filters, $perPage);
        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn ($partner) => (new ContentProviderListResource($partner))->toArray($request)
            )
        );

        return $paginator;
    }

    /**
     * Override validateStore to use content_providers table
     */
    protected function validateStore(Request $request): array
    {
        $businessTypeRule = 'nullable|string|max:255';
        if (class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')) {
            $businessTypeRule = 'nullable|in:' . implode(',', array_keys(\App\Enums\BusinessType::toArray()));
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:partners,email',
                'unique:users,email',
            ],
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'business_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'business_type' => $businessTypeRule,
            'is_active' => 'sometimes|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'sometimes|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
            'status' => 'sometimes|in:' . implode(',', ContentProvider::STATUS),
            'country_id' => 'nullable|uuid',
            'partner_category_id' => [
                'required',
                'uuid',
                Rule::exists('service_categories', 'id')->where('type', ServiceCategory::TYPE_PARTNER),
            ],
            'is_parent' => 'sometimes|boolean',
        ]);
    }

    /**
     * Override validateUpdate to use content_providers table
     */
    protected function validateUpdate(Request $request, $contentProvider): array
    {
        $businessTypeRule = 'sometimes|nullable|string|max:255';
        if (class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')) {
            $businessTypeRule = 'sometimes|nullable|in:' . implode(',', array_keys(\App\Enums\BusinessType::toArray()));
        }

        return $request->validate([
            'name' => 'sometimes|string|max:255',
            'owner_name' => 'sometimes|nullable|string|max:255',
            'business_name' => 'sometimes|nullable|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('partners', 'email')->ignore($contentProvider->id),
                Rule::unique('users', 'email')->ignore($contentProvider->user_id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($contentProvider->user_id),
            ],
            'business_phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string',
            'business_type' => $businessTypeRule,
            'is_active' => 'sometimes|boolean',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'sometimes|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
            'status' => 'sometimes|in:' . implode(',', ContentProvider::STATUS),
            'country_id' => 'sometimes|nullable',
            'partner_category_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('service_categories', 'id')->where('type', ServiceCategory::TYPE_PARTNER),
            ],
            'is_parent' => 'sometimes|boolean',
        ]);
    }

    /**
     * Validation for creating a sub-partner (country & category are taken from the parent server-side).
     */
    protected function validateSubPartnerStore(Request $request): array
    {
        $businessTypeRule = 'nullable|string|max:255';
        if (class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')) {
            $businessTypeRule = 'nullable|in:' . implode(',', array_keys(\App\Enums\BusinessType::toArray()));
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:partners,email',
                'unique:users,email',
            ],
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'business_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'business_type' => $businessTypeRule,
            'is_active' => 'sometimes|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'sometimes|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
            'status' => 'sometimes|in:' . implode(',', ContentProvider::STATUS),
        ]);
    }

    /**
     * Create a sub-partner under a root parent (inherits country & partner category from parent).
     */
    public function storeSubPartner(Request $request, string $parentId): ContentProvider
    {
        $parent = ContentProvider::query()->whereNull('parent_id')->findOrFail($parentId);

        if (! $parent->is_parent) {
            throw ValidationException::withMessages([
                'parent_id' => ['This partner is not enabled to have sub-partners. Turn on "Parent organization" on the partner profile first.'],
            ]);
        }

        $data = $this->validateSubPartnerStore($request);
        $data['country_id'] = $parent->country_id;
        $data['partner_category_id'] = $parent->partner_category_id;
        $data['parent_id'] = $parent->id;
        $data['is_parent'] = false;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            $logoPath = $this->storeLogoIfPresent($data['logo'] ?? null);
            if ($logoPath) {
                $data['logo'] = $logoPath;
            } else {
                unset($data['logo']);
            }

            unset($data['tax_certified_number'], $data['tax_number'], $data['city_id'],
                $data['trade_license_number'], $data['trade_license_start_date'], $data['trade_license_expired_date'],
                $data['scopes'], $data['plan_id']);

            $data['merchant_code'] = ContentProvider::generateMerchantCode();
            $data['status'] = $data['status'] ?? 'pending';
            $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

            $contentProvider = $this->merchantRepository->create($data);

            $this->uploadAdditionalImagesForContentProvider($request, $contentProvider);

            [$user, $plainPassword] = $this->createContentProviderUser($contentProvider, $data);

            if ($user) {
                $contentProvider->update(['user_id' => $user->id]);
            }

            $freshContentProvider = $contentProvider->fresh(['user', 'country', 'city', 'partnerCategory', 'parentPartner']);

            if ($user && $plainPassword) {
                $this->sendWelcomeEmailForContentProvider($user, $plainPassword, $freshContentProvider);
            }

            return $freshContentProvider;
        });
    }

    /**
     * Override store to use ContentProvider model
     * @return ContentProvider
     */
    public function store(Request $request)
    {
        $data = $this->validateStore($request);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            $logoPath = $this->storeLogoIfPresent($data['logo'] ?? null);
            if ($logoPath) {
                $data['logo'] = $logoPath;
            } else {
                unset($data['logo']);
            }

            // Remove fields that don't exist in the table
            unset($data['tax_certified_number'], $data['tax_number'], $data['city_id'], 
                  $data['trade_license_number'], $data['trade_license_start_date'], $data['trade_license_expired_date'],
                  $data['scopes'], $data['plan_id']);

            $data['parent_id'] = null;
            $data['is_parent'] = array_key_exists('is_parent', $data) ? (bool) $data['is_parent'] : false;

            $data['merchant_code'] = ContentProvider::generateMerchantCode();
            $data['status'] = $data['status'] ?? 'pending';
            $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

            $contentProvider = $this->merchantRepository->create($data);

            $this->uploadAdditionalImagesForContentProvider($request, $contentProvider);

            [$user, $plainPassword] = $this->createContentProviderUser($contentProvider, $data);

            if ($user) {
                $contentProvider->update(['user_id' => $user->id]);
                // $this->assignContentProviderRole($contentProvider, $user);
            }

            // $this->logContentProviderAction($contentProvider, 'created', null, $contentProvider->getAttributes(), [
            //     'type' => 'creation',
            //     'event' => 'Admin created new content provider',
            //     'message' => 'New content provider profile created by Admin',
            // ]);

            $freshContentProvider = $contentProvider->fresh(['user', 'country', 'city', 'partnerCategory']);

            if ($user && $plainPassword) {
                $this->sendWelcomeEmailForContentProvider($user, $plainPassword, $freshContentProvider);
            }

            return $freshContentProvider;
        });
    }

    /**
     * Override show to use ContentProvider model
     */
    public function show(string $id): array
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);

        $contentProvider->load([
            'user',
            'attachments',
            'partnerCategory',
            'parentPartner',
        ]);

        if (! $contentProvider->parent_id) {
            $contentProvider->loadCount('subPartners');
        }

        // dd($contentProvider);
        // $hasUsersPartnerIdColumn = Schema::hasTable('users') && Schema::hasColumn('users', 'partner_id');

        $statistics = [
            'total_users' => 0 , //$hasUsersPartnerIdColumn ? $contentProvider->users()->count() : 0,
            'total_branches' =>  0 , // Schema::hasTable('branches') ? $contentProvider->branches()->count() : 0,
            'total_terminals' => 0 , // Schema::hasTable('terminals') ? $contentProvider->terminals()->count() : 0,
        ];

        $pendingChangeRequests = 0;
     
        // dd($pendingChangeRequests);
        return [
            'merchant' => $contentProvider, // Keep 'merchant' key for frontend compatibility
            'partner' => $contentProvider,
            'contentProvider' => $contentProvider,
            'statistics' => $statistics,
        ];
    }

    /**
     * Override update to use ContentProvider model
     * @return ContentProvider
     */
    public function update(Request $request, string $id)
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);
        $data = $this->validateUpdate($request, $contentProvider);
        $oldValues = $contentProvider->getAttributes();

        if (array_key_exists('logo', $data)) {
            $newLogoPath = $this->storeLogoIfPresent($data['logo']);
            if ($newLogoPath) {
                $this->deleteStoredFile($contentProvider->logo);
                $data['logo'] = $newLogoPath;
            } else {
                unset($data['logo']);
            }
        }

        // Remove fields that don't exist in the table
        unset($data['tax_certified_number'], $data['tax_number'], $data['city_id'], 
              $data['trade_license_number'], $data['trade_license_start_date'], $data['trade_license_expired_date'],
              $data['scopes'], $data['plan_id']);

        if ($contentProvider->parent_id) {
            unset($data['country_id'], $data['partner_category_id'], $data['parent_id'], $data['is_parent']);
        } elseif (array_key_exists('is_parent', $data)) {
            $data['is_parent'] = (bool) $data['is_parent'];
        }

        $updatedContentProvider = $this->merchantRepository->update($contentProvider, $data);

        $this->uploadAdditionalImagesForContentProvider($request, $contentProvider);

        $this->logContentProviderAction($contentProvider, 'updated', $oldValues, $updatedContentProvider->getAttributes(), [
            'type' => 'update',
            'event' => 'Admin updated content provider information',
            'message' => 'Content provider information updated by Admin',
        ]);

        return $updatedContentProvider->load(['user', 'country', 'partnerCategory']);
    }

    /**
     * Override statistics to use ContentProvider model
     */
    public function statistics(): array
    {
        return [
            'total_merchants' => ContentProvider::count(),
            'active_merchants' => ContentProvider::where('is_active', true)->count(),
            'inactive_merchants' => ContentProvider::where('is_active', false)->count(),
            'pending_merchants' => ContentProvider::where('status', 'pending')->count(),
            'approved_merchants' => ContentProvider::where('status', 'approved')->count(),
            'rejected_merchants' => ContentProvider::where('status', 'rejected')->count(),
            'suspended_merchants' => ContentProvider::where('status', 'suspended')->count(),
            'merchants_this_month' => ContentProvider::whereMonth('created_at', now()->month)->count(),
            'merchants_today' => ContentProvider::whereDate('created_at', now())->count(),
        ];
    }

    /**
     * Override export to use ContentProvider model
     */
    public function export(Request $request): array
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'country_id' => $request->input('country_id'),
            'partner_category_id' => $request->input('partner_category_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'parent_id' => $request->input('parent_id'),
            'sub_partners_only' => $request->boolean('sub_partners_only'),
        ];

        $query = ContentProvider::query()->with('partnerCategory');

        if (! empty($filters['sub_partners_only'])) {
            $query->whereNotNull('parent_id');
            if (! empty($filters['parent_id'])) {
                $query->where('parent_id', $filters['parent_id']);
            }
        } elseif (! empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        } else {
            $query->whereNull('parent_id');
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('merchant_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['partner_category_id'])) {
            $query->where('partner_category_id', $filters['partner_category_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        $contentProviders = $query->get();

        $exportData = $contentProviders->map(function (ContentProvider $contentProvider) {
            return [
                'ID' => $contentProvider->id,
                'Business Name' => $contentProvider->name,
                'Owner Name' => $contentProvider->owner_name,
                'Email' => $contentProvider->email,
                'Phone' => $contentProvider->phone,
                'Partner Category' => $contentProvider->partnerCategory?->name_en ?? 'N/A',
                'Country' => 'N/A',
                'City' => 'N/A',
                'Status' => $contentProvider->status,
                'Is Active' => $contentProvider->is_active ? 'Yes' : 'No',
                'Created At' => optional($contentProvider->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'data' => $exportData,
            'filename' => 'partners_export_' . date('Y-m-d_H-i-s') . '.csv',
        ];
    }

    /**
     * Override lookupMerchantCountryInfo to use ContentProvider model
     */
    public function lookupMerchantCountryInfo(array $shopIds): array
    {
        $normalizedIds = collect($shopIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(function ($id) {
                if (is_string($id)) {
                    return trim($id);
                }

                if (is_numeric($id)) {
                    return (string) (int) $id;
                }

                return (string) $id;
            })
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return [];
        }

        $contentProviders = ContentProvider::query()
            ->with(['country'])
            ->select([
                'partners.id',
                'partners.name',
                'partners.country_id',
            ])
            ->whereIn('partners.id', $normalizedIds->all())
            ->get();

        $results = [];

        foreach ($contentProviders as $contentProvider) {
            $contentProviderId = (string) $contentProvider->id;
            $contentProviderName = $this->normalizeLocaleValue($contentProvider->name)
                ?: 'Partner #' . $contentProviderId;

            $country = $contentProvider->country;
            $countryName = $country ? $this->normalizeLocaleValue($country->name) : '';
            $countryCode = $country ? (string) ($country->code ?: ($country->short_name ?? '')) : '';

            $results[$contentProviderId] = [
                'id' => $contentProviderId,
                'shop_id' => $contentProviderId,
                'name' => $contentProviderName,
                'country_id' => $contentProvider->country_id,
                'country_name' => $countryName,
                'countryName' => $countryName,
                'country_code' => $countryCode,
            ];
        }

        foreach ($normalizedIds as $id) {
            $key = (string) $id;
            if (!array_key_exists($key, $results)) {
                $results[$key] = [
                    'id' => $key,
                    'shop_id' => $key,
                    'name' => '',
                    'country_id' => null,
                    'country_name' => '',
                    'countryName' => '',
                    'country_code' => '',
                ];
            }
        }

        return $results;
    }

    /**
     * Override createMerchantUser to use content_provider_id instead of merchant_id
     */
    protected function createContentProviderUser(ContentProvider $contentProvider, array $data): array
    {
        if (empty($data['email'])) {
            return [null, null];
        }

        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            if (!$existingUser->partner_id) {
                $existingUser->update(['partner_id' => $contentProvider->id]);
            }

            return [$existingUser, null];
        }

        $plainPassword = \Illuminate\Support\Str::random(10);

        $user = User::create([
            'name' => $data['owner_name'] ?? $data['name'],
            'email' => $data['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($plainPassword),
            'partner_id' => $contentProvider->id,
            'phone' => $data['phone'] ?? null,
        ]);

        return [$user, $plainPassword];
    }

    /**
     * Upload additional attachments for content provider
     */
    protected function uploadAdditionalImagesForContentProvider(Request $request, ContentProvider $contentProvider): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images') as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $storedPath = $file->store('partners/images', 'public');
            if (!$storedPath) {
                continue;
            }

            $relativePath = 'storage/' . $storedPath;
            $contentProvider->attachments()->create([
                'url' => $relativePath,
                'type' => $this->checkFileType($relativePath),
            ]);
        }
    }

    /**
     * Assign content provider role and permissions
     */
    protected function assignContentProviderRole(ContentProvider $contentProvider, User $user): void
    {
        if (!class_exists(Role::class) || !class_exists(\Spatie\Permission\Models\Permission::class)) {
            return;
        }

        $roleName = trim('content-provider ' . $contentProvider->name);

        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['merchant_id' => $contentProvider->id] // Keep for backward compatibility if needed
        );

        $role->merchant_id = $contentProvider->id; // Keep for backward compatibility
        $role->save();

        $webPermissions = config('permission.merchant_permissions', []);
        $permissionNames = [];
        
        foreach ($webPermissions as $group => $categories) {
            if (is_array($categories)) {
                $prefix = str_replace('_permissions', '', $group);
                
                foreach ($categories as $category => $permissions) {
                    if (is_array($permissions)) {
                        foreach ($permissions as $permName) {
                            $permissionNames[] = "{$prefix}.{$category}.{$permName}";
                        }
                    }
                }
            }
        }

        $permissionNames = array_unique(array_filter($permissionNames, 'is_string'));

        if (!empty($permissionNames)) {
            $permissions = \Spatie\Permission\Models\Permission::whereIn('name', $permissionNames)
                ->where('guard_name', 'web')
                ->get();

            if ($permissions->isNotEmpty()) {
                $role->syncPermissions($permissions);
            }
        }

        $user->syncRoles([$role]);
    }

    /**
     * Log content provider activity
     */
    protected function logContentProviderAction(ContentProvider $contentProvider, string $action, $oldValues = null, $newValues = null, array $metadata = []): void
    {
        $actor = \Illuminate\Support\Facades\Auth::guard('admin')->user() ?? \Illuminate\Support\Facades\Auth::user();

        $metadata = array_merge([
            'timestamp' => now(),
            'performed_by' => $actor?->name ?? 'System',
        ], $metadata);

        $contentProvider->logs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'user_id' => $actor?->id,
            'user_type' => $actor ? get_class($actor) : null,
        ]);
    }

    /**
     * Send welcome email for content provider
     */
    protected function sendWelcomeEmailForContentProvider(?User $user, ?string $password, ContentProvider $contentProvider): void
    {
        if (!$user || !$password || !class_exists(WelcomeMail::class)) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new WelcomeMail($user, $password, $contentProvider));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send welcome email: ' . $e->getMessage());
        }
    }

    /**
     * Override destroy to use ContentProvider model
     */
    public function destroy(string $id): bool
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);

        if ($contentProvider->subPartners()->exists()) {
            throw new \RuntimeException('Cannot delete a partner that has sub-partners. Delete or reassign sub-partners first.');
        }

        $contentProviderData = $contentProvider->getAttributes();

        $this->deleteStoredFile($contentProvider->logo);

        $this->logContentProviderAction($contentProvider, 'deleted', $contentProviderData, null, [
            'type' => 'deletion',
            'event' => 'Admin deleted content provider',
            'message' => 'Content provider was deleted by Admin',
        ]);

        return (bool) $this->merchantRepository->delete($contentProvider);
    }

    /**
     * Override bulkDelete to use ContentProvider model
     */
    public function bulkDelete(array $ids): array
    {
        $deleted = $this->merchantRepository->bulkDelete($ids);

        return [
            'message' => "{$deleted} content provider(s) deleted successfully",
            'count' => $deleted,
        ];
    }

    /**
     * Override approve to use ContentProvider model
     */
    public function approve(string $id): array
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($contentProvider) {
            $this->merchantRepository->update($contentProvider, [
                'status' => 'approved',
                'is_active' => true,
            ]);

            $fresh = $contentProvider->fresh();

            $this->logContentProviderAction($fresh, 'approved', null, $fresh->getAttributes(), [
                'type' => 'approval',
                'event' => 'Admin approved content provider profile',
                'message' => 'Content provider profile approved by Admin',
            ]);

            $this->sendApprovalEmailForContentProvider($fresh);

            return [
                'message' => 'Content provider approved successfully',
                'merchant' => $fresh, // Keep 'merchant' key for frontend compatibility
                'partner' => $fresh,
                'contentProvider' => $fresh,
            ];
        });
    }

    /**
     * Override reject to use ContentProvider model
     */
    public function reject(string $id, string $rejectionReason, array $invalidFields = []): array
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($contentProvider, $rejectionReason, $invalidFields) {
            $this->merchantRepository->update($contentProvider, ['status' => 'rejected']);

            if (class_exists(ContentProviderRejection::class)) {
                $rejectedById = $this->resolveRejectionActorId();

                ContentProviderRejection::create([
                    'partner_id' => $contentProvider->id,
                    'rejection_reason' => $rejectionReason,
                    'invalid_fields' => $invalidFields,
                    'missing_attachments' => null,
                    'rejected_by' => $rejectedById,
                ]);
            }

            $fresh = $contentProvider->fresh();

            $this->logContentProviderAction($fresh, 'rejected', null, $fresh->getAttributes(), [
                'type' => 'rejection',
                'reason' => $rejectionReason,
                'event' => 'Admin rejected content provider profile',
                'message' => 'Content provider profile rejected by Admin: ' . $rejectionReason,
            ]);

            $this->sendRejectionEmailForContentProvider($fresh, $rejectionReason);

            return [
                'message' => 'Content provider rejected successfully',
                'merchant' => $fresh, // Keep 'merchant' key for frontend compatibility
                'partner' => $fresh,
                'contentProvider' => $fresh,
            ];
        });
    }

    /**
     * Override suspend to use ContentProvider model
     */
    public function suspend(string $id, string $suspensionReason): array
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($contentProvider, $suspensionReason) {
            $oldStatus = $contentProvider->status;

            $this->merchantRepository->update($contentProvider, [
                'status' => 'suspended',
                'is_active' => false,
            ]);

            $fresh = $contentProvider->fresh();

            $this->logContentProviderAction($fresh, 'suspended', ['status' => $oldStatus], ['status' => 'suspended'], [
                'type' => 'suspension',
                'reason' => $suspensionReason,
                'event' => 'Admin suspended content provider',
                'message' => 'Content provider suspended by Admin: ' . $suspensionReason,
            ]);

            return [
                'message' => 'Content provider suspended successfully',
                'merchant' => $fresh, // Keep 'merchant' key for frontend compatibility
                'partner' => $fresh,
                'contentProvider' => $fresh,
            ];
        });
    }

    /**
     * Override unsuspend to use ContentProvider model
     */
    public function unsuspend(string $id): array
    {
        $contentProvider = $this->merchantRepository->findOrFail($id);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($contentProvider) {
            $oldStatus = $contentProvider->status;

            $this->merchantRepository->update($contentProvider, [
                'status' => 'approved',
                'is_active' => true,
            ]);

            $fresh = $contentProvider->fresh();

            $this->logContentProviderAction($fresh, 'activated', ['status' => $oldStatus], ['status' => 'approved'], [
                'type' => 'activation',
                'event' => 'Admin activated suspended content provider',
                'message' => 'Content provider activated by Admin after suspension',
            ]);

            return [
                'message' => 'Content provider unsuspended successfully',
                'merchant' => $fresh, // Keep 'merchant' key for frontend compatibility
                'partner' => $fresh,
                'contentProvider' => $fresh,
            ];
        });
    }

    /**
     * Override getAllMerchants to use ContentProvider model
     */
    public function getAllMerchants(Request $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = ContentProvider::with('user');

        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('merchant_code', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status);
        }

        if ($request->has('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        $perPage = $request->get('per_page', 15);

        return $query->paginate($perPage);
    }

    /**
     * Override getMerchantsForSelect to use ContentProvider model
     */
    public function getMerchantsForSelect(Request $request): \Illuminate\Support\Collection
    {
        $query = ContentProvider::select('id', 'name', 'merchant_code');

        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('merchant_code', 'like', "%{$searchValue}%");
            });
        }

        $query->where('is_active', 1);

        return $query->get();
    }

    /**
     * Send approval email for content provider
     */
    protected function sendApprovalEmailForContentProvider(ContentProvider $contentProvider): void
    {
        if (!class_exists(MerchantApprovalMail::class) || !$contentProvider->user) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($contentProvider->email)->send(new MerchantApprovalMail($contentProvider->user, $contentProvider->user->password, $contentProvider));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send approval email: ' . $e->getMessage());
        }
    }

    /**
     * Send rejection email for content provider
     */
    protected function sendRejectionEmailForContentProvider(ContentProvider $contentProvider, string $reason): void
    {
        if (!class_exists(MerchantRejectionMail::class)) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($contentProvider->email)->send(new MerchantRejectionMail($contentProvider, $reason));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send rejection email: ' . $e->getMessage());
        }
    }

    /**
     * Override storeLogoIfPresent to save logos in public directory for asset access
     */
    protected function storeLogoIfPresent($logo): ?string
    {
        if (!$logo || !method_exists($logo, 'isValid') || !$logo->isValid()) {
            return null;
        }

        // Define the directory in public folder
        $directory = 'assets/partners/logos';
        $publicPath = public_path($directory);
        
        // Create directory if it doesn't exist
        if (!\Illuminate\Support\Facades\File::exists($publicPath)) {
            \Illuminate\Support\Facades\File::makeDirectory($publicPath, 0755, true);
        }

        // Generate unique filename
        $filename = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
        
        // Move file to public directory
        $logo->move($publicPath, $filename);
        
        // Return path relative to public directory (for asset() function)
        return $directory . '/' . $filename;
    }

    /**
     * Delete stored file if exists.
     */
    protected function deleteStoredFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        // Check if file is in public directory (assets path)
        if (str_starts_with($path, 'assets/')) {
            $filePath = public_path($path);
            if (\Illuminate\Support\Facades\File::exists($filePath)) {
                \Illuminate\Support\Facades\File::delete($filePath);
            }
            return;
        }

        // Handle storage path (for backward compatibility)
        $relativePath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($relativePath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
            return;
        }

        if (\Illuminate\Support\Facades\Storage::exists($path)) {
            \Illuminate\Support\Facades\Storage::delete($path);
        }
    }

    /**
     * Normalize translated or localized values into a plain string.
     */
    protected function normalizeLocaleValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return '';
            }

            if (\Illuminate\Support\Str::startsWith($trimmed, '{') && \Illuminate\Support\Str::endsWith($trimmed, '}')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->normalizeLocaleValue($decoded);
                }
            }

            return $trimmed;
        }

        if (is_array($value)) {
            $preferredLocales = ['en', 'en_US', 'en-Us', 'en-us', 'en-GB', 'ar', 'ar_SA', 'ar-SA'];

            foreach ($preferredLocales as $locale) {
                if (!empty($value[$locale])) {
                    return (string) $value[$locale];
                }
            }

            foreach ($value as $entry) {
                $normalized = $this->normalizeLocaleValue($entry);
                if ($normalized !== '') {
                    return $normalized;
                }
            }

            return '';
        }

        if (is_object($value)) {
            return $this->normalizeLocaleValue((array) $value);
        }

        return (string) $value;
    }

    /**
     * Resolve the authenticated actor responsible for a rejection.
     *
     * @throws \RuntimeException
     */
    protected function resolveRejectionActorId(): string
    {
        $guards = ['admin-api', 'admin', null];

        foreach ($guards as $guard) {
            try {
                $authGuard = $guard ? \Illuminate\Support\Facades\Auth::guard($guard) : \Illuminate\Support\Facades\Auth::guard();
            } catch (\Throwable $e) {
                continue;
            }

            if ($authGuard->check()) {
                $id = $authGuard->id();
                if (!empty($id)) {
                    return $id;
                }
            }
        }

        throw new \RuntimeException('Unable to determine the rejecting user.');
    }

    /**
     * Check file type based on extension.
     */
    protected function checkFileType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        }

        if (in_array($extension, $documentExtensions)) {
            return 'document';
        }

        return 'other';
    }
}
