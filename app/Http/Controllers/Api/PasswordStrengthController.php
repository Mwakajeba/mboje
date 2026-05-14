<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PasswordStrengthController extends Controller
{
    protected $passwordService;

    public function __construct()
    {
        $this->passwordService = new PasswordService();
    }

    /**
     * Calculate password strength
     */
    public function calculateStrength(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $password = $request->input('password');
        $strength = $this->passwordService->calculatePasswordStrength($password);

        return response()->json([
            'score' => $strength['score'],
            'level' => $strength['level'],
            'feedback' => $strength['feedback'],
            'length' => $strength['length'],
            'has_lower' => $strength['has_lower'],
            'has_upper' => $strength['has_upper'],
            'has_number' => $strength['has_number'],
            'has_special' => $strength['has_special'],
        ]);
    }
}
