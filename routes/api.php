<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\MerchantController;
use App\Http\Controllers\Api\Admin\TerminalController;
use App\Http\Controllers\Api\TermainlController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\ValidationController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\TwilioTestController;
use App\Http\Controllers\Api\AdyenTestController;
use App\Http\Controllers\TerminalGroupController;
use App\Http\Controllers\DocumentationAccessController;
use App\Http\Controllers\MerchantTerminalGroupController;
use App\Http\Controllers\MerchantUserGroupController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\Api\AuthenticationApiController;
use App\Http\Controllers\Api\MerchantTerminalApiController;
use App\Http\Controllers\Api\MerchantPaymentLinkApiController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\V2\Admin\AdminPaymentLinkController;
use App\Http\Controllers\Api\V2\Admin\AdminServiceCategoryController;
use App\Http\Controllers\Api\V2\Admin\AdminServiceSubCategoryController;
use App\Http\Controllers\Api\V2\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V2\Admin\AdminTagController;
use App\Http\Controllers\Api\V2\Admin\AdminHomeScreenConfigurationController;
use App\Http\Middleware\ApiKeyAuthMiddleware;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Controllers\Api\GoogleOAuthController;
use App\Http\Controllers\Api\Cashier\AdminCustomerApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/contract-terms', [\App\Http\Controllers\Api\ContractTermsController::class, 'getContractTerms']);


// Route::get('test', function(){

//     return response()->json(auth()->user()->merchant);
// })->middleware('auth:api');
// });
// External Authentication API (for Pos system)
Route::prefix('v1')->group(function () {
    Route::post('/authenticate/verify', [AuthenticationApiController::class, 'verifyAuthentication']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Google OAuth (Socialite) — served by SoftPos; Payment SPA uses SOFTPOS_API_BASE for these URLs
Route::get('/oauth/google', [GoogleOAuthController::class, 'redirectToGoogle']);
Route::get('/oauth/google/callback', [GoogleOAuthController::class, 'handleCallback']);
Route::post('/oauth/google/exchange', [GoogleOAuthController::class, 'exchange']);

if (config('app.debug')) {
    Route::get('/oauth/google/debug', function () {
        return response()->json([
            'message' => 'Copy redirect_uri into Google Cloud → Credentials → your Web client → Authorized redirect URIs (exact match, no trailing slash).',
            'redirect_uri' => config('services.google.redirect'),
            'app_url' => config('app.url'),
        ]);
    });
}

// Password reset
Route::post('/password/request-reset', [\App\Http\Controllers\Api\PasswordResetController::class, 'requestReset']);
Route::post('/password/reset', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetPassword']);
Route::post('/merchants/register', [\App\Http\Controllers\Api\MerchantRegistrationController::class, 'register']);

// Countries and Cities routes
Route::get('/countries', [\App\Http\Controllers\Api\CountryController::class, 'index']);
Route::get('/countries/select', [\App\Http\Controllers\Api\CountryController::class, 'select']);
Route::get('/cities', [\App\Http\Controllers\Api\CityController::class, 'index']);
Route::get('/cities/select', [\App\Http\Controllers\Api\CityController::class, 'select']);
Route::get('/business-types', [\App\Http\Controllers\Api\BusinessTypeController::class, 'index']);
Route::get('/business-types/select', [\App\Http\Controllers\Api\BusinessTypeController::class, 'select']);

// Currency routes
Route::get('/currencies', [\App\Http\Controllers\Api\CurrencyController::class, 'index']);
Route::get('/currencies/select', [\App\Http\Controllers\Api\CurrencyController::class, 'select']);
Route::get('/currencies/popular', [\App\Http\Controllers\Api\CurrencyController::class, 'popular']);
Route::get('/currencies/by-code/{code}', [\App\Http\Controllers\Api\CurrencyController::class, 'getByCode']);

// Contract Terms routes (Public access - no middleware)
// Route::get('/contract-terms/all', [\App\Http\Controllers\Api\ContractTermsController::class, 'getAllContractTerms']);

// Public service categories select (no auth/JWT)
Route::get('/service-categories/select-public', [\App\Http\Controllers\Api\ServiceCategoryController::class, 'select']);

// Merchant file upload routes
Route::post('/upload-partner-compnay/profile', [\App\Http\Controllers\Api\PartnerFileUploadController::class, 'upload'])->middleware('auth:api');
Route::post('/upload-merchant-file', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'upload'])->middleware('auth:api');
Route::delete('/delete-merchant-file/{fileId}', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'delete']);
Route::post('/test-upload', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'testUpload']);
Route::get('/merchant-files', [\App\Http\Controllers\Api\MerchantFileUploadController::class, 'getMerchantFiles']);


// Twilio SMS Test routes
Route::prefix('twilio')->group(function () {
    Route::get('/test-config', [TwilioTestController::class, 'testConfiguration']);
    Route::post('/send-sms', [TwilioTestController::class, 'sendSms']);
    Route::post('/send-otp', [TwilioTestController::class, 'sendOtpSms']);
    Route::post('/message-status', [TwilioTestController::class, 'getMessageStatus']);
    Route::get('/account-info', [TwilioTestController::class, 'getAccountInfo']);
});

// Adyen Checkout test routes (enable with ADYEN_TEST_ROUTES_ENABLED=true)
Route::prefix('adyen')->group(function () {
    Route::get('/test/status', [AdyenTestController::class, 'status']);
    Route::post('/test/payments', [AdyenTestController::class, 'testPayments']);
});

// Terminal registration endpoint
Route::post('/terminals/register-device', [TermainlController::class, 'registerOrRetrieveTerminal']);

// Public POS transaction invoice (no auth, used by external POS receipt links)
Route::get('/pos/invoice/{encryptedId}', [PosController::class, 'posInvoicePublic']);
Route::get('/v2/pos/invoice/{encryptedId}', [PosController::class, 'posInvoicePublic']);
Route::get('/link-invoice/{uuid}', [PosController::class, 'linkInvoicePublic']);

// Public merchant profile by UUID (payment checkout “View merchant”, no auth)
Route::get('/merchants/public/{uuid}', [\App\Http\Controllers\Api\PublicMerchantController::class, 'show']);

// Public payment link fetch by UUID (no auth, used by external payment pages)
Route::prefix('payment-links')->group(function () {
    // Public: customers open payment links without a JWT token
    Route::get('/uuid/{uuid}', [App\Http\Controllers\Api\PaymentLinkController::class, 'showByUuid']);
    // Step 1 of the Stripe flow: creates a PaymentIntent and returns client_secret
    Route::post('/uuid/{uuid}/create-intent', [App\Http\Controllers\Api\PaymentLinkController::class, 'createPaymentIntent']);
    // Stripe.js PaymentMethod id → server confirms the PaymentIntent from create-intent (no raw card)
    Route::post('/uuid/{uuid}/confirm-intent', [App\Http\Controllers\Api\PaymentLinkController::class, 'confirmPaymentIntent']);
    // Server-side raw card (legacy / PCI-heavy)
    Route::post('/uuid/{uuid}/pay-card', [App\Http\Controllers\Api\PaymentLinkController::class, 'payWithCard']);
    // Public receipt for success page (amount, reference, merchant) — no auth
    Route::get('/uuid/{uuid}/receipt', [App\Http\Controllers\Api\PaymentLinkController::class, 'receiptByUuid']);
});

// Stripe webhook — must be outside CSRF/auth middleware
Route::post('/stripe/webhook', [App\Http\Controllers\Api\PaymentLinkController::class, 'handleWebhook']);

Route::post('v2/pos', [PosController::class, 'posV2'])->middleware('auth:api');
Route::post('v2/services/pos', [PosController::class, 'servicePosV2'])->middleware('auth:api');
Route::post('v2/qr-payment', [\App\Http\Controllers\Api\V3\QrCodePaymentController::class, 'processQrPayment'])->middleware('auth:api');

// Protected routes
Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/force-logout', [AuthController::class, 'forceLogout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::get('/profile/me', [AuthController::class, 'profileMe']);
        
        // Merchant terminals endpoint for dropdown
        Route::get('/merchant/terminals', [\App\Http\Controllers\MerchantTransactionController::class, 'getTerminals']);

        // Profile management
        Route::post('/profile/update', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
        Route::post('/profile/change-password', [\App\Http\Controllers\Api\ProfileController::class, 'changePassword']);

        Route::post('/pos', [PosController::class, 'pos']);
        Route::post('/getTermainlId', [TermainlController::class, 'getTermainlId']);
        Route::post('/terminals/link-terminal', [TermainlController::class, 'linkTerminal']);
        
        // POS Transaction routes
        Route::get('/pos/transactions', [PosController::class, 'getPosTransactions']);
        Route::get('/pos/transactions/{id}', [PosController::class, 'getPosTransaction']);
        
        // Terminal routes
        Route::post('/terminals/register', [PosController::class, 'registerTerminal']);
        
        // Transaction routes
        Route::post('/transactions', [PosController::class, 'createTransaction']);
        Route::get('/transactions', [PosController::class, 'getTransactions']);
        Route::get('/transactions/{id}', [PosController::class, 'getTransaction']);
        Route::put('/transactions/{id}', [PosController::class, 'updateTransaction']);
        Route::delete('/transactions/{id}', [PosController::class, 'deleteTransaction']);
        
        // Transaction operations with logging
        Route::post('/transactions/refund', [TransactionController::class, 'refund']);
        Route::post('/transactions/partial-refund', [TransactionController::class, 'partialRefund']);
        Route::post('/transactions/void', [TransactionController::class, 'void']);
        Route::post('/transactions/cancel', [TransactionController::class, 'cancel']);
        Route::get('/transactions/{id}/audit-trail', [TransactionController::class, 'auditTrail']);
        Route::get('/transactions/{id}/history', [TransactionController::class, 'history']);
        Route::get('/transactions/{id}/refund-details', [TransactionController::class, 'refundDetails']);
        Route::get('/transactions/{id}/refund-history', [TransactionController::class, 'refundHistory']);
        
        // Transaction reporting
        Route::get('/transactions/summary', [TransactionController::class, 'summary']);
        Route::get('/transactions/refunded', [TransactionController::class, 'refundedTransactions']);
        Route::get('/transactions/voided', [TransactionController::class, 'voidedTransactions']);
        Route::get('/transactions/cancelled', [TransactionController::class, 'cancelledTransactions']);
        
        // Dashboard routes (v1 - legacy)
        Route::prefix('dashboard')->group(function () {
            // Comprehensive dashboard data for React component
            Route::get('/', [\App\Http\Controllers\MerchantDashboardController::class, 'getDashboardDataApi']);
            
            Route::get('/chart/daily', [\App\Http\Controllers\Api\DailyTransactionApiController::class, 'index']);
            Route::get('/chart/weekly', [\App\Http\Controllers\Api\WeeklyTransactionApiController::class, 'index']);
            Route::get('/chart/monthly', [\App\Http\Controllers\Api\MonthlyTransactionApiController::class, 'index']);
            
            // Merchant dashboard statistics route
            Route::get('/statistics', [\App\Http\Controllers\ApiMerchantDashboardControler::class, 'index']);
            
            // Transaction statistics routes
            Route::get('/statistics/daily', [\App\Http\Controllers\ApiMerchantDashboardControler::class, 'getDailyStatisticsApi']);
            Route::get('/statistics/weekly', [\App\Http\Controllers\ApiMerchantDashboardControler::class, 'getWeeklyStatisticsApi']);
            Route::get('/statistics/monthly', [\App\Http\Controllers\ApiMerchantDashboardControler::class, 'getMonthlyStatisticsApi']);
            Route::get('/statistics/summary', [\App\Http\Controllers\ApiMerchantDashboardControler::class, 'getTransactionSummaryApi']);
            Route::get('/statistics/custom', [\App\Http\Controllers\ApiMerchantDashboardControler::class, 'getCustomStatisticsApi']);
        });

        // Dashboard routes v2 (React dashboard)
        

        // Admin Dashboard API routes
        Route::prefix('admin')->middleware('auth:api')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\AdminDashboardController::class, 'index']);
            Route::get('/dashboard/statistics', [\App\Http\Controllers\AdminDashboardController::class, 'getTransactionStatisticsApi']);
            Route::get('/dashboard/statistics/daily', [\App\Http\Controllers\AdminDashboardController::class, 'getDailyStatisticsApi']);
            Route::get('/dashboard/statistics/weekly', [\App\Http\Controllers\AdminDashboardController::class, 'getWeeklyStatisticsApi']);
            Route::get('/dashboard/statistics/monthly', [\App\Http\Controllers\AdminDashboardController::class, 'getMonthlyStatisticsApi']);
            Route::get('/dashboard/statistics/summary', [\App\Http\Controllers\AdminDashboardController::class, 'getTransactionSummaryApi']);
            Route::get('/dashboard/merchant/{merchantId}/statistics', [\App\Http\Controllers\AdminDashboardController::class, 'getMerchantStatisticsApi']);
        });

        // Batch routes
        Route::get('/batches', [BatchController::class, 'index']);
        Route::get('/batches/summary', [BatchController::class, 'summary']);
        Route::get('/batches/{batch}', [BatchController::class, 'show']);

        // Settlement routes
        Route::get('/settlements', [SettlementController::class, 'index']);
        Route::get('/settlements/{id}', [SettlementController::class, 'show']);
    
        // V1 API Routes
        Route::prefix('v1')->group(function () {
            // API Key routes
            Route::prefix('api-keys')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\ApiKeyController::class, 'index']);
                Route::post('/generate', [\App\Http\Controllers\Api\ApiKeyController::class, 'generate']);
                Route::post('/{id}/regenerate', [\App\Http\Controllers\Api\ApiKeyController::class, 'regenerate']);
                Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\ApiKeyController::class, 'deactivate']);
            });

            // Webhook routes
            Route::prefix('webhooks')->group(function () {
                // Get available webhook events
                Route::get('/events', [\App\Http\Controllers\Api\WebhookController::class, 'getAvailableEvents']);
                
                // CRUD operations
                Route::get('/', [\App\Http\Controllers\Api\WebhookController::class, 'index']);
                Route::post('/', [\App\Http\Controllers\Api\WebhookController::class, 'store']);
                Route::get('/{id}', [\App\Http\Controllers\Api\WebhookController::class, 'show']);
                Route::put('/{id}', [\App\Http\Controllers\Api\WebhookController::class, 'update']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\WebhookController::class, 'destroy']);
                
                // Additional actions
                Route::post('/{id}/toggle', [\App\Http\Controllers\Api\WebhookController::class, 'toggle']);
                Route::post('/{id}/regenerate-secret', [\App\Http\Controllers\Api\WebhookController::class, 'regenerateSecret']);
                Route::get('/{id}/logs', [\App\Http\Controllers\Api\WebhookController::class, 'logs']);
            });
        });

        Route::prefix('customers')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\CustomerController::class, 'index']);
            Route::get('/list', [App\Http\Controllers\Api\CustomerController::class, 'list']);
            Route::post('/', [App\Http\Controllers\Api\CustomerController::class, 'store']);
            Route::get('/{id}', [App\Http\Controllers\Api\CustomerController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Api\CustomerController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\CustomerController::class, 'destroy']);
        });

        // Payment Links API Routes (protected - dashboard/merchant actions)
        Route::prefix('payment-links')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\PaymentLinkController::class, 'index']);
            Route::get('/statistics', [App\Http\Controllers\Api\PaymentLinkController::class, 'statistics']);
            Route::post('/', [App\Http\Controllers\Api\PaymentLinkController::class, 'store']);
            Route::post('/pos', [App\Http\Controllers\Api\PaymentLinkController::class, 'storePosPaymentLink']);
            Route::get('/{id}', [App\Http\Controllers\Api\PaymentLinkController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\Api\PaymentLinkController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\PaymentLinkController::class, 'destroy']);
            Route::post('/{id}/cancel', [App\Http\Controllers\Api\PaymentLinkController::class, 'cancel']);
            Route::post('/{id}/update-date', [App\Http\Controllers\Api\PaymentLinkController::class, 'updateDate']);
            Route::post('/{id}/send', [App\Http\Controllers\Api\PaymentLinkController::class, 'send']);
            Route::post('/generate-stripe-session', [App\Http\Controllers\Api\PaymentLinkController::class, 'generateStripeSessionUrl']);
        });

    // Advertisements API Routes
    Route::get('/advertisements', [App\Http\Controllers\Api\AdvertisementController::class, 'getByCountry']);
    Route::get('/v1/advertisements', [App\Http\Controllers\Api\AdvertisementController::class, 'getByCountry']);
});

Route::post('/stripe/webhook', [\App\Http\Controllers\PaymentByLinkController::class, 'handleWebhook']);

// V2 Admin Authentication Routes (Public)
Route::prefix('v2/admin/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V2\Admin\AdminAuthController::class, 'login']);
});

// Admin API routes
Route::prefix('admin')->middleware('auth:admin')->group(function () {
        // Admin transaction chart data routes
        Route::get('/transactions/chart/daily/{merchantId}', [\App\Http\Controllers\Api\DailyTransactionApiController::class, 'show']);
        Route::get('/transactions/chart/weekly/{merchantId}', [\App\Http\Controllers\Api\WeeklyTransactionApiController::class, 'show']);
        Route::get('/transactions/chart/monthly/{merchantId}', [\App\Http\Controllers\Api\MonthlyTransactionApiController::class, 'show']);
        
        // Admin transaction statistics routes
        Route::get('/transactions/statistics/{merchantId}', [\App\Http\Controllers\MerchantDashboardController::class, 'getMerchantStatisticsApi']);
        
        // Hierarchical filtering endpoints
        Route::post('/admin/terminals/models-by-brands', [TerminalController::class, 'getModelsByBrands']);
        Route::post('/admin/terminals/manufacturers-by-models', [TerminalController::class, 'getManufacturersByModels']);

        // Terminal Groups endpoints
        Route::get('/merchant/terminal-groups/parent-groups', [MerchantTerminalGroupController::class, 'getParentGroups']);

        // Add individual terminal group endpoints for edit functionality
        Route::get('/admin/terminal-groups/{terminalGroup}', [TerminalGroupController::class, 'showApi']);
        Route::get('/merchant/terminal-groups/{terminalGroup}', [MerchantTerminalGroupController::class, 'showApi']);

        // Merchant API routes
        Route::prefix('merchant')->group(function () {
            // User Groups routes
            Route::get('/user-groups/select', [MerchantUserGroupController::class, 'select']);
            
            // Terminal Groups routes
            Route::get('/terminal-groups/parent-groups', [MerchantTerminalGroupController::class, 'getParentGroups']);
    });
});

Route::get('/admin/terminals', [TerminalController::class, 'select']);
Route::get('/admin/brands', [TerminalController::class, 'getBrands']);
Route::get('/admin/terminals/brands', [TerminalController::class, 'getTermainlsbrands']);
Route::get('/admin/terminals/filters', [TerminalController::class, 'getTermainlsWithFilters']);
Route::get('/admin/terminals/models', [TerminalController::class, 'getTermainlsmodels']);
Route::get('/admin/terminals/manufacturers', [TerminalController::class, 'getTermainlsmanufacturers']);

Route::get('/admin/terminal-groups/parent-groups', [TerminalGroupController::class, 'getParentGroups']);

// Hierarchical filtering endpoints
Route::post('/admin/terminals/models-by-brands', [TerminalController::class, 'getModelsByBrands']);
Route::post('/admin/terminals/manufacturers-by-models', [TerminalController::class, 'getManufacturersByModels']);

// Admin Users API: select and show
Route::get('/admin/users/select', [UserController::class, 'select']);
Route::get('/admin/users/{user}', [UserController::class, 'show']);

// Admin User Groups API: select
Route::get('/admin/user-groups/select', [UserGroupController::class, 'select'])->middleware('auth:admin-api');

Route::prefix('v1/merchant')->group(function () {
    Route::prefix('topup')->group(function () {
        Route::post('/prepaid', [\App\Http\Controllers\Api\TopupTestController::class, 'prepaid']);
        Route::post('/postpaid', [\App\Http\Controllers\Api\TopupTestController::class, 'postpaid']);
        Route::post('/invoice', [\App\Http\Controllers\Api\TopupTestController::class, 'invoice']);
    });
});

// Merchant Terminal API Routes
Route::prefix('v1/merchant')->middleware(['auth:api'])->group(function () {
    // One-shot merchant catalog for client-side navigation:
    // services -> products -> forms (with fields)
    Route::get('/services/catalog', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'catalog']);
    Route::get('/services/home', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'homeServices']);
    Route::get('/services/{id}', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'serviceDetails']);
    Route::get('/partners/{id}', [\App\Http\Controllers\Api\MerchantPartnerPublicController::class, 'show']);
    Route::prefix('terminals')->group(function () {
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
    
    // Payment Links API Routes
    Route::prefix('payment-links')->group(function () {
        Route::get('/', [MerchantPaymentLinkApiController::class, 'index']);
        Route::get('/statistics', [MerchantPaymentLinkApiController::class, 'statistics']);
        Route::get('/export', [MerchantPaymentLinkApiController::class, 'export']);
        Route::post('/bulk-delete', [MerchantPaymentLinkApiController::class, 'bulkDelete']);
        Route::get('/{paymentLink}', [MerchantPaymentLinkApiController::class, 'show']);
        Route::post('/', [MerchantPaymentLinkApiController::class, 'store']);
        Route::put('/{paymentLink}', [MerchantPaymentLinkApiController::class, 'update']);
        Route::delete('/{paymentLink}', [MerchantPaymentLinkApiController::class, 'destroy']);
        Route::post('/{paymentLink}/update-date', [MerchantPaymentLinkApiController::class, 'updateDate']);
        Route::post('/{paymentLink}/send', [MerchantPaymentLinkApiController::class, 'send']);
    });
    
    // Transactions API Routes
    Route::prefix('transactions')->group(function () {
        Route::get('/data', [\App\Http\Controllers\MerchantTransactionController::class, 'data']);
        Route::get('/statistics', [\App\Http\Controllers\MerchantTransactionController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\MerchantTransactionController::class, 'export']);
        Route::post('/bulk-delete', [\App\Http\Controllers\MerchantTransactionController::class, 'bulkDelete']);
        Route::get('/{transaction}', [\App\Http\Controllers\MerchantTransactionController::class, 'show']);
        Route::post('/{transaction}/void', [\App\Http\Controllers\MerchantTransactionController::class, 'voidTransaction']);
        Route::post('/{transaction}/refund', [\App\Http\Controllers\MerchantTransactionController::class, 'refundTransaction']);
        Route::post('/{transaction}/send-receipt', [\App\Http\Controllers\MerchantTransactionController::class, 'sendReceipt']);
        Route::get('/{transaction}/receipt', [\App\Http\Controllers\MerchantTransactionController::class, 'receipt']);
    });
    
    // Settlements API Routes
    Route::prefix('settlements')->group(function () {
        Route::get('/data', [\App\Http\Controllers\MerchantSettlementController::class, 'data']);
        Route::get('/statistics', [\App\Http\Controllers\MerchantSettlementController::class, 'statistics']);
        Route::get('/transactions/data', [\App\Http\Controllers\MerchantSettlementController::class, 'transactionsData']);
        Route::get('/transactions/statistics', [\App\Http\Controllers\MerchantSettlementController::class, 'transactionsStatistics']);
        Route::get('/{settlement}', [\App\Http\Controllers\MerchantSettlementController::class, 'show']);
    });
    
    // Batches API Routes
    Route::prefix('batches')->group(function () {
        Route::get('/data', [\App\Http\Controllers\MerchantBatchController::class, 'data']);
        Route::get('/statistics', [\App\Http\Controllers\MerchantBatchController::class, 'statistics']);
        Route::get('/{batch}', [\App\Http\Controllers\MerchantBatchController::class, 'show']);
    });

    // Dashboard API Routes (V2)
    Route::prefix('dashboard')->group(function () {
        // Comprehensive dashboard data for React component
        Route::get('/', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'getDashboardData']);
        Route::get('/latest-transactions', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'getLatestTransactions']);
        
        // Dashboard export
        Route::get('/export', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'export']);
        
        // Transaction chart data routes
        Route::get('/chart/daily', [\App\Http\Controllers\Api\V2\Merchant\MerchantDailyTransactionV2Controller::class, 'index']);
        Route::get('/chart/weekly', [\App\Http\Controllers\Api\V2\Merchant\MerchantWeeklyTransactionV2Controller::class, 'index']);
        Route::get('/chart/monthly', [\App\Http\Controllers\Api\V2\Merchant\MerchantMonthlyTransactionV2Controller::class, 'index']);
        
        // Dashboard statistics routes
        Route::get('/statistics', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardStatisticsV2Controller::class, 'index']);
        Route::get('/statistics/daily', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardStatisticsV2Controller::class, 'getDailyStatistics']);
        Route::get('/statistics/weekly', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardStatisticsV2Controller::class, 'getWeeklyStatistics']);
        Route::get('/statistics/monthly', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardStatisticsV2Controller::class, 'getMonthlyStatistics']);
        Route::get('/statistics/summary', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardStatisticsV2Controller::class, 'getTransactionSummary']);
        Route::get('/statistics/custom', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardStatisticsV2Controller::class, 'getCustomStatistics']);
    });
    
});


Route::prefix('v2/merchant/dashboard')->middleware(['auth:api'])->group(function () {
    // Comprehensive dashboard data for React component
    Route::get('/', [\App\Http\Controllers\MerchantDashboardController::class, 'getDashboardDataApi']);
    
    // Separate dashboard endpoints
    Route::get('/statistics', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'getStatistics']);
    Route::get('/charts', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'getCharts']);
    Route::get('/latest-transactions', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'getLatestTransactions']);
    
    // Dashboard export (using V2 controller)
    Route::get('/export', [\App\Http\Controllers\Api\V2\Merchant\MerchantDashboardV2Controller::class, 'export']);
});

Route::prefix('v2/merchant')->middleware(['auth:api'])->group(function () {
    Route::get('/services/catalog', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'catalogV2']);
    Route::get('/services/home', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'homeServicesV2']);
});

// V2 Admin API Routes (SoftPos Only - Dashboard & Transactions)
// Note: Auth/Merchants/Branches/Users/Terminals moved to AuthService
// Note: Customers moved to Pos
Route::prefix('v2/admin')->middleware([
    'auth:admin-api'
])->group(function () {
    Route::prefix('services')->group(function () {
        Route::get('/catalog', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'catalogV2']);
        Route::get('/home', [\App\Http\Controllers\Api\MerchantHomeCategoryController::class, 'homeServicesV2']);
    });

    
    // Dashboard (aggregates data from AuthService and Pos)
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminDashboardController::class, 'index']);
        Route::get('/export', [\App\Http\Controllers\Api\V2\Admin\AdminDashboardController::class, 'export']);
        Route::get('/charts', [\App\Http\Controllers\Api\V2\Admin\AdminDashboardController::class, 'charts']);
        Route::get('/latest-transactions', [\App\Http\Controllers\Api\V2\Admin\AdminDashboardController::class, 'latestTransactions']);
    });

    Route::prefix('payment-links')->group(function () {
        Route::get('/', [AdminPaymentLinkController::class, 'index']);
        Route::get('/statistics', [AdminPaymentLinkController::class, 'statistics']);
        Route::get('/export', [AdminPaymentLinkController::class, 'export']);
        Route::get('/{id}', [AdminPaymentLinkController::class, 'show']);
    });

    // Transactions (local to SoftPos)
    Route::prefix('transactions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'export']);
        Route::post('/bulk-delete', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'bulkDelete']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'show']);
        Route::post('/{id}/refund', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'refund']);
        Route::post('/{id}/void', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'void']);
        Route::post('/{id}/send-receipt', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'sendReceipt']);
        Route::get('/{id}/receipt', [\App\Http\Controllers\Api\V2\Admin\AdminTransactionController::class, 'receipt']);
    });

    Route::prefix('service-transactions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminServiceTransactionController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminServiceTransactionController::class, 'show']);
    });

    // Settlements (local to SoftPos)
    Route::prefix('settlements')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminSettlementController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\V2\Admin\AdminSettlementController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\Api\V2\Admin\AdminSettlementController::class, 'export']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminSettlementController::class, 'show']);
    });

    // Batches (local to SoftPos)
    Route::prefix('batches')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminBatchController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\V2\Admin\AdminBatchController::class, 'statistics']);
        Route::get('/export', [\App\Http\Controllers\Api\V2\Admin\AdminBatchController::class, 'export']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminBatchController::class, 'show']);
        Route::post('/{id}/process-settlement', [\App\Http\Controllers\Api\V2\Admin\AdminBatchController::class, 'processSettlement']);
    });

    // Tags (local to SoftPos)
    Route::prefix('tags')->group(function () {
        Route::get('/', [AdminTagController::class, 'index']);
        Route::get('/statistics', [AdminTagController::class, 'statistics']);
        Route::get('/export', [AdminTagController::class, 'export']);
        Route::post('/', [AdminTagController::class, 'store']);
        Route::get('/{id}', [AdminTagController::class, 'show']);
        Route::put('/{id}', [AdminTagController::class, 'update']);
        Route::delete('/{id}', [AdminTagController::class, 'destroy']);
    });

    // Notification management
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
});

// Admin customer API (Payment admin dashboard)
Route::prefix('v2/admin')->middleware(['auth:admin-api'])->group(function () {
    Route::get('customers/export', [AdminCustomerApiController::class, 'export']);
    Route::get('customers/export-template', [AdminCustomerApiController::class, 'exportTemplate']);
    Route::post('customers/import-preview', [AdminCustomerApiController::class, 'importPreview']);
    Route::post('customers/import', [AdminCustomerApiController::class, 'import']);
    Route::get('customers', [AdminCustomerApiController::class, 'index']);
    Route::get('customers/{id}/wallet', [AdminCustomerApiController::class, 'wallet']);
    Route::get('customers/{id}/transactions', [AdminCustomerApiController::class, 'transactions']);
    Route::get('customers/{id}', [AdminCustomerApiController::class, 'show']);
    Route::post('customers', [AdminCustomerApiController::class, 'store']);
    Route::put('customers/{id}', [AdminCustomerApiController::class, 'update']);
    Route::delete('customers/{id}', [AdminCustomerApiController::class, 'destroy']);
    Route::post('customers/bulk-delete', [AdminCustomerApiController::class, 'bulkDelete']);
    Route::post('customers/{id}/status', [AdminCustomerApiController::class, 'updateStatus']);
    Route::post('customers/{id}/toggle-status', [AdminCustomerApiController::class, 'toggleStatus']);
    Route::post('customers/{id}/resend-password-invite', [AdminCustomerApiController::class, 'resendPasswordInvite']);

    Route::prefix('wallets')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'index']);
        Route::get('/export', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'export']);
        Route::get('/transactions/export', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'exportTransactions']);
        Route::get('/transactions/{transactionId}', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'showTransaction']);
        Route::get('/transactions', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'allTransactions']);
        Route::post('/opening-capital', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'openingCapital']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'show']);
        Route::get('/{id}/transactions', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'transactions']);
        Route::post('/{id}/cash-in', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'cashIn']);
        Route::post('/{id}/cash-out', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'cashOut']);
        Route::post('/{id}/suspend', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'suspend']);
        Route::post('/{id}/activate', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'activate']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminWalletController::class, 'destroy']);
    });

    Route::prefix('accounting')->group(function () {
        Route::get('account-types', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'types']);
        Route::get('chart-of-accounts/next-code', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'nextCode']);
        Route::get('chart-of-accounts/export', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'export']);
        Route::get('chart-of-accounts/sample', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'sample']);
        Route::post('chart-of-accounts/import', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'import']);
        Route::get('chart-of-accounts', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'index']);
        Route::post('chart-of-accounts', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'store']);
        Route::get('chart-of-accounts/{id}', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'show']);
        Route::put('chart-of-accounts/{id}', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'update']);
        Route::delete('chart-of-accounts/{id}', [\App\Modules\Accounting\Controllers\ChartOfAccountController::class, 'destroy']);
        Route::get('ledger/export', [\App\Modules\Accounting\Controllers\LedgerController::class, 'export']);
        Route::get('ledger/customers', [\App\Modules\Accounting\Controllers\LedgerController::class, 'customers']);
        Route::get('ledger', [\App\Modules\Accounting\Controllers\LedgerController::class, 'index']);
        Route::get('reports/balance-sheet/export', [\App\Modules\Accounting\Controllers\ReportController::class, 'balanceSheetExport']);
        Route::get('reports/balance-sheet', [\App\Modules\Accounting\Controllers\ReportController::class, 'balanceSheet']);
        Route::get('reports/profit-loss/export', [\App\Modules\Accounting\Controllers\ReportController::class, 'profitLossExport']);
        Route::get('reports/profit-loss', [\App\Modules\Accounting\Controllers\ReportController::class, 'profitLoss']);
        Route::get('reports/trial-balance', [\App\Modules\Accounting\Controllers\ReportController::class, 'trialBalance']);
    });
});

// V3 Admin API Routes
Route::prefix('v3/admin')->middleware([
    'auth:admin-api'
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\V2\Admin\AdminDashboardController::class, 'v3Dashboard']);
});

Route::prefix('register')->group(function () {
    Route::post('/validate-details', [ValidationController::class, 'validateDetails']);
    Route::post('/send-verification-code', [VerificationController::class, 'sendVerificationCode']);
    Route::post('/verify-code', [VerificationController::class, 'verifyCode']);
    Route::get('/merchant/send-continuation-email', [VerificationController::class, 'sendMerchantContinuationEmail'])->middleware('auth:api');
    Route::get('/partner/send-continuation-email', [VerificationController::class, 'sendPartnerContinuationEmail'])->middleware('auth:api');
    Route::post('/user', [RegisterController::class, 'register']);
    Route::post('/merchant', [RegisterController::class, 'registerMerchant'])->middleware('auth:api');
    Route::put('/merchant/update', [RegisterController::class, 'updateMerchant'])->middleware('auth:api');
    Route::post('/partner', [RegisterController::class, 'registerPartner'])->middleware('auth:api');
});

Route::prefix('admin')->group(function () {
    Route::get('/countries', [\App\Http\Controllers\Api\CountryController::class, 'index']);
    Route::get('/cities', [\App\Http\Controllers\Api\CityController::class, 'index']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Protected routes (Auth0 authentication required)
Route::middleware('auth:api')->group(function () {
    
    // Documentation access check endpoint
    Route::get('/docs/access', [DocumentationAccessController::class, 'checkAccess']);
    
    // Customers API Routes
    

    
    
    // Public payment link routes (no authentication required)
    Route::prefix('payment-links')->group(function () {
        Route::get('/public/{uuid}', [App\Http\Controllers\Api\PaymentLinkController::class, 'showPublic']);
        Route::get('/pay/{uuid}', [App\Http\Controllers\Api\PaymentLinkController::class, 'pay']);
        Route::post('/webhook', [App\Http\Controllers\Api\PaymentLinkController::class, 'handleWebhook']);
    });
    
    // Add other protected API routes here
    // Route::get('/softpos/profile', [ProfileController::class, 'getProfile']);
    // Route::get('/softpos/users', [UserController::class, 'index']);
});

// ============================================================================
// V3 API - API Key Authentication (for external integrations)
// ============================================================================
Route::prefix('v3')->middleware([ApiKeyAuthMiddleware::class])->group(function () {
    
    // *** MOST IMPORTANT *** - Create/Store Transaction (POS)
    Route::post('/pos', [PosController::class, 'posV2']);
    
    // Transaction Routes
    Route::prefix('transactions')->group(function () {
        // Create Transaction (alternative endpoint)
        Route::post('/', [PosController::class, 'createTransaction']);
        Route::post('/create', [PosController::class, 'posV2']);
        
        // List & Search
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/total-amount', [TransactionController::class, 'getTotalTransactionAmount']);
        Route::get('/summary', [TransactionController::class, 'summary']);
        
        // Single Transaction
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::get('/{id}/details', [TransactionController::class, 'show']);
        
        // Transaction Operations
        Route::post('/refund', [TransactionController::class, 'refund']);
        Route::post('/partial-refund', [TransactionController::class, 'partialRefund']);
        Route::post('/void', [TransactionController::class, 'void']);
        Route::post('/cancel', [TransactionController::class, 'cancel']);
        
        // Transaction History & Audit
        Route::get('/{id}/audit-trail', [TransactionController::class, 'auditTrail']);
        Route::get('/{id}/history', [TransactionController::class, 'history']);
        Route::get('/{id}/refund-details', [TransactionController::class, 'refundDetails']);
        Route::get('/{id}/refund-history', [TransactionController::class, 'refundHistory']);
        
        // Transaction Reporting
        Route::get('/refunded/list', [TransactionController::class, 'refundedTransactions']);
        Route::get('/voided/list', [TransactionController::class, 'voidedTransactions']);
        Route::get('/cancelled/list', [TransactionController::class, 'cancelledTransactions']);
    });
    
    // Settlement Routes
    Route::prefix('settlements')->group(function () {
        // List Settlements
        Route::get('/', [SettlementController::class, 'index']);
        Route::get('/summary', [SettlementController::class, 'summary']);
        
        // Single Settlement
        Route::get('/{id}', [SettlementController::class, 'show']);
        Route::get('/{id}/details', [SettlementController::class, 'show']);
        
        // Settlement Transactions
        Route::get('/{id}/transactions', [SettlementController::class, 'transactions']);
    });
    
    // Batch Routes
    Route::prefix('batches')->group(function () {
        // List Batches
        Route::get('/', [BatchController::class, 'index']);
        Route::get('/summary', [BatchController::class, 'summary']);
        
        // Single Batch
        Route::get('/{batch}', [BatchController::class, 'show']);
        Route::get('/{batch}/details', [BatchController::class, 'show']);
        
        // Batch Transactions
        Route::get('/{batch}/transactions', [BatchController::class, 'transactions']);
    });
    
    // Payment Link Routes
    Route::prefix('payment-links')->group(function () {
        // Create Payment Link (for POS - uses API Key auth, no Auth::user() needed)
        Route::post('/', [\App\Http\Controllers\Api\PaymentLinkController::class, 'storeV3PosPaymentLink']);
        
        // List Payment Links
        Route::get('/', [\App\Http\Controllers\Api\PaymentLinkController::class, 'index']);
        Route::get('/statistics', [\App\Http\Controllers\Api\PaymentLinkController::class, 'statistics']);
        
        // Single Payment Link
        Route::get('/{id}', [\App\Http\Controllers\Api\PaymentLinkController::class, 'show']);
        Route::get('/{id}/details', [\App\Http\Controllers\Api\PaymentLinkController::class, 'show']);
        
        // Update Payment Link
        Route::put('/{id}', [\App\Http\Controllers\Api\PaymentLinkController::class, 'update']);
        Route::patch('/{id}', [\App\Http\Controllers\Api\PaymentLinkController::class, 'update']);
        
        // Payment Link Operations
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\PaymentLinkController::class, 'cancel']);
        Route::post('/{id}/send', [\App\Http\Controllers\Api\PaymentLinkController::class, 'send']);
        Route::post('/{id}/update-date', [\App\Http\Controllers\Api\PaymentLinkController::class, 'updateDate']);
        
        // Delete Payment Link
        Route::delete('/{id}', [\App\Http\Controllers\Api\PaymentLinkController::class, 'destroy']);
    });
    
    // ============================================================================
    // QR Code Payment Routes (V3)
    // ============================================================================
    Route::prefix('qr-payment')->group(function () {
        // Process QR Code Payment
        Route::post('/', [\App\Http\Controllers\Api\V3\QrCodePaymentController::class, 'processQrPayment']);
        Route::post('/process', [\App\Http\Controllers\Api\V3\QrCodePaymentController::class, 'processQrPayment']);
        
        // List QR Transactions
        Route::get('/transactions', [\App\Http\Controllers\Api\V3\QrCodePaymentController::class, 'getQrTransactions']);
        
        // Single QR Transaction
        Route::get('/transactions/{id}', [\App\Http\Controllers\Api\V3\QrCodePaymentController::class, 'getQrTransaction']);
    });
    
    
});

// Service Types (Admin) — dropdown; kept unversioned for legacy frontend path `/api/service-types/select`
Route::middleware('auth:admin-api')->prefix('service-types')->group(function () {
    Route::get('/select', [\App\Http\Controllers\Api\V2\Admin\AdminServiceTypeController::class, 'select']);
});

Route::prefix('v1/admin')->group(function () {
    Route::get('service-categories/select-public', [\App\Http\Controllers\Api\ServiceCategoryController::class, 'select']);

    Route::middleware('auth:admin-api')->group(function () {
// Partners
Route::prefix('partners')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'index']);
    Route::get('/select', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'select']);
    Route::get('/scopes', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'scopes']);
    Route::get('/statistics', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'statistics']);
    Route::get('/export', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'export']);
    Route::get('/export-template', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'exportTemplate']);
    Route::post('/import-preview', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'importPreview']);
    Route::post('/import', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'import']);
    Route::post('/country-lookup', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'countryLookup']);
    Route::post('/bulk-delete', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'bulkDelete']);
    Route::post('/{id}/approve', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'approve']);
    Route::post('/{id}/reject', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'reject']);
    Route::post('/{id}/suspend', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'suspend']);
    Route::post('/{id}/unsuspend', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'unsuspend']);
    Route::get('/{id}/logs', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'logs']);
    Route::get('/{id}/change-requests', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'changeRequests']);
    Route::post('/{id}/change-requests/{changeRequest}/approve', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'approveChangeRequest']);
    Route::post('/{id}/change-requests/{changeRequest}/reject', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'rejectChangeRequest']);
    Route::get('/{parentId}/sub-partners', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerSubPartnerController::class, 'index']);
    Route::post('/{parentId}/sub-partners', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerSubPartnerController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminContentProviderController::class, 'show']);
    Route::post('/', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\V2\Admin\AdminPartnerController::class, 'destroy']);
});

// Service Categories Management (Admin)
Route::prefix('service-categories')->group(function () {
    Route::get('/', [AdminServiceCategoryController::class, 'index']);
    Route::post('/', [AdminServiceCategoryController::class, 'store']);
    Route::get('/select', [AdminServiceCategoryController::class, 'select']);
    Route::post('/bulk-delete', [AdminServiceCategoryController::class, 'bulkDelete']);
    Route::get('/export', [AdminServiceCategoryController::class, 'export']);
    Route::patch('/{id}/toggle-status', [AdminServiceCategoryController::class, 'toggleStatus']);
    Route::get('/{id}', [AdminServiceCategoryController::class, 'show']);
    Route::put('/{id}', [AdminServiceCategoryController::class, 'update']);
    Route::delete('/{id}', [AdminServiceCategoryController::class, 'destroy']);
});

// Service Sub-Categories Management (Admin)
Route::prefix('service-sub-categories')->group(function () {
    Route::get('/', [AdminServiceSubCategoryController::class, 'index']);
    Route::post('/', [AdminServiceSubCategoryController::class, 'store']);
    Route::get('/select', [AdminServiceSubCategoryController::class, 'select']);
    Route::post('/bulk-delete', [AdminServiceSubCategoryController::class, 'bulkDelete']);
    Route::patch('/{id}/toggle-status', [AdminServiceSubCategoryController::class, 'toggleStatus']);
    Route::get('/{id}', [AdminServiceSubCategoryController::class, 'show']);
    Route::put('/{id}', [AdminServiceSubCategoryController::class, 'update']);
    Route::delete('/{id}', [AdminServiceSubCategoryController::class, 'destroy']);
});

// Services Management (Admin)
Route::prefix('services')->group(function () {
    Route::get('/catalog', [\App\Http\Controllers\Api\MerchantServiceController::class, 'catalogForAdmin']);
    Route::get('/home-screen-config', [AdminHomeScreenConfigurationController::class, 'index']);
    Route::get('/home-screen-config/search', [AdminHomeScreenConfigurationController::class, 'search']);
    Route::put('/home-screen-config', [AdminHomeScreenConfigurationController::class, 'update']);
    Route::get('/', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'store']);
    Route::get('/select', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'selectOptions']);
    Route::post('/bulk-delete', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'bulkDelete']);
    Route::get('/export', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'export']);
    Route::post('/import', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'import']);
    Route::patch('/{id}/toggle-status', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'toggleStatus']);
    Route::get('/{id}', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\V2\Admin\AdminServiceController::class, 'destroy']);
});

// Products Management (Admin)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/export', [ProductController::class, 'export']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/select', [ProductController::class, 'selectOptions']);
    Route::post('/bulk-delete', [ProductController::class, 'bulkDelete']);
    Route::patch('/{id}/toggle-status', [ProductController::class, 'toggleStatus']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);

    // Product Service Forms (builder)
    Route::get('/{id}/service-forms', [App\Http\Controllers\Api\V2\Admin\ProductServiceFormController::class, 'index']);
    Route::post('/{id}/service-forms', [App\Http\Controllers\Api\V2\Admin\ProductServiceFormController::class, 'store']);
});

    });
});

// End-customer onboarding geo (NestJS v2/countries parity)
Route::prefix('v1')->group(function () {
    Route::get('countries', [\App\Modules\CustomerAuth\Controllers\CustomerOnboardingController::class, 'listCountries']);
    Route::get('countries/{dialCode}/cities', [\App\Modules\CustomerAuth\Controllers\CustomerOnboardingController::class, 'listCities']);
});

// End-customer registration (NestJS AuthenticationService parity)
Route::prefix('v1/customer')->group(function () {
    Route::prefix('otp')->group(function () {
        Route::post('sms', [\App\Modules\CustomerAuth\Controllers\CustomerOtpController::class, 'sendSms']);
        Route::post('email', [\App\Modules\CustomerAuth\Controllers\CustomerOtpController::class, 'sendEmail']);
        Route::post('verify', [\App\Modules\CustomerAuth\Controllers\CustomerOtpController::class, 'verify']);
    });

    Route::post('auth/register', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'register']);
    Route::post('auth/login', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'login']);
    Route::post('auth/refresh-token', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'refreshToken']);
    Route::post('password/forgot', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'forgotPassword']);
    Route::post('password/reset', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'resetPassword']);

    // Admin-created customers set their own password via emailed/SMS invite link
    Route::get('set-password/validate', [\App\Modules\CustomerAuth\Controllers\CustomerPasswordSetupController::class, 'validateToken']);
    Route::post('set-password', [\App\Modules\CustomerAuth\Controllers\CustomerPasswordSetupController::class, 'setPassword']);

    Route::middleware('customer.jwt')->group(function () {
        Route::get('profile', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'profile']);
        Route::post('profile/complete', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'completeProfile']);
        Route::post('profile/update', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'updateProfile']);
        Route::post('password/change', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'changePassword']);
        Route::post('auth/logout', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'logout']);
        Route::delete('account', [\App\Modules\CustomerAuth\Controllers\CustomerAuthController::class, 'deleteAccount']);
        Route::get('wallet/dashboard', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'dashboard']);
        Route::get('wallet/transactions', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'transactions'])
            ->middleware('customer.active');
        Route::get('wallet/query', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'query'])
            ->middleware('customer.active');
        Route::get('wallet/resolve-recipient', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'resolveRecipient'])
            ->middleware('customer.active');
        Route::post('wallet/transfer/otp', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'requestTransferOtp'])
            ->middleware('customer.active');
        Route::post('wallet/transfer', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'transfer'])
            ->middleware('customer.active');
        Route::post('wallet/withdraw', [\App\Modules\CustomerAuth\Controllers\CustomerWalletController::class, 'withdraw'])
            ->middleware('customer.active');
        Route::get('banners', [\App\Modules\CustomerAuth\Controllers\CustomerBannerController::class, 'index']);
        // One-shot customer catalog for client-side navigation:
        // services -> products -> forms (with fields)
        Route::get('services/catalog', [\App\Http\Controllers\Api\CustomerHomeCategoryController::class, 'catalog']);
        Route::get('services/home', [\App\Http\Controllers\Api\CustomerHomeCategoryController::class, 'homeServices']);
        Route::get('services/{id}', [\App\Http\Controllers\Api\CustomerHomeCategoryController::class, 'serviceDetails']);
        Route::get('partners/{id}', [\App\Http\Controllers\Api\CustomerPartnerPublicController::class, 'show']);
        Route::prefix('notifications')->group(function () {
            Route::get('/', [\App\Modules\CustomerAuth\Controllers\CustomerNotificationController::class, 'index']);
            Route::get('/unread-count', [\App\Modules\CustomerAuth\Controllers\CustomerNotificationController::class, 'unreadCount']);
            Route::post('/mark-all-read', [\App\Modules\CustomerAuth\Controllers\CustomerNotificationController::class, 'markAllAsRead']);
            Route::post('/{id}/mark-as-read', [\App\Modules\CustomerAuth\Controllers\CustomerNotificationController::class, 'markAsRead']);
            Route::delete('/{id}', [\App\Modules\CustomerAuth\Controllers\CustomerNotificationController::class, 'destroy']);
        });
    });
});