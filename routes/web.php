<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpEmailController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Production\ItemBatchController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\CashCollateralTypeController;
use App\Http\Controllers\CashCollateralController;
use App\Http\Controllers\AccountClassGroupController;
use App\Http\Controllers\Accounting\BankReconciliationController;
use App\Http\Controllers\Printing\EscposTestPrintController;

// Temporary route to set location
Route::get('/set-location/{locationId}', function ($locationId) {
    $user = auth()->user();
    $location = $user->locations()->find($locationId);

    if ($location) {
        session(['location_id' => $locationId, 'branch_id' => $location->branch_id]);
        return redirect()->back()->with('success', 'Location set to: ' . $location->name);
    }

    return redirect()->back()->with('error', 'Invalid location');
})->middleware('auth');

// Debug route: ESC/POS USB test print (requires auth)
Route::get('/debug-print', EscposTestPrintController::class)->middleware('auth');

// Debug route for POS
Route::get('/debug-pos', function () {
    $user = auth()->user();
    $sessionLocationId = session('location_id');

    echo "User: " . $user->name . "<br>";
    echo "Session location_id: " . ($sessionLocationId ?? 'NULL') . "<br>";
    echo "Session branch_id: " . (session('branch_id') ?? 'NULL') . "<br>";

    if ($sessionLocationId) {
        $stockService = new \App\Services\InventoryStockService();
        $availableItems = $stockService->getAvailableItemsForSales($user->company_id, $sessionLocationId);
        $inventoryItems = $availableItems->filter(function ($item) {
            return !$item->is_service;
        })->values();

        echo "Available items count: " . $inventoryItems->count() . "<br>";
        foreach ($inventoryItems as $item) {
            echo "- " . $item->name . " (" . $item->code . ")<br>";
        }
    } else {
        echo "No session location set!<br>";
    }
})->middleware('auth');

// Temporary debug routes for session and stock testing
Route::get('/debug-session', function () {
    $user = Auth::user();
    if (!$user) {
        return 'Not authenticated';
    }

    $sessionLocationId = session('location_id');
    $sessionBranchId = session('branch_id');

    $defaultLocation = $user->defaultLocation()->first();
    $firstLocation = $user->locations()->first();

    $calculator = \App\Models\Inventory\Item::where('code', 'CALC001')->first();
    $currentStock = $calculator ? $calculator->current_stock : 'N/A';

    return [
        'user' => $user->name,
        'session_location_id' => $sessionLocationId,
        'session_branch_id' => $sessionBranchId,
        'default_location' => $defaultLocation ? ['id' => $defaultLocation->id, 'name' => $defaultLocation->name] : null,
        'first_location' => $firstLocation ? ['id' => $firstLocation->id, 'name' => $firstLocation->name] : null,
        'calculator_current_stock' => $currentStock,
        'all_locations' => $user->locations()->get(['id', 'name', 'branch_id'])->toArray()
    ];
});

Route::get('/set-location/{locationId}', function ($locationId) {
    $user = Auth::user();
    if (!$user) {
        return 'Not authenticated';
    }

    // Verify user has access to this location
    $location = $user->locations()->where('inventory_locations.id', $locationId)->first();
    if (!$location) {
        return 'Location not accessible to user';
    }

    session(['location_id' => $locationId, 'branch_id' => $location->branch_id]);

    return redirect()->route('inventory.movements.index')->with('success', 'Location set to: ' . $location->name);
});

// Auto-set location route
Route::get('/auto-set-location', function () {
    $user = Auth::user();
    if (!$user) {
        return 'Not authenticated';
    }

    $defaultLocation = $user->defaultLocation()->first();

    if ($defaultLocation) {
        session(['location_id' => $defaultLocation->id, 'branch_id' => $defaultLocation->branch_id]);
        $locationName = $defaultLocation->name;
    } else {
        $firstLocation = $user->locations()->first();
        if ($firstLocation) {
            session(['location_id' => $firstLocation->id, 'branch_id' => $firstLocation->branch_id]);
            $locationName = $firstLocation->name;
        } else {
            return 'No locations available for user';
        }
    }

    return redirect()->route('inventory.movements.index')->with('success', 'Location automatically set to: ' . $locationName);
});

use App\Http\Controllers\Accounting\BillPurchaseController;
use App\Http\Controllers\Accounting\BudgetController;

use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\PaymentVoucherController;
use App\Http\Controllers\Accounting\ReceiptVoucherController;
use App\Http\Controllers\Accounting\Reports\BankReconciliationReportController;
use App\Http\Controllers\Accounting\SupplierController;
use App\Http\Controllers\ActivityLogsController;
use App\Http\Controllers\ChartAccountController;
use App\Http\Controllers\BankAccountController;
// use App\Http\Controllers\CashDepositController; // Controller missing - commented out
use App\Http\Controllers\ProductionBatchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\JournalController;


use App\Http\Controllers\Inventory\ItemController;
use App\Http\Controllers\Inventory\CategoryController;
use App\Http\Controllers\Inventory\MovementController;
use App\Http\Controllers\Inventory\TransferController;
use App\Http\Controllers\Inventory\WriteOffController;
use App\Http\Controllers\TransferRequestController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Purchase\PurchaseRequisitionController;
use App\Http\Controllers\Purchase\QuotationController;
use App\Http\Controllers\Purchase\OrderController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Sales\DeliveryController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\CreditNoteController;
use App\Http\Controllers\Sales\SalesOrderController;
use App\Http\Controllers\Sales\SalesProformaController;
use App\Http\Controllers\Sales\CashSaleController;
use App\Http\Controllers\Sales\PosSaleController;
use App\Http\Controllers\ChangeBranchController;
use App\Http\Controllers\Inventory\OpeningBalanceController;
// Public Job Portal Routes (no authentication required)
Route::prefix('jobs')->name('public.job-portal.')->group(function () {
    Route::get('/', [App\Http\Controllers\Public\JobPortalController::class, 'index'])->name('index');
    Route::get('/{vacancyRequisition}', [App\Http\Controllers\Public\JobPortalController::class, 'show'])->name('show');
    Route::post('/{vacancyRequisition}/apply', [App\Http\Controllers\Public\JobPortalController::class, 'apply'])->name('apply');
});

// Public API Routes for Job Portal (no authentication required)
Route::prefix('api')->group(function () {
    Route::get('/qualifications/{qualification}/documents', [App\Http\Controllers\Public\JobPortalController::class, 'getQualificationDocuments']);
});

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login', [AuthController::class, 'showLoginForm']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle.login');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registration');

Route::get('/verify-sms', [AuthController::class, 'showVerificationForm'])->name('verify-sms');
Route::post('/verify-sms', [AuthController::class, 'verifySmsCode'])->middleware('throttle:otp');

Route::get('/forgotPassword', [AuthController::class, 'showForgotPasswordForm'])->name('forgotPassword');
Route::post('/forgotPassword', [AuthController::class, 'forgotPassword'])->middleware('throttle:password_reset');

Route::get('/verify-otp-password', [AuthController::class, 'showVerificationForm'])->name('verify-otp-password');
Route::post('/verify-otp-password', [AuthController::class, 'verifyPasswordCode'])->middleware('throttle:otp');

Route::get('/reset-password', [AuthController::class, 'showNewPasswordForm'])->name('new-password-form');
Route::post('/reset-password', [AuthController::class, 'storeNewPassword'])->middleware('throttle:password_reset');

Route::get('/resend-otp/{phone}', [AuthController::class, 'resendOtp'])->name('resend.otp')->middleware('throttle:otp');

// Subscription expired page
Route::get('/subscription-expired', function () {
    return view('auth.subscription-expired');
})->name('subscription.expired');

// Language switching
Route::get('/language/{locale}', [LanguageController::class, 'switchLanguage'])->name('language.switch');
// Test language route
Route::get('/test-language', function () {
    return view('test-language');
})->name('test.language');

Route::get('/request-email-otp', [OtpEmailController::class, 'showEmailForm'])->name('email-otp-form');
Route::post('/send-email-otp', [OtpEmailController::class, 'sendOtpEmail'])->name('email-otp-send');

Route::get('/global-search', [\App\Http\Controllers\GlobalSearchController::class, 'search'])->middleware(['auth', 'throttle:search'])->name('global-search');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'require.branch'])->name('dashboard');

// Approval Queue
Route::get('/approvals/queue', [App\Http\Controllers\ApprovalQueueController::class, 'index'])->middleware(['auth'])->name('approvals.queue');
Route::post('/approvals/bulk-approve', [App\Http\Controllers\ApprovalQueueController::class, 'bulkApprove'])->middleware(['auth'])->name('approvals.bulk-approve');
Route::post('/approvals/bulk-reject', [App\Http\Controllers\ApprovalQueueController::class, 'bulkReject'])->middleware(['auth'])->name('approvals.bulk-reject');
Route::get('/analytics', [AnalyticsController::class, 'analytics'])->middleware(['auth', 'require.branch'])->name('analytics.index');
Route::get('/analytics/dashboard-data', [AnalyticsController::class, 'getDashboardData'])->middleware(['auth', 'require.branch'])->name('analytics.dashboard-data');
Route::get('/analytics/drill-down', [AnalyticsController::class, 'getDrillDown'])->middleware(['auth', 'require.branch'])->name('analytics.drill-down');
Route::get('/analytics/export-pdf', [AnalyticsController::class, 'exportPdf'])->middleware(['auth', 'require.branch'])->name('analytics.export-pdf');
Route::get('/expiry-alerts', [DashboardController::class, 'expiryAlerts'])->middleware(['auth'])->name('expiry-alerts');
Route::get('/expiry-alerts/data', [DashboardController::class, 'expiryAlertsData'])->middleware(['auth'])->name('expiry-alerts.data');
Route::get('/dashboard/top-items-sold', [DashboardController::class, 'topItemsSoldYear'])->middleware(['auth']);
Route::get('/dashboard/gross-profit-trend', [DashboardController::class, 'grossProfitTrend'])->middleware(['auth']);
Route::get('/dashboard/kpis', [DashboardController::class, 'dashboardKpis'])->middleware(['auth']);
Route::get('/dashboard/enhanced-kpis', [DashboardController::class, 'enhancedKpis'])->middleware(['auth']);
Route::get('/dashboard/revenue-trend', [DashboardController::class, 'revenueTrend'])->middleware(['auth']);
Route::get('/dashboard/order-status', [DashboardController::class, 'orderStatusDistribution'])->middleware(['auth']);
Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts'])->middleware(['auth']);
Route::get('/dashboard/profit-by-year', [DashboardController::class, 'profitByYear'])->middleware(['auth']);
Route::get('/dashboard/receivables-aging', [DashboardController::class, 'dashboardReceivablesAging'])->middleware(['auth']);
Route::get('/dashboard/inventory-summary', [DashboardController::class, 'dashboardInventorySummary'])->middleware(['auth']);
Route::get('/dashboard/cards-summary', [DashboardController::class, 'dashboardCardsSummary'])->middleware(['auth']);
Route::get('/dashboard/revenue-by-location', [DashboardController::class, 'revenueByLocation'])->middleware(['auth']);
Route::get('/dashboard/labor-trend', [DashboardController::class, 'laborTrend'])->middleware(['auth']);
Route::get('/dashboard/operating-expense-by-department', [DashboardController::class, 'operatingExpenseByDepartment'])->middleware(['auth']);
Route::get('/dashboard/company-performance', [DashboardController::class, 'companyPerformance'])->middleware(['auth']);
Route::get('/dashboard/ebitda-trend', [DashboardController::class, 'ebitdaTrend'])->middleware(['auth']);
Route::get('/dashboard/net-income-trend', [DashboardController::class, 'netIncomeTrend'])->middleware(['auth']);

// Change Branch Routes (excluded from require.branch middleware to avoid infinite redirects)
Route::get('/change-branch', [ChangeBranchController::class, 'show'])->middleware('auth')->name('change-branch');
Route::post('/change-branch', [ChangeBranchController::class, 'change'])->middleware('auth')->name('change-branch.submit');
Route::get('/change-branch/locations', [ChangeBranchController::class, 'locations'])->middleware('auth')->name('change-branch.locations');

// Reports Route
Route::get('/reports', [ReportsController::class, 'index'])->middleware(['auth', 'require.branch'])->name('reports.index');
Route::get('/reports/customers', [ReportsController::class, 'customers'])->middleware(['auth', 'require.branch'])->name('reports.customers');
Route::get('/reports/accounting', [ReportsController::class, 'accounting'])->middleware(['auth', 'require.branch'])->name('reports.accounting');

////////////////////////////////////////ROLES & PERMISSIONS MANAGEMENT /////////////////////////////////////////////
Route::middleware(['auth', 'require.branch'])->group(function () {
    Route::bind('role', function ($value) {
        $query = \App\Models\Role::query();

        $decodedId = \Vinkla\Hashids\Facades\Hashids::decode($value)[0] ?? null;
        if ($decodedId) {
            return $query->where('id', $decodedId)->firstOrFail();
        }

        if (is_numeric($value)) {
            return $query->where('id', $value)->firstOrFail();
        }

        return $query->where('name', $value)->firstOrFail();
    });
    Route::model('paymentVoucher', \App\Models\Payment::class);
    Route::model('payment_voucher', \App\Models\Payment::class);

    // Roles management
    Route::get('roles', [RolePermissionController::class, 'index'])->name('roles.index');
    Route::get('roles/create', [RolePermissionController::class, 'create'])->name('roles.create');
    Route::post('roles', [RolePermissionController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}', [RolePermissionController::class, 'show'])->name('roles.show');
    Route::get('roles/{role}/edit', [RolePermissionController::class, 'edit'])->name('roles.edit');
    Route::match(['PUT', 'PATCH'], 'roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RolePermissionController::class, 'destroy'])->name('roles.destroy');

    // Menu management for roles
    Route::get('roles/{role}/menus', [RolePermissionController::class, 'manageMenus'])->name('roles.menus');
    Route::post('roles/{role}/menus/assign', [RolePermissionController::class, 'assignMenus'])->name('roles.menus.assign');
    Route::delete('roles/{role}/menus/remove', [RolePermissionController::class, 'removeMenu'])->name('roles.menus.remove');
    Route::delete('roles/{role}/menus/remove-all-submenus', [RolePermissionController::class, 'removeAllSubmenus'])->name('roles.menus.remove-all-submenus');
    Route::delete('roles/{role}/menus/remove-all', [RolePermissionController::class, 'removeAllMenus'])->name('roles.menus.remove-all');

    // Permissions management
    Route::get('permissions', [RolePermissionController::class, 'permissions'])->name('permissions.index');
    Route::post('permissions', [RolePermissionController::class, 'createPermission'])->name('permissions.store');
    Route::delete('permissions/{permission}', [RolePermissionController::class, 'deletePermission'])->name('permissions.destroy');



    // User role assignment
    Route::post('users/{user}/assign-roles', [RolePermissionController::class, 'assignToUser'])->name('users.assign-roles');
    Route::delete('users/{user}/remove-role', [RolePermissionController::class, 'removeFromUser'])->name('users.remove-role');
    Route::post('users/{user}/assign-branches', [\App\Http\Controllers\UserController::class, 'assignBranches'])->name('users.assign-branches');
    Route::post('users/{user}/assign-locations', [\App\Http\Controllers\UserController::class, 'assignLocations'])->name('users.assign-locations');
    Route::delete('users/{user}/locations/{location}', [\App\Http\Controllers\UserController::class, 'removeLocation'])->name('users.locations.remove');
    Route::patch('users/{user}/locations/default', [\App\Http\Controllers\UserController::class, 'setDefaultLocation'])->name('users.locations.default');

    // Role statistics
    Route::get('roles-stats', [RolePermissionController::class, 'getStats'])->name('roles.stats');
});
////////////////////////////////////////////// END ROLES & PERMISSIONS MANAGEMENT //////////////////////////////////////////

////////////////////////////////////////////// USER MANAGEMENT /////////////////////////////////////////////////////

// Additional user routes (must come BEFORE resource route)
Route::get('/users/profile', [UserController::class, 'profile'])->name('users.profile')->middleware(['auth', 'require.branch']);
Route::put('/users/profile', [UserController::class, 'updateProfile'])->name('users.profile.update')->middleware(['auth', 'require.branch']);
Route::get('/users/employees', [UserController::class, 'employees'])->name('users.employees')->middleware(['auth']);
Route::get('/users/data', [UserController::class, 'data'])->name('users.data')->middleware(['auth', 'company.scope', 'require.branch']);

Route::resource('users', UserController::class)->middleware(['auth', 'company.scope', 'require.branch']);

// Additional user routes that require user parameter
Route::patch('/users/{user}/status', [UserController::class, 'changeStatus'])->name('users.status')->middleware(['auth', 'company.scope', 'require.branch']);
Route::post('/users/{user}/roles', [UserController::class, 'assignRoles'])->name('users.roles')->middleware(['auth', 'company.scope', 'require.branch']);

////////////////////////////////////////////// SETTINGS ROUTES ////////////////////////////////////////////////

Route::prefix('settings')->name('settings.')->middleware(['auth', 'company.scope', 'require.branch'])->group(function () {



    Route::get('/', [SettingsController::class, 'index'])->name('index');

    // Company Settings
    Route::get('/company', [SettingsController::class, 'companySettings'])->name('company');
    Route::put('/company', [SettingsController::class, 'updateCompanySettings'])->name('company.update');

    // Branch Settings
    Route::get('/branches', [SettingsController::class, 'branchSettings'])->name('branches');
    Route::get('/branches/data', [SettingsController::class, 'branchesData'])->name('branches.data');
    Route::get('/branches/create', [SettingsController::class, 'createBranch'])->name('branches.create');
    Route::post('/branches', [SettingsController::class, 'storeBranch'])->name('branches.store');
    Route::get('/branches/{branch}/edit', [SettingsController::class, 'editBranch'])->name('branches.edit');
    Route::put('/branches/{branch}', [SettingsController::class, 'updateBranch'])->name('branches.update');
    Route::delete('/branches/{branch}', [SettingsController::class, 'destroyBranch'])->name('branches.destroy');

    // User Settings
    Route::get('/user', [SettingsController::class, 'userSettings'])->name('user');
    Route::put('/user', [SettingsController::class, 'updateUserSettings'])->name('user.update');

    // System Settings
    Route::get('/system', [SettingsController::class, 'systemSettings'])->name('system');
    Route::put('/system', [SettingsController::class, 'updateSystemSettings'])->name('system.update');
    Route::post('/system/reset', [SettingsController::class, 'resetSystemSettings'])->name('system.reset');
    Route::post('/system/test-email', [SettingsController::class, 'testEmailConfig'])->name('system.test-email');

    // SMS Settings
    Route::get('/sms', [SettingsController::class, 'smsSettings'])->name('sms');
    Route::put('/sms', [SettingsController::class, 'updateSmsSettings'])->name('sms.update');
    Route::post('/sms/test', [SettingsController::class, 'testSmsSettings'])->name('sms.test');

    // Backup Settings
    Route::get('/backup', [SettingsController::class, 'backupSettings'])->name('backup');
    Route::post('/backup/create', [SettingsController::class, 'createBackup'])->name('backup.create');
    Route::post('/backup/restore', [SettingsController::class, 'restoreBackup'])->name('backup.restore');
    Route::get('/backup/{hash_id}/download', [SettingsController::class, 'downloadBackup'])->name('backup.download');
    Route::delete('/backup/{hash_id}', [SettingsController::class, 'deleteBackup'])->name('backup.delete');
    Route::post('/backup/clean', [SettingsController::class, 'cleanOldBackups'])->name('backup.clean');

    // AI Assistant Settings
    Route::get('/ai', [SettingsController::class, 'aiAssistantSettings'])->name('ai');
    Route::post('/ai/chat', [SettingsController::class, 'aiChat'])->name('ai.chat')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    // Budget Settings
    Route::get('/budget', [SettingsController::class, 'budgetSettings'])->name('budget');
    Route::put('/budget', [SettingsController::class, 'updateBudgetSettings'])->name('budget.update');

    // Petty Cash Settings
    Route::get('/petty-cash', [SettingsController::class, 'pettyCashSettings'])->name('petty-cash');
    Route::put('/petty-cash', [SettingsController::class, 'updatePettyCashSettings'])->name('petty-cash.update');

    // LIPISHA Payment Gateway Settings
    Route::get('/lipisha', [SettingsController::class, 'lipishaSettings'])->name('lipisha');
    Route::put('/lipisha', [SettingsController::class, 'updateLipishaSettings'])->name('lipisha.update');
    Route::post('/lipisha/test-network', [SettingsController::class, 'testLipishaNetwork'])->name('lipisha.test-network');

    // Queue Worker Management
    Route::post('/queue-worker/start', [SettingsController::class, 'startQueueWorker'])->name('queue-worker.start');

    // Approval Levels Management
    Route::get('/approval-levels', [App\Http\Controllers\ApprovalLevelsController::class, 'index'])->name('approval-levels.index');
    Route::post('/approval-levels', [App\Http\Controllers\ApprovalLevelsController::class, 'store'])->name('approval-levels.store');
    Route::put('/approval-levels/{approvalLevel}', [App\Http\Controllers\ApprovalLevelsController::class, 'update'])->name('approval-levels.update');
    Route::delete('/approval-levels/{approvalLevel}', [App\Http\Controllers\ApprovalLevelsController::class, 'destroy'])->name('approval-levels.destroy');
    Route::post('/approval-levels/assignments', [App\Http\Controllers\ApprovalLevelsController::class, 'storeAssignment'])->name('approval-levels.assignments.store');
    Route::delete('/approval-levels/assignments/{assignment}', [App\Http\Controllers\ApprovalLevelsController::class, 'destroyAssignment'])->name('approval-levels.assignments.destroy');
    Route::post('/approval-levels/reorder', [App\Http\Controllers\ApprovalLevelsController::class, 'reorder'])->name('approval-levels.reorder');
    Route::get('/ai/test', function () {
        return response()->json([
            'csrf_token' => csrf_token(),
            'status' => 'success',
            'message' => 'AI Assistant connection test successful'
        ]);
    })->name('ai.test');

    //////logs route///
    Route::get('/logs', [ActivityLogsController::class, 'index'])->name('logs.index');
    Route::get('/logs/data', [ActivityLogsController::class, 'getData'])->name('logs.data');
    Route::get('/logs/export/excel', [ActivityLogsController::class, 'exportExcel'])->name('logs.export.excel');
    Route::get('/logs/export/pdf', [ActivityLogsController::class, 'exportPdf'])->name('logs.export.pdf');

    // Fees Settings
    Route::get('/fees', [SettingsController::class, 'feesSettings'])->name('fees');
    Route::put('/fees', [SettingsController::class, 'updateFeesSettings'])->name('fees.update');

    Route::get('/subscription', [SettingsController::class, 'subscriptionSettings'])->name('subscription');
    Route::put('/subscription', [SettingsController::class, 'updateSubscriptionSettings'])->name('subscription.update');


    // Inventory Settings
    Route::get('/inventory', [SettingsController::class, 'inventorySettings'])->name('inventory');
    Route::put('/inventory', [SettingsController::class, 'updateInventorySettings'])->name('inventory.update');

    // Inventory Locations
    Route::get('/inventory-settings/locations', [SettingsController::class, 'inventoryLocations'])->name('inventory.locations.index')->middleware('check.inventory.cost.method');
    Route::get('/inventory-settings/locations/create', [SettingsController::class, 'createInventoryLocation'])->name('inventory.locations.create')->middleware('check.inventory.cost.method');
    Route::post('/inventory-settings/locations', [SettingsController::class, 'storeInventoryLocation'])->name('inventory.locations.store')->middleware('check.inventory.cost.method');
    Route::get('/inventory-settings/locations/{location}', [SettingsController::class, 'showInventoryLocation'])->name('inventory.locations.show')->middleware('check.inventory.cost.method');
    Route::get('/inventory-settings/locations/{location}/edit', [SettingsController::class, 'editInventoryLocation'])->name('inventory.locations.edit')->middleware('check.inventory.cost.method');
    Route::put('/inventory-settings/locations/{location}', [SettingsController::class, 'updateInventoryLocation'])->name('inventory.locations.update')->middleware('check.inventory.cost.method');
    Route::delete('/inventory-settings/locations/{location}', [SettingsController::class, 'destroyInventoryLocation'])->name('inventory.locations.destroy')->middleware('check.inventory.cost.method');
});


////////////////////////////////////////////// END SETTINGS ROUTES /////////////////////////////////////////////

////////////////////////////////////////////// SUBSCRIPTION MANAGEMENT ///////////////////////////////////////////

Route::prefix('subscriptions')->name('subscriptions.')->middleware(['auth', 'role:super-admin'])->group(function () {
    // Subscription Dashboard
    Route::get('/dashboard', [SubscriptionController::class, 'dashboard'])->name('dashboard');

    // Subscription CRUD
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');
    Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
    Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
    Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
    Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');

    // Subscription Actions
    Route::post('/{subscription}/mark-paid', [SubscriptionController::class, 'markAsPaid'])->name('mark-paid');
    Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
    Route::post('/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('extend');
});

// Ticker Messages API - Only for subscription expiry alerts
Route::get('/api/ticker-messages', function () {
    // Get subscription alerts - only show ticker if there are expiring subscriptions
    $expiringSubscriptions = \App\Models\Subscription::where('status', 'active')
        ->where('end_date', '<=', now()->addDays(5))
        ->where('end_date', '>=', now())
        ->with('company')
        ->get();

    // If no expiring subscriptions, return empty messages to hide ticker
    if ($expiringSubscriptions->count() == 0) {
        return response()->json([
            'success' => true,
            'messages' => [],
            'show_ticker' => false,
            'timestamp' => now()->toISOString()
        ]);
    }

    $messages = [];
    $now = now();

    // Build subscription expiry messages
    foreach ($expiringSubscriptions as $subscription) {
        $daysLeft = floor($now->diffInDays($subscription->end_date, false));
        $urgency = $daysLeft <= 1 ? 'urgent' : ($daysLeft <= 3 ? 'warning' : 'info');

        $daysText = $daysLeft == 0 ? 'expires today' : ($daysLeft == 1 ? 'expires tomorrow' : "expires in {$daysLeft} days");

        $messages[] = [
            'text' => "⚠️ URGENT: {$subscription->company->name} subscription ({$subscription->plan_name}) {$daysText} - Amount: " . number_format($subscription->amount, 2) . " {$subscription->currency}",
            'type' => $urgency,
            'icon' => 'bx-credit-card',
            'subscription_id' => $subscription->id,
            'company_name' => $subscription->company->name,
            'days_left' => $daysLeft,
            'expiry_date' => $subscription->end_date->format('M d, Y')
        ];
    }

    // Add a general reminder message
    $messages[] = [
        'text' => "🔔 Action Required: Please renew expiring subscriptions to avoid service interruption",
        'type' => 'urgent',
        'icon' => 'bx-bell'
    ];

    return response()->json([
        'success' => true,
        'messages' => $messages,
        'show_ticker' => true,
        'expiring_count' => $expiringSubscriptions->count(),
        'timestamp' => $now->toISOString()
    ]);
})->middleware('auth');
////////////////////////////////////////////// BRANCH MANAGEMENT ///////////////////////////////////////////////////

//Route::resource('branches', BranchController::class)->middleware('auth');

//Route::resource('companies', CompanyController::class)->middleware('auth');

// Route::resource('cash_deposit_accounts', CashDepositAccountController::class)->middleware(['auth', 'require.branch']); // Controller deleted

////////////////////////////////////////////// END /////////////////////////////////////////////////////////////////

////////////////////////////////////////////// SUPER ADMIN ROUTES ////////////////////////////////////////////////

Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    // Companies
    Route::get('/companies', [SuperAdminController::class, 'companies'])->name('companies');
    Route::get('/companies/create', [SuperAdminController::class, 'createCompany'])->name('companies.create');
    Route::post('/companies', [SuperAdminController::class, 'storeCompany'])->name('companies.store');
    Route::get('/companies/{company}', [SuperAdminController::class, 'showCompany'])->name('companies.show');
    Route::get('/companies/{company}/edit', [SuperAdminController::class, 'editCompany'])->name('companies.edit');
    Route::put('/companies/{company}', [SuperAdminController::class, 'updateCompany'])->name('companies.update');
    Route::delete('/companies/{company}', [SuperAdminController::class, 'destroyCompany'])->name('companies.destroy');

    // Branches
    Route::get('/branches', [SuperAdminController::class, 'branches'])->name('branches');

    // Users
    Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
});

////////////////////////////////////////////// END SUPER ADMIN ROUTES /////////////////////////////////////////////

////////////////////////////////////////////// ACCOUNTING MANAGEMENT ///////////////////////////////////////////////

Route::prefix('accounting')->name('accounting.')->middleware(['auth', 'require.branch'])->group(function () {
    Route::get('/', [App\Http\Controllers\AccountingController::class, 'index'])->name('index');

    // Main Groups
    Route::get('/main-groups', [App\Http\Controllers\MainGroupController::class, 'index'])->name('main-groups.index');
    Route::get('/main-groups/create', [App\Http\Controllers\MainGroupController::class, 'create'])->name('main-groups.create');
    Route::post('/main-groups', [App\Http\Controllers\MainGroupController::class, 'store'])->name('main-groups.store');
    Route::get('/main-groups/{encodedId}', [App\Http\Controllers\MainGroupController::class, 'show'])->name('main-groups.show');
    Route::get('/main-groups/{encodedId}/edit', [App\Http\Controllers\MainGroupController::class, 'edit'])->name('main-groups.edit');
    Route::put('/main-groups/{encodedId}', [App\Http\Controllers\MainGroupController::class, 'update'])->name('main-groups.update');
    Route::delete('/main-groups/{encodedId}', [App\Http\Controllers\MainGroupController::class, 'destroy'])->name('main-groups.destroy');

    // Account Class Groups
    Route::get('/account-class-groups', [AccountClassGroupController::class, 'index'])->name('account-class-groups.index');
    Route::get('/account-class-groups/create', [AccountClassGroupController::class, 'create'])->name('account-class-groups.create');
    Route::post('/account-class-groups', [AccountClassGroupController::class, 'store'])->name('account-class-groups.store');
    Route::get('/account-class-groups/{encodedId}', [AccountClassGroupController::class, 'show'])->name('account-class-groups.show');
    Route::get('/account-class-groups/{encodedId}/edit', [AccountClassGroupController::class, 'edit'])->name('account-class-groups.edit');
    Route::put('/account-class-groups/{encodedId}', [AccountClassGroupController::class, 'update'])->name('account-class-groups.update');
    Route::delete('/account-class-groups/{encodedId}', [AccountClassGroupController::class, 'destroy'])->name('account-class-groups.destroy');

    // Chart Accounts
    Route::get('/chart-accounts', [ChartAccountController::class, 'index'])->name('chart-accounts.index');
    Route::get('/chart-accounts/template', [ChartAccountController::class, 'downloadTemplate'])->name('chart-accounts.template');
    Route::post('/chart-accounts/import', [ChartAccountController::class, 'import'])->name('chart-accounts.import');
    Route::get('/chart-accounts/create', [ChartAccountController::class, 'create'])->name('chart-accounts.create');
    Route::post('/chart-accounts', [ChartAccountController::class, 'store'])->name('chart-accounts.store');
    Route::get('/chart-accounts/{encodedId}', [ChartAccountController::class, 'show'])->name('chart-accounts.show');
    Route::get('/chart-accounts/{encodedId}/edit', [ChartAccountController::class, 'edit'])->name('chart-accounts.edit');
    Route::put('/chart-accounts/{encodedId}', [ChartAccountController::class, 'update'])->name('chart-accounts.update');
    Route::delete('/chart-accounts/{encodedId}', [ChartAccountController::class, 'destroy'])->name('chart-accounts.destroy');

    // Suppliers
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{encodedId}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/{encodedId}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{encodedId}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::patch('/suppliers/{encodedId}/status', [SupplierController::class, 'changeStatus'])->name('suppliers.changeStatus');
    Route::delete('/suppliers/{encodedId}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');


    // Payment Vouchers
    Route::get('payment-vouchers/datatable', [App\Http\Controllers\Accounting\PaymentVoucherController::class, 'getPaymentVouchersData'])->name('payment-vouchers.datatable');
    Route::get('/payment-vouchers/data', [PaymentVoucherController::class, 'data'])->name('payment-vouchers.data');
    Route::get('/payment-vouchers', [PaymentVoucherController::class, 'index'])->name('payment-vouchers.index');
    Route::get('/payment-vouchers/create', [PaymentVoucherController::class, 'create'])->name('payment-vouchers.create');
    Route::post('/payment-vouchers', [PaymentVoucherController::class, 'store'])->name('payment-vouchers.store');
    Route::get('/payment-vouchers/{encodedId}', [PaymentVoucherController::class, 'show'])->name('payment-vouchers.show');
    Route::get('/payment-vouchers/{encodedId}/edit', [PaymentVoucherController::class, 'edit'])->name('payment-vouchers.edit');
    Route::put('/payment-vouchers/{encodedId}', [PaymentVoucherController::class, 'update'])->name('payment-vouchers.update');
    Route::delete('/payment-vouchers/{encodedId}', [PaymentVoucherController::class, 'destroy'])->name('payment-vouchers.destroy');
    Route::get('payment-vouchers/{encodedId}/approve', [PaymentVoucherController::class, 'showApproval'])->name('payment-vouchers.approve');
    Route::post('payment-vouchers/{encodedId}/approve', [PaymentVoucherController::class, 'approve'])->name('payment-vouchers.approve.submit');
    Route::post('payment-vouchers/{encodedId}/reject', [PaymentVoucherController::class, 'reject'])->name('payment-vouchers.reject');
    Route::get('/payment-vouchers/{encodedId}/download-attachment', [PaymentVoucherController::class, 'downloadAttachment'])->name('payment-vouchers.download-attachment');
    Route::delete('/payment-vouchers/{encodedId}/remove-attachment', [PaymentVoucherController::class, 'removeAttachment'])->name('payment-vouchers.remove-attachment');
    Route::get('/payment-vouchers/{encodedId}/export-pdf', [PaymentVoucherController::class, 'exportPdf'])->name('payment-vouchers.export-pdf');
    Route::post('/payment-vouchers/{encodedId}/cheque/clear', [PaymentVoucherController::class, 'clearCheque'])->name('payment-vouchers.cheque.clear');
    Route::post('/payment-vouchers/{encodedId}/cheque/fix-duplicate-gl', [PaymentVoucherController::class, 'fixChequeDuplicateGlTransactions'])->name('payment-vouchers.cheque.fix-duplicate-gl');
    Route::post('/payment-vouchers/{encodedId}/cheque/bounce', [PaymentVoucherController::class, 'bounceCheque'])->name('payment-vouchers.cheque.bounce');
    Route::post('/payment-vouchers/{encodedId}/cheque/cancel', [PaymentVoucherController::class, 'cancelCheque'])->name('payment-vouchers.cheque.cancel');
    Route::post('/payment-vouchers/{encodedId}/cheque/stale', [PaymentVoucherController::class, 'markChequeStale'])->name('payment-vouchers.cheque.stale');
    Route::get('payment-vouchers/customer/{customerId}/cash-deposits', [PaymentVoucherController::class, 'getCustomerCashDeposits'])->name('payment-vouchers.customer-cash-deposits');
    Route::get('payment-vouchers/supplier/{supplierId}/invoices', [PaymentVoucherController::class, 'getSupplierInvoices'])->name('payment-vouchers.supplier-invoices');

    // Bill and Payment PDF Export Routes
    Route::get('/bill-purchases/{billPurchase}/export-pdf', [BillPurchaseController::class, 'exportPdf'])->name('bill-purchases.export-pdf');
    Route::get('/payments/{payment}/export-pdf', [BillPurchaseController::class, 'exportPaymentPdf'])->name('bill-payments.export-pdf');

    // Receipt Vouchers
    Route::get('/receipt-vouchers', [ReceiptVoucherController::class, 'index'])->name('receipt-vouchers.index');
    Route::get('/receipt-vouchers/data', [ReceiptVoucherController::class, 'data'])->name('receipt-vouchers.data');
    Route::get('/receipt-vouchers/create', [ReceiptVoucherController::class, 'create'])->name('receipt-vouchers.create');
    Route::post('/receipt-vouchers', [ReceiptVoucherController::class, 'store'])->name('receipt-vouchers.store');
    Route::get('/receipt-vouchers/{encodedId}', [ReceiptVoucherController::class, 'show'])->name('receipt-vouchers.show');
    Route::get('/receipt-vouchers/{encodedId}/edit', [ReceiptVoucherController::class, 'edit'])->name('receipt-vouchers.edit');
    Route::put('/receipt-vouchers/{encodedId}', [ReceiptVoucherController::class, 'update'])->name('receipt-vouchers.update');
    Route::delete('/receipt-vouchers/{encodedId}', [ReceiptVoucherController::class, 'destroy'])->name('receipt-vouchers.destroy');
    Route::get('/receipt-vouchers/{encodedId}/download-attachment', [ReceiptVoucherController::class, 'downloadAttachment'])->name('receipt-vouchers.download-attachment');
    Route::delete('/receipt-vouchers/{encodedId}/remove-attachment', [ReceiptVoucherController::class, 'removeAttachment'])->name('receipt-vouchers.remove-attachment');
    Route::get('/receipt-vouchers/{encodedId}/export-pdf', [ReceiptVoucherController::class, 'exportPdf'])->name('receipt-vouchers.export-pdf');
    Route::post('/receipt-vouchers/{encodedId}/deposit-cheque', [ReceiptVoucherController::class, 'depositCheque'])->name('receipt-vouchers.deposit-cheque');
    Route::get('/receipt-vouchers-debug', [ReceiptVoucherController::class, 'debug'])->name('receipt-vouchers.debug');
    Route::get('receipt-vouchers/customer/{customerId}/invoices', [ReceiptVoucherController::class, 'getCustomerInvoices'])->name('receipt-vouchers.customer-invoices');

    // Bank Accounts
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts');
    Route::get('/bank-accounts/data', [BankAccountController::class, 'getData'])->name('bank-accounts.data');
    Route::get('/bank-accounts/create', [BankAccountController::class, 'create'])->name('bank-accounts.create');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::get('/bank-accounts/{encodedId}', [BankAccountController::class, 'show'])->name('bank-accounts.show');
    Route::get('/bank-accounts/{encodedId}/edit', [BankAccountController::class, 'edit'])->name('bank-accounts.edit');
    Route::put('/bank-accounts/{encodedId}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{encodedId}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // FX Rates Management
    Route::get('/fx-rates', [App\Http\Controllers\Accounting\FxRateController::class, 'index'])->name('fx-rates.index');
    Route::get('/fx-rates/data', [App\Http\Controllers\Accounting\FxRateController::class, 'data'])->name('fx-rates.data');
    Route::get('/fx-rates/create', [App\Http\Controllers\Accounting\FxRateController::class, 'create'])->name('fx-rates.create');
    Route::post('/fx-rates', [App\Http\Controllers\Accounting\FxRateController::class, 'store'])->name('fx-rates.store');
    Route::get('/fx-rates/{id}/edit', [App\Http\Controllers\Accounting\FxRateController::class, 'edit'])->name('fx-rates.edit');
    Route::put('/fx-rates/{id}', [App\Http\Controllers\Accounting\FxRateController::class, 'update'])->name('fx-rates.update');
    Route::post('/fx-rates/{id}/lock', [App\Http\Controllers\Accounting\FxRateController::class, 'lock'])->name('fx-rates.lock');
    Route::post('/fx-rates/{id}/unlock', [App\Http\Controllers\Accounting\FxRateController::class, 'unlock'])->name('fx-rates.unlock');
    Route::get('/fx-rates/import', [App\Http\Controllers\Accounting\FxRateController::class, 'import'])->name('fx-rates.import');
    Route::post('/fx-rates/import', [App\Http\Controllers\Accounting\FxRateController::class, 'processImport'])->name('fx-rates.process-import');
    Route::get('/fx-rates/download-sample', [App\Http\Controllers\Accounting\FxRateController::class, 'downloadSample'])->name('fx-rates.download-sample');
    Route::get('/api/fx-rates/get-rate', [App\Http\Controllers\Accounting\FxRateController::class, 'getRate'])->name('fx-rates.get-rate');

    // FX Rate Override Routes
    Route::post('/fx-rates/override', [App\Http\Controllers\Accounting\FxRateOverrideController::class, 'requestOverride'])->name('fx-rates.override');
    Route::post('/fx-rates/override/{id}/approve', [App\Http\Controllers\Accounting\FxRateOverrideController::class, 'approve'])->name('fx-rates.override.approve');
    Route::post('/fx-rates/override/{id}/reject', [App\Http\Controllers\Accounting\FxRateOverrideController::class, 'reject'])->name('fx-rates.override.reject');

    // FX Revaluation Routes
    Route::get('/fx-revaluation', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'index'])->name('fx-revaluation.index');
    Route::get('/fx-revaluation/data', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'data'])->name('fx-revaluation.data');
    Route::get('/fx-revaluation/create', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'create'])->name('fx-revaluation.create');
    Route::post('/fx-revaluation/preview', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'preview'])->name('fx-revaluation.preview');
    Route::post('/fx-revaluation', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'store'])->name('fx-revaluation.store');
    Route::get('/fx-revaluation/{id}', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'show'])->name('fx-revaluation.show');
    Route::post('/fx-revaluation/{id}/reverse', [App\Http\Controllers\Accounting\FxRevaluationController::class, 'reverse'])->name('fx-revaluation.reverse');

    // FX Settings Routes
    Route::get('/fx-settings', [App\Http\Controllers\Accounting\FxSettingsController::class, 'index'])->name('fx-settings.index');
    Route::put('/fx-settings', [App\Http\Controllers\Accounting\FxSettingsController::class, 'update'])->name('fx-settings.update');

    // Share Capital Management Routes
    Route::prefix('share-capital')->name('share-capital.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\ShareCapitalController::class, 'index'])->name('index');
        // Future routes:
        // Route::get('/shareholders', ...)->name('shareholders.index');
        // Route::get('/issues', ...)->name('issues.index');
        // Route::get('/dividends', ...)->name('dividends.index');
    });

    // Accruals & Prepayments Routes
    Route::prefix('accruals-prepayments')->name('accruals-prepayments.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'store'])->name('store');
        Route::get('/{encodedId}', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'show'])->name('show');
        Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'edit'])->name('edit');
        Route::put('/{encodedId}', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'update'])->name('update');
        Route::delete('/{encodedId}', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'destroy'])->name('destroy');
        Route::post('/{encodedId}/submit', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'submit'])->name('submit');
        Route::post('/{encodedId}/approve', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'approve'])->name('approve');
        Route::post('/{encodedId}/reject', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'reject'])->name('reject');
        Route::post('/{encodedId}/post-journal/{journalId}', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'postJournal'])->name('post-journal');
        Route::post('/{encodedId}/post-all-pending', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'postAllPending'])->name('post-all-pending');
        Route::get('/{encodedId}/amortisation-schedule', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'amortisationSchedule'])->name('amortisation-schedule');
        Route::get('/{encodedId}/export-pdf', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{encodedId}/export-excel', [App\Http\Controllers\Accounting\AccrualsPrepaymentsController::class, 'exportExcel'])->name('export-excel');
    });

    // IAS 37 Provisions & Contingencies
    Route::prefix('provisions')->name('provisions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\ProvisionController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Accounting\ProvisionController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Accounting\ProvisionController::class, 'store'])->name('store');
        Route::post('/compute', [App\Http\Controllers\Accounting\ProvisionController::class, 'compute'])->name('compute');
        Route::get('/disclosure', [App\Http\Controllers\Accounting\ProvisionDisclosureController::class, 'index'])->name('disclosure');
        Route::get('/disclosure/export-json', [App\Http\Controllers\Accounting\ProvisionDisclosureController::class, 'exportJson'])->name('disclosure.export-json');
        Route::get('/disclosure/export-excel', [App\Http\Controllers\Accounting\ProvisionDisclosureController::class, 'exportExcel'])->name('disclosure.export-excel');
        Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ProvisionController::class, 'show'])->name('show');
        Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\ProvisionController::class, 'edit'])->name('edit');
        Route::put('/{encodedId}', [App\Http\Controllers\Accounting\ProvisionController::class, 'update'])->name('update');
        Route::post('/{encodedId}/submit', [App\Http\Controllers\Accounting\ProvisionController::class, 'submitForApproval'])->name('submit');
        Route::post('/{encodedId}/approve', [App\Http\Controllers\Accounting\ProvisionController::class, 'approve'])->name('approve');
        Route::post('/{encodedId}/reject', [App\Http\Controllers\Accounting\ProvisionController::class, 'reject'])->name('reject');
        Route::post('/{encodedId}/remeasure', [App\Http\Controllers\Accounting\ProvisionController::class, 'remeasure'])->name('remeasure');
        Route::post('/{encodedId}/unwind', [App\Http\Controllers\Accounting\ProvisionController::class, 'unwind'])->name('unwind');
    });

    Route::prefix('contingencies')->name('contingencies.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\ContingencyController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Accounting\ContingencyController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Accounting\ContingencyController::class, 'store'])->name('store');
        Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ContingencyController::class, 'show'])->name('show');
    });

    // Share Capital Management Routes
    Route::prefix('share-capital')->name('share-capital.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\ShareCapitalController::class, 'index'])->name('index');

        // Share Classes
        Route::prefix('share-classes')->name('share-classes.')->group(function () {
            Route::get('/', [App\Http\Controllers\Accounting\ShareClassController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Accounting\ShareClassController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Accounting\ShareClassController::class, 'store'])->name('store');
            Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ShareClassController::class, 'show'])->name('show');
            Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\ShareClassController::class, 'edit'])->name('edit');
            Route::put('/{encodedId}', [App\Http\Controllers\Accounting\ShareClassController::class, 'update'])->name('update');
            Route::delete('/{encodedId}', [App\Http\Controllers\Accounting\ShareClassController::class, 'destroy'])->name('destroy');
        });

        // Shareholders
        Route::prefix('shareholders')->name('shareholders.')->group(function () {
            Route::get('/', [App\Http\Controllers\Accounting\ShareholderController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Accounting\ShareholderController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Accounting\ShareholderController::class, 'store'])->name('store');
            Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ShareholderController::class, 'show'])->name('show');
            Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\ShareholderController::class, 'edit'])->name('edit');
            Route::put('/{encodedId}', [App\Http\Controllers\Accounting\ShareholderController::class, 'update'])->name('update');
            Route::delete('/{encodedId}', [App\Http\Controllers\Accounting\ShareholderController::class, 'destroy'])->name('destroy');
        });

        // Share Issues
        Route::prefix('share-issues')->name('share-issues.')->group(function () {
            Route::get('/', [App\Http\Controllers\Accounting\ShareIssueController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Accounting\ShareIssueController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Accounting\ShareIssueController::class, 'store'])->name('store');
            Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ShareIssueController::class, 'show'])->name('show');
            Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\ShareIssueController::class, 'edit'])->name('edit');
            Route::put('/{encodedId}', [App\Http\Controllers\Accounting\ShareIssueController::class, 'update'])->name('update');
            Route::post('/{encodedId}/approve', [App\Http\Controllers\Accounting\ShareIssueController::class, 'approve'])->name('approve');
            Route::post('/{encodedId}/post-to-gl', [App\Http\Controllers\Accounting\ShareIssueController::class, 'postToGl'])->name('post-to-gl');
        });

        // Dividends
        Route::prefix('dividends')->name('dividends.')->group(function () {
            Route::get('/', [App\Http\Controllers\Accounting\ShareDividendController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Accounting\ShareDividendController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Accounting\ShareDividendController::class, 'store'])->name('store');
            Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ShareDividendController::class, 'show'])->name('show');
            Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\ShareDividendController::class, 'edit'])->name('edit');
            Route::put('/{encodedId}', [App\Http\Controllers\Accounting\ShareDividendController::class, 'update'])->name('update');
            Route::post('/{encodedId}/approve', [App\Http\Controllers\Accounting\ShareDividendController::class, 'approve'])->name('approve');
            Route::post('/{encodedId}/declare', [App\Http\Controllers\Accounting\ShareDividendController::class, 'declare'])->name('declare');
            Route::post('/{encodedId}/process-payment', [App\Http\Controllers\Accounting\ShareDividendController::class, 'processPayment'])->name('process-payment');
        });

        // Corporate Actions
        Route::prefix('corporate-actions')->name('corporate-actions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'store'])->name('store');
            Route::get('/{encodedId}', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'show'])->name('show');
            Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'edit'])->name('edit');
            Route::put('/{encodedId}', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'update'])->name('update');
            Route::post('/{encodedId}/approve', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'approve'])->name('approve');
            Route::post('/{encodedId}/execute', [App\Http\Controllers\Accounting\ShareCorporateActionController::class, 'execute'])->name('execute');
        });
    });

    // Bank Reconciliation
    Route::get('/bank-reconciliation/data', [BankReconciliationController::class, 'data'])->name('bank-reconciliation.data');
    Route::resource('bank-reconciliation', BankReconciliationController::class);

    Route::post('/bank-reconciliation/{bankReconciliation}/add-bank-statement-item', [BankReconciliationController::class, 'addBankStatementItem'])->name('bank-reconciliation.add-bank-statement-item');
    Route::post('/bank-reconciliation/{bankReconciliation}/match-items', [BankReconciliationController::class, 'matchItems'])->name('bank-reconciliation.match-items');
    Route::post('/bank-reconciliation/{bankReconciliation}/unmatch-items', [BankReconciliationController::class, 'unmatchItems'])->name('bank-reconciliation.unmatch-items');
    Route::post('/bank-reconciliation/{bankReconciliation}/confirm-book-item', [BankReconciliationController::class, 'confirmBookItem'])->name('bank-reconciliation.confirm-book-item');
    Route::post('/bank-reconciliation/{bankReconciliation}/mark-previous-month-reconciled', [BankReconciliationController::class, 'markPreviousMonthItemReconciled'])->name('bank-reconciliation.mark-previous-month-reconciled');
    Route::post('/bank-reconciliation/{bankReconciliation}/complete', [BankReconciliationController::class, 'completeReconciliation'])->name('bank-reconciliation.complete');
    Route::post('/bank-reconciliation/{bankReconciliation}/update-book-balance', [BankReconciliationController::class, 'updateBookBalance'])->name('bank-reconciliation.update-book-balance');
    Route::post('/bank-reconciliation/refresh-all', [BankReconciliationController::class, 'refreshAllReconciliations'])->name('bank-reconciliation.refresh-all');
    Route::get('/bank-reconciliation/{bankReconciliation}/statement', [BankReconciliationController::class, 'generateStatement'])->name('bank-reconciliation.statement');
    Route::get('/bank-reconciliation/{bankReconciliation}/export-statement', [BankReconciliationController::class, 'exportStatement'])->name('bank-reconciliation.export-statement');

    // Bank Reconciliation Approval Routes
    Route::post('/bank-reconciliation/{bankReconciliation}/submit-for-approval', [BankReconciliationController::class, 'submitForApproval'])->name('bank-reconciliation.submit-for-approval');
    Route::post('/bank-reconciliation/{bankReconciliation}/approve', [BankReconciliationController::class, 'approve'])->name('bank-reconciliation.approve');
    Route::post('/bank-reconciliation/{bankReconciliation}/reject', [BankReconciliationController::class, 'reject'])->name('bank-reconciliation.reject');
    Route::post('/bank-reconciliation/{bankReconciliation}/reassign', [BankReconciliationController::class, 'reassign'])->name('bank-reconciliation.reassign');
    Route::get('/bank-reconciliation/{bankReconciliation}/approval-history', [BankReconciliationController::class, 'approvalHistory'])->name('bank-reconciliation.approval-history');

    // Bill Purchases
    Route::get('/bill-purchases', [BillPurchaseController::class, 'index'])->name('bill-purchases');
    Route::get('/bill-purchases/create', [BillPurchaseController::class, 'create'])->name('bill-purchases.create');
    Route::post('/bill-purchases', [BillPurchaseController::class, 'store'])->name('bill-purchases.store');

    // Bill Payment Management (must come before bill-purchases/{billPurchase} routes)
    Route::get('/bill-purchases/payment/{payment}', [BillPurchaseController::class, 'showPayment'])->name('bill-purchases.payment.show');
    Route::get('/bill-purchases/payment/{payment}/edit', [BillPurchaseController::class, 'editPayment'])->name('bill-purchases.payment.edit');
    Route::put('/bill-purchases/payment/{payment}', [BillPurchaseController::class, 'updatePayment'])->name('bill-purchases.payment.update');
    Route::delete('/bill-purchases/payment/{payment}', [BillPurchaseController::class, 'deletePayment'])->name('bill-purchases.payment.delete');

    Route::get('/bill-purchases/{billPurchase}', [BillPurchaseController::class, 'show'])->name('bill-purchases.show');
    Route::get('/bill-purchases/{billPurchase}/edit', [BillPurchaseController::class, 'edit'])->name('bill-purchases.edit');
    Route::put('/bill-purchases/{billPurchase}', [BillPurchaseController::class, 'update'])->name('bill-purchases.update');
    Route::delete('/bill-purchases/{billPurchase}', [BillPurchaseController::class, 'destroy'])->name('bill-purchases.destroy');
    Route::get('/bill-purchases/{billPurchase}/payment', [BillPurchaseController::class, 'showPaymentForm'])->name('bill-purchases.payment');
    Route::post('/bill-purchases/{billPurchase}/payment', [BillPurchaseController::class, 'processPayment'])->name('bill-purchases.process-payment');

    // Budget
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::get('/budgets/import', [BudgetController::class, 'import'])->name('budgets.import');
    Route::post('/budgets/import', [BudgetController::class, 'storeImport'])->name('budgets.store-import');
    Route::get('/budgets/template/download', [BudgetController::class, 'downloadTemplate'])->name('budgets.download-template');
    Route::get('/budgets/{budget}/export/excel', [BudgetController::class, 'exportExcel'])->name('budgets.export-excel');
    Route::get('/budgets/{budget}/export/pdf', [BudgetController::class, 'exportPdf'])->name('budgets.export-pdf');


    Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
    Route::get('/budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
    Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
    Route::get('/budgets/{budget}/reallocate', [BudgetController::class, 'showReallocate'])->name('budgets.reallocate');
    Route::post('/budgets/{budget}/reallocate', [BudgetController::class, 'reallocate'])->name('budgets.reallocate.store');

    // Budget Approval Routes
    Route::post('/budgets/{budget}/submit-for-approval', [BudgetController::class, 'submitForApproval'])->name('budgets.submit-for-approval');
    Route::post('/budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');
    Route::post('/budgets/{budget}/reject', [BudgetController::class, 'reject'])->name('budgets.reject');
    Route::post('/budgets/{budget}/reassign', [BudgetController::class, 'reassign'])->name('budgets.reassign');
    Route::get('/budgets/{budget}/approval-history', [BudgetController::class, 'approvalHistory'])->name('budgets.approval-history');



    // Journal Entries CRUD
    Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
    Route::get('/journals/data', [JournalController::class, 'data'])->name('journals.data');
    Route::get('/journals/statistics', [JournalController::class, 'statistics'])->name('journals.statistics');
    Route::get('/journals/create', [JournalController::class, 'create'])->name('journals.create');
    Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
    Route::get('/journals/{journal}', [JournalController::class, 'show'])->name('journals.show');
    Route::get('/journals/{journal}/edit', [JournalController::class, 'edit'])->name('journals.edit');
    Route::put('/journals/{journal}', [JournalController::class, 'update'])->name('journals.update');
    Route::delete('/journals/{journal}', [JournalController::class, 'destroy'])->name('journals.destroy');
    Route::get('/journals/{journal}/export-pdf', [JournalController::class, 'exportPdf'])->name('journals.export-pdf');
    Route::get('/journals/{journal}/approve', [JournalController::class, 'showApproval'])->name('journals.approve');
    Route::post('/journals/{journal}/approve', [JournalController::class, 'approve'])->name('journals.approve.store');
    Route::post('/journals/{journal}/reject', [JournalController::class, 'reject'])->name('journals.reject');

    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        // Consolidated Management Report (Landing)
        Route::get('/consolidated-management-report', [App\Http\Controllers\AccountingController::class, 'consolidatedManagementReport'])->name('consolidated-management-report');
        Route::get('/consolidated-management-report/export', [App\Http\Controllers\AccountingController::class, 'exportConsolidatedManagementReport'])->name('consolidated-management-report.export');
        Route::get('/consolidated-management-report/export-word', [App\Http\Controllers\AccountingController::class, 'exportConsolidatedManagementReportWord'])->name('consolidated-management-report.export-word');
        Route::post('/consolidated-management-report/kpis', [App\Http\Controllers\AccountingController::class, 'updateCmrKpis'])->name('consolidated-management-report.kpis');
        Route::get('/other-income', [App\Http\Controllers\Accounting\Reports\OtherIncomeReportController::class, 'index'])->name('other-income');
        Route::get('/other-income/export', [App\Http\Controllers\Accounting\Reports\OtherIncomeReportController::class, 'export'])->name('other-income.export');
        // Trial Balance Report
        Route::get('/trial-balance', [App\Http\Controllers\Accounting\Reports\TrialBalanceReportController::class, 'index'])->name('trial-balance');
        Route::get('/trial-balance/export', [App\Http\Controllers\Accounting\Reports\TrialBalanceReportController::class, 'export'])->name('trial-balance.export');
        Route::get('/income-statement', [App\Http\Controllers\Accounting\Reports\IncomeStatementReportController::class, 'index'])->name('income-statement');
        Route::get('/income-statement/export', [App\Http\Controllers\Accounting\Reports\IncomeStatementReportController::class, 'export'])->name('income-statement.export');
        Route::get('/cash-book', [App\Http\Controllers\Accounting\Reports\CashBookReportController::class, 'index'])->name('cash-book');
        Route::get('/cash-book/export', [App\Http\Controllers\Accounting\Reports\CashBookReportController::class, 'export'])->name('cash-book.export');
        Route::get('/accounting-notes', [App\Http\Controllers\Accounting\Reports\AccountingNotesReportController::class, 'index'])->name('accounting-notes');
        Route::get('/accounting-notes/export', [App\Http\Controllers\Accounting\Reports\AccountingNotesReportController::class, 'export'])->name('accounting-notes.export');
        Route::get('/balance-sheet', [App\Http\Controllers\Accounting\Reports\BalanceSheetReportController::class, 'index'])->name('balance-sheet');
        Route::get('/balance-sheet/export', [App\Http\Controllers\Accounting\Reports\BalanceSheetReportController::class, 'export'])->name('balance-sheet.export');
        Route::get('/cash-flow', [App\Http\Controllers\Accounting\Reports\CashFlowReportController::class, 'index'])->name('cash-flow');
        Route::match(['GET', 'POST'], '/cash-flow/export', [App\Http\Controllers\Accounting\Reports\CashFlowReportController::class, 'export'])->name('cash-flow.export');
        Route::get('/general-ledger', [App\Http\Controllers\Accounting\Reports\GeneralLedgerReportController::class, 'index'])->name('general-ledger');
        Route::get('/general-ledger/export', [App\Http\Controllers\Accounting\Reports\GeneralLedgerReportController::class, 'export'])->name('general-ledger.export');
        Route::get('/expenses-summary', [App\Http\Controllers\Accounting\Reports\ExpensesSummaryReportController::class, 'index'])->name('expenses-summary');
        Route::get('/expenses-summary/export', [App\Http\Controllers\Accounting\Reports\ExpensesSummaryReportController::class, 'export'])->name('expenses-summary.export');
        Route::get('/accounting-notes', [App\Http\Controllers\Accounting\Reports\AccountingNotesReportController::class, 'index'])->name('accounting-notes');
        Route::get('/changes-equity', [App\Http\Controllers\Accounting\Reports\ChangesEquityReportController::class, 'index'])->name('changes-equity');
        Route::post('/changes-equity', [App\Http\Controllers\Accounting\Reports\ChangesEquityReportController::class, 'export'])->name('changes-equity.export');
        Route::get('/bank-reconciliation', [BankReconciliationReportController::class, 'index'])->name('bank-reconciliation-report');
        Route::get('/bank-reconciliation/reports', [BankReconciliationReportController::class, 'reportsIndex'])->name('bank-reconciliation-report.reports-index');
        Route::get('/bank-reconciliation/generate', [BankReconciliationReportController::class, 'generate'])->name('bank-reconciliation-report.generate');
        Route::get('/bank-reconciliation/{bankReconciliation}/show', [BankReconciliationReportController::class, 'show'])->name('bank-reconciliation-report.show');
        Route::get('/bank-reconciliation/{bankReconciliation}/export', [BankReconciliationReportController::class, 'exportReconciliation'])->name('bank-reconciliation-report.export');
        Route::get('/bank-reconciliation/uncleared-items-aging', [BankReconciliationReportController::class, 'unclearedItemsAging'])->name('bank-reconciliation-report.uncleared-items-aging');
        Route::get('/bank-reconciliation/unreconciled-items-aging', [BankReconciliationReportController::class, 'unreconciledItemsAging'])->name('bank-reconciliation-report.unreconciled-items-aging');
        Route::get('/bank-reconciliation/cleared-items', [BankReconciliationReportController::class, 'clearedItemsFromPreviousMonth'])->name('bank-reconciliation-report.cleared-items');
        Route::get('/bank-reconciliation/cleared-transactions', [BankReconciliationReportController::class, 'clearedTransactions'])->name('bank-reconciliation-report.cleared-transactions');
        Route::get('/bank-reconciliation/adjustments', [BankReconciliationReportController::class, 'bankReconciliationAdjustments'])->name('bank-reconciliation-report.adjustments');
        Route::get('/bank-reconciliation/exception-report', [BankReconciliationReportController::class, 'exceptionReport'])->name('bank-reconciliation-report.exception');
        Route::get('/bank-reconciliation/approval-audit-trail', [BankReconciliationReportController::class, 'approvalAuditTrail'])->name('bank-reconciliation-report.approval-audit-trail');
        Route::get('/bank-reconciliation/full-pack', [BankReconciliationReportController::class, 'fullReconciliationPackSelect'])->name('bank-reconciliation-report.full-pack');
        Route::post('/bank-reconciliation/full-pack/download', [BankReconciliationReportController::class, 'fullReconciliationPack'])->name('bank-reconciliation-report.full-pack-download');
        Route::get('/bank-reconciliation/{bankReconciliation}/full-pack', [BankReconciliationReportController::class, 'fullReconciliationPack'])->name('bank-reconciliation-report.full-pack-reconciliation');
        Route::get('/bank-reconciliation/summary-movement', [BankReconciliationReportController::class, 'reconciliationSummaryMovement'])->name('bank-reconciliation-report.summary-movement');
        Route::get('/budget-report', [App\Http\Controllers\Accounting\Reports\BudgetReportController::class, 'index'])->name('budget-report');
        Route::get('/budget-report/export', [App\Http\Controllers\Accounting\Reports\BudgetReportController::class, 'export'])->name('budget-report.export');
        Route::get('/budget-report/export-pdf', [App\Http\Controllers\Accounting\Reports\BudgetReportController::class, 'exportPdf'])->name('budget-report.export-pdf');
    });

    // Transaction Routes
    Route::get('/transactions/double-entries/{accountId}', [App\Http\Controllers\TransactionController::class, 'doubleEntries'])->name('transactions.doubleEntries');
    Route::get('/transactions/details/{transactionId}/{transactionType?}', [App\Http\Controllers\TransactionController::class, 'showTransactionDetails'])->name('transactions.details');

    // Petty Cash Management Routes
    Route::prefix('petty-cash')->name('petty-cash.')->group(function () {
        // Petty Cash Units - Use resource except for routes we define explicitly with encodedId
        Route::resource('units', App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class)->except(['show', 'edit', 'update', 'destroy']);
        Route::get('units/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'show'])->name('units.show');
        Route::get('download-guide', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'downloadGuide'])->name('download-guide');
        Route::get('units/{encodedId}/edit', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'edit'])->name('units.edit');
        Route::put('units/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'update'])->name('units.update');
        Route::delete('units/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'destroy'])->name('units.destroy');
        Route::get('units/{encodedId}/transactions', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'getTransactions'])->name('units.transactions');
        Route::get('units/{encodedId}/replenishments', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'getReplenishments'])->name('units.replenishments');
        Route::get('units/{encodedId}/export-pdf', [App\Http\Controllers\Accounting\PettyCash\PettyCashUnitController::class, 'exportPdf'])->name('units.export-pdf');

        // Expense Categories - Use resource except for routes we define explicitly with encodedId
        Route::resource('categories', App\Http\Controllers\Accounting\PettyCash\PettyCashExpenseCategoryController::class)->except(['edit', 'update', 'destroy']);
        Route::get('categories/{encodedId}/edit', [App\Http\Controllers\Accounting\PettyCash\PettyCashExpenseCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('categories/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashExpenseCategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashExpenseCategoryController::class, 'destroy'])->name('categories.destroy');

        // Transactions
        Route::get('transactions', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/create', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'create'])->name('transactions.create');
        Route::get('transactions/categories', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'getCategories'])->name('transactions.categories');
        Route::get('transactions/expense-accounts', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'getExpenseAccounts'])->name('transactions.expense-accounts');
        Route::post('transactions', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'store'])->name('transactions.store');
        Route::get('transactions/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'show'])->name('transactions.show');
        Route::get('transactions/{encodedId}/edit', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('transactions/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'update'])->name('transactions.update');
        Route::delete('transactions/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::post('transactions/{encodedId}/approve', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'approve'])->name('transactions.approve');
        Route::post('transactions/{encodedId}/reject', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'reject'])->name('transactions.reject');
        Route::post('transactions/{encodedId}/disburse', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'disburse'])->name('transactions.disburse');
        Route::post('transactions/{encodedId}/upload-receipt', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'uploadReceipt'])->name('transactions.upload-receipt');
        Route::post('transactions/{encodedId}/verify-receipt', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'verifyReceipt'])->name('transactions.verify-receipt');
        Route::post('transactions/{encodedId}/post-to-gl', [App\Http\Controllers\Accounting\PettyCash\PettyCashTransactionController::class, 'postToGL'])->name('transactions.post-to-gl');

        // Replenishments
        Route::get('replenishments', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'index'])->name('replenishments.index');
        Route::get('replenishments/create', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'create'])->name('replenishments.create');
        Route::get('replenishments/bank-accounts', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'getBankAccounts'])->name('replenishments.bank-accounts');
        Route::post('replenishments', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'store'])->name('replenishments.store');
        Route::get('replenishments/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'show'])->name('replenishments.show');
        Route::get('replenishments/{encodedId}/edit', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'edit'])->name('replenishments.edit');
        Route::put('replenishments/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'update'])->name('replenishments.update');
        Route::post('replenishments/{encodedId}/approve', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'approve'])->name('replenishments.approve');
        Route::post('replenishments/{encodedId}/reject', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'reject'])->name('replenishments.reject');
        Route::delete('replenishments/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashReplenishmentController::class, 'destroy'])->name('replenishments.destroy');

        // Petty Cash Register
        Route::get('register/{encodedId}', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'index'])->name('register.index');
        Route::get('reconciliation', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'reconciliationIndex'])->name('reconciliation.index');
        Route::get('reconciliation/export/pdf', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'exportReconciliationIndexPdf'])->name('reconciliation.export.pdf');
        Route::get('reconciliation/export/excel', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'exportReconciliationIndexExcel'])->name('reconciliation.export.excel');
        Route::get('register/{encodedId}/reconciliation', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'reconciliation'])->name('register.reconciliation');
        Route::post('register/{encodedId}/reconciliation', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'saveReconciliation'])->name('register.reconciliation.save');
        Route::get('register/{encodedId}/reconciliation/export/pdf', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'exportReconciliationPdf'])->name('register.reconciliation.export.pdf');
        Route::get('register/{encodedId}/reconciliation/export/excel', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'exportReconciliationExcel'])->name('register.reconciliation.export.excel');
        Route::get('register/{encodedId}/export/pdf', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'exportPdf'])->name('register.export.pdf');
        Route::get('register/{encodedId}/export/excel', [App\Http\Controllers\Accounting\PettyCash\PettyCashRegisterController::class, 'exportExcel'])->name('register.export.excel');
    });

    // Inter-Account Transfers Routes
    Route::prefix('account-transfers')->name('account-transfers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\AccountTransferController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Accounting\AccountTransferController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Accounting\AccountTransferController::class, 'store'])->name('store');
        Route::get('/{encodedId}', [App\Http\Controllers\Accounting\AccountTransferController::class, 'show'])->name('show');
        Route::get('/{encodedId}/edit', [App\Http\Controllers\Accounting\AccountTransferController::class, 'edit'])->name('edit');
        Route::put('/{encodedId}', [App\Http\Controllers\Accounting\AccountTransferController::class, 'update'])->name('update');
        Route::delete('/{encodedId}', [App\Http\Controllers\Accounting\AccountTransferController::class, 'destroy'])->name('destroy');
        Route::post('/{encodedId}/approve', [App\Http\Controllers\Accounting\AccountTransferController::class, 'approve'])->name('approve');
        Route::post('/{encodedId}/reject', [App\Http\Controllers\Accounting\AccountTransferController::class, 'reject'])->name('reject');
        Route::post('/{encodedId}/post-to-gl', [App\Http\Controllers\Accounting\AccountTransferController::class, 'postToGL'])->name('post-to-gl');
        Route::get('/{encodedId}/export-pdf', [App\Http\Controllers\Accounting\AccountTransferController::class, 'exportPdf'])->name('export-pdf');
    });

    // API Routes for Account Transfers
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/bank-accounts/{id}/balance', [App\Http\Controllers\Accounting\AccountTransferController::class, 'getBankAccountBalance'])->name('bank-accounts.balance');
    });

    // Cashflow Forecasting Routes
    Route::prefix('cashflow-forecasts')->name('cashflow-forecasts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'create'])->name('create');
        Route::post('/calculate-balance', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'calculateBalance'])->name('calculate-balance');
        Route::post('/', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'store'])->name('store');
        Route::get('/{encodedId}', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'show'])->name('show');
        Route::post('/{encodedId}/regenerate', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'regenerate'])->name('regenerate');
        Route::get('/{encodedId}/export/pdf', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/{encodedId}/export/excel', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'exportExcel'])->name('export.excel');
        Route::get('/{encodedId}/ap-ar-impact', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'apArCashImpact'])->name('ap-ar-impact');
        Route::get('/{encodedId}/scenario-comparison', [App\Http\Controllers\Accounting\CashflowForecastController::class, 'scenarioComparison'])->name('scenario-comparison');
    });
});

////////////////////////////////////////////// END ACCOUNTING MANAGEMENT ///////////////////////////////////////////

////////////////////////////////////////////// INVENTORY MANAGEMENT ///////////////////////////////////////////

Route::prefix('inventory')->name('inventory.')->middleware(['auth', 'company.scope', 'check.inventory.cost.method', 'require.branch'])->group(function () {
    // Inventory Management Dashboard
    Route::get('/', [InventoryController::class, 'index'])->name('index');

    Route::get('/value', [App\Http\Controllers\Inventory\InventoryValueController::class, 'index'])->name('value.index');
    Route::get('/value/location/{locationId}', [App\Http\Controllers\Inventory\InventoryValueController::class, 'show'])->name('value.show');

    // Inventory Items
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::post('/items/import', [ItemController::class, 'import'])->name('items.import');
    Route::get('/items/import-status/{batchId}', [ItemController::class, 'importStatus'])->name('items.import-status');
    Route::get('/items/download-template', [ItemController::class, 'downloadTemplate'])->name('items.download-template');
    Route::get('/items/export', [ItemController::class, 'export'])->name('items.export');
    Route::get('/items/{encodedId}', [ItemController::class, 'show'])->name('items.show');
    Route::get('/items/{encodedId}/movements', [ItemController::class, 'movements'])->name('items.movements');
    Route::get('/items/{encodedId}/stock', [ItemController::class, 'getItemStock'])->name('items.stock');
    Route::get('/items/{encodedId}/edit', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/items/{encodedId}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('/items/{encodedId}', [ItemController::class, 'destroy'])->name('items.destroy');

    // Stock Reports
    Route::get('/stock-report', [ItemController::class, 'getStockReport'])->name('stock.report');
    Route::get('/location/{locationId}/stock', [ItemController::class, 'getLocationStock'])->name('location.stock');

    // Inventory Categories (use hash ids)
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{encodedId}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{encodedId}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{encodedId}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{encodedId}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Stock Movements
    Route::get('/movements', [MovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/create', [MovementController::class, 'create'])->name('movements.create');
    Route::post('/movements', [MovementController::class, 'store'])->name('movements.store');
    Route::get('/movements/{movement}', [MovementController::class, 'show'])->name('movements.show');
    Route::get('/movements/{movement}/edit', [MovementController::class, 'edit'])->name('movements.edit');
    Route::put('/movements/{movement}', [MovementController::class, 'update'])->name('movements.update');
    Route::delete('/movements/{movement}', [MovementController::class, 'destroy'])->name('movements.destroy');

    // Write-offs
    Route::get('/write-offs', [WriteOffController::class, 'index'])->name('write-offs.index');
    Route::get('/write-offs/create', [WriteOffController::class, 'create'])->name('write-offs.create');
    Route::post('/write-offs', [WriteOffController::class, 'store'])->name('write-offs.store');
    Route::get('/write-offs/{movement}', [WriteOffController::class, 'show'])->name('write-offs.show');
    Route::get('/write-offs/{movement}/edit', [WriteOffController::class, 'edit'])->name('write-offs.edit');
    Route::put('/write-offs/{movement}', [WriteOffController::class, 'update'])->name('write-offs.update');
    Route::delete('/write-offs/{movement}', [WriteOffController::class, 'destroy'])->name('write-offs.destroy');

    // Transfers
    Route::get('/transfers', [TransferController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [TransferController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transfer}', [TransferController::class, 'show'])->name('transfers.show');
    Route::get('/transfers/{transfer}/edit', [TransferController::class, 'edit'])->name('transfers.edit');
    Route::put('/transfers/{transfer}', [TransferController::class, 'update'])->name('transfers.update');
    Route::delete('/transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');

    // Bulk Transfer Operations
    Route::post('/transfers/bulk-delete', [TransferController::class, 'bulkDelete'])->name('transfers.bulk-delete');
    Route::get('/transfers/bulk-edit', [TransferController::class, 'bulkEdit'])->name('transfers.bulk-edit');
    Route::put('/transfers/bulk-update', [TransferController::class, 'bulkUpdate'])->name('transfers.bulk-update');

    // Transfer Requests
    Route::get('/transfer-requests', [TransferRequestController::class, 'index'])->name('transfer-requests.index');
    Route::get('/transfer-requests/create', [TransferRequestController::class, 'create'])->name('transfer-requests.create');
    Route::post('/transfer-requests', [TransferRequestController::class, 'store'])->name('transfer-requests.store');
    Route::get('/transfer-requests/{transferRequest}', [TransferRequestController::class, 'show'])->name('transfer-requests.show');
    Route::get('/transfer-requests/{transferRequest}/edit', [TransferRequestController::class, 'edit'])->name('transfer-requests.edit');
    Route::put('/transfer-requests/{transferRequest}', [TransferRequestController::class, 'update'])->name('transfer-requests.update');
    Route::post('/transfer-requests/{transferRequest}/approve', [TransferRequestController::class, 'approve'])->name('transfer-requests.approve');
    Route::post('/transfer-requests/{transferRequest}/reject', [TransferRequestController::class, 'reject'])->name('transfer-requests.reject');


    // Inventory Count Routes
    Route::prefix('counts')->name('counts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Inventory\InventoryCountController::class, 'index'])->name('index');

        // Count Periods
        Route::get('/periods/create', [App\Http\Controllers\Inventory\InventoryCountController::class, 'createPeriod'])->name('periods.create');
        Route::post('/periods', [App\Http\Controllers\Inventory\InventoryCountController::class, 'storePeriod'])->name('periods.store');
        Route::get('/periods/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showPeriod'])->name('periods.show');

        // Count Sessions
        Route::get('/sessions/create/{periodEncodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'createSession'])->name('sessions.create');
        Route::post('/sessions/{periodEncodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'storeSession'])->name('sessions.store');
        Route::get('/sessions/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showSession'])->name('sessions.show');
        Route::post('/sessions/{encodedId}/freeze', [App\Http\Controllers\Inventory\InventoryCountController::class, 'freezeSession'])->name('sessions.freeze');
        Route::post('/sessions/{encodedId}/start-counting', [App\Http\Controllers\Inventory\InventoryCountController::class, 'startCounting'])->name('sessions.start-counting');
        Route::post('/sessions/{encodedId}/complete-counting', [App\Http\Controllers\Inventory\InventoryCountController::class, 'completeCounting'])->name('sessions.complete-counting');
        Route::post('/sessions/{encodedId}/approve', [App\Http\Controllers\Inventory\InventoryCountController::class, 'approveCountSession'])->name('sessions.approve');
        Route::post('/sessions/{encodedId}/reject', [App\Http\Controllers\Inventory\InventoryCountController::class, 'rejectCountSession'])->name('sessions.reject');
        Route::get('/sessions/{encodedId}/variances', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showVariances'])->name('sessions.variances');
        Route::get('/sessions/{encodedId}/export-counting-sheets-pdf', [App\Http\Controllers\Inventory\InventoryCountController::class, 'exportCountingSheetsPdf'])->name('sessions.export-counting-sheets-pdf');
        Route::get('/sessions/{encodedId}/export-counting-sheets-excel', [App\Http\Controllers\Inventory\InventoryCountController::class, 'exportCountingSheetsExcel'])->name('sessions.export-counting-sheets-excel');
        Route::get('/sessions/{encodedId}/assign-team', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showTeamAssignment'])->name('sessions.assign-team');
        Route::post('/sessions/{encodedId}/assign-team', [App\Http\Controllers\Inventory\InventoryCountController::class, 'assignTeam'])->name('sessions.assign-team.store');
        Route::get('/sessions/{encodedId}/download-counting-template', [App\Http\Controllers\Inventory\InventoryCountController::class, 'downloadCountingTemplate'])->name('sessions.download-counting-template');
        Route::post('/sessions/{encodedId}/upload-counting-excel', [App\Http\Controllers\Inventory\InventoryCountController::class, 'uploadCountingExcel'])->name('sessions.upload-counting-excel');

        // Count Entries
        Route::get('/entries/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showEntry'])->name('entries.show');
        Route::post('/entries/{encodedId}/update-physical-qty', [App\Http\Controllers\Inventory\InventoryCountController::class, 'updatePhysicalQuantity'])->name('entries.update-physical-qty');
        Route::post('/entries/{encodedId}/recount', [App\Http\Controllers\Inventory\InventoryCountController::class, 'requestRecount'])->name('entries.recount');
        Route::post('/entries/{encodedId}/verify', [App\Http\Controllers\Inventory\InventoryCountController::class, 'verifyEntry'])->name('entries.verify');

        // Variances
        Route::post('/variances/{encodedId}/investigation', [App\Http\Controllers\Inventory\InventoryCountController::class, 'updateVarianceInvestigation'])->name('variances.investigation');

        // Adjustments
        Route::get('/sessions/{encodedId}/adjustments', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showAdjustments'])->name('sessions.adjustments');
        Route::get('/adjustments/create/{varianceId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'createAdjustmentForm'])->name('adjustments.create-form');
        Route::post('/adjustments/create/{varianceId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'createAdjustment'])->name('adjustments.create');
        Route::post('/adjustments/bulk-create/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'bulkCreateAdjustments'])->name('adjustments.bulk-create');
        Route::post('/adjustments/bulk-approve/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'bulkApproveAdjustments'])->name('adjustments.bulk-approve');
        Route::post('/adjustments/bulk-post/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'bulkPostAdjustmentsToGL'])->name('adjustments.bulk-post');
        Route::get('/adjustments/{encodedId}', [App\Http\Controllers\Inventory\InventoryCountController::class, 'showAdjustment'])->name('adjustments.show');
        Route::post('/adjustments/{encodedId}/approve', [App\Http\Controllers\Inventory\InventoryCountController::class, 'approveAdjustment'])->name('adjustments.approve');
        Route::post('/adjustments/{encodedId}/reject', [App\Http\Controllers\Inventory\InventoryCountController::class, 'rejectAdjustment'])->name('adjustments.reject');
        Route::post('/adjustments/{encodedId}/post-to-gl', [App\Http\Controllers\Inventory\InventoryCountController::class, 'postAdjustmentToGL'])->name('adjustments.post-to-gl');
    });

    // Inventory Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Inventory\InventoryReportController::class, 'index'])->name('index');
        Route::get('/stock-on-hand', [App\Http\Controllers\Inventory\InventoryReportController::class, 'stockOnHand'])->name('stock-on-hand');
        Route::get('/stock-on-hand/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'stockOnHandExportExcel'])->name('stock-on-hand.export.excel');
        Route::get('/stock-on-hand/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'stockOnHandExportPdf'])->name('stock-on-hand.export.pdf');
        Route::get('/stock-valuation', [App\Http\Controllers\Inventory\InventoryReportController::class, 'stockValuation'])->name('stock-valuation');
        Route::get('/movement-register', [App\Http\Controllers\Inventory\InventoryReportController::class, 'movementRegister'])->name('movement-register');
        Route::get('/movement-register/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'movementRegisterExportExcel'])->name('movement-register.export.excel');
        Route::get('/movement-register/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'movementRegisterExportPdf'])->name('movement-register.export.pdf');
        Route::get('/aging-stock', [App\Http\Controllers\Inventory\InventoryReportController::class, 'agingStock'])->name('aging-stock');
        Route::get('/reorder', [App\Http\Controllers\Inventory\InventoryReportController::class, 'reorderReport'])->name('reorder');
        Route::get('/reorder/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'reorderReportExportExcel'])->name('reorder.export.excel');
        Route::get('/reorder/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'reorderReportExportPdf'])->name('reorder.export.pdf');
        Route::get('/over-understock', [App\Http\Controllers\Inventory\InventoryReportController::class, 'overUnderstock'])->name('over-understock');
        Route::get('/over-understock/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'overUnderstockExportExcel'])->name('over-understock.export.excel');
        Route::get('/over-understock/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'overUnderstockExportPdf'])->name('over-understock.export.pdf');
        Route::get('/item-ledger', [App\Http\Controllers\Inventory\InventoryReportController::class, 'itemLedger'])->name('item-ledger');
        Route::get('/item-ledger/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'itemLedgerExportExcel'])->name('item-ledger.export.excel');
        Route::get('/item-ledger/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'itemLedgerExportPdf'])->name('item-ledger.export.pdf');
        Route::get('/cost-changes', [App\Http\Controllers\Inventory\InventoryReportController::class, 'costChanges'])->name('cost-changes');
        Route::get('/cost-changes/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'costChangesExportExcel'])->name('cost-changes.export.excel');
        Route::get('/cost-changes/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'costChangesExportPdf'])->name('cost-changes.export.pdf');
        Route::get('/stock-take-variance', [App\Http\Controllers\Inventory\InventoryReportController::class, 'stockTakeVariance'])->name('stock-take-variance');
        Route::get('/full-inventory-count', [App\Http\Controllers\Inventory\InventoryReportController::class, 'fullInventoryCountReport'])->name('full-inventory-count');
        Route::get('/variance-summary', [App\Http\Controllers\Inventory\InventoryReportController::class, 'varianceSummaryReport'])->name('variance-summary');
        Route::get('/variance-value', [App\Http\Controllers\Inventory\InventoryReportController::class, 'varianceValueReport'])->name('variance-value');
        Route::get('/high-value-scorecard', [App\Http\Controllers\Inventory\InventoryReportController::class, 'highValueItemsScorecard'])->name('high-value-scorecard');
        Route::get('/expiry-damaged-stock', [App\Http\Controllers\Inventory\InventoryReportController::class, 'expiryDamagedStockReport'])->name('expiry-damaged-stock');
        Route::get('/cycle-count-performance', [App\Http\Controllers\Inventory\InventoryReportController::class, 'cycleCountPerformanceReport'])->name('cycle-count-performance');
        Route::get('/year-end-stock-valuation', [App\Http\Controllers\Inventory\InventoryReportController::class, 'yearEndStockValuationReport'])->name('year-end-stock-valuation');
        Route::get('/location-bin', [App\Http\Controllers\Inventory\InventoryReportController::class, 'locationBin'])->name('location-bin');
        Route::get('/category-brand-mix', [App\Http\Controllers\Inventory\InventoryReportController::class, 'categoryBrandMix'])->name('category-brand-mix');
        Route::get('/category-brand-mix/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'categoryBrandMixExportExcel'])->name('category-brand-mix.export.excel');
        Route::get('/category-brand-mix/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'categoryBrandMixExportPdf'])->name('category-brand-mix.export.pdf');
        Route::get('/profit-margin', [App\Http\Controllers\Inventory\InventoryReportController::class, 'profitMargin'])->name('profit-margin');
        Route::get('/profit-margin/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'profitMarginExportExcel'])->name('profit-margin.export.excel');
        Route::get('/profit-margin/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'profitMarginExportPdf'])->name('profit-margin.export.pdf');
        Route::get('/inventory-value-summary', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryValueSummary'])->name('inventory-value-summary');
        Route::get('/inventory-value-summary/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryValueSummaryExportPdf'])->name('inventory-value-summary.export.pdf');
        Route::get('/inventory-value-summary/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryValueSummaryExportExcel'])->name('inventory-value-summary.export.excel');

        // Inventory Quantity Summary
        Route::get('/inventory-quantity-summary', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryQuantitySummary'])->name('inventory-quantity-summary');
        Route::get('/inventory-quantity-summary/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryQuantitySummaryExportPdf'])->name('inventory-quantity-summary.export.pdf');
        Route::get('/inventory-quantity-summary/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryQuantitySummaryExportExcel'])->name('inventory-quantity-summary.export.excel');

        // Inventory Profit Margin
        Route::get('/inventory-profit-margin', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryProfitMargin'])->name('inventory-profit-margin');
        Route::get('/inventory-profit-margin/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryProfitMarginExportPdf'])->name('inventory-profit-margin.export.pdf');
        Route::get('/inventory-profit-margin/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryProfitMarginExportExcel'])->name('inventory-profit-margin.export.excel');

        // Inventory Price List
        Route::get('/inventory-price-list', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryPriceList'])->name('inventory-price-list');
        Route::get('/inventory-price-list/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryPriceListExportPdf'])->name('inventory-price-list.export.pdf');
        Route::get('/inventory-price-list/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryPriceListExportExcel'])->name('inventory-price-list.export.excel');

        // Inventory Costing Calculation Worksheet
        Route::get('/inventory-costing-worksheet', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryCostingWorksheet'])->name('inventory-costing-worksheet');
        Route::get('/inventory-costing-worksheet/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryCostingWorksheetExportPdf'])->name('inventory-costing-worksheet.export.pdf');
        Route::get('/inventory-costing-worksheet/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryCostingWorksheetExportExcel'])->name('inventory-costing-worksheet.export.excel');

        // Inventory Quantity by Location
        Route::get('/inventory-quantity-by-location', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryQuantityByLocation'])->name('inventory-quantity-by-location');
        Route::get('/inventory-quantity-by-location/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryQuantityByLocationExportPdf'])->name('inventory-quantity-by-location.export.pdf');
        Route::get('/inventory-quantity-by-location/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryQuantityByLocationExportExcel'])->name('inventory-quantity-by-location.export.excel');

        // Inventory Transfer Movement Report
        Route::get('/inventory-transfer-movement', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryTransferMovement'])->name('inventory-transfer-movement');
        Route::get('/inventory-transfer-movement/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryTransferMovementExportPdf'])->name('inventory-transfer-movement.export.pdf');
        Route::get('/inventory-transfer-movement/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryTransferMovementExportExcel'])->name('inventory-transfer-movement.export.excel');

        // Inventory Aging Report
        Route::get('/inventory-aging', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryAging'])->name('inventory-aging');
        Route::get('/inventory-aging/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryAgingExportPdf'])->name('inventory-aging.export.pdf');
        Route::get('/inventory-aging/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'inventoryAgingExportExcel'])->name('inventory-aging.export.excel');

        // Category Performance Report
        Route::get('/category-performance', [App\Http\Controllers\Inventory\InventoryReportController::class, 'categoryPerformance'])->name('category-performance');
        Route::get('/category-performance/export/pdf', [App\Http\Controllers\Inventory\InventoryReportController::class, 'categoryPerformanceExportPdf'])->name('category-performance.export.pdf');
        Route::get('/category-performance/export/excel', [App\Http\Controllers\Inventory\InventoryReportController::class, 'categoryPerformanceExportExcel'])->name('category-performance.export.excel');

        // Expiry Reports
        Route::prefix('expiry')->name('expiry.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Inventory\ExpiryReportController::class, 'index'])->name('index');
            Route::get('/expiring-soon', [\App\Http\Controllers\Inventory\ExpiryReportController::class, 'expiringSoon'])->name('expiring-soon');
            Route::get('/expired', [\App\Http\Controllers\Inventory\ExpiryReportController::class, 'expired'])->name('expired');
            Route::post('/stock-details', [\App\Http\Controllers\Inventory\ExpiryReportController::class, 'stockDetails'])->name('stock-details');
        });
    });
});

// API Routes for AJAX calls
Route::get('/api/branches/{branch}/locations', function ($branchId) {
    $locations = \App\Models\InventoryLocation::where('branch_id', $branchId)
        ->where('is_active', true)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

    return response()->json($locations);
})->name('api.branches.locations');

Route::get('/api/branches/{branch}/users', function ($branchId) {
    $users = \App\Models\User::where('company_id', auth()->user()->company_id)
        ->whereHas('branches', function ($query) use ($branchId) {
            $query->where('branches.id', $branchId);
        })
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

    return response()->json($users);
})->middleware('auth')->name('api.branches.users');

// Debug route to test users
Route::get('/debug/users/{branchId}', function ($branchId) {
    $users = \App\Models\User::where('company_id', 1)
        ->whereHas('branches', function ($query) use ($branchId) {
            $query->where('branches.id', $branchId);
        })
        ->orderBy('name')
        ->get(['id', 'name', 'email']);

    return response()->json([
        'branch_id' => $branchId,
        'users_count' => $users->count(),
        'users' => $users
    ]);
})->name('debug.users');

////////////////////////////////////////////// END INVENTORY MANAGEMENT ///////////////////////////////////////////

// Sales Reports Routes
Route::prefix('sales/reports')->name('sales.reports.')->middleware(['auth', 'require.branch'])->group(function () {
    Route::get('/', [App\Http\Controllers\Sales\SalesReportController::class, 'index'])->name('index');
    Route::get('/sales-summary', [App\Http\Controllers\Sales\SalesReportController::class, 'salesSummary'])->name('sales-summary');
    Route::get('/sales-by-product', [App\Http\Controllers\Sales\SalesReportController::class, 'salesByProduct'])->name('sales-by-product');
    Route::get('/sales-by-customer', [App\Http\Controllers\Sales\SalesReportController::class, 'salesByCustomer'])->name('sales-by-customer');
    Route::get('/sales-by-branch', [App\Http\Controllers\Sales\SalesReportController::class, 'salesByBranch'])->name('sales-by-branch');
    Route::get('/branch-profitability', [App\Http\Controllers\Sales\SalesReportController::class, 'branchProfitability'])->name('branch-profitability');
    Route::get('/sales-trend', [App\Http\Controllers\Sales\SalesReportController::class, 'salesTrend'])->name('sales-trend');
    Route::get('/sales-by-salesperson', [App\Http\Controllers\Sales\SalesReportController::class, 'salesBySalesperson'])->name('sales-by-salesperson');
    Route::get('/discount-effectiveness', [App\Http\Controllers\Sales\SalesReportController::class, 'discountEffectiveness'])->name('discount-effectiveness');
    Route::get('/sales-return', [App\Http\Controllers\Sales\SalesReportController::class, 'salesReturn'])->name('sales-return');
    Route::get('/profitability-by-product', [App\Http\Controllers\Sales\SalesReportController::class, 'profitabilityByProduct'])->name('profitability-by-product');
    Route::get('/receivables-aging', [App\Http\Controllers\Sales\SalesReportController::class, 'receivablesAging'])->name('receivables-aging');
    Route::get('/collection-efficiency', [App\Http\Controllers\Sales\SalesReportController::class, 'collectionEfficiency'])->name('collection-efficiency');
    Route::get('/invoice-register', [App\Http\Controllers\Sales\SalesReportController::class, 'invoiceRegister'])->name('invoice-register');
    Route::get('/customer-statement', [App\Http\Controllers\Sales\SalesReportController::class, 'customerStatement'])->name('customer-statement');
    Route::get('/customer-statement/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportCustomerStatementPdf'])->name('customer-statement.export.pdf');
    Route::get('/customer-statement/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportCustomerStatementExcel'])->name('customer-statement.export.excel');
    Route::get('/sales-return/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesReturnPdf'])->name('sales-return.export.pdf');
    Route::get('/sales-return/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesReturnExcel'])->name('sales-return.export.excel');
    Route::get('/paid-invoice', [App\Http\Controllers\Sales\SalesReportController::class, 'paidInvoice'])->name('paid-invoice');
    Route::get('/credit-note', [App\Http\Controllers\Sales\SalesReportController::class, 'creditNote'])->name('credit-note');
    Route::get('/tax-invoice', [App\Http\Controllers\Sales\SalesReportController::class, 'taxInvoice'])->name('tax-invoice');

    // Export routes
    Route::get('/sales-summary/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesSummaryPdf'])->name('sales-summary.export.pdf');
    Route::get('/sales-summary/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesSummaryExcel'])->name('sales-summary.export.excel');
    Route::get('/sales-by-product/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesByProductPdf'])->name('sales-by-product.export.pdf');
    Route::get('/sales-by-product/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesByProductExcel'])->name('sales-by-product.export.excel');
    Route::get('/sales-by-customer/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesByCustomerPdf'])->name('sales-by-customer.export.pdf');
    Route::get('/sales-by-customer/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesByCustomerExcel'])->name('sales-by-customer.export.excel');
    Route::get('/sales-by-branch/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesByBranchPdf'])->name('sales-by-branch.export.pdf');
    Route::get('/sales-by-branch/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesByBranchExcel'])->name('sales-by-branch.export.excel');
    Route::get('/branch-profitability/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportBranchProfitabilityPdf'])->name('branch-profitability.export.pdf');
    Route::get('/branch-profitability/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportBranchProfitabilityExcel'])->name('branch-profitability.export.excel');
    Route::get('/receivables-aging/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportReceivablesAgingPdf'])->name('receivables-aging.export.pdf');
    Route::get('/receivables-aging/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportReceivablesAgingExcel'])->name('receivables-aging.export.excel');
    Route::get('/tax-invoice/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportTaxInvoicePdf'])->name('tax-invoice.export.pdf');
    Route::get('/tax-invoice/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportTaxInvoiceExcel'])->name('tax-invoice.export.excel');
    Route::get('/sales-by-salesperson/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesBySalespersonPdf'])->name('sales-by-salesperson.export.pdf');
    Route::get('/sales-by-salesperson/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportSalesBySalespersonExcel'])->name('sales-by-salesperson.export.excel');
    Route::get('/discount-effectiveness/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportDiscountEffectivenessPdf'])->name('discount-effectiveness.export.pdf');
    Route::get('/discount-effectiveness/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportDiscountEffectivenessExcel'])->name('discount-effectiveness.export.excel');
    Route::get('/profitability-by-product/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportProfitabilityByProductPdf'])->name('profitability-by-product.export.pdf');
    Route::get('/profitability-by-product/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportProfitabilityByProductExcel'])->name('profitability-by-product.export.excel');
    Route::get('/collection-efficiency/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportCollectionEfficiencyPdf'])->name('collection-efficiency.export.pdf');
    Route::get('/collection-efficiency/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportCollectionEfficiencyExcel'])->name('collection-efficiency.export.excel');
    Route::get('/invoice-register/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportInvoiceRegisterPdf'])->name('invoice-register.export.pdf');
    Route::get('/invoice-register/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportInvoiceRegisterExcel'])->name('invoice-register.export.excel');
    Route::get('/paid-invoice/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportPaidInvoicePdf'])->name('paid-invoice.export.pdf');
    Route::get('/paid-invoice/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportPaidInvoiceExcel'])->name('paid-invoice.export.excel');
    Route::get('/credit-note/export/pdf', [App\Http\Controllers\Sales\SalesReportController::class, 'exportCreditNotePdf'])->name('credit-note.export.pdf');
    Route::get('/credit-note/export/excel', [App\Http\Controllers\Sales\SalesReportController::class, 'exportCreditNoteExcel'])->name('credit-note.export.excel');
});

////////////////////////////////////////////// PURCHASE MANAGEMENT ///////////////////////////////////////////

Route::prefix('purchases')->name('purchases.')->middleware(['auth', 'company.scope', 'check.inventory.cost.method', 'require.branch'])->group(function () {
    Route::get('/', [PurchaseController::class, 'index'])->name('index');

    // Purchase Requisitions
    Route::prefix('requisitions')->name('requisitions.')->group(function () {
        Route::get('/', [PurchaseRequisitionController::class, 'index'])->name('index');
        Route::get('/data', [PurchaseRequisitionController::class, 'data'])->name('data');
        Route::get('/create', [PurchaseRequisitionController::class, 'create'])->name('create');
        Route::post('/', [PurchaseRequisitionController::class, 'store'])->name('store');
        Route::post('/check-budget', [PurchaseRequisitionController::class, 'checkBudget'])->name('check-budget');
        Route::get('/{requisition}', [PurchaseRequisitionController::class, 'show'])->name('show');
        Route::post('/{requisition}/submit', [PurchaseRequisitionController::class, 'submit'])->name('submit');
        Route::post('/{requisition}/choose-supplier-create-po', [PurchaseRequisitionController::class, 'chooseSupplierAndCreatePo'])->name('choose-supplier-create-po');
        Route::post('/{requisition}/approve', [PurchaseRequisitionController::class, 'approve'])->name('approve');
        Route::post('/{requisition}/reject', [PurchaseRequisitionController::class, 'reject'])->name('reject');
        Route::post('/{requisition}/set-preferred-supplier', [PurchaseRequisitionController::class, 'setPreferredSupplierFromQuotation'])->name('set-preferred-supplier');
        Route::delete('/{requisition}', [PurchaseRequisitionController::class, 'destroy'])->name('destroy');
    });

    // Purchase Quotations (copy-from-invoice before create to avoid conflict)
    Route::get('quotations/create-from-invoice/{invoiceEncodedId}', [QuotationController::class, 'createFromInvoice'])->name('quotations.create-from-invoice');
    Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::get('quotations/data', [QuotationController::class, 'data'])->name('quotations.data');
    Route::get('quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
    Route::post('quotations', [QuotationController::class, 'store'])->name('quotations.store');
    Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::get('quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quotations.edit');
    Route::put('quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update');
    Route::delete('quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy');
    Route::put('quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.updateStatus');
    Route::post('quotations/{quotation}/send-email', [QuotationController::class, 'sendEmail'])->name('quotations.send-email');
    Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');

    // Purchase Orders (copy-from-invoice before create to avoid conflict)
    Route::get('orders/create-from-invoice/{invoiceEncodedId}', [OrderController::class, 'createFromInvoice'])->name('orders.create-from-invoice');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::get('orders/create-from-stock', [OrderController::class, 'createFromStock'])->name('orders.create-from-stock');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
    // GRN from Order
    Route::get('orders/{encodedId}/grn/create', [OrderController::class, 'createGrnForm'])->name('orders.grn.create');
    Route::post('orders/{encodedId}/grn', [OrderController::class, 'storeGrn'])->name('orders.grn.store');

    // Standalone GRN (copy-from-invoice before create to avoid conflict)
    Route::get('grn/create-from-invoice/{invoiceEncodedId}', [OrderController::class, 'createGrnFromInvoice'])->name('grn.create-from-invoice');
    Route::get('grn/create', [OrderController::class, 'createGrnForm'])->name('grn.create');
    Route::post('grn/standalone', [OrderController::class, 'storeStandaloneGrn'])->name('grn.store-standalone');

    // GRN CRUD
    Route::get('grn/{grn}', [OrderController::class, 'grnShow'])->name('grn.show');
    Route::get('grn/{grn}/print', [OrderController::class, 'grnPrint'])->name('grn.print');
    Route::get('grn/{grn}/edit', [OrderController::class, 'grnEdit'])->name('grn.edit');
    Route::put('grn/{grn}', [OrderController::class, 'grnUpdate'])->name('grn.update');
    Route::put('grn/{grn}/qc-items', [OrderController::class, 'grnUpdateLineQc'])->name('grn.qc-items.update');
    Route::put('grn/{grn}/qc', [OrderController::class, 'grnUpdateQc'])->name('grn.qc.update');
    Route::delete('grn/{grn}', [OrderController::class, 'grnDestroy'])->name('grn.destroy');
    Route::get('orders/{encodedId}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('orders/{encodedId}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('orders/{encodedId}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{encodedId}', [OrderController::class, 'destroy'])->name('orders.destroy');
    Route::put('orders/{encodedId}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
    Route::get('orders/{encodedId}/print', [OrderController::class, 'print'])->name('orders.print');
    Route::get('orders/convert-from-quotation/{quotation}', [OrderController::class, 'convertFromQuotation'])->name('orders.convert-from-quotation');

    // GRN Management
    Route::get('grn', [OrderController::class, 'grnIndex'])->name('grn.index');

    // Cash Purchases
    Route::prefix('cash-purchases')->name('cash-purchases.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'store'])->name('store');
        Route::get('/{encodedId}', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'show'])->name('show');
        Route::get('/{encodedId}/edit', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'edit'])->name('edit');
        Route::get('/{encodedId}/export-pdf', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'exportPdf'])->name('export-pdf');
        Route::put('/{encodedId}', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'update'])->name('update');
        Route::delete('/{encodedId}', [\App\Http\Controllers\Purchase\CashPurchaseController::class, 'destroy'])->name('destroy');
    });

    // Opening Balances (Purchases)
    Route::get('opening-balances', [\App\Http\Controllers\Purchase\OpeningBalanceController::class, 'index'])->name('opening-balances.index');
    Route::get('opening-balances/create', [\App\Http\Controllers\Purchase\OpeningBalanceController::class, 'create'])->name('opening-balances.create');
    Route::post('opening-balances', [\App\Http\Controllers\Purchase\OpeningBalanceController::class, 'store'])->name('opening-balances.store');
    Route::get('opening-balances/{encodedId}', [\App\Http\Controllers\Purchase\OpeningBalanceController::class, 'show'])->name('opening-balances.show');

    // Hesabu za Kila Siku (daily supplier accounts hub)
    Route::prefix('daily-accounts')->name('daily-accounts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'index'])->name('index');
        Route::post('/mauzo', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'storeMauzo'])->name('mauzo.store');
        Route::post('/matumizi', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'storeMatumizi'])->name('matumizi.store');
        Route::post('/manunuzi', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'storeManunuzi'])->name('manunuzi.store');
        Route::post('/stoo', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'storeStoo'])->name('stoo.store');
        Route::get('/matumizi-manunuzi', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'matumiziManunuzi'])->name('matumizi-manunuzi');
        Route::get('/report', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'report'])->name('report');
        Route::get('/report/show', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'reportShow'])->name('report.show');
        Route::post('/report/notify', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'reportSendNotification'])->name('report.notify');
        Route::patch('/report/lines/{type}/{line}', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'updateReportLine'])->name('report.line.update');
        Route::delete('/report/lines/{type}/{line}', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'destroyReportLine'])->name('report.line.destroy');
        Route::delete('/report/section/{type}', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'destroyReportSection'])->name('report.section.destroy');
        Route::delete('/report/all', [\App\Http\Controllers\Purchase\DailyAccountsController::class, 'destroyReportAll'])->name('report.all.destroy');
    });

    // Supplier advances (prepayments to suppliers)
    Route::prefix('supplier-advances')->name('supplier-advances.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'index'])->name('index');
        Route::get('/opening-advance/create', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'createOpening'])->name('opening-advance.create');
        Route::post('/opening-advance', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'storeOpening'])->name('opening-advance.store');
        Route::get('/create', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'store'])->name('store');
        Route::get('/statement/{encodedSupplierId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'statement'])->name('statement');
        Route::delete('/statement/{encodedSupplierId}/expense/{encodedJournalId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'destroyStatementExpense'])->name('statement.expense.destroy');
        Route::delete('/statement/{encodedSupplierId}/stock/{encodedStockRecordId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'destroyStatementStock'])->name('statement.stock.destroy');
        Route::post('/stock/{encodedSupplierId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'storeStock'])->name('stock.store');
        Route::get('/pay/{encodedSupplierId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'pay'])->name('pay');
        Route::post('/pay/{encodedSupplierId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'payStore'])->name('pay.store');
        Route::get('/expense/{encodedSupplierId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'expense'])->name('expense');
        Route::post('/expense/{encodedSupplierId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'expenseStore'])->name('expense.store');
        Route::get('/{encodedId}/edit', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'edit'])->name('edit');
        Route::put('/{encodedId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'update'])->name('update');
        Route::delete('/{encodedId}', [\App\Http\Controllers\Purchase\SupplierAdvanceController::class, 'destroy'])->name('destroy');
    });

    // Debit Notes (copy-from-invoice before create to avoid conflict)
    Route::prefix('debit-notes')->name('debit-notes.')->group(function () {
        Route::get('/create-from-invoice/{invoiceEncodedId}', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'createFromInvoice'])->name('create-from-invoice');
        Route::get('/', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'store'])->name('store');
        Route::get('/invoice-items/{invoice}', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'invoiceItemsJson'])->name('invoice-items');
        Route::get('/{debitNote}', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'show'])->name('show');
        Route::get('/{debitNote}/edit', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'edit'])->name('edit');
        Route::put('/{debitNote}', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'update'])->name('update');
        Route::delete('/{debitNote}', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'destroy'])->name('destroy');
        Route::post('/{debitNote}/approve', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'approve'])->name('approve');
        Route::post('/{debitNote}/apply', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'apply'])->name('apply');
        Route::post('/{debitNote}/cancel', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'cancel'])->name('cancel');
        Route::get('/api/inventory-item', [\App\Http\Controllers\Purchase\DebitNoteController::class, 'getInventoryItem'])->name('api.inventory-item');
    });
});

// Purchases Reports
Route::prefix('purchases/reports')->name('purchases.reports.')->middleware(['auth', 'company.scope', 'require.branch'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'index'])->name('index');
    Route::get('/purchase-requisition', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchaseRequisitionReport'])->name('purchase-requisition');
    Route::get('/po-register', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchaseOrderRegister'])->name('purchase-order-register');
    Route::get('/po-register/export/pdf', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportPurchaseOrderRegisterPdf'])->name('purchase-order-register.export.pdf');
    Route::get('/po-register/export/excel', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportPurchaseOrderRegisterExcel'])->name('purchase-order-register.export.excel');
    Route::get('/po-vs-grn', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'poVsGrn'])->name('po-vs-grn');
    Route::get('/po-vs-grn/export/pdf', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportPoVsGrnPdf'])->name('po-vs-grn.export.pdf');
    Route::get('/po-vs-grn/export/excel', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportPoVsGrnExcel'])->name('po-vs-grn.export.excel');
    Route::get('/grn-variance', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'grnVariance'])->name('grn-variance');
    Route::get('/grn-variance/export/pdf', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportGrnVariancePdf'])->name('grn-variance.export.pdf');
    Route::get('/grn-variance/export/excel', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportGrnVarianceExcel'])->name('grn-variance.export.excel');
    Route::get('/invoice-register', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'invoiceRegister'])->name('invoice-register');
    Route::get('/invoice-register/export/pdf', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportInvoiceRegisterPdf'])->name('invoice-register.export.pdf');
    Route::get('/invoice-register/export/excel', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportInvoiceRegisterExcel'])->name('invoice-register.export.excel');
    Route::get('/supplier-statement', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'supplierStatement'])->name('supplier-statement');
    Route::get('/supplier-statement/export/pdf', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportSupplierStatementPdf'])->name('supplier-statement.export.pdf');
    Route::get('/supplier-statement/export/excel', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportSupplierStatementExcel'])->name('supplier-statement.export.excel');
    Route::get('/supplier-statement-old', [\App\Http\Controllers\Purchase\SupplierStatementController::class, 'index'])->name('supplier-statement.index');
    Route::post('/supplier-statement', [\App\Http\Controllers\Purchase\SupplierStatementController::class, 'generate'])->name('supplier-statement.generate');
    Route::post('/supplier-statement/export-pdf', [\App\Http\Controllers\Purchase\SupplierStatementController::class, 'exportPdf'])->name('supplier-statement.export-pdf');
    Route::post('/supplier-statement/export-excel', [\App\Http\Controllers\Purchase\SupplierStatementController::class, 'exportExcel'])->name('supplier-statement.export-excel');
    Route::get('/payables-aging', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'payablesAging'])->name('payables-aging');
    Route::get('/payables-aging/export/pdf', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportPayablesAgingPdf'])->name('payables-aging.export.pdf');
    Route::get('/payables-aging/export/excel', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'exportPayablesAgingExcel'])->name('payables-aging.export.excel');
    Route::get('/outstanding-invoices', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'outstandingInvoices'])->name('outstanding-invoices');
    Route::get('/paid-invoices', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'paidInvoices'])->name('paid-invoices');
    Route::get('/supplier-credit-note', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'supplierCreditNoteReport'])->name('supplier-credit-note');
    Route::get('/po-invoice-variance', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'poInvoiceVariance'])->name('po-invoice-variance');
    Route::get('/purchase-returns', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchaseReturnsReport'])->name('purchase-returns');
    Route::get('/purchase-by-supplier', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchaseBySupplier'])->name('purchase-by-supplier');
    Route::get('/purchase-by-item', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchaseByItem'])->name('purchase-by-item');
    Route::get('/purchase-forecast', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchaseForecast'])->name('purchase-forecast');
    Route::get('/supplier-tax', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'supplierTax'])->name('supplier-tax');
    Route::get('/payment-schedule', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'paymentSchedule'])->name('payment-schedule');
    Route::get('/three-way-matching-exception', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'threeWayMatchingException'])->name('three-way-matching-exception');
    Route::get('/supplier-performance', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'supplierPerformance'])->name('supplier-performance');
    Route::get('/purchase-price-variance', [\App\Http\Controllers\Purchase\PurchasesReportController::class, 'purchasePriceVariance'])->name('purchase-price-variance');
});

// Purchase Invoices
Route::middleware(['auth', 'company.scope', 'require.branch'])->group(function () {
    Route::get('/purchases/purchase-invoices', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'index'])->name('purchases.purchase-invoices.index');
    Route::get('/purchases/purchase-invoices/create', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'create'])->name('purchases.purchase-invoices.create');
    Route::post('/purchases/purchase-invoices', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'store'])->name('purchases.purchase-invoices.store');
    // Import routes must come BEFORE parameterized routes to avoid route conflicts
    Route::get('/purchases/purchase-invoices/import', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'showImportForm'])->name('purchases.purchase-invoices.import');
    Route::post('/purchases/purchase-invoices/import-from-csv', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'importFromCsv'])->name('purchases.purchase-invoices.import-from-csv');
    // Parameterized routes come after specific routes
    Route::get('/purchases/purchase-invoices/{encodedId}', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'show'])->name('purchases.purchase-invoices.show');
    Route::get('/purchases/purchase-invoices/{encodedId}/edit', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'edit'])->name('purchases.purchase-invoices.edit');
    Route::put('/purchases/purchase-invoices/{encodedId}', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'update'])->name('purchases.purchase-invoices.update');
    Route::delete('/purchases/purchase-invoices/{encodedId}', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'destroy'])->name('purchases.purchase-invoices.destroy');
    Route::get('/purchases/purchase-invoices/{encodedId}/payment', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'paymentForm'])->name('purchases.purchase-invoices.payment-form');
    Route::post('/purchases/purchase-invoices/{encodedId}/payment', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'recordPayment'])->name('purchases.purchase-invoices.record-payment');
    Route::get('/purchases/purchase-invoices/{encodedId}/export-pdf', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'exportPdf'])->name('purchases.purchase-invoices.export-pdf');
    Route::post('/purchases/purchase-invoices/{encodedId}/send-email', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'sendEmail'])->name('purchases.purchase-invoices.send-email');
    Route::delete('/purchases/purchase-invoices/{encodedId}/payment/{paymentEncodedId}', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'destroyPayment'])->name('purchases.purchase-invoices.payment.destroy');
    Route::get('/purchases/purchase-invoices/{encodedId}/payment/{paymentEncodedId}/edit', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'editPayment'])->name('purchases.purchase-invoices.payment.edit');
    Route::put('/purchases/purchase-invoices/{encodedId}/payment/{paymentEncodedId}', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'updatePayment'])->name('purchases.purchase-invoices.payment.update');
    Route::get('/purchases/purchase-invoices/{encodedId}/payment/{paymentEncodedId}/print', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'printPaymentReceipt'])->name('purchases.purchase-invoices.payment.print');
    Route::post('/purchases/purchase-invoices/{encodedId}/reprocess-items', [\App\Http\Controllers\Purchase\PurchaseInvoiceController::class, 'reprocessItems'])->name('purchases.purchase-invoices.reprocess-items');
});

////////////////////////////////////////////// END PURCHASE MANAGEMENT ///////////////////////////////////////////

////////////////////////////////////////////// CUSTOMER MANAGEMENT ///////////////////////////////////////////

Route::middleware(['auth', 'require.branch'])->group(function () {
    // Customer routes
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('customers/penalty', [CustomerController::class, 'penaltList'])->name('customers.penalty');
    Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');

    // Bulk upload routes (must come before parameterized routes)
    Route::get('customers/bulk-upload', [CustomerController::class, 'bulkUpload'])->name('customers.bulk-upload');
    Route::post('customers/bulk-upload', [CustomerController::class, 'bulkUploadStore'])->name('customers.bulk-upload.store');
    Route::get('customers/download-sample', [CustomerController::class, 'downloadSample'])->name('customers.download-sample');

    // Parameterized routes (must come after specific routes)
    Route::get('customers/{encodedId}', [CustomerController::class, 'show'])->name('customers.show');
    Route::post('customers/{encodedId}/send-sms', [\App\Http\Controllers\DashboardController::class, 'sendBulkSmsToSingleCustomer'])->name('customers.send-sms');
    Route::get('customers/{encodedId}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('customers/{encodedId}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{encodedId}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Customer DataTable routes
    Route::get('customers/{encodedId}/deposits-datatable', [CustomerController::class, 'cashDepositsDataTable'])->name('customers.deposits.datatable');
    Route::get('customers/{encodedId}/invoices-datatable', [CustomerController::class, 'unpaidInvoicesDataTable'])->name('customers.invoices.datatable');
    Route::get('api/customers/{id}/cash-deposits', [CustomerController::class, 'getCashDeposits'])->name('api.customers.cash-deposits');
});

////////////////////////////////////////////// END CUSTOMER MANAGEMENT ///////////////////////////////////////////


// Chat routes
Route::middleware(['auth', 'require.branch'])->group(function () {
    Route::get('/chat', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages/{user}', [App\Http\Controllers\ChatController::class, 'fetchMessages'])->name('chat.messages');
    Route::post('/chat/send', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('/chat/mark-read', [App\Http\Controllers\ChatController::class, 'markAsRead'])->name('chat.mark-read');
    Route::get('/chat/unread-count', [App\Http\Controllers\ChatController::class, 'getUnreadCount'])->name('chat.unread-count');
    Route::post('/chat/clear', [App\Http\Controllers\ChatController::class, 'clearChat'])->name('chat.clear');
    Route::get('/chat/online-users', [App\Http\Controllers\ChatController::class, 'getOnlineUsers'])->name('chat.online-users');
    Route::get('/chat/download/{messageId}', [App\Http\Controllers\ChatController::class, 'downloadFile'])->name('chat.download');
});

////////////////////////////////////////////// SALES MANAGEMENT ///////////////////////////////////////////

Route::prefix('sales')->name('sales.')->middleware(['auth', 'company.scope', 'check.inventory.cost.method', 'require.branch'])->group(function () {
    Route::get('/', [App\Http\Controllers\Sales\SalesController::class, 'index'])->name('index');

    // Test endpoint for debugging
    Route::get('/test-auth', function () {
        return response()->json([
            'authenticated' => auth()->check(),
            'user' => auth()->user(),
            'proformas_count' => App\Models\Sales\SalesProforma::forBranch(auth()->user()->branch_id)->count()
        ]);
    })->name('test-auth');

    // Sales Proforma Routes (copy-from-invoice before resource to avoid conflict)
    Route::get('proformas/create-from-invoice/{invoiceEncodedId}', [SalesProformaController::class, 'createFromInvoice'])->name('proformas.create-from-invoice');
    Route::resource('proformas', SalesProformaController::class);
    Route::get('proformas/item-details/{id}', [SalesProformaController::class, 'getItemDetails'])->name('proformas.item-details');
    Route::patch('proformas/{id}/status', [SalesProformaController::class, 'updateStatus'])->name('proformas.update-status');
    Route::post('proformas/{id}/convert', [SalesProformaController::class, 'convertToDocument'])->name('proformas.convert');
    Route::get('proformas/{id}/export-pdf', [SalesProformaController::class, 'exportPdf'])->name('proformas.export-pdf');

    // Sales Order Routes (copy-from-invoice before resource to avoid conflict)
    Route::get('orders/create-from-invoice/{invoiceEncodedId}', [SalesOrderController::class, 'createFromInvoice'])->name('orders.create-from-invoice');
    Route::resource('orders', SalesOrderController::class);
    Route::get('orders/item-details/{id}', [SalesOrderController::class, 'getItemDetails'])->name('orders.item-details');
    Route::patch('orders/{id}/update-status', [SalesOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('orders/convert-from-proforma/{proformaId}', [SalesOrderController::class, 'convertFromProforma'])->name('orders.convert-from-proforma');
    Route::post('orders/{id}/convert-to-invoice', [SalesOrderController::class, 'convertToInvoice'])->name('orders.convert-to-invoice');
    Route::post('orders/{id}/convert-to-delivery', [SalesOrderController::class, 'convertToDelivery'])->name('orders.convert-to-delivery');
    Route::post('orders/{id}/convert-to-cash', [SalesOrderController::class, 'convertToCash'])->name('orders.convert-to-cash');
    Route::get('orders/{id}/export-pdf', [SalesOrderController::class, 'exportPdf'])->name('orders.export-pdf');

    // Sales Invoice Routes
    // IMPORTANT: More specific routes must come before parameterized routes
    Route::get('invoices/customer-credit-info', [SalesInvoiceController::class, 'getCustomerCreditInfo'])->name('invoices.customer-credit-info');
    Route::get('invoices/import', [SalesInvoiceController::class, 'showImportForm'])->name('invoices.import');
    Route::post('invoices/import-from-csv', [SalesInvoiceController::class, 'importFromCsv'])->name('invoices.import-from-csv');
    Route::get('invoices', [SalesInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/create', [SalesInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [SalesInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{encodedId}', [SalesInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{encodedId}/edit', [SalesInvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('invoices/{encodedId}', [SalesInvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('invoices/{encodedId}', [SalesInvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('invoices/{encodedId}/send-email', [SalesInvoiceController::class, 'sendEmail'])->name('invoices.send-email');

    Route::get('invoices/item-details/{id}', [SalesInvoiceController::class, 'getInventoryItem'])->name('invoices.item-details');
    Route::get('invoices/sales-order/{orderId}/details', [SalesInvoiceController::class, 'getSalesOrderDetails'])->name('invoices.sales-order-details');
    Route::post('invoices/convert-from-order/{orderId}', [SalesInvoiceController::class, 'convertFromOrder'])->name('invoices.convert-from-order');
    Route::get('invoices/{encodedId}/payment', [SalesInvoiceController::class, 'showPaymentForm'])->name('invoices.payment-form');
    Route::post('invoices/{encodedId}/payment', [SalesInvoiceController::class, 'recordPayment'])->name('invoices.record-payment');
    Route::get('invoices/{encodedId}/export-pdf', [SalesInvoiceController::class, 'exportPdf'])->name('invoices.export-pdf');
    Route::get('invoices/{encodedId}/print', [SalesInvoiceController::class, 'printInvoice'])->name('invoices.print');
    Route::post('invoices/{encodedId}/pos-print', [SalesInvoiceController::class, 'posPrint'])->name('invoices.pos-print');
    Route::get('invoices/payment/{paymentId}/edit', [SalesInvoiceController::class, 'editPayment'])->name('invoices.payment.edit');
    Route::put('invoices/payment/{paymentId}', [SalesInvoiceController::class, 'updatePayment'])->name('invoices.payment.update');
    Route::get('invoices/receipt/{encodedId}/edit', [SalesInvoiceController::class, 'editReceipt'])->name('invoices.receipt.edit');
    Route::put('invoices/receipt/{encodedId}', [SalesInvoiceController::class, 'updateReceipt'])->name('invoices.receipt.update');
    Route::post('invoices/{encodedId}/reverse-payment', [SalesInvoiceController::class, 'reversePayment'])->name('invoices.reverse-payment');
    Route::delete('invoices/{encodedId}/payment', [SalesInvoiceController::class, 'deletePayment'])->name('invoices.delete-payment');
    Route::get('invoices/receipt/{encodedId}/print', [App\Http\Controllers\Sales\SalesInvoiceController::class, 'printReceipt'])->name('invoices.print-receipt');
    Route::delete('invoices/payment/{encodedId}', [SalesInvoiceController::class, 'deleteInvoicePayment'])->name('invoices.delete-invoice-payment');
    Route::delete('invoices/{encodedId}/journal-payment/{journal}', [SalesInvoiceController::class, 'deleteCashDepositJournalPayment'])->name('invoices.delete-cash-deposit-journal');
    Route::get('invoices/customer/{customerId}/cash-deposits', [SalesInvoiceController::class, 'getCustomerCashDeposits'])->name('invoices.customer-cash-deposits');
    Route::post('invoices/{encodedId}/apply-late-fees', [SalesInvoiceController::class, 'applyLatePaymentFees'])->name('invoices.apply-late-fees');
});

// Credit info route moved to sales routes group above to avoid route conflicts

// Sales routes that require branch selection
Route::prefix('sales')->name('sales.')->middleware(['auth', 'company.scope', 'check.inventory.cost.method', 'require.branch'])->group(function () {
    // Opening Balances (Sales)
    Route::get('opening-balances', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'index'])->name('opening-balances.index');
    Route::get('opening-balances/create', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'create'])->name('opening-balances.create');
    Route::post('opening-balances', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'store'])->name('opening-balances.store');

    // Opening Balances Import (must be before parameterized routes)
    Route::get('opening-balances/import', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'import'])->name('opening-balances.import');
    Route::post('opening-balances/import', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'processImport'])->name('opening-balances.process-import');
    Route::get('opening-balances/template', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'downloadTemplate'])->name('opening-balances.download-template');

    // Parameterized routes (must be after specific routes)
    Route::get('opening-balances/{encodedId}', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'show'])->name('opening-balances.show');
    Route::get('opening-balances/{encodedId}/edit', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'edit'])->name('opening-balances.edit');
    Route::put('opening-balances/{encodedId}', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'update'])->name('opening-balances.update');
    Route::delete('opening-balances/{encodedId}', [\App\Http\Controllers\Sales\OpeningBalanceController::class, 'destroy'])->name('opening-balances.destroy');

    // Credit Note Routes (copy-from-invoice before resource to avoid conflict)
    Route::get('credit-notes/create-from-invoice/{invoiceEncodedId}', [CreditNoteController::class, 'createFromInvoice'])->name('credit-notes.create-from-invoice');
    Route::resource('credit-notes', CreditNoteController::class);
    Route::get('credit-notes/{encodedId}/pdf', [CreditNoteController::class, 'exportPdf'])->name('credit-notes.pdf');
    Route::post('credit-notes/{encodedId}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');
    Route::post('credit-notes/{encodedId}/cancel', [CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');
    Route::post('credit-notes/{encodedId}/apply', [CreditNoteController::class, 'apply'])->name('credit-notes.apply');
    Route::post('credit-notes/{encodedId}/apply-to-invoice', [CreditNoteController::class, 'applyToInvoice'])->name('credit-notes.apply-to-invoice');
    Route::post('credit-notes/{encodedId}/process-refund', [CreditNoteController::class, 'processRefund'])->name('credit-notes.process-refund');
    Route::get('credit-notes/customer/{customerId}/invoices', [CreditNoteController::class, 'getCustomerInvoices'])->name('credit-notes.customer-invoices');
    Route::get('credit-notes/customer/{customerId}/available-invoices', [CreditNoteController::class, 'getAvailableInvoices'])->name('credit-notes.available-invoices');
    Route::get('credit-notes/invoice/{invoiceId}/details', [CreditNoteController::class, 'getInvoiceDetails'])->name('credit-notes.invoice-details');
    Route::get('credit-notes/invoice/{invoiceId}/items', [CreditNoteController::class, 'getInvoiceItems'])->name('credit-notes.invoice-items');
    Route::get('credit-notes/item-details/{id}', [CreditNoteController::class, 'getInventoryItem'])->name('credit-notes.item-details');
    Route::get('credit-notes/statistics', [CreditNoteController::class, 'getStatistics'])->name('credit-notes.statistics');

    // Debug route for credit note testing
    Route::post('credit-notes/test-debug', [CreditNoteController::class, 'testDebug'])->name('credit-notes.test-debug');

    // Cash Sales Routes
    Route::get('cash-sales/customer/{customerId}/cash-deposits', [CashSaleController::class, 'getCustomerCashDeposits'])->name('cash-sales.customer-cash-deposits');
    Route::get('cash-sales/item-details/{id}', [CashSaleController::class, 'getInventoryItem'])->name('cash-sales.item-details');
    Route::get('cash-sales/{encodedId}/print', [CashSaleController::class, 'print'])->name('cash-sales.print');
    Route::resource('cash-sales', CashSaleController::class);

    // POS Sales Routes
    Route::get('pos', [PosSaleController::class, 'index'])->name('pos.index');
    Route::post('pos', [PosSaleController::class, 'store'])->name('pos.store');
    Route::get('pos/list', [PosSaleController::class, 'list'])->name('pos.list');
    Route::get('pos/statistics', [PosSaleController::class, 'statistics'])->name('pos.statistics');
    Route::get('pos/{encodedId}', [PosSaleController::class, 'show'])->name('pos.show');
    Route::get('pos/{encodedId}/edit', [PosSaleController::class, 'edit'])->name('pos.edit');
    Route::put('pos/{encodedId}', [PosSaleController::class, 'update'])->name('pos.update');
    Route::get('pos/{encodedId}/receipt', [PosSaleController::class, 'printReceipt'])->name('pos.receipt');
    Route::delete('pos/{encodedId}/void', [PosSaleController::class, 'void'])->name('pos.void');
    Route::post('pos/item-details', [PosSaleController::class, 'getItemDetails'])->name('pos.item-details');

    // Legacy POS routes (for backward compatibility)
    Route::resource('pos-sales', PosSaleController::class);
    Route::get('pos-sales/item-details/{id}', [PosSaleController::class, 'getInventoryItem'])->name('pos-sales.item-details');
    Route::get('pos-sales/{encodedId}/print', [PosSaleController::class, 'print'])->name('pos-sales.print');
    Route::post('pos-sales/{encodedId}/mark-printed', [PosSaleController::class, 'markReceiptPrinted'])->name('pos-sales.mark-printed');

    // Delivery Routes (copy-from-invoice before resource to avoid conflict)
    Route::get('deliveries/create-from-invoice/{invoiceEncodedId}', [DeliveryController::class, 'createFromInvoice'])->name('deliveries.create-from-invoice');
    Route::resource('deliveries', DeliveryController::class);
    Route::patch('deliveries/{id}/start-picking', [DeliveryController::class, 'startPicking'])->name('deliveries.start-picking');
    Route::patch('deliveries/{id}/complete-picking', [DeliveryController::class, 'completePicking'])->name('deliveries.complete-picking');
    Route::patch('deliveries/{id}/pick-all', [DeliveryController::class, 'pickAllItems'])->name('deliveries.pick-all');
    Route::patch('deliveries/items/{item}/pick', [DeliveryController::class, 'pickItem'])->name('deliveries.items.pick');
    Route::patch('deliveries/{id}/start-delivery', [DeliveryController::class, 'startDelivery'])->name('deliveries.start-delivery');
    Route::patch('deliveries/{id}/complete-delivery', [DeliveryController::class, 'completeDelivery'])->name('deliveries.complete-delivery');
    Route::patch('deliveries/{id}/pack-all', [DeliveryController::class, 'packAllItems'])->name('deliveries.pack-all');
    Route::patch('deliveries/items/{item}/pack', [DeliveryController::class, 'packItem'])->name('deliveries.items.pack');
    Route::patch('deliveries/{id}/deliver-all', [DeliveryController::class, 'deliverAllItems'])->name('deliveries.deliver-all');
    Route::patch('deliveries/items/{item}/deliver', [DeliveryController::class, 'deliverItem'])->name('deliveries.items.deliver');
    Route::get('deliveries/{id}/generate-note', [DeliveryController::class, 'generateDeliveryNote'])->name('deliveries.generate-note');
    Route::get('deliveries/{id}/note', [DeliveryController::class, 'showDeliveryNote'])->name('deliveries.note');
    Route::get('deliveries/{id}/note/pdf', [DeliveryController::class, 'downloadDeliveryNotePdf'])->name('deliveries.note.pdf');
    Route::get('deliveries/convert-from-order/{orderId}', [DeliveryController::class, 'convertFromOrder'])->name('deliveries.convert-from-order');
    Route::post('deliveries/{id}/convert-to-invoice', [DeliveryController::class, 'convertToInvoice'])->name('deliveries.convert-to-invoice');
});

Route::post('sms/bulk', [App\Http\Controllers\DashboardController::class, 'sendBulkSms'])->name('sms.bulk');
// Payment Voucher Approval Settings
Route::get('/payment-voucher-approval', [SettingsController::class, 'paymentVoucherApprovalSettings'])->name('settings.payment-voucher-approval');
Route::put('/payment-voucher-approval', [SettingsController::class, 'updatePaymentVoucherApprovalSettings'])->name('settings.payment-voucher-approval.update');

// Account Transfer Approval Settings
Route::get('/account-transfer-approval', [SettingsController::class, 'accountTransferApprovalSettings'])->name('settings.account-transfer-approval');
Route::put('/account-transfer-approval', [SettingsController::class, 'updateAccountTransferApprovalSettings'])->name('settings.account-transfer-approval.update');
Route::get('/provision-approval', [SettingsController::class, 'provisionApprovalSettings'])->name('settings.provision-approval');
Route::put('/provision-approval', [SettingsController::class, 'updateProvisionApprovalSettings'])->name('settings.provision-approval.update');

// Journal Entry Approval Settings
Route::get('/journal-entry-approval', [SettingsController::class, 'journalEntryApprovalSettings'])->name('settings.journal-entry-approval');
Route::put('/journal-entry-approval', [SettingsController::class, 'updateJournalEntryApprovalSettings'])->name('settings.journal-entry-approval.update');

// Period-End Closing Routes
Route::prefix('period-closing')->name('settings.period-closing.')->group(function () {
    Route::get('/', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'index'])->name('index');
    Route::get('/fiscal-years', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'fiscalYears'])->name('fiscal-years');
    Route::get('/fiscal-years/data', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'fiscalYearsData'])->name('fiscal-years.data');
    Route::post('/fiscal-years', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'storeFiscalYear'])->name('fiscal-years.store');
    Route::get('/periods', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'periods'])->name('periods');
    Route::get('/fiscal-years/{fiscalYear}/periods', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'getPeriodsForFiscalYear'])->name('fiscal-years.periods');
    Route::get('/fiscal-years/{fiscalYear}/year-end-wizard', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'yearEndWizard'])->name('fiscal-years.year-end-wizard');
    Route::get('/fiscal-years/{fiscalYear}/period-status', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'getPeriodClosingStatus'])->name('fiscal-years.period-status');
    Route::get('/check-date', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'checkDateLock'])->name('check-date');

    Route::prefix('close-batch')->name('close-batch.')->group(function () {
        Route::get('/create/{period}', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'createCloseBatch'])->name('create');
        Route::post('/store/{period}', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'storeCloseBatch'])->name('store');
        Route::get('/{closeBatch}', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'showCloseBatch'])->name('show');
        Route::get('/{closeBatch}/snapshots/data', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'snapshotsData'])->name('snapshots.data');
        Route::post('/{closeBatch}/adjustments', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'addAdjustment'])->name('adjustments.add');
        Route::delete('/{closeBatch}/adjustments/{closeAdjustment}', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'deleteAdjustment'])->name('adjustments.destroy');
        Route::post('/{closeBatch}/submit-review', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'submitForReview'])->name('submit-review');
        Route::post('/{closeBatch}/approve', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'approve'])->name('approve');
        Route::post('/{closeBatch}/roll-retained-earnings', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'rollRetainedEarnings'])->name('roll-retained-earnings');
    });

    Route::post('/periods/{period}/reopen', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'reopenPeriod'])->name('periods.reopen');
    Route::get('/download-guide', [App\Http\Controllers\PeriodClosing\PeriodClosingController::class, 'downloadGuide'])->name('download-guide');
});

////////////////////////////////////////////// END SALES MANAGEMENT ///////////////////////////////////////////

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/')->with('success', 'You are successfully logout.');
})->middleware('auth');

// Exchange Rate API Routes
Route::prefix('api/exchange-rates')->middleware('throttle.api')->group(function () {
    Route::get('/rate', [App\Http\Controllers\Api\ExchangeRateController::class, 'getRate'])->name('api.exchange-rates.rate');
    Route::get('/convert', [App\Http\Controllers\Api\ExchangeRateController::class, 'convertAmount'])->name('api.exchange-rates.convert');
    Route::get('/history', [App\Http\Controllers\Api\ExchangeRateController::class, 'getHistory'])->name('api.exchange-rates.history');
    Route::get('/currencies', [App\Http\Controllers\Api\ExchangeRateController::class, 'getSupportedCurrencies'])->name('api.exchange-rates.currencies');
    Route::post('/clear-cache', [App\Http\Controllers\Api\ExchangeRateController::class, 'clearCache'])->name('api.exchange-rates.clear-cache');
});

// Currency Reports Routes
Route::prefix('reports/currency')->group(function () {
    Route::get('/', [App\Http\Controllers\Reports\Sales\CurrencyReportController::class, 'index'])->name('reports.currency.index');
    Route::get('/summary', [App\Http\Controllers\Reports\Sales\CurrencyReportController::class, 'summary'])->name('reports.currency.summary');
    Route::get('/comparison', [App\Http\Controllers\Reports\Sales\CurrencyReportController::class, 'comparison'])->name('reports.currency.comparison');
    Route::get('/exchange-rate-analysis', [App\Http\Controllers\Reports\Sales\CurrencyReportController::class, 'exchangeRateAnalysis'])->name('reports.currency.exchange-rate-analysis');
    Route::get('/export-pdf', [App\Http\Controllers\Reports\Sales\CurrencyReportController::class, 'exportPdf'])->name('reports.currency.export-pdf');
});

// Production Management Module Routes
Route::prefix('production')->name('production.')->middleware(['auth'])->group(function () {
    Route::resource('orders', App\Http\Controllers\Sales\SalesOrderController::class);
    Route::resource('batches', App\Http\Controllers\Production\ProductionBatchController::class)->names('batches');
    Route::resource('machines', App\Http\Controllers\Production\ProductionMachineController::class)->names('machines');

    // Work Orders for Sweater Production
    Route::resource('work-orders', App\Http\Controllers\Production\WorkOrderController::class)->names('work-orders');
    Route::post('work-orders/{encodedId}/advance-stage', [App\Http\Controllers\Production\WorkOrderController::class, 'advanceStage'])->name('work-orders.advance-stage');
    Route::post('work-orders/{encodedId}/issue-materials', [App\Http\Controllers\Production\WorkOrderController::class, 'issuesMaterials'])->name('work-orders.issue-materials');
    Route::post('work-orders/{encodedId}/record-production', [App\Http\Controllers\Production\WorkOrderController::class, 'recordProduction'])->name('work-orders.record-production');
    Route::post('work-orders/{encodedId}/quality-check', [App\Http\Controllers\Production\WorkOrderController::class, 'qualityCheck'])->name('work-orders.quality-check');
    Route::post('work-orders/{encodedId}/record-packaging', [App\Http\Controllers\Production\WorkOrderController::class, 'recordPackaging'])->name('work-orders.record-packaging');

    // Finished Goods Packaging (Standalone)
    Route::get('finished-goods-packaging', [App\Http\Controllers\Production\FinishedGoodsPackagingController::class, 'index'])->name('finished-goods-packaging.index');
    Route::post('finished-goods-packaging', [App\Http\Controllers\Production\FinishedGoodsPackagingController::class, 'store'])->name('finished-goods-packaging.store');
    Route::get('finished-goods-packaging/search-items', [App\Http\Controllers\Production\FinishedGoodsPackagingController::class, 'searchItems'])->name('finished-goods-packaging.search-items');

    // Assign order to batch
    Route::get('batches/{encodedId}/assign-order', [App\Http\Controllers\Production\ProductionBatchController::class, 'assignOrderForm'])->name('batches.assign-order');
    Route::post('batches/{encodedId}/assign-order', [App\Http\Controllers\Production\ProductionBatchController::class, 'assignOrder'])->name('batches.assign-order.store');
    // Update assigned order quantity
    Route::post('batches/{batchHashid}/assigned-orders/{orderHashid}/update', [App\Http\Controllers\Production\ProductionBatchController::class, 'updateAssignedOrder']);
    // Delete assigned order
    Route::post('batches/{batchHashid}/assigned-orders/{orderHashid}/delete', [App\Http\Controllers\Production\ProductionBatchController::class, 'deleteAssignedOrder']);
});

// Production Reports (fix for reports.production route used in side menu)
Route::get('/reports/production', function () {
    return redirect()->route('production.batches.index');
})->middleware(['auth'])->name('reports.production');

// Purchases Reports (fix for reports.purchases route used in side menu)
Route::get('/reports/purchases', function () {
    return redirect()->route('purchases.index');
})->middleware(['auth'])->name('reports.purchases');

// Opening Balances
Route::get('/inventory/opening-balances', [OpeningBalanceController::class, 'index'])
    ->middleware(['auth', 'require.branch'])
    ->name('inventory.opening-balances.index');
// Stock Movements
Route::get('/inventory/opening-balances', [OpeningBalanceController::class, 'index'])->middleware(['auth', 'require.branch'])
    ->name('inventory.opening-balances.index');
// Gracefully handle GET requests to import URL by redirecting to index
Route::get('/inventory/opening-balances/import', function () {
    return redirect()->route('inventory.opening-balances.index');
})->middleware(['auth', 'require.branch'])->name('inventory.opening-balances.import.get');
Route::get('/inventory/opening-balances/create', [OpeningBalanceController::class, 'create'])->name('inventory.opening-balances.create');
Route::post('/inventory/opening-balances', [OpeningBalanceController::class, 'store'])->name('inventory.opening-balances.store');
Route::post('/inventory/opening-balances/import', [OpeningBalanceController::class, 'import'])->name('inventory.opening-balances.import');
Route::get('/inventory/opening-balances/template', [OpeningBalanceController::class, 'downloadTemplate'])->name('inventory.opening-balances.template');
// Route::get('/inventory/opening-balances/{movement}', [OpeningBalanceController::class, 'show'])->name('movements.show');
// Route::get('/inventory/opening-balances/{movement}/edit', [OpeningBalanceController::class, 'edit'])->name('movements.edit');
// Route::put('/inventory/opening-balances/{movement}', [OpeningBalanceController::class, 'update'])->name('movements.update');
// Route::delete('/inventory/opening-balances/{movement}', [OpeningBalanceController::class, 'destroy'])->name('movements.destroy');

Route::prefix('production/batches')->group(function () {
    Route::get('{batch}/add-item', [ItemBatchController::class, 'create'])->name('production.batches.add-item');
    Route::post('{batch}/add-item', [ItemBatchController::class, 'store'])->name('production.batches.add-item.store');
    Route::delete('item-batch/{id}/delete', [ItemBatchController::class, 'destroy'])->name('production.batches.item-batch.delete');
});

// API Routes for dynamic data
Route::prefix('api')->middleware(['auth', 'throttle.api'])->group(function () {
    // Password strength API
    Route::post('/password-strength', [\App\Http\Controllers\Api\PasswordStrengthController::class, 'calculateStrength']);
    Route::get('/customers-by-branch/{branchId}', function ($branchId) {
        $customers = \App\Models\Customer::where('branch_id', $branchId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        return response()->json($customers);
    });

    Route::get('/customers', function (Request $request) {
        $user = auth()->user();
        $companyId = $user->company_id;
        $branchId = $request->get('branch_id') ?? session('branch_id') ?? $user->branch_id;

        $query = \App\Models\Customer::where('company_id', $companyId);
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $customers = $query->orderBy('name')
            ->get(['id', 'name', 'customerNo', 'phone', 'email'])
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_no' => $customer->customerNo, // Map customerNo to customer_no for frontend
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ];
            });
        return response()->json($customers);
    });

    Route::get('/suppliers', function (Request $request) {
        $user = auth()->user();
        $companyId = $user->company_id;
        $branchId = $request->get('branch_id') ?? session('branch_id') ?? $user->branch_id;

        $query = \App\Models\Supplier::where('company_id', $companyId);
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $suppliers = $query->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);
        return response()->json($suppliers);
    });

    Route::get('/salesperson-invoices/{salespersonId}', function ($salespersonId, Request $request) {
        try {
            \Log::info('API Request:', [
                'salespersonId' => $salespersonId,
                'user_id' => auth()->id(),
                'request_params' => $request->all()
            ]);

            $dateFrom = $request->get('date_from', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));

            // Get user's assigned branches
            $assignedBranches = auth()->user()->branches()->get();
            $defaultBranchId = $assignedBranches->count() > 1 ? 'all' : ($assignedBranches->first()->id ?? null);
            $branchId = $request->get('branch_id', $defaultBranchId);

            // Parse dates
            $dateFrom = \Carbon\Carbon::parse($dateFrom)->startOfDay();
            $dateTo = \Carbon\Carbon::parse($dateTo)->endOfDay();

            \Log::info('Query Parameters:', [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'branchId' => $branchId,
                'salespersonId' => $salespersonId
            ]);

            $query = \App\Models\Sales\SalesInvoice::with(['customer'])
                ->where('created_by', $salespersonId)
                ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                ->where('status', '!=', 'cancelled');

            // Apply assigned branch filtering
            $user = auth()->user();
            $assignedBranchIds = $user->branches()->pluck('branches.id')->toArray();

            if ($branchId === 'all') {
                if (!empty($assignedBranchIds)) {
                    $query->whereIn('branch_id', $assignedBranchIds);
                } else {
                    $query->whereRaw('1 = 0'); // No assigned branches, return empty
                }
            } else {
                // Ensure the selected branch is in the user's assigned branches
                if (in_array($branchId, $assignedBranchIds)) {
                    $query->where('branch_id', $branchId);
                } else {
                    $query->whereRaw('1 = 0'); // Branch not assigned, return empty
                }
            }

            $invoices = $query->select(['id', 'invoice_number', 'customer_id', 'invoice_date', 'status', 'total_amount', 'paid_amount', 'balance_due', 'branch_id', 'created_by'])
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function ($invoice) {
                    return (object)[
                        'type' => 'invoice',
                        'id' => $invoice->id,
                        'number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer->name ?? 'N/A',
                        'date' => $invoice->invoice_date,
                        'status' => $invoice->status,
                        'total_amount' => (float)$invoice->total_amount,
                        'paid_amount' => (float)($invoice->paid_amount ?? 0),
                        'balance_due' => (float)($invoice->balance_due ?? 0),
                        'encoded_id' => \Vinkla\Hashids\Facades\Hashids::encode($invoice->id),
                    ];
                });

            // POS Sales by operator/creator
            $posQuery = \App\Models\Sales\PosSale::with(['customer'])
                ->where(function ($q) use ($salespersonId) {
                    $q->where('operator_id', $salespersonId)
                        ->orWhere('created_by', $salespersonId);
                })
                ->whereBetween('sale_date', [$dateFrom, $dateTo]);
            if ($branchId === 'all') {
                if (!empty($assignedBranchIds)) {
                    $posQuery->whereIn('branch_id', $assignedBranchIds);
                } else {
                    $posQuery->whereRaw('1 = 0');
                }
            } else {
                if (in_array($branchId, $assignedBranchIds)) {
                    $posQuery->where('branch_id', $branchId);
                } else {
                    $posQuery->whereRaw('1 = 0');
                }
            }
            $posSales = $posQuery->select(['id', 'pos_number', 'customer_id', 'sale_date', 'total_amount', 'branch_id'])
                ->orderBy('sale_date', 'desc')
                ->get()
                ->map(function ($pos) {
                    return (object)[
                        'type' => 'pos',
                        'id' => $pos->id,
                        'number' => $pos->pos_number,
                        'customer_name' => $pos->customer->name ?? 'N/A',
                        'date' => $pos->sale_date,
                        'status' => 'paid',
                        'total_amount' => (float)$pos->total_amount,
                        'paid_amount' => (float)$pos->total_amount,
                        'balance_due' => 0.0,
                        'encoded_id' => \Vinkla\Hashids\Facades\Hashids::encode($pos->id),
                    ];
                });

            // Cash Sales by creator
            $cashQuery = \App\Models\Sales\CashSale::with(['customer'])
                ->where('created_by', $salespersonId)
                ->whereBetween('sale_date', [$dateFrom, $dateTo]);
            if ($branchId === 'all') {
                if (!empty($assignedBranchIds)) {
                    $cashQuery->whereIn('branch_id', $assignedBranchIds);
                } else {
                    $cashQuery->whereRaw('1 = 0');
                }
            } else {
                if (in_array($branchId, $assignedBranchIds)) {
                    $cashQuery->where('branch_id', $branchId);
                } else {
                    $cashQuery->whereRaw('1 = 0');
                }
            }
            $cashSales = $cashQuery->select(['id', 'sale_number', 'customer_id', 'sale_date', 'total_amount', 'paid_amount', 'branch_id'])
                ->orderBy('sale_date', 'desc')
                ->get()
                ->map(function ($cs) {
                    $paid = (float)($cs->paid_amount ?? $cs->total_amount);
                    return (object)[
                        'type' => 'cash_sale',
                        'id' => $cs->id,
                        'number' => $cs->sale_number,
                        'customer_name' => $cs->customer->name ?? 'N/A',
                        'date' => $cs->sale_date,
                        'status' => 'paid',
                        'total_amount' => (float)$cs->total_amount,
                        'paid_amount' => $paid,
                        'balance_due' => max(0.0, (float)$cs->total_amount - $paid),
                        'encoded_id' => \Vinkla\Hashids\Facades\Hashids::encode($cs->id),
                    ];
                });

            \Log::info('Query Results:', [
                'invoices_count' => $invoices->count(),
                'invoices' => $invoices->toArray()
            ]);

            // Merge all documents
            $documents = $invoices->merge($posSales)->merge($cashSales)
                ->sortByDesc('date')
                ->values();

            return response()->json([
                'success' => true,
                'invoices' => $documents
            ]);
        } catch (\Exception $e) {
            \Log::error('API Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'salespersonId' => $salespersonId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching invoices: ' . $e->getMessage()
            ], 500);
        }
    });
});


// Hotel & Property Management Routes
Route::prefix('hotel')->name('hotel.')->group(function () {
    Route::get('/management', [App\Http\Controllers\Hotel\HotelManagementController::class, 'index'])->name('management.index');
    Route::post('/management/room-status', [App\Http\Controllers\Hotel\HotelManagementController::class, 'getRoomStatus'])->name('management.room-status');
    Route::get('/settings', [App\Http\Controllers\Hotel\HotelManagementController::class, 'settings'])->name('settings');
    Route::post('/settings', [App\Http\Controllers\Hotel\HotelManagementController::class, 'updateSettings'])->name('settings.update');

    // Guest Messages
    Route::prefix('guest-messages')->name('guest-messages.')->group(function () {
        Route::get('/', [App\Http\Controllers\Hotel\GuestMessageController::class, 'index'])->name('index');
        Route::get('/{message}', [App\Http\Controllers\Hotel\GuestMessageController::class, 'show'])->name('show');
        Route::post('/{message}/respond', [App\Http\Controllers\Hotel\GuestMessageController::class, 'respond'])->name('respond');
        Route::post('/{message}/mark-read', [App\Http\Controllers\Hotel\GuestMessageController::class, 'markAsRead'])->name('mark-read');
    });
    Route::get('/property/settings', [App\Http\Controllers\Hotel\PropertySettingsController::class, 'index'])->name('property.settings');
    Route::post('/property/settings', [App\Http\Controllers\Hotel\PropertySettingsController::class, 'update'])->name('property.settings.update');

    // Hotel Expenses
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'store'])->name('store');
        Route::get('/{encodedId}', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'show'])->name('show');
        Route::get('/{encodedId}/edit', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'edit'])->name('edit');
        Route::put('/{encodedId}', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'update'])->name('update');
        Route::delete('/{encodedId}', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'destroy'])->name('destroy');
        Route::get('/{encodedId}/export-pdf', [App\Http\Controllers\Hotel\HotelExpenseController::class, 'exportPdf'])->name('export-pdf');
    });

    // Hotel Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Hotel\HotelReportController::class, 'index'])->name('index');
        Route::get('/daily-booking-vs-collection', [App\Http\Controllers\Hotel\HotelReportController::class, 'dailyBookingVsCollection'])->name('daily-booking-vs-collection');
        Route::post('/daily-booking-vs-collection/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'dailyBookingVsCollectionExportPdf'])->name('daily-booking-vs-collection.export-pdf');
        Route::get('/daily-occupancy', [App\Http\Controllers\Hotel\HotelReportController::class, 'dailyOccupancy'])->name('daily-occupancy');
        Route::post('/daily-occupancy/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'dailyOccupancyExportPdf'])->name('daily-occupancy.export-pdf');
        Route::get('/daily-sales', [App\Http\Controllers\Hotel\HotelReportController::class, 'dailySales'])->name('daily-sales');
        Route::post('/daily-sales/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'dailySalesExportPdf'])->name('daily-sales.export-pdf');
        Route::get('/monthly-revenue', [App\Http\Controllers\Hotel\HotelReportController::class, 'monthlyRevenue'])->name('monthly-revenue');
        Route::post('/monthly-revenue/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'monthlyRevenueExportPdf'])->name('monthly-revenue.export-pdf');
        Route::get('/booking', [App\Http\Controllers\Hotel\HotelReportController::class, 'bookingReport'])->name('booking');
        Route::post('/booking/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'bookingReportExportPdf'])->name('booking.export-pdf');
        Route::get('/check-in-out', [App\Http\Controllers\Hotel\HotelReportController::class, 'checkInOut'])->name('check-in-out');
        Route::post('/check-in-out/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'checkInOutExportPdf'])->name('check-in-out.export-pdf');
        Route::get('/room-status', [App\Http\Controllers\Hotel\HotelReportController::class, 'roomStatus'])->name('room-status');
        Route::post('/room-status/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'roomStatusExportPdf'])->name('room-status.export-pdf');
        Route::get('/housekeeping', [App\Http\Controllers\Hotel\HotelReportController::class, 'housekeeping'])->name('housekeeping');
        Route::post('/housekeeping/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'housekeepingExportPdf'])->name('housekeeping.export-pdf');
        Route::get('/guest-history', [App\Http\Controllers\Hotel\HotelReportController::class, 'guestHistory'])->name('guest-history');
        Route::post('/guest-history/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'guestHistoryExportPdf'])->name('guest-history.export-pdf');
        Route::get('/payment-method', [App\Http\Controllers\Hotel\HotelReportController::class, 'paymentMethod'])->name('payment-method');
        Route::post('/payment-method/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'paymentMethodExportPdf'])->name('payment-method.export-pdf');
        Route::get('/staff-activity', [App\Http\Controllers\Hotel\HotelReportController::class, 'staffActivity'])->name('staff-activity');
        Route::post('/staff-activity/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'staffActivityExportPdf'])->name('staff-activity.export-pdf');
        Route::get('/profit-loss', [App\Http\Controllers\Hotel\HotelReportController::class, 'profitLoss'])->name('profit-loss');
        Route::post('/profit-loss/export-pdf', [App\Http\Controllers\Hotel\HotelReportController::class, 'profitLossExportPdf'])->name('profit-loss.export-pdf');
    });
});

Route::prefix('real-estate')->name('real.estate.')->group(function () {
    Route::get('/', [App\Http\Controllers\Property\RealEstateController::class, 'index'])->name('index');
});

// Property Management Routes
Route::prefix('properties')->name('properties.')->group(function () {
    Route::get('/', [App\Http\Controllers\Hotel\PropertyController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Hotel\PropertyController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Hotel\PropertyController::class, 'store'])->name('store');
    Route::get('/{property}', [App\Http\Controllers\Hotel\PropertyController::class, 'show'])->name('show');
    Route::get('/{property}/edit', [App\Http\Controllers\Hotel\PropertyController::class, 'edit'])->name('edit');
    Route::put('/{property}', [App\Http\Controllers\Hotel\PropertyController::class, 'update'])->name('update');
    Route::delete('/{property}', [App\Http\Controllers\Hotel\PropertyController::class, 'destroy'])->name('destroy');
});

Route::resource('cash_collateral_types', CashCollateralTypeController::class)->middleware('auth');

////////////////////////////////////////////// CASHCOLLATERALS MANAGEMENT ///////////////////////////////////////////

Route::middleware(['auth'])->prefix('cash_collaterals')->group(function () {
    Route::get('/', [CashCollateralController::class, 'index'])->name('cash_collaterals.index');
    Route::get('/datatable', [CashCollateralController::class, 'getDataTable'])->name('cash_collaterals.datatable');
    Route::get('/create', [CashCollateralController::class, 'create'])->name('cash_collaterals.create');
    Route::post('/', [CashCollateralController::class, 'store'])->name('cash_collaterals.store');
    Route::get('/{cashcollateral}', [CashCollateralController::class, 'show'])->name('cash_collaterals.show');
    Route::get('/{cashcollateral}/statement-pdf', [CashCollateralController::class, 'exportStatementPdf'])->name('cash_collaterals.statement-pdf');
    Route::get('/{cashcollateral}/edit', [CashCollateralController::class, 'edit'])->name('cash_collaterals.edit');
    Route::put('/{cashcollateral}', [CashCollateralController::class, 'update'])->name('cash_collaterals.update');
    Route::delete('/{cashcollateral}', [CashCollateralController::class, 'destroy'])->name('cash_collaterals.destroy');


    // Direct Receipt and Payment Routes for Cash Collateral
    Route::get('/receipts/{receipt}/edit', [CashCollateralController::class, 'editReceipt'])->name('receipts.edit');
    Route::put('/receipts/{receipt}', [CashCollateralController::class, 'updateReceipt'])->name('receipts.update');
    Route::delete('/receipts/{receipt}', [CashCollateralController::class, 'deleteReceipt'])->name('receipts.destroy');

    Route::get('/payments/{payment}/edit', [CashCollateralController::class, 'editPayment'])->name('payments.edit');
    Route::put('/payments/{payment}', [CashCollateralController::class, 'updatePayment'])->name('payments.update');
    Route::delete('/payments/{payment}', [CashCollateralController::class, 'deletePayment'])->name('payments.destroy');

    // Deposit and Withdrawal routes
    Route::get('/{cashcollateral}/deposit', [CashCollateralController::class, 'deposit'])->name('cash_collaterals.deposit');
    Route::post('/deposit-store', [CashCollateralController::class, 'depositStore'])->name('cash_collaterals.depositStore');
    Route::get('/print-deposit-receipt/{id}', [CashCollateralController::class, 'printDepositReceipt'])->name('cash_collaterals.printDepositReceipt');
    Route::get('/{cashcollateral}/withdraw', [CashCollateralController::class, 'withdraw'])->name('cash_collaterals.withdraw');
    Route::post('/withdraw-store', [CashCollateralController::class, 'withdrawStore'])->name('cash_collaterals.withdrawStore');
    Route::get('/print-withdrawal-receipt/{id}', [CashCollateralController::class, 'printWithdrawalReceipt'])->name('cash_collaterals.printWithdrawalReceipt');

    // Transaction delete routes
    Route::delete('/delete-deposit/{receiptId}', [CashCollateralController::class, 'deleteDeposit'])->name('cash_collaterals.deleteDeposit');
    Route::delete('/delete-withdrawal/{paymentId}', [CashCollateralController::class, 'deleteWithdrawal'])->name('cash_collaterals.deleteWithdrawal');
});

////////////////////////////////////////////// END CASHCOLLATERALS  MANAGEMENT ///////////////////////////////////////////

// Room Management Routes
Route::prefix('rooms')->name('rooms.')->group(function () {
    Route::get('/', [App\Http\Controllers\Hotel\RoomController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Hotel\RoomController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Hotel\RoomController::class, 'store'])->name('store');
    Route::get('/{room}', [App\Http\Controllers\Hotel\RoomController::class, 'show'])->name('show');
    Route::get('/{room}/edit', [App\Http\Controllers\Hotel\RoomController::class, 'edit'])->name('edit');
    Route::put('/{room}', [App\Http\Controllers\Hotel\RoomController::class, 'update'])->name('update');
    Route::delete('/{room}', [App\Http\Controllers\Hotel\RoomController::class, 'destroy'])->name('destroy');
});

// Booking Management Routes
Route::prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [App\Http\Controllers\Hotel\BookingController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Hotel\BookingController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Hotel\BookingController::class, 'store'])->name('store');

    // Availability utilities MUST come before parameterized {booking} routes
    Route::get('/check-availability', [App\Http\Controllers\Hotel\BookingController::class, 'checkAvailability'])->name('check-availability');
    Route::get('/available-rooms', [App\Http\Controllers\Hotel\BookingController::class, 'availableRooms'])->name('available-rooms');
    Route::get('/{booking}', [App\Http\Controllers\Hotel\BookingController::class, 'show'])->name('show');
    Route::get('/{booking}/edit', [App\Http\Controllers\Hotel\BookingController::class, 'edit'])->name('edit');
    Route::put('/{booking}', [App\Http\Controllers\Hotel\BookingController::class, 'update'])->name('update');
    Route::delete('/{booking}', [App\Http\Controllers\Hotel\BookingController::class, 'destroy'])->name('destroy');

    // Booking actions
    Route::post('/{booking}/check-in', [App\Http\Controllers\Hotel\BookingController::class, 'checkIn'])->name('check-in');
    Route::post('/{booking}/check-out', [App\Http\Controllers\Hotel\BookingController::class, 'checkOut'])->name('check-out');
    Route::post('/{booking}/confirm', [App\Http\Controllers\Hotel\BookingController::class, 'confirm'])->name('confirm');
    Route::post('/{booking}/accept', [App\Http\Controllers\Hotel\BookingController::class, 'accept'])->name('accept');
    Route::post('/{booking}/cancel', [App\Http\Controllers\Hotel\BookingController::class, 'cancel'])->name('cancel');
    Route::post('/{booking}/record-payment', [App\Http\Controllers\Hotel\BookingController::class, 'recordPayment'])->name('record-payment');
    Route::get('/{booking}/export-pdf', [App\Http\Controllers\Hotel\BookingController::class, 'exportPdf'])->name('export-pdf');

    // Booking receipt routes
    Route::get('/receipts/{receipt}/edit', [App\Http\Controllers\Hotel\BookingController::class, 'editReceipt'])->name('receipts.edit');
    Route::get('/receipts/{receipt}/print', [App\Http\Controllers\Hotel\BookingController::class, 'printReceipt'])->name('receipts.print');
    // Fallback show route for receipts if within hotel group
    Route::put('/receipts/{receipt}', [App\Http\Controllers\Hotel\BookingController::class, 'updateReceipt'])->name('receipts.update');
    Route::delete('/receipts/{receipt}', [App\Http\Controllers\Hotel\BookingController::class, 'deleteReceipt'])->name('receipts.destroy');

    // Web portal settings
    Route::put('/webportal-settings', [App\Http\Controllers\Hotel\BookingController::class, 'updateWebPortalSettings'])->name('webportal-settings.update');
});

// Guest Management Routes
Route::prefix('guests')->name('guests.')->group(function () {
    Route::get('/', [App\Http\Controllers\Hotel\GuestController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Hotel\GuestController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Hotel\GuestController::class, 'store'])->name('store');
    Route::get('/{guest}', [App\Http\Controllers\Hotel\GuestController::class, 'show'])->name('show');
    Route::get('/{guest}/edit', [App\Http\Controllers\Hotel\GuestController::class, 'edit'])->name('edit');
    Route::put('/{guest}', [App\Http\Controllers\Hotel\GuestController::class, 'update'])->name('update');
    Route::delete('/{guest}', [App\Http\Controllers\Hotel\GuestController::class, 'destroy'])->name('destroy');
});

// Lease Management Routes
Route::prefix('leases')->name('leases.')->group(function () {
    Route::get('/', [App\Http\Controllers\Property\LeaseController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\Property\LeaseController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\Property\LeaseController::class, 'store'])->name('store');
    Route::get('/{id}', function ($id) {
        return view('property.leases.show', compact('id'));
    })->name('show');
    Route::get('/{id}/edit', function ($id) {
        return view('property.leases.edit', compact('id'));
    })->name('edit');
});

// Tenant Management Routes (for future CRUD operations)
Route::prefix('tenants')->name('tenants.')->group(function () {
    Route::get('/', [App\Http\Controllers\Property\TenantController::class, 'index'])->name('index');
    Route::get('/create', function () {
        return view('property.tenants.create');
    })->name('create');
    Route::get('/{id}', function ($id) {
        return view('property.tenants.show', compact('id'));
    })->name('show');
    Route::get('/{id}/edit', function ($id) {
        return view('property.tenants.edit', compact('id'));
    })->name('edit');
});

// Remove Hotel Reports menu usage (replaced by Hotel Expenses)

// Property Reports Routes (for future implementation)
Route::prefix('property-reports')->name('property.reports.')->group(function () {
    Route::get('/', function () {
        return view('property.reports.index');
    })->name('index');
});



// User Manuals
Route::prefix('manuals')->name('manuals.')->middleware('auth')->group(function () {
    Route::get('/', [App\Http\Controllers\ManualController::class, 'index'])->name('index');
    Route::post('/generate', [App\Http\Controllers\ManualController::class, 'generateManual'])->name('generate');
});

////////////////////////////////////////////// RENTAL & EVENT EQUIPMENT ///////////////////////////////////////////

Route::prefix('rental-event-equipment')->name('rental-event-equipment.')->middleware(['auth', 'company.scope'])->group(function () {
    Route::get('/', [App\Http\Controllers\RentalEventEquipmentController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\RentalEventEquipmentController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\RentalEventEquipmentController::class, 'store'])->name('store');

    // Equipment Categories Routes
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'data'])->name('data');
        Route::get('/check-name', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'checkName'])->name('check-name');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'show'])->name('show');
        Route::get('/{category}/edit', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'edit'])->name('edit');
        Route::put('/{category}', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [App\Http\Controllers\RentalEventEquipment\EquipmentCategoryController::class, 'destroy'])->name('destroy');
    });

    // Equipment Master Routes
    Route::prefix('equipment')->name('equipment.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'store'])->name('store');
        Route::get('/{equipment}', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'show'])->name('show');
        Route::get('/{equipment}/edit', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'edit'])->name('edit');
        Route::put('/{equipment}', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'update'])->name('update');
        Route::delete('/{equipment}', [App\Http\Controllers\RentalEventEquipment\EquipmentController::class, 'destroy'])->name('destroy');
    });

    // Equipment Status Routes
    Route::prefix('status')->name('status.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\EquipmentStatusController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\EquipmentStatusController::class, 'data'])->name('data');
    });

    // Rental Quotations Routes
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'store'])->name('store');
        Route::get('/{quotation}/export-pdf', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{quotation}', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'show'])->name('show');
        Route::get('/{quotation}/edit', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'edit'])->name('edit');
        Route::put('/{quotation}', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'update'])->name('update');
        Route::post('/{quotation}/submit-for-approval', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'submitForApproval'])->name('submit-for-approval');
        Route::delete('/{quotation}', [App\Http\Controllers\RentalEventEquipment\RentalQuotationController::class, 'destroy'])->name('destroy');
    });

    // Rental Contracts Routes
    Route::prefix('contracts')->name('contracts.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'create'])->name('create');
        Route::get('/create/{quotation}', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'create'])->name('create.from-quotation');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'store'])->name('store');
        Route::get('/{contract}/edit', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'edit'])->name('edit');
        Route::put('/{contract}', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'update'])->name('update');
        Route::get('/{contract}/export-pdf', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{contract}', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'show'])->name('show');
        Route::delete('/{contract}', [App\Http\Controllers\RentalEventEquipment\RentalContractController::class, 'destroy'])->name('destroy');
    });

    // Accounting Settings Routes
    Route::prefix('accounting-settings')->name('accounting-settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\AccountingSettingController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\AccountingSettingController::class, 'store'])->name('store');
    });

    // Customer Deposits Routes
    Route::prefix('customer-deposits')->name('customer-deposits.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'store'])->name('store');
        Route::get('/get-contracts/{customer}', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'getContractsByCustomer'])->name('get-contracts');
        Route::get('/customer/{customer}/detail', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'customerDetail'])->name('customer-detail');
        Route::get('/customer/{customer}/export-pdf', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'exportCustomerPdf'])->name('export-customer-pdf');
        Route::get('/{deposit}', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'show'])->name('show');
        Route::get('/{deposit}/edit', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'edit'])->name('edit');
        Route::put('/{deposit}', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'update'])->name('update');
        Route::delete('/{deposit}', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'destroy'])->name('destroy');
        Route::get('/{deposit}/export-pdf', [App\Http\Controllers\RentalEventEquipment\CustomerDepositController::class, 'exportPdf'])->name('export-pdf');
    });

    // Rental Dispatches Routes
    Route::prefix('rental-dispatches')->name('rental-dispatches.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'store'])->name('store');
        Route::get('/{dispatch}/items', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'getDispatchItems'])->name('items');
        Route::post('/{dispatch}/confirm', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'confirmDispatch'])->name('confirm');
        Route::delete('/{dispatch}', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'destroy'])->name('destroy');
        Route::get('/{dispatch}', [App\Http\Controllers\RentalEventEquipment\RentalDispatchController::class, 'show'])->name('show');
    });

    // Rental Returns Routes
    Route::prefix('rental-returns')->name('rental-returns.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalReturnController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\RentalReturnController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\RentalReturnController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalReturnController::class, 'store'])->name('store');
        Route::get('/{return}', [App\Http\Controllers\RentalEventEquipment\RentalReturnController::class, 'show'])->name('show');
        Route::delete('/{return}', [App\Http\Controllers\RentalEventEquipment\RentalReturnController::class, 'destroy'])->name('destroy');
    });

    // Damage & Loss Charges Routes
    Route::prefix('damage-charges')->name('damage-charges.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'create'])->name('create');
        Route::get('/returns/{returnId}/items', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'getReturnItems'])->name('returns.items');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'store'])->name('store');
        Route::get('/{charge}', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'show'])->name('show');
        Route::delete('/{charge}', [App\Http\Controllers\RentalEventEquipment\RentalDamageChargeController::class, 'destroy'])->name('destroy');
    });

    // Rental Invoices Routes
    Route::prefix('rental-invoices')->name('rental-invoices.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'create'])->name('create');
        Route::get('/contracts/{contractId}/invoice-data', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'getContractInvoiceData'])->name('contracts.invoice-data');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'update'])->name('update');
        Route::patch('/{invoice}/update-status', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'updateStatus'])->name('update-status');
        Route::get('/{invoice}/payment', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'showPaymentForm'])->name('payment');
        Route::post('/{invoice}/payment', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'storePayment'])->name('payment.store');
        Route::get('/receipt/{receipt}/edit', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'editReceipt'])->name('receipt.edit');
        Route::put('/receipt/{receipt}', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'updateReceipt'])->name('receipt.update');
        Route::delete('/receipt/{receipt}', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'deleteReceipt'])->name('receipt.delete');
        Route::get('/{invoice}/export-pdf', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/{invoice}/export-receipt-pdf', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'exportReceiptPdf'])->name('export-receipt-pdf');
        Route::delete('/{invoice}', [App\Http\Controllers\RentalEventEquipment\RentalInvoiceController::class, 'destroy'])->name('destroy');
    });

    // Decoration Jobs Routes
    Route::prefix('decoration-jobs')->name('decoration-jobs.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'store'])->name('store');
        Route::get('/{job}', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'show'])->name('show');
        Route::get('/{job}/edit', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'edit'])->name('edit');
        Route::put('/{job}', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'update'])->name('update');
        Route::delete('/{job}', [App\Http\Controllers\RentalEventEquipment\DecorationJobController::class, 'destroy'])->name('destroy');
    });

    // Decoration Equipment Plans Routes
    Route::prefix('decoration-equipment-plans')->name('decoration-equipment-plans.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'store'])->name('store');
        Route::get('/{plan}', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'show'])->name('show');
        Route::get('/{plan}/edit', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentPlanController::class, 'update'])->name('update');
    });

    // Decoration Equipment Issues Routes
    Route::prefix('decoration-equipment-issues')->name('decoration-equipment-issues.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'store'])->name('store');
        Route::get('/{issue}/items', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'getIssueItems'])->name('items');
        Route::post('/{issue}/confirm', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'confirmIssue'])->name('confirm');
        Route::get('/{issue}', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentIssueController::class, 'show'])->name('show');
    });

    // Decoration Equipment Returns Routes
    Route::prefix('decoration-equipment-returns')->name('decoration-equipment-returns.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentReturnController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentReturnController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentReturnController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentReturnController::class, 'store'])->name('store');
        Route::get('/{return}', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentReturnController::class, 'show'])->name('show');
    });

    // Decoration Loss Handling Routes
    Route::prefix('decoration-losses')->name('decoration-losses.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'store'])->name('store');
        Route::get('/equipment-for-job/{job}', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'getJobEquipment'])->name('equipment-for-job');
        Route::post('/{loss}/confirm', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'confirm'])->name('confirm');
        Route::get('/{loss}', [App\Http\Controllers\RentalEventEquipment\DecorationEquipmentLossController::class, 'show'])->name('show');
    });

    // Decoration Service Invoices Routes
    Route::prefix('decoration-invoices')->name('decoration-invoices.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'index'])->name('index');
        Route::get('/data', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'data'])->name('data');
        Route::get('/create', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'show'])->name('show');
        Route::get('/{invoice}/edit', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'edit'])->name('edit');
        Route::put('/{invoice}', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'update'])->name('update');
        Route::delete('/{invoice}', [App\Http\Controllers\RentalEventEquipment\DecorationInvoiceController::class, 'destroy'])->name('destroy');
    });

    // Approval Settings Routes
    Route::prefix('approval-settings')->name('approval-settings.')->group(function () {
        Route::get('/', [App\Http\Controllers\RentalEventEquipment\RentalApprovalSettingsController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\RentalEventEquipment\RentalApprovalSettingsController::class, 'store'])->name('store');
    });

    // Approval Routes
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::post('/{type}/{encodedId}/approve', [App\Http\Controllers\RentalEventEquipment\RentalApprovalController::class, 'approve'])->name('approve');
        Route::post('/{type}/{encodedId}/reject', [App\Http\Controllers\RentalEventEquipment\RentalApprovalController::class, 'reject'])->name('reject');
    });
});

////////////////////////////////////////////// END RENTAL & EVENT EQUIPMENT ///////////////////////////////////////////

// LIPISHA Webhook (no authentication required, uses signature verification)
Route::post('/api/lipisha/webhook', [App\Http\Controllers\Api\LipishaWebhookController::class, 'handle'])
    ->name('api.lipisha.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
