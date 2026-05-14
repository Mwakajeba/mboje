<?php

namespace App\Services\Purchase;

use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\ApprovalHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementReportingService
{
    /**
     * Get PR to PO cycle tracking metrics
     */
    public function getPrToPoCycleMetrics($companyId, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $requisitions = PurchaseRequisition::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $metrics = [
            'total_requisitions' => $requisitions->count(),
            'approved_requisitions' => $requisitions->where('status', 'approved')->count(),
            'po_created' => $requisitions->where('status', 'po_created')->count(),
            'average_approval_time_hours' => $this->calculateAverageApprovalTime($requisitions),
            'average_pr_to_po_time_hours' => $this->calculateAveragePrToPoTime($requisitions),
            'pending_approvals' => $requisitions->whereIn('status', ['submitted', 'pending_approval', 'in_review'])->count(),
            'rejected' => $requisitions->where('status', 'rejected')->count(),
        ];

        return $metrics;
    }

    /**
     * Get pending approvals summary
     */
    public function getPendingApprovalsSummary($companyId): array
    {
        $requisitions = PurchaseRequisition::where('company_id', $companyId)
            ->whereIn('status', ['submitted', 'pending_approval', 'in_review'])
            ->with(['requestor', 'department'])
            ->get();

        $orders = PurchaseOrder::where('company_id', $companyId)
            ->where('status', 'pending_approval')
            ->with(['supplier', 'requisition'])
            ->get();

        return [
            'requisitions' => $requisitions->map(function($pr) {
                return [
                    'id' => $pr->id,
                    'pr_no' => $pr->pr_no,
                    'requestor' => $pr->requestor->name ?? 'N/A',
                    'department' => $pr->department->name ?? 'N/A',
                    'total_amount' => $pr->total_amount,
                    'status' => $pr->status,
                    'current_level' => $pr->current_approval_level,
                    'submitted_at' => $pr->submitted_at?->format('Y-m-d H:i:s'),
                    'pending_hours' => $pr->submitted_at ? Carbon::parse($pr->submitted_at)->diffInHours(now()) : 0,
                ];
            }),
            'orders' => $orders->map(function($po) {
                return [
                    'id' => $po->id,
                    'order_number' => $po->order_number,
                    'supplier' => $po->supplier->name ?? 'N/A',
                    'total_amount' => $po->total_amount,
                    'status' => $po->status,
                    'created_at' => $po->created_at->format('Y-m-d H:i:s'),
                    'pending_hours' => $po->created_at->diffInHours(now()),
                ];
            }),
            'total_pending' => $requisitions->count() + $orders->count(),
        ];
    }

    /**
     * Get budget utilization and variance
     */
    public function getBudgetUtilization($companyId, $budgetId = null): array
    {
        $budget = $budgetId 
            ? \App\Models\Budget::find($budgetId)
            : \App\Models\Budget::where('company_id', $companyId)
                ->where('year', date('Y'))
                ->where('status', 'active')
                ->first();

        if (!$budget) {
            return ['error' => 'No active budget found'];
        }

        $budgetLines = \App\Models\BudgetLine::where('budget_id', $budget->id)->get();

        $utilization = [];
        foreach ($budgetLines as $line) {
            $used = (float) \App\Models\GlTransaction::where('chart_account_id', $line->account_id)
                ->where('date', '>=', $budget->year . '-01-01')
                ->where('date', '<=', $budget->year . '-12-31')
                ->where('nature', 'debit')
                ->sum('amount');

            $committed = (float) PurchaseRequisition::where('budget_id', $budget->id)
                ->whereIn('status', ['submitted', 'pending_approval', 'in_review', 'approved'])
                ->whereHas('lines', function($q) use ($line) {
                    $q->where('gl_account_id', $line->account_id);
                })
                ->get()
                ->sum(function($pr) use ($line) {
                    return $pr->lines->where('gl_account_id', $line->account_id)->sum('line_total_estimate');
                });

            $utilization[] = [
                'account_id' => $line->account_id,
                'account_name' => $line->account->account_name ?? 'N/A',
                'budgeted' => (float) $line->amount,
                'used' => $used,
                'committed' => $committed,
                'available' => (float) $line->amount - $used - $committed,
                'utilization_percent' => $line->amount > 0 ? (($used + $committed) / $line->amount) * 100 : 0,
                'variance' => (float) $line->amount - $used - $committed,
            ];
        }

        return [
            'budget' => [
                'id' => $budget->id,
                'year' => $budget->year,
                'name' => $budget->name ?? 'Budget ' . $budget->year,
            ],
            'utilization' => $utilization,
            'summary' => [
                'total_budgeted' => $budgetLines->sum('amount'),
                'total_used' => collect($utilization)->sum('used'),
                'total_committed' => collect($utilization)->sum('committed'),
                'total_available' => collect($utilization)->sum('available'),
                'overall_utilization_percent' => $budgetLines->sum('amount') > 0 
                    ? ((collect($utilization)->sum('used') + collect($utilization)->sum('committed')) / $budgetLines->sum('amount')) * 100 
                    : 0,
            ],
        ];
    }

    /**
     * Get procurement KPIs
     */
    public function getProcurementKPIs($companyId, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $requisitions = PurchaseRequisition::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $orders = PurchaseOrder::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $invoices = PurchaseInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->get();

        // Calculate cycle times
        $prToApprovalTimes = [];
        $prToPoTimes = [];
        $poToInvoiceTimes = [];

        foreach ($requisitions as $pr) {
            if ($pr->submitted_at && $pr->approved_at) {
                $prToApprovalTimes[] = Carbon::parse($pr->submitted_at)->diffInHours(Carbon::parse($pr->approved_at));
            }
            if ($pr->approved_at && $pr->purchaseOrder && $pr->purchaseOrder->created_at) {
                $prToPoTimes[] = Carbon::parse($pr->approved_at)->diffInHours(Carbon::parse($pr->purchaseOrder->created_at));
            }
        }

        foreach ($orders as $po) {
            $relatedInvoice = $invoices->first(function($inv) use ($po) {
                return $inv->supplier_id == $po->supplier_id && 
                       abs(Carbon::parse($inv->invoice_date)->diffInDays(Carbon::parse($po->order_date))) <= 30;
            });
            if ($relatedInvoice) {
                $poToInvoiceTimes[] = Carbon::parse($po->order_date)->diffInHours(Carbon::parse($relatedInvoice->invoice_date));
            }
        }

        return [
            'cycle_times' => [
                'pr_to_approval_avg_hours' => !empty($prToApprovalTimes) ? round(array_sum($prToApprovalTimes) / count($prToApprovalTimes), 2) : 0,
                'pr_to_po_avg_hours' => !empty($prToPoTimes) ? round(array_sum($prToPoTimes) / count($prToPoTimes), 2) : 0,
                'po_to_invoice_avg_hours' => !empty($poToInvoiceTimes) ? round(array_sum($poToInvoiceTimes) / count($poToInvoiceTimes), 2) : 0,
            ],
            'volumes' => [
                'total_requisitions' => $requisitions->count(),
                'total_orders' => $orders->count(),
                'total_invoices' => $invoices->count(),
            ],
            'delays' => [
                'requisitions_pending_over_48h' => $requisitions->filter(function($pr) {
                    return $pr->submitted_at && Carbon::parse($pr->submitted_at)->diffInHours(now()) > 48;
                })->count(),
                'orders_pending_over_48h' => $orders->filter(function($po) {
                    return $po->status === 'pending_approval' && $po->created_at->diffInHours(now()) > 48;
                })->count(),
            ],
            'bottlenecks' => $this->identifyBottlenecks($companyId),
        ];
    }

    /**
     * Identify bottlenecks in approval process
     */
    protected function identifyBottlenecks($companyId): array
    {
        $bottlenecks = [];

        // Check approval levels with most pending items
        // Use direct query on purchase_requisitions table to avoid polymorphic relationship issues
        // with BankReconciliation which doesn't have direct company_id column
        $pendingByLevel = ApprovalHistory::where('approvable_type', PurchaseRequisition::class)
            ->whereExists(function($query) use ($companyId) {
                $query->select(DB::raw(1))
                    ->from('purchase_requisitions')
                    ->whereColumn('purchase_requisitions.id', 'approval_histories.approvable_id')
                    ->where('purchase_requisitions.company_id', $companyId)
                    ->whereIn('purchase_requisitions.status', ['submitted', 'pending_approval', 'in_review']);
            })
            ->select('approval_level_id', DB::raw('count(*) as pending_count'))
            ->groupBy('approval_level_id')
            ->orderByDesc('pending_count')
            ->limit(5)
            ->get();

        foreach ($pendingByLevel as $level) {
            $approvalLevel = \App\Models\ApprovalLevel::find($level->approval_level_id);
            if ($approvalLevel) {
                $bottlenecks[] = [
                    'level' => $approvalLevel->level_name,
                    'pending_count' => $level->pending_count,
                ];
            }
        }

        return $bottlenecks;
    }

    /**
     * Calculate average approval time for requisitions
     */
    protected function calculateAverageApprovalTime($requisitions): float
    {
        $times = [];
        foreach ($requisitions as $pr) {
            if ($pr->submitted_at && $pr->approved_at) {
                $times[] = Carbon::parse($pr->submitted_at)->diffInHours(Carbon::parse($pr->approved_at));
            }
        }
        return !empty($times) ? round(array_sum($times) / count($times), 2) : 0;
    }

    /**
     * Calculate average PR to PO time
     */
    protected function calculateAveragePrToPoTime($requisitions): float
    {
        $times = [];
        foreach ($requisitions as $pr) {
            if ($pr->approved_at && $pr->purchaseOrder && $pr->purchaseOrder->created_at) {
                $times[] = Carbon::parse($pr->approved_at)->diffInHours(Carbon::parse($pr->purchaseOrder->created_at));
            }
        }
        return !empty($times) ? round(array_sum($times) / count($times), 2) : 0;
    }

    /**
     * Get full audit trail for a document
     */
    public function getAuditTrail($documentType, $documentId): array
    {
        $modelClass = match($documentType) {
            'purchase_requisition' => PurchaseRequisition::class,
            'purchase_order' => PurchaseOrder::class,
            'purchase_invoice' => PurchaseInvoice::class,
            default => null,
        };

        if (!$modelClass) {
            return ['error' => 'Invalid document type'];
        }

        $document = $modelClass::find($documentId);
        if (!$document) {
            return ['error' => 'Document not found'];
        }

        // Get approval history
        $approvalHistory = ApprovalHistory::where('approvable_type', $modelClass)
            ->where('approvable_id', $documentId)
            ->with(['approvalLevel', 'approver'])
            ->orderBy('created_at')
            ->get();

        // Get activity logs if model uses LogsActivity trait
        $activityLogs = [];
        if (method_exists($document, 'activities')) {
            $activityLogs = $document->activities()
                ->with('user')
                ->orderBy('created_at')
                ->get();
        }

        return [
            'document' => [
                'type' => $documentType,
                'id' => $documentId,
                'identifier' => $document->pr_no ?? $document->order_number ?? $document->invoice_number ?? 'N/A',
            ],
            'approval_history' => $approvalHistory->map(function($entry) {
                return [
                    'action' => $entry->action,
                    'level' => $entry->approvalLevel->level_name ?? 'N/A',
                    'approver' => $entry->approver->name ?? 'System',
                    'comments' => $entry->comments,
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'activity_logs' => $activityLogs->map(function($log) {
                return [
                    'action' => $log->action ?? 'N/A',
                    'user' => $log->user->name ?? 'System',
                    'description' => $log->description ?? 'N/A',
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ];
    }
}

