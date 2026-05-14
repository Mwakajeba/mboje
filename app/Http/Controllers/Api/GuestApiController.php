<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Guest;
use App\Models\Hotel\GuestMessage;
use App\Models\BankAccount;
use App\Models\GlTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class GuestApiController extends Controller
{
    /**
     * Guest registration API endpoint
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50|unique:guests,phone',
            'email' => 'nullable|email|max:255|unique:guests,email',
            'password' => 'required|string|min:6|confirmed',
            'room_id' => 'nullable|exists:rooms,id', // Optional: if registering from room selection
        ]);

        DB::beginTransaction();
        
        try {
            // Get branch_id from room if provided, otherwise use default
            $branchId = 1; // Default branch
            $companyId = 1; // Default company
            
            if ($request->has('room_id') && $request->room_id) {
                $room = \App\Models\Hotel\Room::find($request->room_id);
                if ($room) {
                    $branchId = $room->branch_id;
                    $companyId = $room->company_id;
                }
            }
            
            // Generate guest number
            $lastGuest = Guest::orderBy('id', 'desc')->first();
            $nextNumber = $lastGuest ? $lastGuest->id + 1 : 1;
            $guestNumber = 'G' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            $guest = Guest::create([
                'guest_number' => $guestNumber,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 'active',
                'company_id' => $companyId,
                'created_by' => 1, // System user
            ]);

            DB::commit();

            // Generate token using Sanctum
            $token = $guest->createToken('guest-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $guest->id,
                        'name' => $guest->full_name,
                        'first_name' => $guest->first_name,
                        'last_name' => $guest->last_name,
                        'email' => $guest->email,
                        'phone' => $guest->phone,
                    ],
                    'token' => $token,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guest login API endpoint
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find guest by phone
        $guest = Guest::where('phone', $request->phone)
            ->where('status', 'active')
            ->first();

        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password.',
            ], 401);
        }

        // Check if guest has password set
        if (!$guest->password) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not set up. Please contact support.',
            ], 403);
        }

        // Verify password
        if (!Hash::check($request->password, $guest->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password.',
            ], 401);
        }

        // Generate token using Sanctum
        $token = $guest->createToken('guest-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * Get current authenticated guest
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $guest = $request->user();
        
        if (!$guest) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $guest->id,
                    'name' => $guest->full_name,
                    'first_name' => $guest->first_name,
                    'last_name' => $guest->last_name,
                    'email' => $guest->email,
                    'phone' => $guest->phone,
                ],
            ],
        ]);
    }

    /**
     * Update guest profile
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $guest = $request->user();
        
        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|nullable|email|max:255|unique:guests,email,' . $guest->id,
            'phone' => 'sometimes|nullable|string|max:50|unique:guests,phone,' . $guest->id,
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        DB::beginTransaction();
        
        try {
            $updateData = [];
            
            if ($request->has('first_name')) {
                $updateData['first_name'] = $request->first_name;
            }
            
            if ($request->has('last_name')) {
                $updateData['last_name'] = $request->last_name;
            }
            
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $guest->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $guest->id,
                        'name' => $guest->full_name,
                        'first_name' => $guest->first_name,
                        'last_name' => $guest->last_name,
                        'email' => $guest->email,
                        'phone' => $guest->phone,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guest logout API endpoint
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $guest = $request->user();
        
        if ($guest) {
            // Revoke token if using Sanctum
            if (method_exists($guest, 'tokens')) {
                $guest->tokens()->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get bank accounts for payment (public endpoint)
     * 
     * @return JsonResponse
     */
    public function getBankAccounts(): JsonResponse
    {
        try {
            // Get all active bank accounts (you may want to filter by company_id)
            // For now, we'll get all bank accounts that have account numbers
            $bankAccounts = BankAccount::with('chartAccount')
                ->whereNotNull('account_number')
                ->where('account_number', '!=', '')
                ->orderBy('name')
                ->get()
                ->map(function ($account) {
                    // Calculate balance from GL transactions
                    $debits = GlTransaction::where('chart_account_id', $account->chart_account_id)
                        ->where('nature', 'debit')
                        ->sum('amount');
                    $credits = GlTransaction::where('chart_account_id', $account->chart_account_id)
                        ->where('nature', 'credit')
                        ->sum('amount');
                    $balance = $debits - $credits;

                    // Extract bank name from chart account name or account name
                    // Chart account name typically contains bank name (e.g., "CRDB Bank - Main Account")
                    $bankName = null;
                    if ($account->chartAccount && $account->chartAccount->account_name) {
                        // Try to extract bank name from chart account name
                        // Format might be "Bank Name - Account Name" or just "Bank Name"
                        $chartAccountName = $account->chartAccount->account_name;
                        if (strpos($chartAccountName, ' - ') !== false) {
                            $bankName = trim(explode(' - ', $chartAccountName)[0]);
                        } else {
                            $bankName = $chartAccountName;
                        }
                    }
                    // Fallback to account name if no bank name found
                    if (!$bankName) {
                        $bankName = $account->name;
                    }

                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'bank_name' => $bankName,
                        'account_number' => $account->account_number,
                        'currency' => $account->currency ?? 'TZS',
                        'balance' => $balance,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'bank_accounts' => $bankAccounts,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bank accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get branches for guest selection (public endpoint)
     * 
     * @return JsonResponse
     */
    public function getBranches(): JsonResponse
    {
        try {
            // Get all active branches
            $branches = \App\Models\Branch::where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function ($branch) {
                    return [
                        'id' => $branch->id,
                        'name' => $branch->name ?? $branch->branch_name,
                        'address' => $branch->address ?? $branch->location,
                        'phone' => $branch->phone,
                        'email' => $branch->email,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'branches' => $branches,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message from guest
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'branch_id' => 'nullable|exists:branches,id',
            'source' => 'nullable|in:about_page,contact_page',
        ]);

        try {
            $message = GuestMessage::create([
                'name' => $request->name,
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'branch_id' => $request->branch_id,
                'source' => $request->source ?? 'contact_page',
                'is_read' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $message->id,
                    'name' => $message->name,
                    'email' => $message->email,
                    'subject' => $message->subject,
                    'message' => $message->message,
                    'branch_id' => $message->branch_id,
                    'source' => $message->source,
                    'created_at' => $message->created_at->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get messages for authenticated guest
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyMessages(Request $request): JsonResponse
    {
        $guest = $request->user();

        if (!$guest || !($guest instanceof \App\Models\Hotel\Guest)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        try {
            // Get messages by email and/or branch_id
            $query = GuestMessage::with('branch');
            
            // Build query to match messages by email or branch
            $query->where(function($q) use ($guest) {
                // Match by email if guest has email
                if ($guest->email) {
                    $q->where('email', $guest->email);
                }
                
                // Also include messages for the guest's branch
                if ($guest->branch_id) {
                    $q->orWhere('branch_id', $guest->branch_id);
                }
            });
            
            $messages = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'subject' => $message->subject,
                        'message' => $message->message,
                        'response' => $message->response,
                        'branch' => $message->branch ? [
                            'id' => $message->branch->id,
                            'name' => $message->branch->name,
                        ] : null,
                        'created_at' => $message->created_at->toISOString(),
                        'responded_at' => $message->responded_at ? $message->responded_at->toISOString() : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => $messages,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch messages: ' . $e->getMessage(),
            ], 500);
        }
    }
}
