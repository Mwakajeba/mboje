# Password Management Implementation

This document describes the comprehensive password management features implemented in the SmartAccounting application.

## Overview

The password management system provides enterprise-grade password security features:
1. Password history tracking (prevents reuse)
2. Password expiration and age tracking
3. Real-time password strength meter
4. Common password blacklist
5. Password strength validation

## Components

### 1. Password History
**Prevents password reuse**

- **Table**: `password_history`
- **Model**: `PasswordHistory`
- **Service Method**: `PasswordService::isPasswordInHistory()`
- **Service Method**: `PasswordService::savePasswordToHistory()`

**Configuration:**
- `password_history_count` (default: 5) - Number of previous passwords to prevent reuse
- Set to 0 to disable password history

**How it works:**
- When a user changes their password, the old password hash is saved to history
- When validating a new password, the system checks against the last N passwords
- Users cannot reuse any of their last N passwords

### 2. Password Expiration
**Forces periodic password changes**

- **Fields**: `password_expires_at`, `password_expired`, `force_password_change`, `password_changed_at`
- **Middleware**: `CheckPasswordExpiration`
- **Service Methods**: 
  - `PasswordService::isPasswordExpired()`
  - `PasswordService::getDaysUntilExpiration()`
  - `PasswordService::getPasswordAge()`

**Configuration:**
- `password_expiration_days` (default: 0) - Days before password expires (0 = never expires)
- `password_expiration_warning_days` (default: 7) - Show warning when password expires in N days

**How it works:**
- When password is changed, `password_expires_at` is calculated based on expiration days
- Middleware checks expiration on each request
- Users with expired passwords are redirected to password change page
- Warning is shown when password is expiring soon

### 3. Password Age Tracking
**Tracks when passwords were last changed**

- **Field**: `password_changed_at`
- **Service Method**: `PasswordService::getPasswordAge()`

**How it works:**
- Automatically updated when password is changed
- Used to calculate password age and expiration
- Can be used for password age-based policies

### 4. Password Strength Meter
**Real-time password strength indicator**

- **Component**: `resources/views/components/password-strength-meter.blade.php`
- **API Endpoint**: `POST /api/password-strength`
- **Service Method**: `PasswordService::calculatePasswordStrength()`

**Features:**
- Visual progress bar showing strength (0-100%)
- Color-coded strength levels:
  - Very Weak (red) - 0-20%
  - Weak (orange) - 20-40%
  - Fair (yellow) - 40-60%
  - Good (cyan) - 60-80%
  - Strong (green) - 80-100%
- Real-time feedback on password requirements
- Configurable minimum strength score

**Configuration:**
- `password_require_strength_meter` (default: true) - Show/hide strength meter
- `password_min_strength_score` (default: 40) - Minimum strength score required (0-100)

**Usage:**
```blade
<x-password-strength-meter input-id="password" />
```

### 5. Common Password Blacklist
**Blocks weak/common passwords**

- **Service Method**: `PasswordService::isCommonPassword()`
- Built-in list of common weak passwords
- Custom blacklist support

**Configuration:**
- `password_enable_blacklist` (default: true) - Enable/disable blacklist
- `password_custom_blacklist` (default: '') - Comma-separated list of passwords to block

**How it works:**
- Checks password against built-in common passwords list
- Checks against custom blacklist from settings
- Case-insensitive matching
- Prevents users from using easily guessable passwords

## PasswordService

The `PasswordService` class provides all password management functionality:

### Key Methods:

1. **`isCommonPassword(string $password): bool`**
   - Checks if password is in blacklist

2. **`isPasswordInHistory(User $user, string $password): bool`**
   - Checks if password was used before

3. **`savePasswordToHistory(User $user, string $password): void`**
   - Saves password to history

4. **`updatePassword(User $user, string $newPassword): void`**
   - Updates password with full tracking (history, expiration, etc.)

5. **`isPasswordExpired(User $user): bool`**
   - Checks if password has expired

6. **`getPasswordAge(User $user): ?int`**
   - Returns password age in days

7. **`getDaysUntilExpiration(User $user): ?int`**
   - Returns days until password expires

8. **`calculatePasswordStrength(string $password): array`**
   - Calculates password strength score and feedback

9. **`validatePassword(string $password, ?User $user = null): array`**
   - Validates password with all checks (returns array of errors)

## PasswordValidation Rule

Updated `PasswordValidation` rule now includes:
- Minimum length check
- Uppercase requirement
- Number requirement
- Special character requirement
- Password strength score check
- Common password blacklist check
- Password history check (if user provided)

**Usage:**
```php
// For new users
new PasswordValidation(null)

// For existing users (checks history)
new PasswordValidation($user)
```

## Controllers Updated

All password update operations now use `PasswordService`:

1. **AuthController** - Password reset
2. **UserController** - User creation and updates
3. **SettingsController** - User settings password change

## Middleware

**CheckPasswordExpiration** middleware:
- Checks password expiration on each request
- Redirects to password change page if expired
- Shows warning if expiring soon
- Skips check for password change routes

**Registration:**
Registered in `bootstrap/app.php` and applies to all authenticated requests.

## Database Migrations

1. **`create_password_history_table.php`**
   - Creates `password_history` table

2. **`add_password_tracking_to_users_table.php`**
   - Adds password tracking fields to `users` table

3. **`add_password_management_settings_to_system_settings.php`**
   - Adds password management settings

## System Settings

All password management settings are in **System Settings → Security Settings**:

1. **Password History Count** (`password_history_count`)
   - Type: Integer
   - Default: 5
   - Description: Number of previous passwords to prevent reuse

2. **Password Expiration (days)** (`password_expiration_days`)
   - Type: Integer
   - Default: 0 (disabled)
   - Description: Days before password expires

3. **Password Expiration Warning (days)** (`password_expiration_warning_days`)
   - Type: Integer
   - Default: 7
   - Description: Show warning when password expires in N days

4. **Enable Common Password Blacklist** (`password_enable_blacklist`)
   - Type: Boolean
   - Default: true
   - Description: Block common/weak passwords

5. **Custom Password Blacklist** (`password_custom_blacklist`)
   - Type: String
   - Default: ''
   - Description: Comma-separated list of passwords to block

6. **Show Password Strength Meter** (`password_require_strength_meter`)
   - Type: Boolean
   - Default: true
   - Description: Display real-time password strength indicator

7. **Minimum Password Strength Score** (`password_min_strength_score`)
   - Type: Integer (0-100)
   - Default: 40
   - Description: Minimum strength score required

## API Endpoints

### POST `/api/password-strength`
Calculate password strength score.

**Request:**
```json
{
    "password": "MyPassword123!"
}
```

**Response:**
```json
{
    "score": 85,
    "level": "strong",
    "feedback": [],
    "length": 14,
    "has_lower": true,
    "has_upper": true,
    "has_number": true,
    "has_special": true
}
```

## Usage Examples

### Updating Password
```php
use App\Services\PasswordService;

$passwordService = new PasswordService();
$passwordService->updatePassword($user, $newPassword);
```

### Checking Password Strength
```php
$strength = $passwordService->calculatePasswordStrength('MyPassword123!');
// Returns: ['score' => 85, 'level' => 'strong', 'feedback' => [], ...]
```

### Validating Password
```php
$errors = $passwordService->validatePassword($password, $user);
if (!empty($errors)) {
    // Handle errors
}
```

## Testing

### Test Password History
1. Change password to "Password123!"
2. Try to change password again to "Password123!"
3. Should show error: "You cannot reuse any of your last 5 passwords"

### Test Password Expiration
1. Set `password_expiration_days` to 30
2. Change password
3. After 30 days, user should be forced to change password

### Test Common Password Blacklist
1. Try to set password to "password123"
2. Should show error: "This password is too common"

### Test Password Strength Meter
1. Go to password change form
2. Type password in real-time
3. Watch strength meter update dynamically

## Security Impact

**Before Implementation:**
- ❌ No password history (users could reuse passwords)
- ❌ No password expiration (passwords never expired)
- ❌ No password age tracking
- ❌ No password strength indicator
- ❌ No common password blacklist

**After Implementation:**
- ✅ Password history prevents reuse
- ✅ Password expiration forces periodic changes
- ✅ Password age tracked and displayed
- ✅ Real-time password strength meter
- ✅ Common password blacklist blocks weak passwords
- ✅ Comprehensive password validation

## Migration

Run migrations to activate features:

```bash
php artisan migrate
```

This will:
1. Create `password_history` table
2. Add password tracking fields to `users` table
3. Add password management settings to `system_settings` table

## Troubleshooting

### Password history not working
- Check `password_history_count` setting (should be > 0)
- Verify `password_history` table exists
- Check that `PasswordService::updatePassword()` is being used

### Password expiration not working
- Check `password_expiration_days` setting (should be > 0)
- Verify middleware is registered in `bootstrap/app.php`
- Check that `password_changed_at` is being set

### Password strength meter not showing
- Check `password_require_strength_meter` setting
- Verify API route is registered
- Check browser console for JavaScript errors
- Ensure CSRF token meta tag exists in layout

### Common passwords not blocked
- Check `password_enable_blacklist` setting
- Verify password is in blacklist
- Check custom blacklist setting

## References

- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [NIST Password Guidelines](https://pages.nist.gov/800-63-3/sp800-63b.html)
- [Laravel Password Hashing](https://laravel.com/docs/hashing)

