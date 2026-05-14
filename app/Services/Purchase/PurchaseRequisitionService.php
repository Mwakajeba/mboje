<?php

namespace App\Services\Purchase;

use App\Models\BudgetLine;
use App\Models\ChartAccount;
use App\Models\GlTransaction;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderItem;
use App\Models\Purchase\PurchaseQuotation;
use App\Models\Purchase\PurchaseQuotationItem;
use App\Models\Purchase\PurchaseRequisition;
use App\Models\Purchase\PurchaseRequisitionLine;
use App\Models\SystemSetting;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseRequisitionService
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {
    }

    /**
     * Create or update a draft requisition with lines.
     */
    public function saveDraft(array $data, ?PurchaseRequisition $requisition = null): PurchaseRequisition
    {
        return DB::transaction(function () use ($data, $requisition) {
            $linesData = $data['lines'] ?? [];
            unset($data['lines']);

            if ($requisition) {
                $requisition->fill($data);
                $requisition->save();
            } else {
                $requisition = PurchaseRequisition::create($data);
            }

            // Replace lines for simplicity (can be optimized later)
            $requisition->lines()->delete();
            foreach ($linesData as $line) {
                $this->addOrUpdateLine($requisition, $line);
            }

            $this->recalculateTotals($requisition);

            return $requisition->fresh(['lines']);
        });
    }

    protected function addOrUpdateLine(PurchaseRequisition $requisition, array $lineData): PurchaseRequisitionLine
    {
        $quantity = (float) ($lineData['quantity'] ?? 0);
        $unitPrice = (float) ($lineData['unit_price_estimate'] ?? 0);

        $lineData['line_total_estimate'] = $quantity * $unitPrice;
        $lineData['purchase_requisition_id'] = $requisition->id;

        return PurchaseRequisitionLine::create($lineData);
    }

    protected function recalculateTotals(PurchaseRequisition $requisition): void
    {
        $total = $requisition->lines()->sum('line_total_estimate');
        $requisition->update(['total_amount' => $total]);
    }

    /**
     * Submit requisition for approval (will trigger ApprovalService).
     * Includes comprehensive validation before submission.
     */
    public function submitForApproval(PurchaseRequisition $requisition, int $userId): PurchaseRequisition
    {
        DB::transaction(function () use ($requisition, $userId) {
            // Simple status guard
            if (!in_array($requisition->status, ['draft', 'rejected'])) {
                throw new \RuntimeException('Requisition must be in draft or rejected status to submit');
            }

            // Validate mandatory fields
            $this->validateMandatoryFields($requisition);

            // Run comprehensive budget & policy validation
            $budgetValidation = $this->validateBudgetAndPolicy($requisition);
            if (!$budgetValidation['valid']) {
                throw new \RuntimeException('Budget validation failed: ' . implode(', ', $budgetValidation['errors']));
            }

            // Identify purchase type (Capex/Opex) and store it
            $purchaseType = $this->identifyPurchaseType($requisition);
            // Note: If purchase_type column exists, we can store it here

            // Use generic approval engine (module-specific handling will be added there)
            $this->approvalService->submitForApproval($requisition, $userId);
        });

        return $requisition->fresh(['lines']);
    }

    /**
     * Validate mandatory fields before submission
     */
    protected function validateMandatoryFields(PurchaseRequisition $requisition): void
    {
        $errors = [];

        // Ensure requisition has lines
        if ($requisition->lines()->count() === 0) {
            $errors[] = 'Purchase Requisition must have at least one line item';
        }

        // Validate each line has required fields
        foreach ($requisition->lines as $line) {
            if (!$line->description && !$line->inventory_item_id && !$line->asset_id) {
                $errors[] = "Line item must have description or item code";
            }

            if (!$line->gl_account_id) {
                $errors[] = "Line item must have a GL account (cost center)";
            }

            if ($line->quantity <= 0) {
                $errors[] = "Line item must have quantity greater than zero";
            }
        }

        if (!$requisition->department_id) {
            $errors[] = 'Department (cost center) is required';
        }

        if (empty($errors)) {
            return;
        }

        throw new \RuntimeException('Validation failed: ' . implode(', ', $errors));
    }

    /**
     * Comprehensive budget & policy validation
     * Checks budget balance, spending limits, and procurement policies
     */
    public function validateBudgetAndPolicy(PurchaseRequisition $requisition): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        // Check if budget checking is enabled
        $budgetCheckEnabled = SystemSetting::getValue('budget_check_enabled', false);
        if (!$budgetCheckEnabled) {
            $results['warnings'][] = 'Budget checking is disabled in system settings';
            return $results;
        }

        // Get budget for the requisition
        if (!$requisition->budget_id) {
            // Try to find active budget for company/branch
            $budget = \App\Models\Budget::where('company_id', $requisition->company_id)
                ->where(function($q) use ($requisition) {
                    $q->where('branch_id', $requisition->branch_id)
                      ->orWhereNull('branch_id');
                })
                ->where('year', date('Y'))
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->first();

            if ($budget) {
                $requisition->budget_id = $budget->id;
                $requisition->save();
            }
        }

        if (!$requisition->budget_id) {
            $results['warnings'][] = 'No budget found for validation';
            return $results;
        }

        $budget = \App\Models\Budget::find($requisition->budget_id);
        if (!$budget) {
            $results['errors'][] = 'Budget not found';
            $results['valid'] = false;
            return $results;
        }

        // Get over-budget tolerance percentage
        $overBudgetPercentage = SystemSetting::getValue('budget_over_budget_percentage', 10);

        // Check each line item
        foreach ($requisition->lines as $line) {
            if (!$line->gl_account_id) {
                $results['errors'][] = "Line item '{$line->description}' has no GL account";
                $results['valid'] = false;
                continue;
            }

            $budgetLine = BudgetLine::where('account_id', $line->gl_account_id)
                ->where('budget_id', $requisition->budget_id)
                ->first();

            if (!$budgetLine) {
                $results['warnings'][] = "No budget line found for GL account on line '{$line->description}'";
                continue;
            }

            // Calculate requested amount in budget currency
            $requestedAmount = (float) $line->line_total_estimate;
            if ($requisition->exchange_rate && $requisition->exchange_rate != 1) {
                $requestedAmount = $requestedAmount * (float) $requisition->exchange_rate;
            }

            // Calculate used amount from GL transactions
            $usedAmount = (float) GlTransaction::where('chart_account_id', $line->gl_account_id)
                ->where('branch_id', $requisition->branch_id)
                ->where('date', '>=', $budget->year . '-01-01')
                ->where('date', '<=', $budget->year . '-12-31')
                ->where('nature', 'debit')
                ->where('transaction_type', '!=', 'purchase_requisition') // Exclude requisitions
                ->sum('amount');

            // Calculate remaining budget
            $remainingBudget = (float) $budgetLine->amount - $usedAmount;

            // Calculate allowed amount (budget + tolerance)
            $allowedAmount = (float) $budgetLine->amount * (1 + ($overBudgetPercentage / 100));
            $totalAfterTransaction = $usedAmount + $requestedAmount;

            $lineResult = [
                'line_id' => $line->id,
                'description' => $line->description,
                'gl_account' => $line->glAccount->account_name ?? 'N/A',
                'budgeted' => (float) $budgetLine->amount,
                'used' => $usedAmount,
                'remaining' => $remainingBudget,
                'requested' => $requestedAmount,
                'total_after' => $totalAfterTransaction,
                'allowed' => $allowedAmount,
            ];

            if ($totalAfterTransaction > $allowedAmount) {
                $exceededBy = $totalAfterTransaction - $allowedAmount;
                $lineResult['status'] = 'over_budget';
                $lineResult['message'] = "Exceeds budget limit by " . number_format($exceededBy, 2);
                $results['errors'][] = "Line '{$line->description}': Requested amount exceeds budget limit by " . number_format($exceededBy, 2);
                $results['valid'] = false;
            } elseif ($totalAfterTransaction > (float) $budgetLine->amount) {
                $lineResult['status'] = 'over_budget_warning';
                $lineResult['message'] = "Exceeds allocated budget but within tolerance";
                $results['warnings'][] = "Line '{$line->description}': Exceeds allocated budget but within tolerance";
            } else {
                $lineResult['status'] = 'ok';
                $lineResult['message'] = 'Within budget';
            }

            $results['details'][] = $lineResult;
        }

        return $results;
    }

    /**
     * Lightweight budget check: maps each line to a BudgetLine by GL account.
     * This can be extended later with cost centers / projects.
     */
    public function runBudgetCheck(PurchaseRequisition $requisition): array
    {
        $validation = $this->validateBudgetAndPolicy($requisition);
        return $validation['details'] ?? [];
    }

    /**
     * Identify purchase type (Capex/Opex) based on GL account classification
     */
    public function identifyPurchaseType(PurchaseRequisition $requisition): string
    {
        // Check GL accounts of all lines to determine if it's Capex or Opex
        $hasAssetAccount = false;
        $hasExpenseAccount = false;

        foreach ($requisition->lines as $line) {
            if (!$line->gl_account_id) {
                continue;
            }

            // Load the account with its class group (which carries the name like Assets, Expenses, etc.)
            $account = ChartAccount::with('accountClassGroup')->find($line->gl_account_id);
            if (!$account || !$account->accountClassGroup) {
                continue;
            }

            $groupName = strtolower($account->accountClassGroup->name ?? '');
            
            // Check if it's an asset account (Capex)
            if (str_contains($groupName, 'asset') || str_contains($groupName, 'fixed') || str_contains($groupName, 'non-current')) {
                $hasAssetAccount = true;
            }
            
            // Check if it's an expense account (Opex)
            if (str_contains($groupName, 'expense') || str_contains($groupName, 'cost') || str_contains($groupName, 'operating')) {
                $hasExpenseAccount = true;
            }
        }

        // If any line uses asset account, it's Capex
        if ($hasAssetAccount) {
            return 'capex';
        }

        // Default to Opex if expense accounts found or mixed
        return 'opex';
    }

    /**
     * Create a Purchase Order from an approved requisition.
     * Includes supplier validation, tax configuration, and auto-fill from PR.
     */
    public function createPurchaseOrderFromRequisition(PurchaseRequisition $requisition, int $supplierId, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($requisition, $supplierId, $userId) {
            // Guard: must be approved
            if ($requisition->status !== 'approved') {
                throw new \RuntimeException('Requisition must be approved before creating a PO');
            }

            // Validate supplier
            $supplier = \App\Models\Supplier::findOrFail($supplierId);
            $this->validateSupplier($supplier);

            // Try to find a related quotation for this requisition & supplier (preferred RFQ/priced quote)
            $quotation = PurchaseQuotation::with('quotationItems')
                ->where('purchase_requisition_id', $requisition->id)
                ->where('supplier_id', $supplierId)
                ->orderByDesc('created_at')
                ->first();

            $orderDate = now()->toDateString();

            // Auto-fill tax configurations from requisition lines or supplier defaults
            $taxConfig = $this->determineTaxConfiguration($requisition, $supplier);

            $po = PurchaseOrder::create([
                'order_number' => PurchaseOrder::generateOrderNumber(),
                'purchase_requisition_id' => $requisition->id,
                'quotation_id' => $quotation?->id,
                'supplier_id' => $supplierId,
                'order_date' => $orderDate,
                'expected_delivery_date' => $requisition->required_date ?? $orderDate,
                'status' => 'draft',
                'payment_terms' => $supplier->payment_terms ?? 'immediate',
                'payment_days' => $supplier->payment_days ?? 0,
                'subtotal' => $requisition->total_amount,
                'vat_type' => $taxConfig['vat_type'],
                'vat_rate' => $taxConfig['vat_rate'],
                'vat_amount' => $taxConfig['vat_amount'],
                'tax_amount' => 0,
                'discount_type' => 'percentage',
                'discount_rate' => 0,
                'discount_amount' => 0,
                'total_amount' => $requisition->total_amount + $taxConfig['vat_amount'],
                'notes' => $requisition->justification,
                'terms_conditions' => null,
                'branch_id' => $requisition->branch_id,
                'company_id' => $requisition->company_id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Index quotation items by inventory item for quick lookup
            $quotationItemsByItem = collect();
            if ($quotation) {
                $quotationItemsByItem = $quotation->quotationItems->keyBy('item_id');
            }

            // Auto-fill items from requisition lines, using quotation prices where available
            foreach ($requisition->lines as $line) {
                if ($line->quantity <= 0) {
                    continue;
                }

                $qty = (float) $line->quantity;

                // Start with PR estimated unit price
                $unit = (float) $line->unit_price_estimate;

                // If we have a quotation and this line is inventory, try to use quoted price
                if ($quotation && $line->inventory_item_id && $quotationItemsByItem->has($line->inventory_item_id)) {
                    $quoted = $quotationItemsByItem->get($line->inventory_item_id);
                    if ($quoted && $quoted->unit_price > 0) {
                        $unit = (float) $quoted->unit_price;
                    }
                }
                $gross = $qty * $unit;

                // Determine VAT for this line
                $lineVatType = $line->taxGroup?->vat_type ?? $taxConfig['vat_type'];
                $lineVatRate = $line->taxGroup?->vat_rate ?? $taxConfig['vat_rate'];

                // Override VAT from quotation item if present
                if ($quotation && $line->inventory_item_id && $quotationItemsByItem->has($line->inventory_item_id)) {
                    $quoted = $quotationItemsByItem->get($line->inventory_item_id);
                    if ($quoted) {
                        $lineVatType = $quoted->vat_type ?? $lineVatType;
                        $lineVatRate = $quoted->vat_rate ?? $lineVatRate;
                    }
                }
                $lineVatAmount = 0;
                
                if ($lineVatType === 'exclusive' && $lineVatRate > 0) {
                    $lineVatAmount = $gross * ($lineVatRate / 100);
                } elseif ($lineVatType === 'inclusive' && $lineVatRate > 0) {
                    $lineVatAmount = $gross - ($gross / (1 + ($lineVatRate / 100)));
                }

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $line->inventory_item_id,
                    'asset_id' => $line->asset_id,
                    'description' => $line->description,
                    'quantity' => $qty,
                    'cost_price' => $unit,
                    'tax_calculation_type' => 'percentage',
                    'vat_type' => $lineVatType,
                    'vat_rate' => $lineVatRate,
                    'vat_amount' => $lineVatAmount,
                    'tax_amount' => 0,
                    'subtotal' => $gross,
                    'total_amount' => $gross + $lineVatAmount,
                ]);

                // Update ordered quantity on requisition line
                $line->ordered_quantity = ($line->ordered_quantity ?? 0) + $line->quantity;
                $line->line_status = 'fully_ordered';
                $line->save();
            }

            // Recalculate PO totals
            $po->refresh();
            $this->recalculatePOTotals($po);

            $requisition->update([
                'purchase_order_id' => $po->id,
                'status' => 'po_created',
            ]);

            return $po->fresh('items');
        });
    }

    /**
     * Validate supplier before creating PO
     */
    protected function validateSupplier(\App\Models\Supplier $supplier): void
    {
        // Check supplier credit terms
        if ($supplier->credit_limit && $supplier->credit_limit > 0) {
            // Could check current outstanding balance here
            // For now, just validate supplier is active
        }

        // Check tax registration (TIN, VRN) if required by system settings
        $requireTaxRegistration = SystemSetting::getValue('require_supplier_tax_registration', false);
        if ($requireTaxRegistration) {
            if (empty($supplier->tin) && empty($supplier->vrn)) {
                throw new \RuntimeException('Supplier must have TIN or VRN registered');
            }
        }
    }

    /**
     * Determine tax configuration for PO from requisition lines or supplier defaults
     */
    protected function determineTaxConfiguration(PurchaseRequisition $requisition, \App\Models\Supplier $supplier): array
    {
        // Try to get tax config from requisition lines
        $vatTypes = [];
        $vatRates = [];
        
        foreach ($requisition->lines as $line) {
            if ($line->taxGroup) {
                $vatTypes[] = $line->taxGroup->vat_type ?? 'no_vat';
                $vatRates[] = (float) ($line->taxGroup->vat_rate ?? 0);
            }
        }

        // Use most common VAT type and rate
        $vatType = !empty($vatTypes) ? collect($vatTypes)->mode()->first() : 'no_vat';
        $vatRate = !empty($vatRates) ? collect($vatRates)->mode()->first() : 0;

        // Calculate VAT amount based on type
        $vatAmount = 0;
        if ($vatType === 'exclusive' && $vatRate > 0) {
            $vatAmount = $requisition->total_amount * ($vatRate / 100);
        } elseif ($vatType === 'inclusive' && $vatRate > 0) {
            $vatAmount = $requisition->total_amount - ($requisition->total_amount / (1 + ($vatRate / 100)));
        }

        return [
            'vat_type' => $vatType,
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
        ];
    }

    /**
     * Recalculate PO totals from items
     */
    protected function recalculatePOTotals(PurchaseOrder $po): void
    {
        $po->load('items');
        
        $subtotal = $po->items->sum('subtotal');
        $vatAmount = $po->items->sum('vat_amount');
        $totalAmount = $subtotal + $vatAmount;

        $po->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'total_amount' => $totalAmount,
        ]);
    }
}


