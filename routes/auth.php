<?php


use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\CustomAuthenticatedController;
use \App\Http\Controllers\AdminAuthenticatedController;
use \App\Http\Controllers\MerchantAuthenticatedController;
use \App\Http\Controllers\UserController;

Route::middleware('guest:web')->group(function () {

    Route::prefix('merchant')->group(function () {

    Route::get('login', [CustomAuthenticatedController::class, 'create'])->name('merchant.login');
    Route::get('login', [CustomAuthenticatedController::class, 'create'])->name('login');

    Route::post('login', [CustomAuthenticatedController::class, 'store'])->name('merchant.login');
    
    // Merchant Registration Routes
    Route::get('registration/success', [App\Http\Controllers\MerchantRegistrationController::class, 'showSuccess'])->name('merchant.registration.success');
});

Route::prefix('merchant')->group(function () {
    Route::get('register', [App\Http\Controllers\MerchantRegistrationController::class, 'showRegistrationForm'])->name('merchant.register');
    Route::post('register', [App\Http\Controllers\MerchantRegistrationController::class, 'register'])->name('merchant.register');
});
    // Admin authentication routes

});

Route::middleware('guest:admin')->prefix('admin')->group(function () {
    Route::get('login', [AdminAuthenticatedController::class, 'create'])
        ->name('admin.login');
        
    Route::post('login', [AdminAuthenticatedController::class, 'store'])
        ->name('admin.login');
});

Route::get('password-request', [UserController::class, 'passwordRequest'])->name('password.request');
Route::get('reset-password', [UserController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('reset-password', [UserController::class, 'resetPasswordForm'])->name('password.update');
Route::post("/logout", [CustomAuthenticatedController::class, 'CompanyLogout'])->name('logout')->middleware('auth');

// Admin logout route
Route::post("/admin/logout", [AdminAuthenticatedController::class, 'destroy'])->name('admin.logout')->middleware('auth:admin');

// Admin profile routes (dashboard is handled in web.php)
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/profile', [AdminAuthenticatedController::class, 'profile'])->name('admin.profile');
    Route::post('/admin/profile', [AdminAuthenticatedController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/admin/change-password', [AdminAuthenticatedController::class, 'changePassword'])->name('admin.change-password');
});

// Merchant dashboard and profile routes
Route::middleware('auth')->group(function () {
    // Route::get('/merchant/dashboard', [MerchantAuthenticatedController::class, 'dashboard'])->name('merchant.dashboard');
    Route::post('/merchant/change-password', [MerchantAuthenticatedController::class, 'changePassword'])->name('merchant.change-password');
    Route::post('/merchant/logout', [MerchantAuthenticatedController::class, 'destroy'])->name('merchant.logout');
});
