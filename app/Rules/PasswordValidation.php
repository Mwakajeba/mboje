<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Services\SystemSettingService;
use App\Services\PasswordService;
use App\Models\User;

class PasswordValidation implements Rule
{
    protected $message = '';
    protected $user = null;
    protected $passwordService;

    /**
     * Create a new rule instance.
     */
    public function __construct(?User $user = null)
    {
        $this->user = $user;
        $this->passwordService = new PasswordService();
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        $securityConfig = SystemSettingService::getSecurityConfig();
        
        // Check minimum length
        $minLength = $securityConfig['password_min_length'] ?? 8;
        if (strlen($value) < $minLength) {
            $this->message = "Password must be at least {$minLength} characters long.";
            return false;
        }

        // Check for uppercase letters
        if ($securityConfig['password_require_uppercase'] ?? true) {
            if (!preg_match('/[A-Z]/', $value)) {
                $this->message = 'Password must contain at least one uppercase letter.';
                return false;
            }
        }

        // Check for numbers
        if ($securityConfig['password_require_numbers'] ?? true) {
            if (!preg_match('/[0-9]/', $value)) {
                $this->message = 'Password must contain at least one number.';
                return false;
            }
        }

        // Check for special characters
        if ($securityConfig['password_require_special'] ?? true) {
            if (!preg_match('/[^A-Za-z0-9]/', $value)) {
                $this->message = 'Password must contain at least one special character.';
                return false;
            }
        }

        // Check password strength score
        $minStrength = (int) SystemSettingService::get('password_min_strength_score', 40);
        $strength = $this->passwordService->calculatePasswordStrength($value);
        if ($strength['score'] < $minStrength) {
            $this->message = 'Password is too weak. Please choose a stronger password.';
            return false;
        }

        // Check common password blacklist
        if ($this->passwordService->isCommonPassword($value)) {
            $this->message = 'This password is too common. Please choose a more unique password.';
            return false;
        }

        // Check password history (if user is provided)
        if ($this->user && $this->passwordService->isPasswordInHistory($this->user, $value)) {
            $historyCount = (int) SystemSettingService::get('password_history_count', 5);
            $this->message = "You cannot reuse any of your last {$historyCount} passwords.";
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message;
    }
} 