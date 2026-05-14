<?php

namespace App\Http\Controllers\Accounting\PettyCash;

use App\Http\Controllers\Controller;
use App\Models\PettyCash\PettyCashUnit;
use App\Models\PettyCash\PettyCashReplenishment;
use App\Models\BankAccount;
use App\Services\PettyCashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class PettyCashReplenishmentController extends Controller
{
    protected $pettyCashService;

    public function __construct(PettyCashService $pettyCashService)
    {
        $this->pettyCashService = $pettyCashService;
    }

    /**
     * Display a listing of replenishments
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $query = PettyCashReplenishment::with(['pettyCashUnit', 'requestedBy', 'approvedBy'])
            ->whereHas('pettyCashUnit', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        
        if ($request->filled('petty_cash_unit_id')) {
            $query->where('petty_cash_unit_id', $request->petty_cash_unit_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $replenishments = $query->orderBy('request_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $units = PettyCashUnit::forCompany($companyId)->active()->orderBy('name')->get();
        
        return view('accounting.petty-cash.replenishments.index', compact('replenishments', 'units'));
    }

    /**
     * Get bank accounts for replenishment (AJAX)
     */
    public function getBankAccounts(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->with('chartAccount')
        ->orderBy('name')
        ->get()
        ->map(function($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'account_number' => $account->account_number,
                'balance' => $account->balance ?? 0,
                'display' => $account->name . ' (' . $account->account_number . ') - Balance: ' . number_format($account->balance ?? 0, 2)
            ];
        });
        
        return response()->json($bankAccounts);
    }

    /**
     * Store a newly created replenishment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'petty_cash_unit_id' => 'required|exists:petty_cash_units,id',
            'request_date' => 'required|date',
            'requested_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'source_account_id' => 'nullable|exists:bank_accounts,id',
        ]);
        
        // Validate against maximum limit
        $unit = PettyCashUnit::findOrFail($validated['petty_cash_unit_id']);
        $maximumLimit = $unit->maximum_limit;
        
        // If unit doesn't have maximum_limit, get from settings
        if (!$maximumLimit || $maximumLimit <= 0) {
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
            $maximumLimit = $settings ? $settings->maximum_limit : null;
        }
        
        // Check if requested amount would exceed maximum limit
        if ($maximumLimit && $maximumLimit > 0) {
            $projectedBalance = $unit->current_balance + $validated['requested_amount'];
            if ($projectedBalance > $maximumLimit) {
                $errorMessage = "Replenishment amount (TZS " . number_format($validated['requested_amount'], 2) . ") would exceed the maximum limit. Current balance: TZS " . number_format($unit->current_balance, 2) . ", Maximum limit: TZS " . number_format($maximumLimit, 2) . ". Maximum allowed replenishment: TZS " . number_format($maximumLimit - $unit->current_balance, 2) . ".";
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['requested_amount' => [$errorMessage]]
                    ], 422);
                }
                
                return back()->withInput()->withErrors(['requested_amount' => $errorMessage]);
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Generate replenishment number
            $replenishmentNumber = $this->generateReplenishmentNumber();
            
            $validated['replenishment_number'] = $replenishmentNumber;
            $validated['requested_by'] = Auth::id();
            $validated['status'] = 'submitted';
            
            $replenishment = PettyCashReplenishment::create($validated);
            
            DB::commit();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Replenishment request created successfully.',
                    'replenishment' => $replenishment
                ]);
            }
            
            return redirect()->route('accounting.petty-cash.replenishments.index')
                ->with('success', 'Replenishment request created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create replenishment: ' . $e->getMessage(),
                    'errors' => $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Failed to create replenishment: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a replenishment (AJAX)
     */
    public function edit($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Replenishment not found.'], 404);
            }
            abort(404);
        }
        
        $replenishment = PettyCashReplenishment::with(['pettyCashUnit', 'sourceAccount'])->findOrFail($id);
        
        if (!$replenishment->canBeEdited() && !($replenishment->status === 'submitted' && !$replenishment->approved_by)) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Replenishment cannot be edited in its current status.'], 400);
            }
            return back()->with('error', 'Replenishment cannot be edited in its current status.');
        }
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'replenishment' => [
                    'id' => $replenishment->id,
                    'encoded_id' => $replenishment->encoded_id,
                    'petty_cash_unit_id' => $replenishment->petty_cash_unit_id,
                    'request_date' => $replenishment->request_date->format('Y-m-d'),
                    'requested_amount' => $replenishment->requested_amount,
                    'reason' => $replenishment->reason,
                    'source_account_id' => $replenishment->source_account_id,
                    'status' => $replenishment->status,
                ]
            ]);
        }
        
        // Non-AJAX fallback (if needed)
        $units = PettyCashUnit::forCompany(Auth::user()->company_id)->active()->orderBy('name')->get();
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) {
            $q->where('company_id', Auth::user()->company_id);
        })->orderBy('name')->get();
        
        return view('accounting.petty-cash.replenishments.edit', compact('replenishment', 'units', 'bankAccounts'));
    }

    /**
     * Update a replenishment
     */
    public function update($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Replenishment not found.'], 404);
            }
            abort(404);
        }
        
        $replenishment = PettyCashReplenishment::findOrFail($id);
        
        if (!$replenishment->canBeEdited() && !($replenishment->status === 'submitted' && !$replenishment->approved_by)) {
            $message = 'Replenishment cannot be edited in its current status.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        $validated = $request->validate([
            'request_date' => 'required|date',
            'requested_amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string',
            'source_account_id' => 'nullable|exists:bank_accounts,id',
        ]);
        
        // Validate against maximum limit
        $unit = $replenishment->pettyCashUnit;
        $maximumLimit = $unit->maximum_limit;
        
        // If unit doesn't have maximum_limit, get from settings
        if (!$maximumLimit || $maximumLimit <= 0) {
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
            $maximumLimit = $settings ? $settings->maximum_limit : null;
        }
        
        // Check if requested amount would exceed maximum limit
        if ($maximumLimit && $maximumLimit > 0) {
            $projectedBalance = $unit->current_balance + $validated['requested_amount'];
            if ($projectedBalance > $maximumLimit) {
                $errorMessage = "Replenishment amount (TZS " . number_format($validated['requested_amount'], 2) . ") would exceed the maximum limit. Current balance: TZS " . number_format($unit->current_balance, 2) . ", Maximum limit: TZS " . number_format($maximumLimit, 2) . ". Maximum allowed replenishment: TZS " . number_format($maximumLimit - $unit->current_balance, 2) . ".";
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['requested_amount' => [$errorMessage]]
                    ], 422);
                }
                
                return back()->withInput()->withErrors(['requested_amount' => $errorMessage]);
            }
        }
        
        try {
            DB::beginTransaction();
            
            $replenishment->update($validated);
            
            DB::commit();
            
            $message = 'Replenishment updated successfully.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->route('accounting.petty-cash.replenishments.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to update replenishment: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->withInput()->with('error', $errorMessage);
        }
    }

    /**
     * Approve a replenishment
     */
    public function approve($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Replenishment not found.'], 404);
            }
            abort(404);
        }
        
        $replenishment = PettyCashReplenishment::findOrFail($id);
        
        if (!$replenishment->canBeApproved()) {
            $message = 'Replenishment cannot be approved.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:0.01',
            'approval_notes' => 'nullable|string',
        ]);
        
        $unit = $replenishment->pettyCashUnit;
        $approvedAmount = $validated['approved_amount'] ?? $replenishment->requested_amount;
        
        // Validate against maximum limit
        $maximumLimit = $unit->maximum_limit;
        
        // If unit doesn't have maximum_limit, get from settings
        if (!$maximumLimit || $maximumLimit <= 0) {
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
            $maximumLimit = $settings ? $settings->maximum_limit : null;
        }
        
        // Check if approved amount would exceed maximum limit
        if ($maximumLimit && $maximumLimit > 0) {
            $projectedBalance = $unit->current_balance + $approvedAmount;
            if ($projectedBalance > $maximumLimit) {
                $errorMessage = "Approved amount (TZS " . number_format($approvedAmount, 2) . ") would exceed the maximum limit. Current balance: TZS " . number_format($unit->current_balance, 2) . ", Maximum limit: TZS " . number_format($maximumLimit, 2) . ". Maximum allowed approval: TZS " . number_format($maximumLimit - $unit->current_balance, 2) . ".";
                
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'errors' => ['approved_amount' => [$errorMessage]]
                    ], 422);
                }
                
                return back()->withInput()->withErrors(['approved_amount' => $errorMessage]);
            }
        }
        
        try {
            DB::beginTransaction();
            
            $replenishment->update([
                'status' => 'approved',
                'approved_amount' => $approvedAmount,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $validated['approval_notes'] ?? null,
            ]);
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($unit->company_id);
            
            // In Sub-Imprest mode, create imprest request for replenishment
            // This links the replenishment to the imprest module for Finance to reimburse
            if ($settings->isSubImprestMode()) {
                try {
                    $imprestRequest = \App\Services\PettyCashImprestService::createImprestRequestFromReplenishment($replenishment);
                    if ($imprestRequest) {
                        \Log::info('Imprest request created from approved replenishment (Sub-Imprest Mode)', [
                            'replenishment_id' => $replenishment->id,
                            'imprest_request_id' => $imprestRequest->id,
                            'imprest_request_number' => $imprestRequest->request_number
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to create imprest request from replenishment', [
                        'replenishment_id' => $replenishment->id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't fail the approval if imprest creation fails
                }
            } else {
                // In standalone mode, post to GL directly
                $this->pettyCashService->postReplenishmentToGL($replenishment);
            }
            
            DB::commit();
            
            $message = 'Replenishment approved and posted to GL.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to approve replenishment: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Reject a replenishment
     */
    public function reject($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Replenishment not found.'], 404);
            }
            abort(404);
        }
        
        $replenishment = PettyCashReplenishment::findOrFail($id);
        
        if (!$replenishment->canBeApproved()) {
            $message = 'Replenishment cannot be rejected.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);
        
        try {
            DB::beginTransaction();
            
            $replenishment->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            
            DB::commit();
            
            $message = 'Replenishment rejected successfully.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = 'Failed to reject replenishment: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Remove the specified replenishment
     */
    public function destroy($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Replenishment not found.'], 404);
            }
            abort(404);
        }
        
        $replenishment = PettyCashReplenishment::findOrFail($id);
        
        // Only allow deletion of draft, rejected, or submitted (unapproved) replenishments
        if (!in_array($replenishment->status, ['draft', 'rejected', 'submitted']) || $replenishment->approved_by) {
            $message = 'Replenishment cannot be deleted in its current status.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 400);
            }
            return back()->with('error', $message);
        }
        
        try {
            $replenishment->delete();
            
            $message = 'Replenishment deleted successfully.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            $errorMessage = 'Failed to delete replenishment: ' . $e->getMessage();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return back()->with('error', $errorMessage);
        }
    }

    /**
     * Display the specified replenishment
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $replenishment = PettyCashReplenishment::with([
            'pettyCashUnit',
            'requestedBy',
            'approvedBy',
            'sourceAccount.chartAccount',
            'journal.items.chartAccount'
        ])->findOrFail($id);
        
        return view('accounting.petty-cash.replenishments.show', compact('replenishment'));
    }

    /**
     * Generate replenishment number
     */
    private function generateReplenishmentNumber(): string
    {
        $prefix = 'PCR-';
        $year = date('Y');
        $month = date('m');
        
        $lastReplenishment = PettyCashReplenishment::where('replenishment_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('replenishment_number', 'desc')
            ->first();
        
        if ($lastReplenishment) {
            $lastNumber = (int) substr($lastReplenishment->replenishment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}

