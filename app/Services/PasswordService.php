<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordHistory;
use App\Services\SystemSettingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordService
{
    /**
     * Common weak passwords blacklist
     */
    protected const COMMON_PASSWORDS = [
        'password', 'password123', '12345678', '123456789', '1234567890',
        'qwerty', 'qwerty123', 'abc123', 'letmein', 'welcome',
        'monkey', 'dragon', 'master', 'sunshine', 'princess',
        'football', 'iloveyou', 'admin', 'root', 'administrator',
        'passw0rd', 'Password1', 'Password123', 'Welcome123!',
        'qwertyuiop', 'asdfghjkl', 'zxcvbnm', '11111111',
        '00000000', '12341234', 'qwerty12', '1qaz2wsx',
    ];

    /**
     * Check if password is in common passwords blacklist
     */
    public function isCommonPassword(string $password): bool
    {
        $enableBlacklist = SystemSettingService::get('password_enable_blacklist', true);
        
        if (!$enableBlacklist) {
            return false;
        }

        $passwordLower = strtolower($password);
        
        // Check against built-in common passwords
        foreach (self::COMMON_PASSWORDS as $common) {
            if ($passwordLower === strtolower($common)) {
                return true;
            }
        }

        // Check against custom blacklist from settings
        $customBlacklist = SystemSettingService::get('password_custom_blacklist', '');
        if (!empty($customBlacklist)) {
            $blacklist = array_map('trim', explode(',', $customBlacklist));
            foreach ($blacklist as $blocked) {
                if ($passwordLower === strtolower($blocked)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if password was used before (password history)
     */
    public function isPasswordInHistory(User $user, string $password): bool
    {
        $historyCount = (int) SystemSettingService::get('password_history_count', 5);
        
        if ($historyCount <= 0) {
            return false;
        }

        // Get recent password history
        $history = PasswordHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->get();

        foreach ($history as $record) {
            if (Hash::check($password, $record->password_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save password to history
     */
    public function savePasswordToHistory(User $user, string $password): void
    {
        $historyCount = (int) SystemSettingService::get('password_history_count', 5);
        
        if ($historyCount <= 0) {
            return;
        }

        // Save current password hash to history
        PasswordHistory::create([
            'user_id' => $user->id,
            'password_hash' => Hash::make($password),
            'created_at' => now(),
        ]);

        // Clean up old history (keep only the configured number)
        // Use skip + take to generate proper LIMIT/OFFSET for MySQL
        $oldHistory = PasswordHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->skip($historyCount)
            ->take(PHP_INT_MAX)
            ->get();

        foreach ($oldHistory as $old) {
            $old->delete();
        }
    }

    /**
     * Update user password with history tracking
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        // Save old password to history before updating
        if ($user->password) {
            // We can't get the plain text of old password, so we save the hash
            // This is a limitation - we can only check against history going forward
            PasswordHistory::create([
                'user_id' => $user->id,
                'password_hash' => $user->password, // Current hash
                'created_at' => $user->password_changed_at ?? now(),
            ]);
        }

        // Update password
        $user->password = Hash::make($newPassword);
        $user->password_changed_at = now();
        
        // Calculate password expiration
        $expirationDays = (int) SystemSettingService::get('password_expiration_days', 0);
        if ($expirationDays > 0) {
            $user->password_expires_at = now()->addDays($expirationDays);
            $user->password_expired = false;
        } else {
            $user->password_expires_at = null;
            $user->password_expired = false;
        }

        $user->force_password_change = false;
        $user->save();

        // Save new password to history
        $this->savePasswordToHistory($user, $newPassword);
    }

    /**
     * Check if user's password has expired
     */
    public function isPasswordExpired(User $user): bool
    {
        $expirationDays = (int) SystemSettingService::get('password_expiration_days', 0);
        
        if ($expirationDays <= 0) {
            return false;
        }

        if (!$user->password_changed_at) {
            // Password never changed, consider it expired
            return true;
        }

        if ($user->password_expires_at && $user->password_expires_at->isPast()) {
            return true;
        }

        // Check if password age exceeds expiration days
        $ageInDays = $user->password_changed_at->diffInDays(now());
        return $ageInDays >= $expirationDays;
    }

    /**
     * Get password age in days
     */
    public function getPasswordAge(User $user): ?int
    {
        if (!$user->password_changed_at) {
            return null;
        }

        return $user->password_changed_at->diffInDays(now());
    }

    /**
     * Get days until password expires
     */
    public function getDaysUntilExpiration(User $user): ?int
    {
        $expirationDays = (int) SystemSettingService::get('password_expiration_days', 0);
        
        if ($expirationDays <= 0) {
            return null;
        }

        if (!$user->password_changed_at) {
            return 0; // Already expired
        }

        $ageInDays = $user->password_changed_at->diffInDays(now());
        $daysRemaining = $expirationDays - $ageInDays;

        return max(0, $daysRemaining);
    }

    /**
     * Calculate password strength score (0-100)
     */
    public function calculatePasswordStrength(string $password): array
    {
        $score = 0;
        $feedback = [];

        // Length (max 25 points)
        $length = strlen($password);
        if ($length >= 12) {
            $score += 25;
        } elseif ($length >= 8) {
            $score += 15;
        } elseif ($length >= 6) {
            $score += 5;
        }

        // Character variety (max 50 points)
        $hasLower = preg_match('/[a-z]/', $password);
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

        $variety = ($hasLower ? 1 : 0) + ($hasUpper ? 1 : 0) + ($hasNumber ? 1 : 0) + ($hasSpecial ? 1 : 0);
        $score += ($variety * 12.5); // 12.5 points per character type

        // Complexity (max 25 points)
        if ($length >= 8 && $variety >= 3) {
            $score += 15;
        }
        if ($length >= 12 && $variety === 4) {
            $score += 10;
        }

        // Penalties
        if ($this->isCommonPassword($password)) {
            $score = max(0, $score - 30);
            $feedback[] = 'Password is too common';
        }

        if (preg_match('/(.)\1{2,}/', $password)) {
            $score = max(0, $score - 10);
            $feedback[] = 'Avoid repeating characters';
        }

        if (preg_match('/\d{4,}/', $password)) {
            $score = max(0, $score - 10);
            $feedback[] = 'Avoid consecutive numbers';
        }

        // Determine strength level
        if ($score >= 80) {
            $level = 'strong';
        } elseif ($score >= 60) {
            $level = 'good';
        } elseif ($score >= 40) {
            $level = 'fair';
        } elseif ($score >= 20) {
            $level = 'weak';
        } else {
            $level = 'very-weak';
        }

        return [
            'score' => min(100, max(0, $score)),
            'level' => $level,
            'feedback' => $feedback,
            'length' => $length,
            'has_lower' => $hasLower,
            'has_upper' => $hasUpper,
            'has_number' => $hasNumber,
            'has_special' => $hasSpecial,
        ];
    }

    /**
     * Validate password with all checks
     */
    public function validatePassword(string $password, ?User $user = null): array
    {
        $errors = [];

        // Check common password blacklist
        if ($this->isCommonPassword($password)) {
            $errors[] = 'This password is too common. Please choose a more unique password.';
        }

        // Check password history
        if ($user && $this->isPasswordInHistory($user, $password)) {
            $historyCount = (int) SystemSettingService::get('password_history_count', 5);
            $errors[] = "You cannot reuse any of your last {$historyCount} passwords.";
        }

        return $errors;
    }
}

