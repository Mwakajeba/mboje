<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

// ==================== PUBLIC API ROUTES (No Authentication) ====================

// Public Room API (for website)
Route::prefix('rooms')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\RoomApiController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\Api\RoomApiController::class, 'show']);
});

// Public Bookings API (for website)
Route::prefix('bookings')->group(function () {
    Route::get('/available-rooms', [App\Http\Controllers\Hotel\BookingController::class, 'availableRoomsApi']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [App\Http\Controllers\Hotel\BookingController::class, 'createOnlineBookingApi']);
        Route::get('/', [App\Http\Controllers\Hotel\BookingController::class, 'getMyBookingsApi']);
        Route::get('/{booking}', [App\Http\Controllers\Hotel\BookingController::class, 'getMyBookingByIdApi']);
        Route::get('/{booking}/receipt', [App\Http\Controllers\Hotel\BookingController::class, 'downloadReceiptApi']);
        Route::post('/{booking}/cancel', [App\Http\Controllers\Hotel\BookingController::class, 'cancelBookingApi']);
    });
});

// Company Settings API (for website)
Route::prefix('settings')->group(function () {
    Route::get('/company', [App\Http\Controllers\SettingsController::class, 'getCompanySettingsApi']);
});

// Guest Authentication API (for website)
Route::prefix('guest')->group(function () {
    Route::post('/register', [App\Http\Controllers\Api\GuestApiController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\GuestApiController::class, 'login']);
    Route::get('/bank-accounts', [App\Http\Controllers\Api\GuestApiController::class, 'getBankAccounts']); // Public endpoint for bank accounts
    Route::get('/branches', [App\Http\Controllers\Api\GuestApiController::class, 'getBranches']); // Public endpoint for branches
    Route::post('/messages', [App\Http\Controllers\Api\GuestApiController::class, 'sendMessage']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\GuestApiController::class, 'me']);
        Route::put('/profile', [App\Http\Controllers\Api\GuestApiController::class, 'updateProfile']);
        Route::get('/messages', [App\Http\Controllers\Api\GuestApiController::class, 'getMyMessages']);
        Route::post('/logout', [App\Http\Controllers\Api\GuestApiController::class, 'logout']);
    });
});

// ==================== MACHINGA MOBILE API ====================

Route::prefix('machinga')->name('machinga.')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Machinga\MachingaAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'mobile.context'])->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\Machinga\MachingaAuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\Machinga\MachingaAuthController::class, 'me']);
        Route::get('/branches', [\App\Http\Controllers\Api\Machinga\MachingaAuthController::class, 'branches']);
        Route::get('/locations', [\App\Http\Controllers\Api\Machinga\MachingaAuthController::class, 'locations']);
        Route::post('/context', [\App\Http\Controllers\Api\Machinga\MachingaAuthController::class, 'setContext']);

        Route::get('/hub', [\App\Http\Controllers\Api\Machinga\MachingaHubController::class, 'index']);

        Route::get('/suppliers', [\App\Http\Controllers\Api\Machinga\MachingaSupplierController::class, 'index']);
        Route::post('/suppliers', [\App\Http\Controllers\Api\Machinga\MachingaSupplierController::class, 'store']);

        Route::prefix('supplier-advances')->group(function () {
            Route::get('/balances', [\App\Http\Controllers\Api\Machinga\MachingaSupplierAdvanceController::class, 'balances']);
            Route::get('/suppliers', [\App\Http\Controllers\Api\Machinga\MachingaSupplierAdvanceController::class, 'suppliers']);
            Route::get('/statement/{supplierId}', [\App\Http\Controllers\Api\Machinga\MachingaSupplierAdvanceController::class, 'statement']);
            Route::get('/pay/{supplierId}', [\App\Http\Controllers\Api\Machinga\MachingaSupplierAdvanceController::class, 'payForm']);
            Route::post('/pay/{supplierId}', [\App\Http\Controllers\Api\Machinga\MachingaSupplierAdvanceController::class, 'storePay']);
            Route::post('/manunuzi/{supplierId}', [\App\Http\Controllers\Api\Machinga\MachingaSupplierAdvanceController::class, 'storeManunuzi']);
        });

        Route::prefix('daily-accounts')->group(function () {
            Route::get('/hub', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'hub']);
            Route::get('/employees', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'entryEmployees']);
            Route::get('/report/employees', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'reportEmployees']);
            Route::get('/report', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'report']);
            Route::post('/mauzo', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'storeMauzo']);
            Route::post('/matumizi', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'storeMatumizi']);
            Route::post('/manunuzi', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'storeManunuzi']);
            Route::post('/stoo', [\App\Http\Controllers\Api\Machinga\MachingaDailyAccountsController::class, 'storeStoo']);
        });
    });
});

// ==================== PROTECTED API ROUTES (Require Authentication) ====================

Route::middleware('auth:sanctum')->group(function () {
    
    // ==================== TEACHER ROUTES (Future Implementation) ====================
    Route::prefix('teacher')->group(function () {
        // Will be implemented when teacher mobile app is needed
    });
    
    // ==================== ADMIN ROUTES (Future Implementation) ====================
    Route::prefix('admin')->group(function () {
        // Will be implemented when admin mobile app is needed
    });
});

// ==================== WEBHOOK ROUTES (No Authentication Required) ====================
Route::prefix('webhooks')->group(function () {
    Route::post('/lipisha', [App\Http\Controllers\WebhookController::class, 'lipisha']);
});
