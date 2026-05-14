<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FxRate;
use App\Models\SystemSetting;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FxRateOverrideController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Request rate override approval
     */
    public function requestOverride(Request $request)
    {
        $request->validate([
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate_date' => 'required|date',
            'original_rate' => 'required|numeric|min:0',
            'new_rate' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $threshold = SystemSetting::getValue('fx_rate_override_threshold', 5);
        
        // Calculate percentage difference
        $difference = abs(($request->new_rate - $request->original_rate) / $request->original_rate * 100);
        
        // Check if approval is required
        $approvalRequired = $difference > $threshold;
        
        try {
            DB::beginTransaction();
            
            if ($approvalRequired) {
                // Create approval request (you can extend this to use ApprovalService)
                // For now, we'll create a pending FX rate override record
                $override = FxRate::create([
                    'rate_date' => $request->rate_date,
                    'from_currency' => $request->from_currency,
                    'to_currency' => $request->to_currency,
                    'spot_rate' => $request->new_rate,
                    'source' => 'override_pending',
                    'is_locked' => false,
                    'company_id' => $user->company_id,
                    'created_by' => $user->id,
                ]);
                
                // Log activity
                $override->logActivity('create', "Requested FX Rate Override Approval - {$request->from_currency}/{$request->to_currency}", [
                    'Original Rate' => number_format($request->original_rate, 6),
                    'New Rate' => number_format($request->new_rate, 6),
                    'Difference' => number_format($difference, 2) . '%',
                    'Threshold' => number_format($threshold, 2) . '%',
                    'Rate Date' => $request->rate_date,
                    'Reason' => $request->reason ?? 'No reason provided',
                    'Status' => 'Pending Approval',
                    'Requested By' => $user->name
                ]);
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Rate override request submitted for approval.',
                    'approval_required' => true,
                    'override_id' => $override->id,
                    'difference' => round($difference, 2)
                ]);
            } else {
                // No approval needed, directly update/create rate
                $fxRate = FxRate::updateOrCreate(
                    [
                        'rate_date' => $request->rate_date,
                        'from_currency' => $request->from_currency,
                        'to_currency' => $request->to_currency,
                        'company_id' => $user->company_id,
                    ],
                    [
                        'spot_rate' => $request->new_rate,
                        'source' => 'manual_override',
                        'is_locked' => false,
                        'created_by' => $user->id,
                    ]
                );
                
                // Log activity
                $fxRate->logActivity('update', "Overrode FX Rate - {$request->from_currency}/{$request->to_currency} (Difference: " . number_format($difference, 2) . "%)", [
                    'Original Rate' => number_format($request->original_rate, 6),
                    'New Rate' => number_format($request->new_rate, 6),
                    'Difference' => number_format($difference, 2) . '%',
                    'Rate Date' => $request->rate_date,
                    'Reason' => $request->reason ?? 'No reason provided',
                    'Overridden By' => $user->name
                ]);
                
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Exchange rate updated successfully.',
                    'approval_required' => false,
                    'rate_id' => $fxRate->id,
                    'difference' => round($difference, 2)
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FX Rate Override Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process rate override: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve rate override
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        
        // Check permission
        if (!$user->can('approve fx rate override')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to approve rate overrides.'
            ], 403);
        }

        try {
            $override = FxRate::where('id', $id)
                ->where('company_id', $user->company_id)
                ->where('source', 'override_pending')
                ->firstOrFail();

            DB::beginTransaction();
            
            // Update to approved status
            $override->update([
                'source' => 'override_approved',
                'is_locked' => true, // Lock the approved override
            ]);
            
            // Log activity
            $override->logActivity('approve', "Approved FX Rate Override - {$override->from_currency}/{$override->to_currency}", [
                'Rate' => number_format($override->spot_rate, 6),
                'Rate Date' => $override->rate_date ? $override->rate_date->format('Y-m-d') : 'N/A',
                'Comments' => $request->comments ?? 'No comments',
                'Approved By' => $user->name,
                'Approved At' => now()->format('Y-m-d H:i:s')
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Rate override approved successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FX Rate Override Approval Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve rate override: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject rate override
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        
        // Check permission
        if (!$user->can('approve fx rate override')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reject rate overrides.'
            ], 403);
        }

        try {
            $override = FxRate::where('id', $id)
                ->where('company_id', $user->company_id)
                ->where('source', 'override_pending')
                ->firstOrFail();

            DB::beginTransaction();
            
            // Log activity before deletion
            $override->logActivity('reject', "Rejected FX Rate Override - {$override->from_currency}/{$override->to_currency}", [
                'Rate' => number_format($override->spot_rate, 6),
                'Rate Date' => $override->rate_date ? $override->rate_date->format('Y-m-d') : 'N/A',
                'Rejection Reason' => $request->reason,
                'Rejected By' => $user->name,
                'Rejected At' => now()->format('Y-m-d H:i:s')
            ]);
            
            // Delete the override request
            $override->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Rate override rejected successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FX Rate Override Rejection Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject rate override: ' . $e->getMessage()
            ], 500);
        }
    }
}

