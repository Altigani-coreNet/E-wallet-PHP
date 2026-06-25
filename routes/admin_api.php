<?php

use App\Http\Controllers\Api\AdvertisementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthenticationApiController;
use App\Http\Controllers\Api\MerchantLookupController;
use App\Http\Controllers\Api\PosProfileController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BusinessTypeController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\CurrencyController;
use App\Http\Controllers\Api\MerchantTerminalApiController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceFeeController;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\TerminalRegistrationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminChangeRequestController;
use App\Http\Controllers\Api\AdminNotificationController;
use App\Http\Controllers\Api\AdminPlanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\AdminAdvertisementController;
use App\Http\Controllers\Api\PublicPlanController;
use App\Http\Controllers\UserGroupController;
use Symfony\Component\HttpFoundation\Request;
// use App\Http\Controllers\Api\ServiceFeeController;
// External Authentication API (for Pos system)
Route::prefix('v1')->group(function () {
    Route::post('/authenticate/verify', [AuthenticationApiController::class, 'verifyAuthentication']);
    // Testing/Lookup: return a small list of merchant IDs for cross-service syncs
    Route::get('/merchants/test-ids', [MerchantLookupController::class, 'testMerchantIds']);
});

// Lightweight POS profile (public, no auth, used by POS backend)
Route::get('/pos/profile/{user}', [PosProfileController::class, 'showForPos']);
// Lightweight merchant + terminal lookup (public, no auth, used by SoftPos/POS)
Route::get('/pos/merchant-terminal', [PosProfileController::class, 'merchantTerminal']);

// Merchant profile by merchant_id (public, no auth, used by API key authentication)
Route::get('/profile/me/{merchantId}', [AuthController::class, 'profileMeByMerchant']);

// Route::prefix('auth')->group(function () {

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/username', [AuthController::class, 'loginWithUsername']);

// Password reset
Route::post('/password/request-reset', [PasswordResetController::class, 'requestReset']);
Route::post('/password/verify-code', [PasswordResetController::class, 'verifyCode']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::get('/password/reset-token/validate', [\App\Http\Controllers\Api\AdminUserController::class, 'validateResetPasswordTokenQuery']);
Route::get('/password/reset-token/{token}/validate', [\App\Http\Controllers\Api\AdminUserController::class, 'validateResetPasswordTokenPath']);
Route::post('/password/reset-token', [\App\Http\Controllers\Api\AdminUserController::class, 'resetPasswordWithToken']);

// Test/Development: Delete test user by email
Route::post('/delete-test-user', [UserController::class, 'deleteTestUser']);

// Merchant registration routes
Route::post('/merchants/register', [RegisterController::class, 'registerMerchant'])->middleware('auth:api');

// Lookup routes (public)
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/countries/select', [CountryController::class, 'select']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/select', [CityController::class, 'select']);
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::get('/currencies/select', [CurrencyController::class, 'select']);
Route::get('/currencies/{id}', [CurrencyController::class, 'show']);
Route::get('/business-types', [BusinessTypeController::class, 'index']);
Route::get('/business-types/select', [BusinessTypeController::class, 'select']);

// Contract Terms routes (public)
Route::get('/contract-terms', [\App\Http\Controllers\Api\ContractTermsController::class, 'getContractTerms']);

// Terminal Registration (public - no auth required)
Route::post('/terminals/register-device', [TerminalRegistrationController::class, 'registerOrRetrieveTerminal']);

// Merchant file upload routes (protected)
Route::post('/upload-merchant-file', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'upload'])->middleware('auth:api');
Route::delete('/delete-merchant-file/{fileId}', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'delete'])->middleware('auth:api');
Route::get('/merchant-files', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'getMerchantFiles'])->middleware('auth:api');

// Partner file upload routes (protected)
Route::post('/upload-partner-file', [\App\Http\Controllers\Api\PartnerFileUploadController::class, 'upload'])->middleware('auth:api');
Route::delete('/delete-partner-file/{fileId}', [\App\Http\Controllers\Api\PartnerFileUploadController::class, 'delete'])->middleware('auth:api');
Route::get('/partner-files', [\App\Http\Controllers\Api\PartnerFileUploadController::class, 'getPartnerFiles'])->middleware('auth:api');



// Protected routes
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/force-logout', [AuthController::class, 'forceLogout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/profile/me', [AuthController::class, 'profileMe']);
    Route::get('/merchant/currency', [\App\Http\Controllers\Api\MerchantCurrencyController::class, 'show']);
    Route::post('/merchant/plan/upgrade', [\App\Http\Controllers\Api\MerchantPlanController::class, 'upgrade']);

    // Terminal linking (user must be authenticated)
    Route::post('/terminals/link-terminal', [\App\Http\Controllers\Api\TerminalRegistrationController::class, 'linkTerminal']);

    // User Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ApiNotificationController::class, 'getNotifications']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\ApiNotificationController::class, 'getUnreadNotificationCount']);
        Route::post('/mark-all-read', [\App\Http\Controllers\Api\ApiNotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/mark-as-read', [\App\Http\Controllers\Api\ApiNotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\ApiNotificationController::class, 'deleteNotification']);
    });

    // Profile management
    Route::get('/profile/info', [ProfileController::class, 'getUserInfo']);
    Route::get('/profile/completion', [ProfileController::class, 'getProfileCompletion']);
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/upload-image', [ProfileController::class, 'uploadProfileImage']);
    Route::delete('/profile/delete-image', [ProfileController::class, 'deleteProfileImage']);

    // User Management (Merchant Dashboard)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/select/dropdown', [UserController::class, 'select']);
        Route::get('/export', [UserController::class, 'export']);
        Route::get('/export-template', [UserController::class, 'exportTemplate']);
        Route::post('/import-preview', [UserController::class, 'importPreview']);
        Route::post('/import', [UserController::class, 'import']);
        Route::post('/lookup', [UserController::class, 'lookup']);
        Route::post('/{id}/send-reset-password-link', [UserController::class, 'sendResetPasswordLink']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Permissions API (reads from config)
    Route::get('/permissions', [RoleController::class, 'getPermissionsList']);

    // Role Management (Merchant Dashboard)
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::put('/{id}', [RoleController::class, 'update']);
        Route::delete('/{id}', [RoleController::class, 'destroy']);
        Route::get('/permissions/all', [RoleController::class, 'permissions']);
    });

    // Merchant Profile Management
    Route::prefix('merchant-profile')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\MerchantProfileController::class, 'show']);
        Route::post('/update', [\App\Http\Controllers\Api\MerchantProfileController::class, 'updateProfile']);
        Route::post('/update-attachments', [\App\Http\Controllers\Api\MerchantProfileController::class, 'updateAttachments']);
        Route::get('/rejected-fields', [\App\Http\Controllers\Api\MerchantProfileController::class, 'getRejectedFields']);
        Route::post('/update-rejected-fields', [\App\Http\Controllers\Api\MerchantProfileController::class, 'updateRejectedFields']);
    });

    // Change Request Management
    Route::prefix('change-requests')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ChangeRequestController::class, 'index']);
        Route::get('/{changeRequest}', [\App\Http\Controllers\Api\ChangeRequestController::class, 'show']);
        Route::post('/{changeRequest}/approve', [\App\Http\Controllers\Api\ChangeRequestController::class, 'approve']);
        Route::post('/{changeRequest}/reject', [\App\Http\Controllers\Api\ChangeRequestController::class, 'reject']);
        Route::get('/history/merchant', [\App\Http\Controllers\Api\ChangeRequestController::class, 'history']);
    });

    // Branch Management (Merchant Dashboard)
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::post('/', [BranchController::class, 'store']);
        Route::get('/select', [BranchController::class, 'select']);
        Route::post('/by-ids', [BranchController::class, 'byIds']);
        Route::get('/export', [BranchController::class, 'export']);
        Route::get('/export-template', [BranchController::class, 'exportTemplate']);
        Route::post('/import', [BranchController::class, 'import']);
        Route::post('/import-preview', [BranchController::class, 'importPreview']);
        Route::delete('/bulk-delete', [BranchController::class, 'bulkDelete']);
        Route::get('/{id}', [BranchController::class, 'show']);
        Route::put('/{id}', [BranchController::class, 'update']);
        Route::delete('/{id}', [BranchController::class, 'destroy']);
    });

    // Contract Management (Merchant Dashboard - Read Only)
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::get('/download', [ContractController::class, 'download']);
    });

    // Service Fees (Merchant Dashboard - Read Only)
    Route::prefix('service-fees')->group(function () {
        Route::get('/', [ServiceFeeController::class, 'index']);
        Route::get('/types', [ServiceFeeController::class, 'types']);
        Route::get('/{id}', [ServiceFeeController::class, 'show']);
    });

    // User Groups Management (Merchant Dashboard)
    Route::prefix('user-groups')->group(function () {
        Route::get('/', [UserGroupController::class, 'index']);
        Route::post('/', [UserGroupController::class, 'store']);
        Route::get('/select', [UserGroupController::class, 'select']);
        Route::get('/merchant-users', [UserGroupController::class, 'getMerchantUsers']);
        Route::get('/merchant-branches', [UserGroupController::class, 'getMerchantBranches']);
        Route::post('/bulk-delete', [UserGroupController::class, 'bulkDelete']);
        Route::get('/{id}', [UserGroupController::class, 'show']);
        Route::put('/{id}', [UserGroupController::class, 'update']);
        Route::delete('/{id}', [UserGroupController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [UserGroupController::class, 'toggleStatus']);
    });

    // Terminal Management (Merchant Dashboard)
    Route::prefix('merchant/terminals')->group(function () {
        Route::get('/', [MerchantTerminalApiController::class, 'index']);
        Route::get('/select', [MerchantTerminalApiController::class, 'select']);
        Route::get('/by-branch', [MerchantTerminalApiController::class, 'getByBranch']);
        Route::get('/export', [MerchantTerminalApiController::class, 'export']);
        Route::get('/export-template', [MerchantTerminalApiController::class, 'exportTemplate']);
        Route::post('/import-preview', [MerchantTerminalApiController::class, 'importPreview']);
        Route::post('/import', [MerchantTerminalApiController::class, 'import']);
        Route::post('/bulk-delete', [MerchantTerminalApiController::class, 'bulkDelete']);
        Route::get('/{terminal}', [MerchantTerminalApiController::class, 'show']);
        Route::post('/', [MerchantTerminalApiController::class, 'store']);
        Route::put('/{terminal}', [MerchantTerminalApiController::class, 'update']);
        Route::delete('/{terminal}', [MerchantTerminalApiController::class, 'destroy']);
    });

    // Advertisements API Routes
    Route::get('v1/advertisements', [AdvertisementController::class, 'getByCountry']);
});
// });

// Registration flow routes
Route::prefix('/register')->group(function () {
    Route::post('/validate-details', [ValidationController::class, 'validateDetails']);
    Route::post('/send-verification-code', [VerificationController::class, 'sendVerificationCode']);
    Route::post('/verify-code', [VerificationController::class, 'verifyCode']);
    Route::get('/merchant/send-continuation-email', [VerificationController::class, 'sendMerchantContinuationEmail'])->middleware('auth:api');
    Route::get('/partner/send-continuation-email', [VerificationController::class, 'sendPartnerContinuationEmail'])->middleware('auth:api');
    Route::post('/user', [RegisterController::class, 'register']);
    Route::post('/merchant', [RegisterController::class, 'registerMerchant'])->middleware('auth:api');
});

// Test endpoint for merchant configuration setup integration
Route::post('/test/merchant-configuration', [RegisterController::class, 'testMerchantConfiguration']);

// V2 Admin Authentication Routes (Public)
Route::prefix('v2/admin/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AdminAuthController::class, 'login']);
});

// V2 Public routes (no auth) - landing page data
Route::prefix('v2/public')->group(function () {
    Route::get('/plans', [PublicPlanController::class, 'index']);
    Route::get('/plans/{id}', [PublicPlanController::class, 'show']);
});

// V2 Users Lookup (Admin access required)
Route::prefix('v2')->middleware(['auth:admin-api'])->group(function () {
    Route::post('/users/lookup', [\App\Http\Controllers\Api\AdminUserController::class, 'lookup']);
});

// V2 Admin API Routes (Protected with Sanctum + CheckIsAdmin)
Route::prefix('v2/admin')->middleware([
    'auth:admin-api',
    // \App\Http\Middleware\CheckIsAdmin::class
])->group(function () {

    Route::prefix('dashboard')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'overview']);
        Route::get('/terminals', [AdminDashboardController::class, 'terminalStatus']);
    });

    // Auth routes (protected)
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AdminAuthController::class, 'logout']);
        Route::post('/logout-all', [\App\Http\Controllers\Api\AdminAuthController::class, 'logoutAll']);
        Route::get('/profile', [\App\Http\Controllers\Api\AdminAuthController::class, 'profile']);
        Route::post('/profile/update', [\App\Http\Controllers\Api\AdminAuthController::class, 'updateProfile']);
        Route::post('/profile/change-password', [\App\Http\Controllers\Api\AdminAuthController::class, 'changePassword']);
        Route::post('/refresh-token', [\App\Http\Controllers\Api\AdminAuthController::class, 'refreshToken']);
    });

    // Merchants
    Route::prefix('merchants')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminMerchantController::class, 'index']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminMerchantController::class, 'select']);
        Route::get('/scopes', [\App\Http\Controllers\Api\AdminMerchantController::class, 'scopes']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AdminMerchantController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\Api\AdminMerchantController::class, 'export']);
        Route::get('/export-template', [\App\Http\Controllers\Api\AdminMerchantController::class, 'exportTemplate']);
        Route::post('/import-preview', [\App\Http\Controllers\Api\AdminMerchantController::class, 'importPreview']);
        Route::post('/import', [\App\Http\Controllers\Api\AdminMerchantController::class, 'import']);
        Route::post('/country-lookup', [\App\Http\Controllers\Api\AdminMerchantController::class, 'countryLookup']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminMerchantController::class, 'bulkDelete']);
        Route::post('/{id}/approve', [\App\Http\Controllers\Api\AdminMerchantController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\AdminMerchantController::class, 'reject']);
        Route::post('/{id}/suspend', [\App\Http\Controllers\Api\AdminMerchantController::class, 'suspend']);
        Route::post('/{id}/unsuspend', [\App\Http\Controllers\Api\AdminMerchantController::class, 'unsuspend']);
        Route::get('/{id}/logs', [\App\Http\Controllers\Api\AdminMerchantController::class, 'logs']);
        Route::get('/{id}/change-requests', [\App\Http\Controllers\Api\AdminMerchantController::class, 'changeRequests']);
        Route::post('/{id}/change-requests/{changeRequest}/approve', [\App\Http\Controllers\Api\AdminMerchantController::class, 'approveChangeRequest']);
        Route::post('/{id}/change-requests/{changeRequest}/reject', [\App\Http\Controllers\Api\AdminMerchantController::class, 'rejectChangeRequest']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminMerchantController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminMerchantController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminMerchantController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminMerchantController::class, 'destroy']);
    });

    Route::prefix('change-requests')->group(function () {
        Route::get('/', [AdminChangeRequestController::class, 'index']);
        Route::get('/statistics', [AdminChangeRequestController::class, 'statistics']);
        Route::post('/{changeRequest}/approve', [AdminChangeRequestController::class, 'approve']);
        Route::post('/{changeRequest}/reject', [AdminChangeRequestController::class, 'reject']);
        Route::get('/{changeRequest}', [AdminChangeRequestController::class, 'show']);
    });

    // Branches
    Route::prefix('branches')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminBranchController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AdminBranchController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\Api\AdminBranchController::class, 'export']);
        Route::get('/export-template', [\App\Http\Controllers\Api\AdminBranchController::class, 'exportTemplate']);
        Route::post('/import-preview', [\App\Http\Controllers\Api\AdminBranchController::class, 'importPreview']);
        Route::post('/import', [\App\Http\Controllers\Api\AdminBranchController::class, 'import']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminBranchController::class, 'bulkDelete']);
        Route::post('/{id}/approve', [\App\Http\Controllers\Api\AdminBranchController::class, 'approve']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\AdminBranchController::class, 'reject']);
        Route::post('/{id}/suspend', [\App\Http\Controllers\Api\AdminBranchController::class, 'suspend']);
        Route::post('/{id}/unsuspend', [\App\Http\Controllers\Api\AdminBranchController::class, 'unsuspend']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminBranchController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminBranchController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminBranchController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminBranchController::class, 'destroy']);
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminUserController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AdminUserController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\Api\AdminUserController::class, 'export']);
        Route::get('/export-template', [\App\Http\Controllers\Api\AdminUserController::class, 'exportTemplate']);
        Route::post('/import-preview', [\App\Http\Controllers\Api\AdminUserController::class, 'importPreview']);
        Route::post('/import', [\App\Http\Controllers\Api\AdminUserController::class, 'import']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminUserController::class, 'bulkDelete']);
        Route::post('/{id}/activate', [\App\Http\Controllers\Api\AdminUserController::class, 'activate']);
        Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\AdminUserController::class, 'deactivate']);
        Route::post('/{id}/send-reset-password-link', [\App\Http\Controllers\Api\AdminUserController::class, 'sendResetPasswordLink']);
        Route::post('/lookup', [\App\Http\Controllers\Api\AdminUserController::class, 'lookup']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminUserController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'destroy']);
    });

    // User Groups
    Route::prefix('user-groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'statistics']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'select']);
        Route::get('/export', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'export']);
        Route::get('/export-template', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'exportTemplate']);
        Route::post('/import-preview', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'importPreview']);
        Route::post('/import', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'import']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'bulkDelete']);
        Route::post('/{id}/activate', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'activate']);
        Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'deactivate']);
        Route::get('/merchant-users', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'getMerchantUsers']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminUserGroupController::class, 'destroy']);
    });

    // Terminals
    Route::prefix('terminals')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminTerminalController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AdminTerminalController::class, 'statistics']);
        Route::get('/filters', [\App\Http\Controllers\Api\AdminTerminalController::class, 'filters']);
        Route::get('/export', [\App\Http\Controllers\Api\AdminTerminalController::class, 'export']);
        Route::get('/export-template', [\App\Http\Controllers\Api\AdminTerminalController::class, 'exportTemplate']);
        Route::post('/import-preview', [\App\Http\Controllers\Api\AdminTerminalController::class, 'importPreview']);
        Route::post('/import', [\App\Http\Controllers\Api\AdminTerminalController::class, 'import']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminTerminalController::class, 'bulkDelete']);
        Route::post('/bulk-status', [\App\Http\Controllers\Api\AdminTerminalController::class, 'bulkStatusChange']);
        Route::post('/models-by-brands', [\App\Http\Controllers\Api\AdminTerminalController::class, 'modelsByBrands']);
        Route::post('/manufacturers-by-models', [\App\Http\Controllers\Api\AdminTerminalController::class, 'manufacturersByModels']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminTerminalController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminTerminalController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminTerminalController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminTerminalController::class, 'destroy']);
    });

    // Brands
    Route::get('/brands', [\App\Http\Controllers\Api\AdminTerminalController::class, 'brands']);

    // Terminal Groups
    Route::prefix('terminal-groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'statistics']);
        Route::get('/parent-groups', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'parentGroups']);
        Route::get('/export', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'export']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'bulkDelete']);
        Route::post('/{id}/toggle-status', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'toggleStatus']);
        Route::post('/{id}/activate', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'activate']);
        Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'deactivate']);
        Route::delete('/{id}/remove-terminal', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'removeTerminal']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminTerminalGroupController::class, 'destroy']);
    });

    // System Administration - Roles
    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminRoleController::class, 'index']);
        Route::get('/data', [\App\Http\Controllers\Api\AdminRoleController::class, 'data']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminRoleController::class, 'select']);
        Route::get('/permissions', [\App\Http\Controllers\Api\AdminRoleController::class, 'permissions']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminRoleController::class, 'bulkDelete']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminRoleController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminRoleController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminRoleController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminRoleController::class, 'destroy']);
    });

    // System Administration - Admins
    Route::prefix('admins')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminAdminController::class, 'index']);
        Route::get('/data', [\App\Http\Controllers\Api\AdminAdminController::class, 'data']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminAdminController::class, 'select']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminAdminController::class, 'bulkDelete']);
        Route::post('/{id}/change-status', [\App\Http\Controllers\Api\AdminAdminController::class, 'changeStatus']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminAdminController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminAdminController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminAdminController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminAdminController::class, 'destroy']);
    });

    // Attachments
    Route::prefix('attachments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminAttachmentController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminAttachmentController::class, 'show']);
        Route::get('/{id}/download', [\App\Http\Controllers\Api\AdminAttachmentController::class, 'download']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminAttachmentController::class, 'destroy']);
        Route::delete('/delete-by-path', [\App\Http\Controllers\Api\AdminAttachmentController::class, 'deleteByPath']);
    });

    // System Administration - Countries
    Route::prefix('countries')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminCountryController::class, 'index']);
        Route::get('/data', [\App\Http\Controllers\Api\AdminCountryController::class, 'data']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminCountryController::class, 'select']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminCountryController::class, 'bulkDelete']);
        Route::post('/{id}/change-status', [\App\Http\Controllers\Api\AdminCountryController::class, 'changeStatus']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminCountryController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminCountryController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminCountryController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminCountryController::class, 'destroy']);
    });

    // System Administration - Cities
    Route::prefix('cities')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminCityController::class, 'index']);
        Route::get('/data', [\App\Http\Controllers\Api\AdminCityController::class, 'data']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminCityController::class, 'select']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminCityController::class, 'bulkDelete']);
        Route::post('/{id}/change-status', [\App\Http\Controllers\Api\AdminCityController::class, 'changeStatus']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminCityController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminCityController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminCityController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminCityController::class, 'destroy']);
    });

    // System Administration - Advertisements
    Route::prefix('advertisements')->group(function () {
        Route::get('/', [AdminAdvertisementController::class, 'index']);
        Route::get('/data', [AdminAdvertisementController::class, 'data']);
        Route::post('/bulk-delete', [AdminAdvertisementController::class, 'bulkDelete']);
        Route::post('/{id}/change-status', [AdminAdvertisementController::class, 'changeStatus']);
        Route::get('/{id}', [AdminAdvertisementController::class, 'show']);
        Route::post('/', [AdminAdvertisementController::class, 'store']);
        Route::put('/{id}', [AdminAdvertisementController::class, 'update']);
        Route::delete('/{id}', [AdminAdvertisementController::class, 'destroy']);
    });

    // Plans Management
    Route::prefix('plans')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index']);
        Route::get('/select', [AdminPlanController::class, 'select']);
        Route::get('/report', [AdminPlanController::class, 'report']);
        Route::post('/{id}/change-status', [AdminPlanController::class, 'changeStatus']);
        Route::get('/{id}', [AdminPlanController::class, 'show']);
        Route::post('/', [AdminPlanController::class, 'store']);
        Route::put('/{id}', [AdminPlanController::class, 'update']);
        Route::delete('/{id}', [AdminPlanController::class, 'destroy']);
    });

    // Notification Management
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index']);
        Route::post('/', [AdminNotificationController::class, 'store']);
        Route::get('/lookups/merchants/select', [AdminNotificationController::class, 'merchantsSelect']);
        Route::get('/lookups/users', [AdminNotificationController::class, 'usersByMerchant']);
        Route::get('/lookups/customers/select', [AdminNotificationController::class, 'customersSelect']);
        Route::post('/{id}/resend', [AdminNotificationController::class, 'resend']);
        Route::get('/{id}', [AdminNotificationController::class, 'show']);
        Route::put('/{id}', [AdminNotificationController::class, 'update']);
        Route::delete('/{id}', [AdminNotificationController::class, 'destroy']);
    });

    // Settings Management - Service Fees
    Route::prefix('settings/service-fees')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'index']);
        Route::get('/data', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'data']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'bulkDelete']);
        Route::post('/import', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'import']);
        Route::get('/export-template', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'exportTemplate']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminServiceFeeController::class, 'destroy']);
    });

    // Settings Management - Currencies
    Route::prefix('settings/currencies')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'index']);
        Route::get('/data', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'data']);
        Route::get('/select', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'select']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'bulkDelete']);
        Route::get('/{id}', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\AdminCurrencyController::class, 'destroy']);
    });

    // Settings Management - Contract Terms
    Route::prefix('settings/contract-terms')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AdminSettingsController::class, 'getContractTerms']);
        Route::post('/update', [\App\Http\Controllers\Api\AdminSettingsController::class, 'updateContractTerms']);
        Route::get('/preview/{lang}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'previewTerms']);
        Route::get('/download/{lang}', [\App\Http\Controllers\Api\AdminSettingsController::class, 'downloadTerms']);
    });
});

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'AuthService']);
});
