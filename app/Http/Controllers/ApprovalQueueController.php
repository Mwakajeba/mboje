<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalEntryApproval;
use App\Models\JournalEntryApprovalSetting;
use App\Models\Payment;
use App\Models\PaymentVoucherApproval;
use App\Models\PaymentVoucherApprovalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApprovalQueueController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // Get journal entry approvals
        $journalSettings = JournalEntryApprovalSetting::where('company_id', $companyId)->first();
        $journalApprovals = collect();
        
        if ($journalSettings) {
            $journalApprovals = JournalEntryApproval::whereHas('journal', function($query) use ($companyId) {
                $query->whereHas('user', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->where('status', 'pending')
            ->with(['journal.user', 'journal.branch'])
            ->get()
            ->filter(function($approval) use ($journalSettings, $user) {
                return $journalSettings->canUserApproveAtLevel($user, $approval->approval_level);
            })
            ->map(function($approval) {
                return [
                    'type' => 'journal',
                    'id' => $approval->id,
                    'journal_id' => $approval->journal_id,
                    'reference' => $approval->journal->reference,
                    'description' => $approval->journal->description,
                    'amount' => $approval->journal->total,
                    'date' => $approval->journal->date,
                    'created_by' => $approval->journal->user->name ?? 'N/A',
                    'branch' => $approval->journal->branch->name ?? 'N/A',
                    'approval_level' => $approval->approval_level,
                    'created_at' => $approval->created_at,
                ];
            });
        }

        // Get payment voucher approvals
        $paymentSettings = PaymentVoucherApprovalSetting::where('company_id', $companyId)->first();
        $paymentApprovals = collect();
        
        if ($paymentSettings) {
            $paymentApprovals = PaymentVoucherApproval::whereHas('payment', function($query) use ($companyId) {
                $query->whereHas('user', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->where('status', 'pending')
            ->with(['payment.user', 'payment.branch'])
            ->get()
            ->filter(function($approval) use ($paymentSettings, $user) {
                return $paymentSettings->canUserApproveAtLevel($user, $approval->approval_level);
            })
            ->map(function($approval) {
                return [
                    'type' => 'payment',
                    'id' => $approval->id,
                    'payment_id' => $approval->payment_id,
                    'reference' => $approval->payment->reference ?? $approval->payment->reference_number,
                    'description' => $approval->payment->description,
                    'amount' => $approval->payment->amount ?? 0,
                    'date' => $approval->payment->date,
                    'created_by' => $approval->payment->user->name ?? 'N/A',
                    'branch' => $approval->payment->branch->name ?? 'N/A',
                    'approval_level' => $approval->approval_level,
                    'created_at' => $approval->created_at,
                ];
            });
        }

        // Combine and sort by created_at
        $allApprovals = $journalApprovals->merge($paymentApprovals)
            ->sortByDesc('created_at')
            ->values();

        return view('approvals.queue', compact('allApprovals'));
    }

    /**
     * Get pending approvals count for dashboard
     */
    public static function getPendingApprovalsCount($userId = null)
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();
        if (!$user) {
            return 0;
        }

        $companyId = $user->company_id;
        $count = 0;

        // Count journal entry approvals
        $journalSettings = JournalEntryApprovalSetting::where('company_id', $companyId)->first();
        if ($journalSettings) {
            $journalCount = JournalEntryApproval::whereHas('journal', function($query) use ($companyId) {
                $query->whereHas('user', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->where('status', 'pending')
            ->get()
            ->filter(function($approval) use ($journalSettings, $user) {
                return $journalSettings->canUserApproveAtLevel($user, $approval->approval_level);
            })
            ->count();
            
            $count += $journalCount;
        }

        // Count payment voucher approvals
        $paymentSettings = PaymentVoucherApprovalSetting::where('company_id', $companyId)->first();
        if ($paymentSettings) {
            $paymentCount = PaymentVoucherApproval::whereHas('payment', function($query) use ($companyId) {
                $query->whereHas('user', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->where('status', 'pending')
            ->get()
            ->filter(function($approval) use ($paymentSettings, $user) {
                return $paymentSettings->canUserApproveAtLevel($user, $approval->approval_level);
            })
            ->count();
            
            $count += $paymentCount;
        }

        return $count;
    }

    /**
     * Bulk approve items
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'approvals' => 'required|array|min:1',
            'approvals.*' => 'required|string',
        ]);

        $user = Auth::user();
        $approved = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->approvals as $approvalKey) {
                [$type, $approvalId] = explode('_', $approvalKey, 2);
                
                try {
                    if ($type === 'journal') {
                        $approval = JournalEntryApproval::findOrFail($approvalId);
                        $journal = $approval->journal;
                        
                        $settings = JournalEntryApprovalSetting::where('company_id', $user->company_id)->first();
                        if (!$settings || !$settings->canUserApproveAtLevel($user, $approval->approval_level)) {
                            $errors[] = "Journal {$journal->reference}: Permission denied";
                            continue;
                        }

                        $approval->update([
                            'status' => 'approved',
                            'approver_id' => $user->id,
                            'approved_at' => now(),
                        ]);

                        if ($journal->isFullyApproved()) {
                            $journal->update([
                                'approved' => true,
                                'approved_by' => $user->id,
                                'approved_at' => now(),
                            ]);
                            $journal->createGlTransactions();
                        }
                        
                        $approved++;
                    } elseif ($type === 'payment') {
                        $approval = PaymentVoucherApproval::findOrFail($approvalId);
                        $payment = $approval->payment;
                        
                        $settings = PaymentVoucherApprovalSetting::where('company_id', $user->company_id)->first();
                        if (!$settings || !$settings->canUserApproveAtLevel($user, $approval->approval_level)) {
                            $errors[] = "Payment {$payment->reference}: Permission denied";
                            continue;
                        }

                        $approval->update([
                            'status' => 'approved',
                            'approver_id' => $user->id,
                            'approved_at' => now(),
                        ]);

                        if ($payment->isFullyApproved()) {
                            $payment->update([
                                'approved' => true,
                                'approved_by' => $user->id,
                                'approved_at' => now(),
                            ]);
                            $payment->createGlTransactions();
                        }
                        
                        $approved++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error processing {$approvalKey}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Successfully approved {$approved} item(s).";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->route('approvals.queue')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('approvals.queue')->with('error', 'Failed to approve items: ' . $e->getMessage());
        }
    }

    /**
     * Bulk reject items
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'approvals' => 'required|array|min:1',
            'approvals.*' => 'required|string',
            'notes' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $rejected = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->approvals as $approvalKey) {
                [$type, $approvalId] = explode('_', $approvalKey, 2);
                
                try {
                    if ($type === 'journal') {
                        $approval = JournalEntryApproval::findOrFail($approvalId);
                        $journal = $approval->journal;
                        
                        $settings = JournalEntryApprovalSetting::where('company_id', $user->company_id)->first();
                        if (!$settings || !$settings->canUserApproveAtLevel($user, $approval->approval_level)) {
                            $errors[] = "Journal {$journal->reference}: Permission denied";
                            continue;
                        }

                        $approval->update([
                            'status' => 'rejected',
                            'approver_id' => $user->id,
                            'notes' => $request->notes,
                            'rejected_at' => now(),
                        ]);
                        
                        $rejected++;
                    } elseif ($type === 'payment') {
                        $approval = PaymentVoucherApproval::findOrFail($approvalId);
                        $payment = $approval->payment;
                        
                        $settings = PaymentVoucherApprovalSetting::where('company_id', $user->company_id)->first();
                        if (!$settings || !$settings->canUserApproveAtLevel($user, $approval->approval_level)) {
                            $errors[] = "Payment {$payment->reference}: Permission denied";
                            continue;
                        }

                        $approval->update([
                            'status' => 'rejected',
                            'approver_id' => $user->id,
                            'comments' => $request->notes,
                            'approved_at' => now(), // PaymentVoucherApproval uses approved_at for rejections
                        ]);
                        
                        $rejected++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error processing {$approvalKey}: " . $e->getMessage();
                }
            }

            DB::commit();

            $message = "Successfully rejected {$rejected} item(s).";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            return redirect()->route('approvals.queue')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('approvals.queue')->with('error', 'Failed to reject items: ' . $e->getMessage());
        }
    }
}
