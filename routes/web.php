<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MerchantProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MerchantDashboardController;
use App\Http\Controllers\MerchantBranchController;
use App\Http\Controllers\MerchantTerminalController;
use App\Http\Controllers\MerchantTerminalGroupController;
use App\Http\Controllers\MerchantUserController;
use App\Http\Controllers\MerchantUserGroupController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchantFormGetPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\MerchantAuthenticatedController;
use App\Http\Controllers\TerminalController;
use App\Http\Controllers\TerminalGroupController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\TerminalAssignmentController;
use App\Http\Controllers\MerchantTerminalAssignmentController;
use App\Http\Controllers\PaymentByLinkController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MerchantRoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AuthenticationRedirectController;
use App\Http\Controllers\SalesController;
use App\Http\Middleware\ApprovedMiddleware;

// Landing Pages
Route::get('/', [HomeController::class, 'index']);
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');

// Merchant forgot password page
Route::get('merchant/forgot-password', [MerchantFormGetPasswordController::class, 'show'])->name('merchant.forgot-password');

Route::middleware('auth:admin,web')->group(function () {
    Route::get('logs/data', [App\Http\Controllers\LogController::class, 'data'])->name('logs.data');
    Route::get('country-select', [App\Http\Controllers\CountryController::class, 'select'])->name('countries.select');
    Route::get('city-select', [App\Http\Controllers\CityController::class, 'select'])->name('city.select');
    Route::get('currencies/select', [CurrencyController::class, 'select'])->name('currencies.select');

});

Route::middleware('auth:web,external')->group(function () {
    // Profile Routes

    Route::get('merchant/profile', [ProfileController::class, 'show'])->name('merchant.profile');
    Route::get('merchant/profile/user-info', [ProfileController::class, 'userInfo'])->name('profile.user_info');
    Route::post('merchant/profile/user-info/update', [ProfileController::class, 'updateUserInfo'])->name('user.info.update');
    Route::post('merchant/profile/user-info/change-password', [ProfileController::class, 'changePassword'])->name('user.change-password');
    // Route::get('merchant/profile/user-info', [ProfileController::class, 'user-info'])->name('profile.user_info');
    Route::get('merchant/profile/events', [ProfileController::class, 'events'])->name('profile.events');
    Route::get('merchant/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('merchant/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::get('profile',  function(){})->name('profile.show'); 

    // Route::get('/merchant/profile', [MerchantAuthenticatedController::class, 'profile'])->name('merchant.profile');
    Route::post('/merchant/profile', [MerchantAuthenticatedController::class, 'updateProfile'])->name('merchant.profile.update');



    // Merchant Dashboard Route
    Route::get('/merchant/dashboard', [MerchantDashboardController::class, 'index'])->name('merchant.dashboard')->middleware(['auth:external', ApprovedMiddleware::class]);
    Route::get('/merchant/dashboard/latest-transactions', [MerchantDashboardController::class, 'latestTransactions'])->name('merchant.dashboard.latest-transactions')->middleware(['auth:external', ApprovedMiddleware::class]);
    Route::get('/merchant/dashboard/export', [MerchantDashboardController::class, 'exportDashboard'])->name('merchant.dashboard.export')->middleware(['auth:external', ApprovedMiddleware::class]);

    // POS Redirect Routes
    Route::get('/merchant/pos-dashboard', [AuthenticationRedirectController::class, 'redirectToPOSDashboard'])->name('merchant.pos-dashboard');
    Route::get('/merchant/pos', [AuthenticationRedirectController::class, 'redirectToPOS'])->name('merchant.pos');

    // Merchant Branch Management Routes
    Route::prefix('merchant')->name('merchant.')->group(function () {
        // Profile routes
        Route::get('profile/edit', [MerchantProfileController::class, 'edit'])->name('edit');
        Route::get('profile/rejected-fields-edit/{merchant}', [MerchantProfileController::class, 'editRejectedFields'])->name('rejected_filed_eidt');
        Route::put('profile/update', [MerchantProfileController::class, 'update'])->name('update');
        Route::put('profile/update-rejected-fields/{merchant}', [MerchantProfileController::class, 'updateRejectedFields'])->name('update.rejected_fields');
        Route::put('profile/attachments', [MerchantProfileController::class, 'updateAttachments'])->name('update.attachments');
        Route::get('branches/data', [MerchantBranchController::class, 'data'])->name('branches.data');
        Route::get('branches/select', [MerchantBranchController::class, 'select'])->name('branches.select');
        Route::post('branches/bulk-delete', [MerchantBranchController::class, 'bulkDelete'])->name('branches.bulk-delete');
        Route::post('branches/import-preview', [MerchantBranchController::class, 'importPreview'])->name('branches.import-preview');
        Route::post('branches/import', [MerchantBranchController::class, 'import'])->name('branches.import');
        Route::get('branches/export-template', [MerchantBranchController::class, 'exportTemplate'])->name('branches.export-template');
        Route::get('branches/export', [MerchantBranchController::class, 'export'])->name('branches.export');
        Route::resource('branches', MerchantBranchController::class);

        // Merchant Terminal Management Routes
        Route::get('terminals/data', [MerchantTerminalController::class, 'data'])->name('terminals.data');
        Route::get('terminals/select', [MerchantTerminalController::class, 'select'])->name('terminals.select');
        Route::post('terminals/bulk-delete', [MerchantTerminalController::class, 'bulkDelete'])->name('terminals.bulk-delete');
        Route::post('terminals/import-preview', [MerchantTerminalController::class, 'importPreview'])->name('terminals.import-preview');
        Route::post('terminals/import', [MerchantTerminalController::class, 'import'])->name('terminals.import');
        Route::get('terminals/export-template', [MerchantTerminalController::class, 'exportTemplate'])->name('terminals.export-template');
        Route::get('terminals/export', [MerchantTerminalController::class, 'export'])->name('terminals.export');
        Route::get('terminals/get-by-branch', [MerchantTerminalController::class, 'getByBranch'])->name('terminals.get-by-branch');
        Route::get('terminals/get-active-by-branch', [MerchantTerminalController::class, 'getActiveByBranch'])->name('terminals.get-active-by-branch');
        Route::resource('terminals', MerchantTerminalController::class);

        // Merchant Terminal Groups Management Routes
        Route::get('terminal-groups/data', [MerchantTerminalGroupController::class, 'data'])->name('terminal-groups.data');
        Route::get('terminal-groups/select', [MerchantTerminalGroupController::class, 'select'])->name('terminal-groups.select');
        Route::get('terminal-groups/get-merchant-terminals', [MerchantTerminalGroupController::class, 'getMerchantTerminals'])->name('terminal-groups.get-merchant-terminals');
        Route::get('terminal-groups/get-parent-groups', [MerchantTerminalGroupController::class, 'getParentGroups'])->name('terminal-groups.get-parent-groups');
        Route::post('terminal-groups/toggle-status/{terminalGroup}', [MerchantTerminalGroupController::class, 'toggleStatus'])->name('terminal-groups.toggle-status');
        Route::post('terminal-groups/bulk-delete', [MerchantTerminalGroupController::class, 'bulkDelete'])->name('terminal-groups.bulk-delete');
        Route::resource('terminal-groups', MerchantTerminalGroupController::class);

        // Merchant User Management Routes
        Route::get('users/data', [MerchantUserController::class, 'data'])->name('users.data');
        Route::get('users/select', [MerchantUserController::class, 'getUserInSelect'])->name('users.select');
        Route::post('users/bulk-delete', [MerchantUserController::class, 'bulkDelete'])->name('users.bulk-delete');
        Route::post('users/import', [MerchantUserController::class, 'import'])->name('users.import');
        Route::get('users/sections/{user}/{type}', [MerchantUserController::class, 'usersSections'])->name('users.sections');
        Route::resource('users', MerchantUserController::class);

        // Merchant User Groups Management Routes
        Route::get('user-groups/data', [MerchantUserGroupController::class, 'data'])->name('user-groups.data');
        Route::get('user-groups/select', [MerchantUserGroupController::class, 'select'])->name('user-groups.select');
        Route::get('user-groups/get-merchant-users', [MerchantUserGroupController::class, 'getMerchantUsers'])->name('user-groups.get-merchant-users');
        Route::get('user-groups/get-merchant-terminal-groups', [MerchantUserGroupController::class, 'getMerchantTerminalGroups'])->name('user-groups.get-merchant-terminal-groups');
        Route::get('user-groups/get-merchant-terminals', [MerchantUserGroupController::class, 'getMerchantTerminals'])->name('user-groups.get-merchant-terminals');
        Route::get('user-groups/get-merchant-branches', [MerchantUserGroupController::class, 'getMerchantBranches'])->name('user-groups.get-merchant-branches');
        Route::post('user-groups/toggle-status/{userGroup}', [MerchantUserGroupController::class, 'toggleStatus'])->name('user-groups.toggle-status');
        Route::post('user-groups/bulk-delete', [MerchantUserGroupController::class, 'bulkDelete'])->name('user-groups.bulk-delete');
        Route::post('user-groups/import', [MerchantUserGroupController::class, 'import'])->name('user-groups.import');
        Route::get('user-groups/export-template', [MerchantUserGroupController::class, 'exportTemplate'])->name('user-groups.export-template');
        Route::delete('user-groups/{userGroup}/users/{user}', [MerchantUserGroupController::class, 'removeUser'])->name('user-groups.remove-user');
        Route::resource('user-groups', MerchantUserGroupController::class);

        // Merchant Terminal Assignment routes
        Route::get('terminal-assignments', [MerchantTerminalAssignmentController::class, 'index'])->name('terminal-assignments.index');
        Route::post('terminal-assignments', [MerchantTerminalAssignmentController::class, 'store'])->name('terminal-assignments.store');
        Route::get('branches/{branch}/user-groups', [MerchantTerminalAssignmentController::class, 'getUserGroupsByBranch'])->name('terminal-assignments.user-groups');
        Route::get('branches/{branch}/terminal-groups', [MerchantTerminalAssignmentController::class, 'getTerminalGroupsByBranch'])->name('terminal-assignments.terminal-groups');
        Route::get('branches/{branch}/terminals', [MerchantTerminalAssignmentController::class, 'getTerminalsByBranch'])->name('terminal-assignments.terminals');

        // Sales SPA Routes - catch-all for React Router
        Route::get('sales/{any?}', [SalesController::class, 'index'])->where('any', '.*')->name('sales.index');

        // Merchant Transaction routes
        Route::get('transactions/statistics', [App\Http\Controllers\MerchantTransactionController::class, 'statistics'])->name('transactions.statistics');
        Route::get('transactions/export', [App\Http\Controllers\MerchantTransactionController::class, 'export'])->name('transactions.export');
        Route::get('transactions/data', [App\Http\Controllers\MerchantTransactionController::class, 'data'])->name('transactions.data');
        Route::get('transactions/{transaction}/receipt', [App\Http\Controllers\MerchantTransactionController::class, 'receipt'])->name('transactions.receipt');
        Route::post('transactions/{transaction}/send-receipt', [App\Http\Controllers\MerchantTransactionController::class, 'sendReceipt'])->name('transactions.send-receipt');
        Route::post('transactions/{transaction}/void', [App\Http\Controllers\MerchantTransactionController::class, 'voidTransaction'])->name('transactions.void');
        Route::post('transactions/{transaction}/refund', [App\Http\Controllers\MerchantTransactionController::class, 'refundTransaction'])->name('transactions.refund');
        Route::resource('transactions', App\Http\Controllers\MerchantTransactionController::class)->only(['index', 'show']);

        // Attachments routes

        // Merchant Batch routes
        Route::get('batches/data', [App\Http\Controllers\MerchantBatchController::class, 'data'])->name('batches.data');
        Route::resource('batches', App\Http\Controllers\MerchantBatchController::class)->only(['index', 'show']);

        // Merchant Settlement routes
        Route::get('settlements/data', [App\Http\Controllers\MerchantSettlementController::class, 'data'])->name('settlements.data');
        Route::get('settlements/transactions', [App\Http\Controllers\MerchantSettlementController::class, 'transactions'])->name('settlements.transactions');
        Route::get('settlements/transactions/data', [App\Http\Controllers\MerchantSettlementController::class, 'transactionsData'])->name('settlements.transactions.data');
        Route::resource('settlements', App\Http\Controllers\MerchantSettlementController::class)->only(['index', 'show']);
        // Merchant Payment Link routes
        Route::get('payment-links/data', [App\Http\Controllers\MerchantPaymentLinkController::class, 'data'])->name('payment-links.data');
        Route::get('payment-links/export', [App\Http\Controllers\MerchantPaymentLinkController::class, 'export'])->name('payment-links.export');
        Route::post('payment-links/{payment_link}/update-date', [App\Http\Controllers\MerchantPaymentLinkController::class, 'updateDate'])->name('payment-links.update-date');
        Route::post('payment-links/{payment_link}/send', [App\Http\Controllers\MerchantPaymentLinkController::class, 'send'])->name('payment-links.send');
        Route::resource('payment-links', App\Http\Controllers\MerchantPaymentLinkController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);

        // Merchant Customer routes
        Route::post('customers-ajax', [App\Http\Controllers\MerchantCustomerController::class, 'storeAjax'])->name('customers.ajax.store');

        Route::get('customers/data', [App\Http\Controllers\MerchantCustomerController::class, 'data'])->name('customers.data');
        Route::get('customers/export', [App\Http\Controllers\MerchantCustomerController::class, 'export'])->name('customers.export');
        Route::get('customers/export-template', [App\Http\Controllers\MerchantCustomerController::class, 'exportTemplate'])->name('customers.export-template');
        Route::post('customers/import-preview', [App\Http\Controllers\MerchantCustomerController::class, 'importPreview'])->name('customers.import-preview');
        Route::get('customers/select', [App\Http\Controllers\MerchantCustomerController::class, 'select'])->name('customers.select');
        Route::post('customers/import', [App\Http\Controllers\MerchantCustomerController::class, 'import'])->name('customers.import');
        Route::resource('customers', App\Http\Controllers\MerchantCustomerController::class);

        // Contract Management Routes
        Route::get('contracts/data', [App\Http\Controllers\MerchantContractController::class, 'data'])->name('contracts.data');
        Route::resource('contracts', App\Http\Controllers\MerchantContractController::class);

        // Service Fees Routes
        Route::get('service-fees/data', [App\Http\Controllers\ServiceFeeController::class, 'data'])->name('service-fees.data');
        Route::resource('service-fees', App\Http\Controllers\MerchantServiceFeeController::class)->only(['index', 'show']);

        // Route::get('customers/create', [App\Http\Controllers\MerchantCustomerController::class, 'create'])->name('customers.create');

        Route::get('roles/select', [MerchantRoleController::class, 'select'])->name('roles.select');
        Route::get('roles/data', [MerchantRoleController::class, 'data'])->name('roles.data');
        Route::resource('roles', MerchantRoleController::class);
    });



    // Route::get('users/data', [UserController::class, 'data'])->name('user.data');
    // Route::get('users/select', [UserController::class, 'getUserInSelect'])->name('users.select');
 
    // Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    // Route::get('users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
   
    // Route::resource('users', UserController::class);

    Route::get('categories-ids', [CategoryController::class, 'ids'])->name('categories.ids');
    Route::get('categories-data', [CategoryController::class, 'data'])->name('categories.data');
    Route::get('categories/select', [CategoryController::class, 'getSelectData'])->name('categories.select');
    Route::resource('categories', CategoryController::class);

    Route::get('brands/data', [BrandController::class, 'data'])->name('brands.data');
    Route::get('brands/select-data', [BrandController::class, 'getSelectData'])->name('brands.select-data');
    Route::get('brands/get-brand-name', [BrandController::class, 'getBrandName'])->name('brands.get-brand-name');
    Route::resource('brands', BrandController::class);

    Route::get('units-data', [UnitController::class, 'data'])->name('units.data');
    Route::resource('units', UnitController::class);

    Route::get('merchants/data', [MerchantController::class, 'data'])->name('merchants.data');
    Route::post('merchants/bulk-delete', [MerchantController::class, 'bulkDelete'])->name('merchants.bulk-delete');
    Route::resource('merchants', MerchantController::class);


    // Admin routes

});

Route::prefix('admin')->middleware('auth:admin')->group(function () {

    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Settings Routes
    Route::get('settings/contract-terms', [App\Http\Controllers\Admin\SettingsController::class, 'contractTerms'])->name('settings.contract-terms');
    Route::post('settings/update/terms', [App\Http\Controllers\Admin\SettingsController::class, 'updateTerms'])->name('settings.update.terms');
    Route::get('settings/preview/terms/{lang}', [App\Http\Controllers\Admin\SettingsController::class, 'previewTerms'])->name('settings.preview.terms');

    Route::get('attachments/data', [App\Http\Controllers\AttachmentController::class, 'data'])->name('admin.attachments.data');
    Route::get('attachments/{attachment}', [App\Http\Controllers\AttachmentController::class, 'show'])->name('attachments.show');
    Route::get('attachments/{attachment}/download', [App\Http\Controllers\AttachmentController::class, 'download'])->name('attachments.download');
    Route::post('attachments/delete-file', [App\Http\Controllers\AttachmentController::class, 'deleteImageWithFilePath'])->name('attachments.delete-file');


    Route::name('admin.')->group(function () {
        Route::get('roles/select', [RoleController::class, 'select'])->name('roles.select');
        Route::get('roles/data', [RoleController::class, 'data'])->name('roles.data');
        Route::resource('roles', RoleController::class);
    });

    // Countries Management Routes
    Route::name('admin.')->group(function () {
        Route::get('countries/data', [CountryController::class, 'data'])->name('countries.data');
        Route::post('countries/bulk-delete', [CountryController::class, 'bulkDelete'])->name('countries.bulk-delete');
        Route::get('countries/select', [CountryController::class, 'select'])->name('countries.select');
        Route::resource('countries', CountryController::class);
    });

    // Cities Management Routes
    Route::name('admin.')->group(function () {
        Route::get('cities/data', [CityController::class, 'data'])->name('cities.data');
        Route::post('cities/bulk-delete', [CityController::class, 'bulkDelete'])->name('cities.bulk-delete');
        Route::get('cities/select', [CityController::class, 'select'])->name('cities.select');
        Route::resource('cities', CityController::class);
    });


    Route::get('admins/data', [AdminController::class, 'data'])->name('admins.data');
    Route::get('admins/select', [AdminController::class, 'select'])->name('admins.select');
    Route::post('admins/bulk-delete', [AdminController::class, 'bulkDelete'])->name('admins.bulk-delete');
    Route::resource('admins', AdminController::class);

    Route::get('merchants/select', [MerchantController::class, 'select'])->name('merchants.select');
    Route::get('merchants/data', [MerchantController::class, 'data'])->name('merchants.data');
    Route::post('merchants/bulk-delete', [MerchantController::class, 'bulkDelete'])->name('merchants.bulk-delete');
    Route::get('merchants/export-template', [MerchantController::class, 'exportTemplate'])->name('merchants.export-template');
    Route::get('merchants/export', [MerchantController::class, 'export'])->name('merchants.export');
    Route::post('merchants/import-preview', [MerchantController::class, 'importPreview'])->name('merchants.import-preview');
    Route::post('merchants/import', [MerchantController::class, 'import'])->name('merchants.import');
    Route::post('merchants/{merchant}/approve', [MerchantController::class, 'approve'])->name('merchants.approve');
    Route::post('merchants/{merchant}/reject', [MerchantController::class, 'reject'])->name('merchants.reject');
    Route::post('merchants/{merchant}/suspend', [MerchantController::class, 'suspend'])->name('merchants.suspend');
    Route::post('merchants/{merchant}/unsuspend', [MerchantController::class, 'unsuspend'])->name('merchants.unsuspend');
    Route::get('merchants/{merchant}/show/{tab?}', [MerchantController::class, 'sections'])->name('merchants.sections');
    Route::resource('merchants', MerchantController::class);

    // Route::get('branches/data', [BranchController::class, 'data'])->name('branches.data');
    // Route::get('branches/select', [BranchController::class, 'select'])->name('branches.select');
    // Route::post('branches/bulk-delete', [BranchController::class, 'bulkDelete'])->name('branches.bulk-delete');
    // Route::post('branches/import-preview', [BranchController::class, 'importPreview'])->name('branches.import-preview');
    // Route::post('branches/import', [BranchController::class, 'import'])->name('branches.import');
    // Route::get('branches/export-template', [BranchController::class, 'exportTemplate'])->name('branches.export-template');
    // Route::get('branches/export', [BranchController::class, 'export'])->name('branches.export');
    // Route::get('branches/get-by-merchant', [BranchController::class, 'getByMerchant'])->name('branches.get-by-merchant');
    // Route::get('branches/get-active-by-merchant', [BranchController::class, 'getActiveByMerchant'])->name('branches.get-active-by-merchant');

    // // Branch approval routes
    // Route::post('branches/{branch}/approve', [BranchController::class, 'approve'])->name('branches.approve');
    // Route::post('branches/{branch}/reject', [BranchController::class, 'reject'])->name('branches.reject');
    // Route::post('branches/{branch}/suspend', [BranchController::class, 'suspend'])->name('branches.suspend');
    // Route::post('branches/{branch}/unsuspend', [BranchController::class, 'unsuspend'])->name('branches.unsuspend');

    // Route::resource('branches', BranchController::class);


    Route::get('terminals/data', [TerminalController::class, 'data'])->name('terminals.data');
    Route::get('terminals/select', [TerminalController::class, 'select'])->name('terminals.select');
    Route::post('terminals/bulk-delete', [TerminalController::class, 'bulkDelete'])->name('terminals.bulk-delete');
    Route::post('terminals/import', [TerminalController::class, 'import'])->name('terminals.import');
    Route::get('terminals/export-template', [TerminalController::class, 'exportTemplate'])->name('terminals.export-template');
    Route::get('terminals/export', [TerminalController::class, 'export'])->name('terminals.export');
    Route::get('terminals/get-by-merchant', [TerminalController::class, 'getByMerchant'])->name('terminals.get-by-merchant');
    Route::get('terminals/get-active-by-merchant', [TerminalController::class, 'getActiveByMerchant'])->name('terminals.get-active-by-merchant');
    Route::get('terminals/get-by-branch', [TerminalController::class, 'getByBranch'])->name('terminals.get-by-branch');
    Route::get('terminals/get-active-by-branch', [TerminalController::class, 'getActiveByBranch'])->name('terminals.get-active-by-branch');
    Route::get('terminals/{terminal}/events', [TerminalController::class, 'events'])->name('terminals.events');
    Route::resource('terminals', TerminalController::class);

    // Terminal Groups routes
    Route::get('terminal-groups/data', [TerminalGroupController::class, 'data'])->name('terminal-groups.data');
    Route::get('terminal-groups/select', [TerminalGroupController::class, 'select'])->name('terminal-groups.select');
    Route::get('terminal-groups/export', [TerminalGroupController::class, 'export'])->name('terminal-groups.export');
    Route::get('terminal-groups/get-parent-groups', [TerminalGroupController::class, 'getParentGroups'])->name('terminal-groups.get-parent-groups');
    Route::get('terminal-groups/get-merchant-terminals', [TerminalGroupController::class, 'getMerchantTerminals'])->name('terminal-groups.get-merchant-terminals');
    Route::post('terminal-groups/toggle-status/{terminalGroup}', [TerminalGroupController::class, 'toggleStatus'])->name('terminal-groups.toggle-status');
    Route::post('terminal-groups/bulk-delete', [TerminalGroupController::class, 'bulkDelete'])->name('terminal-groups.bulk-delete');
    Route::delete('terminal-groups/{terminalGroup}/remove-terminal', [TerminalGroupController::class, 'removeTerminal'])->name('terminal-groups.remove-terminal');
    Route::resource('terminal-groups', TerminalGroupController::class);

    // React version of terminal groups
    // Route::get('terminal-groups/create/react', function() {
    //     return view('terminal_groups.create');
    // })->name('terminal-groups.create-react');

    // User Groups routes
    Route::get('user-groups/data', [UserGroupController::class, 'data'])->name('user-groups.data');
    Route::get('user-groups/get-merchant-users', [UserGroupController::class, 'getMerchantUsers'])->name('user-groups.get-merchant-users');
    Route::get('user-groups/get-merchant-terminal-groups', [UserGroupController::class, 'getMerchantTerminalGroups'])->name('user-groups.get-merchant-terminal-groups');
    Route::get('user-groups/get-merchant-terminals', [UserGroupController::class, 'getMerchantTerminals'])->name('user-groups.get-merchant-terminals');
    Route::get('user-groups/select', [UserGroupController::class, 'select'])->name('user-groups.select');
    Route::post('user-groups/toggle-status/{userGroup}', [UserGroupController::class, 'toggleStatus'])->name('user-groups.toggle-status');
    Route::post('user-groups/bulk-delete', [UserGroupController::class, 'bulkDelete'])->name('user-groups.bulk-delete');
    Route::post('user-groups/import', [UserGroupController::class, 'import'])->name('user-groups.import');
    Route::get('user-groups/export-template', [UserGroupController::class, 'exportTemplate'])->name('user-groups.export-template');
    Route::delete('user-groups/{userGroup}/users/{user}', [UserGroupController::class, 'removeUser'])->name('user-groups.remove-user');
    Route::resource('user-groups', UserGroupController::class);


    Route::get('users/data', [UserController::class, 'data'])->name('user.data');
    Route::get('users/select', [UserController::class, 'getUserInSelect'])->name('users.select');
    Route::post('users/assign-terminals', [UserController::class, 'assignTerminals'])->name('users.assign-terminals');
    Route::post('users/remove-terminal', [UserController::class, 'removeTerminal'])->name('users.remove-terminal');
    Route::get('users/export-template', [UserController::class, 'exportTemplate'])->name('users.export-template');
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::post('users/import-preview', [UserController::class, 'importPreview'])->name('users.import-preview');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    // Route::get('users/select-by-merchant' , [UserController::class, 'getUsersByMerchant'])->name('users.select-by-merchant');
    Route::get('users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::resource('users', UserController::class);
    Route::get('users/sections/{user}/{type}', [UserController::class, 'usersSections'])->name('users.sections');
    Route::get('users/{user}/show/{tab?}', [UserController::class, 'showWithTab'])->name('users.show.tab');

    // Terminal Assignment routes
    Route::get('terminal-assignments', [TerminalAssignmentController::class, 'index'])->name('terminal-assignments.index');
    Route::post('terminal-assignments', [TerminalAssignmentController::class, 'store'])->name('terminal-assignments.store');
    Route::get('branches/{branch}/user-groups', [TerminalAssignmentController::class, 'getUserGroupsByBranch'])->name('terminal-assignments.user-groups');
    Route::get('branches/{branch}/terminal-groups', [TerminalAssignmentController::class, 'getTerminalGroupsByBranch'])->name('terminal-assignments.terminal-groups');
    Route::get('branches/{branch}/terminals', [TerminalAssignmentController::class, 'getTerminalsByBranch'])->name('terminal-assignments.terminals');

    // Transaction routes
    Route::name('admin.')->group(function () {
        Route::get('transactions/statistics', [App\Http\Controllers\Admin\TransactionController::class, 'statistics'])->name('transactions.statistics');
        Route::get('transactions/export', [App\Http\Controllers\Admin\TransactionController::class, 'export'])->name('transactions.export');
        Route::post('transactions/search-by-rrn', [App\Http\Controllers\Admin\TransactionController::class, 'searchByRRN'])->name('transactions.search-by-rrn');
        Route::post('transactions/search-by-trace', [App\Http\Controllers\Admin\TransactionController::class, 'searchByTraceNumber'])->name('transactions.search-by-trace');
        Route::get('transactions/data', [App\Http\Controllers\Admin\TransactionController::class, 'data'])->name('transactions.data');
        Route::post('transactions/bulk-delete', [App\Http\Controllers\Admin\TransactionController::class, 'bulkDelete'])->name('transactions.bulk-delete');
        Route::get('transactions/{transaction}/receipt', [App\Http\Controllers\Admin\TransactionController::class, 'receipt'])->name('transactions.receipt');
        Route::post('transactions/{transaction}/void', [App\Http\Controllers\Admin\TransactionController::class, 'voidTransaction'])->name('transactions.void');
        Route::post('transactions/{transaction}/refund', [App\Http\Controllers\Admin\TransactionController::class, 'refundTransaction'])->name('transactions.refund');
        Route::post('transactions/{transaction}/cancel', [App\Http\Controllers\Admin\TransactionController::class, 'cancelTransaction'])->name('transactions.cancel');
        Route::resource('transactions', App\Http\Controllers\Admin\TransactionController::class);
    });

    // Batch routes
    Route::name('admin.')->group(function () {
        Route::get('batches/data', [App\Http\Controllers\Admin\BatchController::class, 'data'])->name('batches.data');
        Route::get('batches/export', [App\Http\Controllers\Admin\BatchController::class, 'export'])->name('batches.export');
        Route::post('batches/{batch}/process-settlement', [App\Http\Controllers\Admin\BatchController::class, 'processSettlement'])->name('batches.process-settlement');
        Route::resource('batches', App\Http\Controllers\Admin\BatchController::class)->only(['index', 'show']);
    });

    // Settlement routes
    Route::name('admin.')->group(function () {
        Route::get('settlements/data', [App\Http\Controllers\Admin\SettlementController::class, 'data'])->name('settlements.data');
        Route::get('settlements/export', [App\Http\Controllers\Admin\SettlementController::class, 'export'])->name('settlements.export');
        Route::get('settlements/by-batch/{batch}', [App\Http\Controllers\Admin\SettlementController::class, 'byBatch'])->name('settlements.by-batch');
        Route::post('settlements/{settlement}/mark-as-settled', [App\Http\Controllers\Admin\SettlementController::class, 'markAsSettled'])->name('settlements.mark-as-settled');
        Route::post('settlements/{settlement}/mark-as-failed', [App\Http\Controllers\Admin\SettlementController::class, 'markAsFailed'])->name('settlements.mark-as-failed');
        Route::resource('settlements', App\Http\Controllers\Admin\SettlementController::class)->only(['index', 'show']);
    });

    // Logs routes
    Route::resource('logs', App\Http\Controllers\LogController::class);

    Route::name('admin.')->group(function () {
        Route::get('payment-links/data', [PaymentByLinkController::class, 'data'])->name('payment-links.data');
        Route::get('payment-links/export', [PaymentByLinkController::class, 'export'])->name('payment-links.export');
        Route::resource('payment-links', PaymentByLinkController::class);
        Route::get('payment-links/uuid/{uuid}', [PaymentByLinkController::class, 'showByUuid'])->name('payment-links.show-by-uuid');
        // Route::post('payment-links/{paymentLink}/mark-as-settled', [App\Http\Controllers\Admin\PaymentByLinkController::class, 'markAsSettled'])->name('payment-links.mark-as-settled');
        // Route::post('payment-links/{paymentLink}/mark-as-failed', [App\Http\Controllers\Admin\PaymentByLinkController::class, 'markAsFailed'])->name('payment-links.mark-as-failed');
        // Route::resource('settlements', App\Http\Controllers\Admin\SettlementController::class)->only(['index', 'show']);
    });

    Route::post('admin/payment-links/generate-stripe-session-url', [PaymentByLinkController::class, 'generateStripeSessionUrl'])->name('generateStripeSessionUrl');
    Route::post('payment-links/{payment_link}/update-date', [PaymentByLinkController::class, 'updateDate'])->name('admin.payment-links.update_date');
    Route::post('payment-links/{payment_link}/send', [PaymentByLinkController::class, 'send'])->name('admin.payment-links.send');

    Route::get('payment-links/demo-url', [App\Http\Controllers\PaymentByLinkController::class, 'demoUrl'])->name('payment-links.demo-url');

    Route::name('admin.')->group(function () {
        // Admin Customer Management Routes
        Route::get('customers/data', [App\Http\Controllers\Admin\AdminCustomerController::class, 'data'])->name('customers.data');
        Route::get('customers/export', [App\Http\Controllers\Admin\AdminCustomerController::class, 'export'])->name('customers.export');
        Route::get('customers/export-template', [App\Http\Controllers\Admin\AdminCustomerController::class, 'exportTemplate'])->name('customers.export-template');
        Route::post('customers/import-preview', [App\Http\Controllers\Admin\AdminCustomerController::class, 'importPreview'])->name('customers.import-preview');
        Route::post('customers/import', [App\Http\Controllers\Admin\AdminCustomerController::class, 'import'])->name('customers.import');
        Route::post('customers/ajax-store', [App\Http\Controllers\Admin\AdminCustomerController::class, 'storeAjax'])->name('customers.ajax.store');
        Route::get('customers/select', [App\Http\Controllers\Admin\AdminCustomerController::class, 'select'])->name('customers.select');
        Route::post('customers/bulk-delete', [App\Http\Controllers\Admin\AdminCustomerController::class, 'bulkDelete'])->name('customers.bulk-delete');
        Route::resource('customers', App\Http\Controllers\Admin\AdminCustomerController::class);

        // Admin Change Request Management Routes
        // Route::get('change-requests/data', [App\Http\Controllers\ChangeRequestController::class, 'data'])->name('change-requests.data');
        // Route::get('change-requests/{changeRequest}/details', [App\Http\Controllers\ChangeRequestController::class, 'getDetails'])->name('change-requests.details');
        // Route::post('change-requests/{changeRequest}/approve', [App\Http\Controllers\ChangeRequestController::class, 'approve'])->name('change-requests.approve');
        // Route::post('change-requests/{changeRequest}/reject', [App\Http\Controllers\ChangeRequestController::class, 'reject'])->name('change-requests.reject');
        // Route::resource('change-requests', App\Http\Controllers\ChangeRequestController::class)->only(['index', 'show']);

        // Admin Currency Management Routes
        // Route::name('admin.')->group(function () {
        Route::resource('currencies', CurrencyController::class);
        // });

        // Admin Service Fees Management Routes
        Route::get('service-fees/data', [App\Http\Controllers\ServiceFeeController::class, 'data'])->name('service-fees.data');
        Route::post('service-fees/bulk-delete', [App\Http\Controllers\ServiceFeeController::class, 'bulkDelete'])->name('service-fees.bulk-delete');
        Route::post('service-fees/import', [App\Http\Controllers\ServiceFeeController::class, 'import'])->name('service-fees.import');
        Route::get('service-fees/export-template', [App\Http\Controllers\ServiceFeeController::class, 'exportTemplate'])->name('service-fees.export-template');

        Route::resource('service-fees', App\Http\Controllers\ServiceFeeController::class);

        // Admin Advertisement Management Routes
        Route::get('advertisements/data', [App\Http\Controllers\Admin\AdminAdvertisementController::class, 'data'])->name('advertisements.data');
        Route::resource('advertisements', App\Http\Controllers\Admin\AdminAdvertisementController::class);
    });
});

Route::get('pay/{uuid}', [PaymentByLinkController::class, 'pay'])->name('payment-link.pay');


// Route::get('city/select' , [UserController::class, 'getCitySelect'])->name('city.select');
// File upload routes
Route::post('/api/upload/process', [App\Http\Controllers\FileUploadController::class, 'process'])->name('filepond.process');
Route::get('/api/upload/load/{id}', [App\Http\Controllers\FileUploadController::class, 'load'])->name('filepond.load');
Route::get('/api/upload/files/{merchantCode}', [App\Http\Controllers\FileUploadController::class, 'getFiles'])->name('filepond.files');
Route::post('/api/upload/cleanup', [App\Http\Controllers\FileUploadController::class, 'cleanup'])->name('filepond.cleanup');

require __DIR__ . '/auth.php';
require __DIR__ . '/oroute.php';

// Stripe webhook route (must be outside auth middleware)
Route::post('/stripe/webhook', [App\Http\Controllers\PaymentByLinkController::class, 'handleWebhook'])->name('stripe.webhook');

// Public payment link access route
// Route::get('/payment-link/{uuid}', [App\Http\Controllers\PaymentByLinkController::class, 'showPublic'])->name('payment-link.public');

// Payment result routes
Route::get('/payments/success', [App\Http\Controllers\PaymentByLinkController::class, 'success'])->name('payments.success');
Route::get('/payments/error', [App\Http\Controllers\PaymentByLinkController::class, 'error'])->name('payments.error');

