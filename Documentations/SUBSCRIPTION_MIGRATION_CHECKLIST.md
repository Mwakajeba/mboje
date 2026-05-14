# Subscription System Migration Checklist

This document lists all files and components needed to migrate the manual subscription functionality from one system to another.

## 📋 Files to Copy/Move

### 1. Database Migrations
- ✅ `database/migrations/2025_09_16_095806_create_subscriptions_table.php`
  - Creates the `subscriptions` table with all required fields

### 2. Models
- ✅ `app/Models/Subscription.php`
  - Main subscription model with relationships, scopes, and helper methods
  - Includes: `markAsPaid()`, `renew()`, `extend()`, `isActive()`, `isExpired()`, etc.

### 3. Controllers
- ✅ `app/Http/Controllers/SubscriptionController.php`
  - Full CRUD operations
  - Actions: `markAsPaid()`, `cancel()`, `renew()`, `extend()`, `dashboard()`
  - User unlocking logic

### 4. Views (Blade Templates)
- ✅ `resources/views/subscriptions/index.blade.php` - List all subscriptions
- ✅ `resources/views/subscriptions/create.blade.php` - Create new subscription
- ✅ `resources/views/subscriptions/edit.blade.php` - Edit subscription
- ✅ `resources/views/subscriptions/show.blade.php` - View subscription details
- ✅ `resources/views/subscriptions/dashboard.blade.php` - Subscription dashboard
- ✅ `resources/views/auth/subscription-expired.blade.php` - Expired subscription page

### 5. Middleware
- ✅ `app/Http/Middleware/CheckSubscriptionStatus.php`
  - Checks subscription status on each request
  - Locks/unlocks users based on subscription
  - Shows expiry notifications

### 6. Jobs (Background Tasks)
- ✅ `app/Jobs/CheckSubscriptionExpiryJob.php`
  - Checks for expired subscriptions
  - Sends notifications
  - Updates subscription statuses

### 7. Routes
Add to `routes/web.php`:
```php
// Subscription expired page
Route::get('/subscription-expired', function () {
    return view('auth.subscription-expired');
})->name('subscription.expired');

// Subscription Management (super-admin only)
Route::prefix('subscriptions')->name('subscriptions.')->middleware(['auth', 'role:super-admin'])->group(function () {
    Route::get('/dashboard', [SubscriptionController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
    Route::post('/', [SubscriptionController::class, 'store'])->name('store');
    Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
    Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
    Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
    Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
    Route::post('/{subscription}/mark-paid', [SubscriptionController::class, 'markAsPaid'])->name('mark-paid');
    Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
    Route::post('/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('extend');
});

// Ticker Messages API (optional - for subscription expiry alerts)
Route::get('/api/ticker-messages', function () {
    // ... ticker message logic
})->middleware('auth');
```

### 8. Middleware Registration
Add to `app/Http/Kernel.php` or `bootstrap/app.php` (Laravel 11+):
```php
'web' => [
    // ... other middleware
    \App\Http\Middleware\CheckSubscriptionStatus::class,
],
```

### 9. Dependencies/Requirements

#### Required Models:
- ✅ `app/Models/Company.php` - Must exist (subscriptions belong to companies)
- ✅ `app/Models/User.php` - Must exist (for user locking/unlocking)

#### Required Tables:
- ✅ `companies` table
- ✅ `users` table
- ✅ `roles` table (for super-admin role check)

#### Required Services (if used):
- ✅ `app/Services/SystemSettingService.php` (optional - for notification days)

### 10. Navigation/Menu Items
Add to navigation menu (e.g., `resources/views/incs/navBar.blade.php`):
```php
@role('super-admin')
<li>
    <a href="{{ route('subscriptions.dashboard') }}">
        <i class="bx bx-credit-card"></i>
        <span>Subscriptions</span>
    </a>
</li>
@endrole
```

### 11. Permissions/Roles
Ensure the following role exists:
- ✅ `super-admin` role (required for subscription management access)

### 12. Scheduled Tasks (Optional)
Add to `app/Console/Kernel.php` or `routes/console.php`:
```php
// Check subscription expiry daily
$schedule->job(new \App\Jobs\CheckSubscriptionExpiryJob())->daily();
```

## 🔧 Configuration Steps

1. **Run Migration:**
   ```bash
   php artisan migrate
   ```

2. **Register Middleware:**
   - Add `CheckSubscriptionStatus` to web middleware group

3. **Add Routes:**
   - Copy subscription routes to `routes/web.php`

4. **Add Navigation:**
   - Add subscription menu items to navigation

5. **Set Permissions:**
   - Ensure `super-admin` role exists
   - Assign `super-admin` role to users who should manage subscriptions

6. **Test:**
   - Create a test subscription
   - Verify user locking/unlocking works
   - Test expiry notifications

## 📝 Key Features

- ✅ Manual subscription creation
- ✅ Subscription status management (active, expired, cancelled, pending)
- ✅ Payment status tracking
- ✅ Automatic user locking when subscription expires
- ✅ Automatic user unlocking when subscription is activated
- ✅ Expiry notifications
- ✅ Subscription renewal and extension
- ✅ Dashboard with statistics

## ⚠️ Important Notes

1. **User Locking:** The system automatically locks all users (except super-admin) when subscription expires
2. **User Unlocking:** Users are automatically unlocked when subscription is marked as paid/active
3. **Middleware:** `CheckSubscriptionStatus` middleware must be registered in the web middleware group
4. **Role Required:** Only users with `super-admin` role can access subscription management
5. **Company Dependency:** Subscriptions are tied to companies, so the `companies` table must exist

## 🔍 Verification Checklist

After migration, verify:
- [ ] Migration runs successfully
- [ ] Subscription CRUD operations work
- [ ] User locking works when subscription expires
- [ ] User unlocking works when subscription is activated
- [ ] Expiry notifications appear
- [ ] Dashboard displays correctly
- [ ] Routes are accessible (with proper permissions)
- [ ] Navigation menu items appear (for super-admin)

