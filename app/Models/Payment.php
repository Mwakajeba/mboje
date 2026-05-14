<?php

namespace App\Models;

use App\Helpers\AmountInWords;
use App\Helpers\HashIdHelper;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'reference',
        'reference_type',
        'reference_number',
        'amount',
        'currency',
        'exchange_rate',
        'amount_fcy',
        'amount_lcy',
        'fx_gain_loss',
        'fx_rate_used',
        'payment_currency',
        'wht_treatment',
        'wht_rate',
        'wht_amount',
        'net_payable',
        'total_cost',
        'vat_mode',
        'vat_amount',
        'base_amount',
        'date',
        'description',
        'attachment',
        'bank_account_id',
        'payee_type',
        'payee_id',
        'payee_name',
        'customer_id',
        'supplier_id',
        'employee_id',
        'branch_id',
        'user_id',
        'cash_deposit_id',
        'payment_method',
        'cheque_id',
        'approved',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function cashDeposit()
    {
        return $this->belongsTo(\App\Models\CashDeposit::class);
    }

    public function cheque()
    {
        return $this->belongsTo(Cheque::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the payee based on payee_type and payee_id
     */
    public function payee()
    {
        if ($this->payee_type === 'customer') {
            return $this->belongsTo(Customer::class, 'payee_id');
        } elseif ($this->payee_type === 'supplier') {
            return $this->belongsTo(Supplier::class, 'payee_id');
        }
        return null;
    }

    /**
     * Get the payee display name
     */
    public function getPayeeDisplayNameAttribute()
    {
        if ($this->payee_type === 'customer' && $this->customer) {
            return $this->customer->name;
        } elseif ($this->payee_type === 'supplier' && $this->supplier) {
            return $this->supplier->name;
        } elseif ($this->payee_type === 'other' || $this->payee_type === 'employee') {
            return $this->payee_name ?? 'N/A';
        }
        return 'N/A';
    }



    public function paymentItems()
    {
        return $this->hasMany(PaymentItem::class);
    }

    public function glTransactions()
    {
        return $this->hasMany(GlTransaction::class, 'transaction_id')
            ->where('transaction_type', 'payment');
    }

    /**
     * Helper attribute: whether this payment has been posted to GL
     */
    public function getGlPostedAttribute(): bool
    {
        return $this->glTransactions()->exists();
    }

    public function approvals()
    {
        return $this->hasMany(PaymentVoucherApproval::class);
    }

    public function pendingApprovals()
    {
        return $this->hasMany(PaymentVoucherApproval::class)->pending();
    }

    public function currentApproval()
    {
        return $this->hasMany(PaymentVoucherApproval::class)->pending()->orderBy('approval_level')->first();
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('approved', false);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeByReference($query, $reference)
    {
        return $query->where('reference', 'like', "%{$reference}%");
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('M d, Y') : 'N/A';
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->approved) {
            return '<span class="badge bg-success">Approved</span>';
        }
        return '<span class="badge bg-warning">Pending</span>';
    }

    public function getTotalAmountAttribute()
    {
        return $this->paymentItems->sum('amount');
    }

    public function getAttachmentNameAttribute()
    {
        if (!$this->attachment) {
            return null;
        }
        return basename($this->attachment);
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->amount);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model instance for the given hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is hash_id, decode the hash ID
        if ($field === 'hash_id' || $field === null) {
            $id = HashIdHelper::decode($value);
            if ($id !== null) {
                return $this->findOrFail($id);
            }
        }
        
        // If not a hash ID, try as regular ID
        return $this->findOrFail($value);
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Get the hash ID for routing.
     */
    public function getRouteKey()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Check if payment requires approval.
     */
    public function requiresApproval()
    {
        // Only manual payment vouchers require approval
        if ($this->reference_type !== 'manual') {
            return false;
        }

        $settings = PaymentVoucherApprovalSetting::where('company_id', $this->user->company_id)->first();
        
        if (!$settings) {
            return false; // No approval settings configured
        }

        return $settings->getRequiredApprovalLevel($this->amount) > 0;
    }

    /**
     * Get the required approval level for this payment.
     */
    public function getRequiredApprovalLevel()
    {
        // Only manual payment vouchers require approval
        if ($this->reference_type !== 'manual') {
            return 0; // No approval required
        }

        $settings = PaymentVoucherApprovalSetting::where('company_id', $this->user->company_id)->first();
        
        if (!$settings) {
            return 0; // No approval required
        }

        return $settings->getRequiredApprovalLevel($this->amount);
    }

    /**
     * Initialize approval workflow for this payment.
     */
    public function initializeApprovalWorkflow()
    {
        // Only manual payment vouchers require approval
        if ($this->reference_type !== 'manual') {
            // Auto-approve non-manual payments
            $this->update([
                'approved' => true,
                'approved_by' => $this->user_id,
                'approved_at' => now(),
            ]);
            return;
        }

        $settings = PaymentVoucherApprovalSetting::where('company_id', $this->user->company_id)->first();
        
        if (!$settings) {
            // No approval settings configured - auto-approve all payments
            $this->update([
                'approved' => true,
                'approved_by' => $this->user_id,
                'approved_at' => now(),
            ]);
            
            // Create GL transactions for auto-approved payments
            $this->createGlTransactions();
            return;
        }

        $requiredLevel = $settings->getRequiredApprovalLevel($this->amount);
        
        if ($requiredLevel === 0) {
            // Auto-approve
            $this->update([
                'approved' => true,
                'approved_by' => $this->user_id,
                'approved_at' => now(),
            ]);
            
            // Create GL transactions for auto-approved payments
            $this->createGlTransactions();
            return;
        }

        // Create approval records for each level
        for ($level = 1; $level <= $requiredLevel; $level++) {
            $approvalType = $settings->{"level{$level}_approval_type"};
            $approvers = $settings->{"level{$level}_approvers"} ?? [];

            if ($approvalType === 'role') {
                foreach ($approvers as $roleName) {
                    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($role) {
                        PaymentVoucherApproval::create([
                            'payment_id' => $this->id,
                            'approval_level' => $level,
                            'approver_type' => 'role',
                            'approver_name' => $role->name,
                            'status' => 'pending',
                        ]);
                    }
                }
            } elseif ($approvalType === 'user') {
                foreach ($approvers as $userId) {
                    // Ensure userId is an integer
                    $userId = (int) $userId;
                    $user = User::find($userId);
                    if ($user) {
                        PaymentVoucherApproval::create([
                            'payment_id' => $this->id,
                            'approval_level' => $level,
                            'approver_id' => $user->id,
                            'approver_type' => 'user',
                            'approver_name' => $user->name,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // Update payment status
        $this->update([
            'approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Check if payment is fully approved.
     */
    public function isFullyApproved()
    {
        $requiredLevel = $this->getRequiredApprovalLevel();
        
        if ($requiredLevel === 0) {
            return $this->approved;
        }

        // Check if the required approval level is approved
        $requiredLevelApproved = $this->approvals()
            ->where('approval_level', $requiredLevel)
            ->where('status', 'approved')
            ->exists();
            
        return $requiredLevelApproved;
    }

    /**
     * Check if payment is rejected.
     */
    public function isRejected()
    {
        return $this->approvals()->rejected()->exists();
    }

    /**
     * Get approval status for display.
     */
    public function getApprovalStatusAttribute()
    {
        if ($this->isRejected()) {
            return 'rejected';
        }

        // If payment is already approved (either auto-approved or manually approved), keep it approved
        if ($this->approved) {
            return 'approved';
        }

        // Only check approval requirements for unapproved payments
        if ($this->requiresApproval()) {
            return 'pending';
        }

        return 'approved'; // Auto-approved if no approval required
    }

    /**
     * Get approval status badge for display.
     */
    public function getApprovalStatusBadgeAttribute()
    {
        $status = $this->approval_status;
        
        switch ($status) {
            case 'approved':
                return '<span class="badge bg-success">Approved</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Rejected</span>';
            case 'pending':
                return '<span class="badge bg-warning">Pending Approval</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Get reference type badge for display.
     */
    public function getReferenceTypeBadgeAttribute()
    {
        if ($this->reference_type === 'manual') {
            return '<span class="badge bg-primary">Manual Payment Voucher</span>';
        } else {
            return '<span class="badge bg-secondary">' . ucfirst(str_replace(' ', ' ', $this->reference_type)) . '</span>';
        }
    }

    /**
     * Create GL transactions for this payment voucher.
     * Handles WHT for Exclusive, Inclusive, and Gross-Up treatments.
     * Integrates with VAT handling per TRA regulations.
     */
    public function createGlTransactions()
    {
        // Skip GL creation for petty cash payments - they are handled by PettyCashService
        if ($this->reference_type === 'petty_cash') {
            return;
        }

        // Skip GL for accrual schedule amortisation - GL is already created by the journal when posting
        if ($this->reference_type === 'accrual_schedule_amortisation') {
            return;
        }
        
        // Check if GL transactions already exist to avoid duplicates
        if ($this->glTransactions()->exists()) {
            return;
        }

        // Check if period is locked
        $companyId = $this->company_id ?? ($this->branch->company_id ?? null);
        if ($companyId) {
            $periodLockService = app(\App\Services\PeriodClosing\PeriodLockService::class);
            try {
                $periodLockService->validateTransactionDate($this->date, $companyId, 'payment');
            } catch (\Exception $e) {
                \Log::warning('Payment - Cannot post: Period is locked', [
                    'payment_id' => $this->id,
                    'payment_reference' => $this->reference,
                    'payment_date' => $this->date,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        // Check if this payment is in a completed reconciliation period - prevent posting
        if ($this->bank_account_id) {
            $isInCompletedReconciliation = \App\Models\BankReconciliation::where('bank_account_id', $this->bank_account_id)
                ->where('status', 'completed')
                ->where('start_date', '<=', $this->date)
                ->where('end_date', '>=', $this->date)
                ->exists();
            
            if ($isInCompletedReconciliation) {
                \Log::warning('Payment::createGlTransactions - Cannot post: Payment is in a completed reconciliation period', [
                    'payment_id' => $this->id,
                    'payment_reference' => $this->reference,
                    'payment_date' => $this->date,
                    'bank_account_id' => $this->bank_account_id
                ]);
                throw new \Exception("Cannot post: Payment is in a completed reconciliation period for date {$this->date}.");
            }
        }

        $this->loadMissing(['bankAccount', 'paymentItems']);

        if (!$this->paymentItems->count()) {
            return;
        }

        // Handle cash deposit payments differently - use Cash Deposit account
        if ($this->payment_method === 'cash_deposit') {
            // For cash deposit payments, use Cash Deposit account (ID 28) like sales invoices
            $cashDepositAccountId = 28; // Cash Deposits account
            
            // Check if cash deposit account is in a completed reconciliation period
            $isInCompletedReconciliation = \App\Services\BankReconciliationService::isChartAccountInCompletedReconciliation(
                $cashDepositAccountId,
                $this->date
            );
            
            if ($isInCompletedReconciliation) {
                \Log::warning('Payment::createGlTransactions - Cannot post: Cash deposit account is in a completed reconciliation period', [
                    'payment_id' => $this->id,
                    'payment_reference' => $this->reference,
                    'chart_account_id' => $cashDepositAccountId,
                    'payment_date' => $this->date
                ]);
                throw new \Exception("Cannot post cash deposit payment: Account is in a completed reconciliation period for date {$this->date}.");
            }
            
            // For cash deposit, we'll debit Cash Deposit account instead of crediting it
            // This will be handled in the credit section below
            $bankAccount = null;
            $cashAccountId = null;
        } else {
            // For cash payments, get cash account if no bank account
            $bankAccount = $this->bankAccount;
            $cashAccountId = null;
            
            if (!$bankAccount) {
                // Cash payment - get default cash account
                $cashAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_cash_account')->value('value') ?? 1);
                if (!$cashAccountId) {
                    // Fallback: try to find Cash on Hand account
                    $cashAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Cash%Hand%')
                        ->orWhere('account_name', 'like', '%Cash on Hand%')
                        ->first();
                    $cashAccountId = $cashAccount ? $cashAccount->id : 0;
                }
                
                if (!$cashAccountId) {
                    \Log::warning('Payment::createGlTransactions - No bank account or cash account found', [
                        'payment_id' => $this->id,
                        'reference' => $this->reference
                    ]);
                    return;
                }
            }
        }
        $date = $this->date;
        $description = $this->description ?: "Payment voucher {$this->reference}";
        $branchId = $this->branch_id;
        $userId = $this->user_id;

        // Get WHT Payable account from system settings
        $whtPayableAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_withholding_tax_account')->value('value') ?? 37);
        if (!$whtPayableAccountId) {
            // Fallback: try to find WHT Payable account by name
            $whtAccount = \App\Models\ChartAccount::where('account_name', 'like', '%WHT%Payable%')
                ->orWhere('account_name', 'like', '%Withholding%Tax%Payable%')
                ->first();
            $whtPayableAccountId = $whtAccount ? $whtAccount->id : 0;
        }

        // Get VAT Input account from system settings
        $vatInputAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_vat_account')->value('value') ?? 36);
        if (!$vatInputAccountId) {
            // Fallback: try to find VAT Input account by name
            $vatAccount = \App\Models\ChartAccount::where('account_name', 'like', '%VAT%Account%')
                ->orWhere('account_name', 'like', '%VAT Account%')
                ->first();
            $vatInputAccountId = $vatAccount ? $vatAccount->id : 0;
        }

        // Get payment-level VAT mode and amounts
        $paymentVatMode = $this->vat_mode ?? 'EXCLUSIVE';
        $paymentVatAmount = $this->vat_amount ?? 0;
        $paymentBaseAmount = $this->base_amount ?? $this->amount;

        // Calculate totals for WHT
        $totalWHT = $this->wht_amount ?? 0;
        $totalNetPayable = $this->net_payable ?? $this->amount;
        $totalCost = $this->total_cost ?? $this->amount;

        // Determine credit account (bank or cash)
        // For cash deposit payments, we'll debit Cash Deposit account instead
        $creditAccountId = null;
        $cashDepositAccountId = null;
        
        if ($this->payment_method === 'cash_deposit') {
            $cashDepositAccountId = 28; // Cash Deposits account
        } else {
            $creditAccountId = $bankAccount ? $bankAccount->chart_account_id : $cashAccountId;
        }
        
        // Calculate totals from all items (accounting for item-level or payment-level WHT)
        $totalItemWHT = 0;
        $totalItemNetPayable = 0;
        $totalItemBase = 0;
        $hasItemLevelWHT = false;
        
        foreach ($this->paymentItems as $item) {
            $itemWHT = $item->wht_amount ?? 0;
            $itemBase = $item->base_amount ?? $item->amount;
            $itemNet = $item->net_payable ?? $itemBase;
            $itemTotalCost = $item->total_cost ?? $item->amount;
            
            // Check if item has its own WHT treatment
            if ($item->wht_treatment && $item->wht_treatment !== 'NONE' && $itemWHT > 0) {
                $hasItemLevelWHT = true;
                // Item has its own WHT - use item's calculated values
                $totalItemWHT += $itemWHT;
                $totalItemBase += $itemBase;
                
                if ($item->wht_treatment === 'GROSS_UP') {
                    // For Gross-Up: net payable = base, total cost = base + WHT
                    $totalItemNetPayable += $itemBase;
                } else {
                    // For Exclusive/Inclusive: net payable = base - WHT
                    $totalItemNetPayable += ($item->net_payable ?? ($itemBase - $itemWHT));
                }
            } else {
                // Item uses payment-level WHT
                $totalItemBase += $itemBase;
                
                // Apply payment-level WHT to this item
                if ($totalWHT > 0 && $this->wht_treatment && $this->wht_treatment !== 'NONE') {
                    // Calculate item's share of payment-level WHT proportionally
                    $itemWHTShare = ($itemBase / $this->amount) * $totalWHT;
                    $totalItemWHT += $itemWHTShare;
                    
                    if ($this->wht_treatment === 'GROSS_UP') {
                        $totalItemNetPayable += $itemBase; // For Gross-Up, net = base
                    } else {
                        $totalItemNetPayable += ($itemBase - $itemWHTShare); // For Exclusive/Inclusive, net = base - WHT
                    }
                } else {
                    // No WHT - net payable = base
                    $totalItemNetPayable += $itemBase;
                }
            }
        }

        // Determine final totals: prioritize payment-level WHT if explicitly set, otherwise use item-level
        // If payment-level WHT is explicitly set (not default), use it; otherwise use item-level if available
        if ($totalWHT > 0 && $this->wht_treatment && $this->wht_treatment !== 'NONE') {
            // Payment-level WHT is explicitly set - use it (this takes precedence)
            $finalNetPayable = $totalNetPayable;
            $finalWHT = $totalWHT;
        } else if ($hasItemLevelWHT) {
            // No payment-level WHT, but items have WHT - use aggregated item-level totals
            $finalNetPayable = $totalItemNetPayable;
            $finalWHT = $totalItemWHT;
        } else {
            // No WHT at all
            $finalNetPayable = $this->amount;
            $finalWHT = 0;
        }

        // Calculate total VAT amount (payment-level or sum of items)
        $totalVAT = $paymentVatAmount;
        $totalBase = $paymentBaseAmount;
        
        // If no payment-level VAT, sum from items
        if ($totalVAT == 0) {
            foreach ($this->paymentItems as $item) {
                $itemVatAmount = $item->vat_amount ?? 0;
                $itemBase = $item->base_amount ?? $item->amount;
                $totalVAT += $itemVatAmount;
                $totalBase += $itemBase;
            }
        }
        
        // CRITICAL FIX: When VAT is INCLUSIVE at payment level, ensure we have correct values
        // Recalculate from payment amount if VAT mode is INCLUSIVE
        if ($paymentVatMode === 'INCLUSIVE' && $this->amount > 0) {
            // If vat_amount is stored, use it; otherwise recalculate
            if ($totalVAT > 0 && $totalBase > 0) {
                // Verify: totalBase + totalVAT should equal amount
                $expectedTotal = $totalBase + $totalVAT;
                if (abs($expectedTotal - $this->amount) > 0.01) {
                    // Recalculate to ensure accuracy
                    $totalBase = $this->amount - $totalVAT;
                }
            } elseif ($totalVAT == 0 && $this->vat_amount > 0) {
                // Use stored vat_amount if totalVAT is 0
                $totalVAT = $this->vat_amount;
                $totalBase = $this->amount - $totalVAT;
            }
        }

        // Calculate bank/cash credit amount
        // When VAT is EXCLUSIVE: bank pays net payable + VAT = (base - WHT) + VAT
        // When VAT is INCLUSIVE: bank pays total amount - WHT (total includes VAT)
        // When no VAT: bank pays net payable (or total if no WHT)
        if ($paymentVatMode === 'EXCLUSIVE' && $totalVAT > 0) {
            // VAT is exclusive: bank pays net payable + VAT
            $bankCreditAmount = $finalNetPayable + $totalVAT;
        } elseif ($paymentVatMode === 'INCLUSIVE' && $totalVAT > 0) {
            // VAT is inclusive: total amount includes VAT, bank pays total - WHT
            $bankCreditAmount = $this->amount - $finalWHT;
        } else {
            // No VAT: bank pays net payable (or total if no WHT)
            $bankCreditAmount = $finalNetPayable;
        }

        // SPECIAL CASE:
        // For purchase invoice payments with:
        // - No WHT, and
        // - No FX difference (invoice in functional currency, rate = 1)
        // the amount leaving the bank MUST equal the payment amount shown in history.
        //
        // To avoid any rounding differences, we hard-align the bank credit to the stored payment amount.
        if ($this->reference_type === 'purchase_invoice' && $this->supplier_id && $totalWHT == 0) {
            $purchaseInvoice = \App\Models\Purchase\PurchaseInvoice::where('invoice_number', $this->reference_number)
                ->where('supplier_id', $this->supplier_id)
                ->first();

            if ($purchaseInvoice) {
                $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $purchaseInvoice->company->functional_currency ?? 'TZS');
                $invoiceCurrency = $purchaseInvoice->currency ?? $functionalCurrency;
                $invoiceRate = $purchaseInvoice->exchange_rate ?? 1.000000;
                $paymentRate = $this->exchange_rate ?? $invoiceRate;

                $noFx = ($invoiceCurrency === $functionalCurrency) && (abs($paymentRate - 1.0) < 0.000001);

                if ($noFx) {
                    // Force GL bank credit to exactly match the payment amount (2 d.p.)
                    $bankCreditAmount = round($this->amount, 2);
                }
            }
        }

        // Handle different payment methods
        if ($this->payment_method === 'cash_deposit') {
            // For cash deposit payments: Debit Cash Deposit Account (like sales invoices)
            GlTransaction::create([
                'chart_account_id' => $cashDepositAccountId,
                'customer_id' => $this->customer_id,
                'supplier_id' => $this->supplier_id,
                'amount' => $bankCreditAmount,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'payment',
                'date' => $date,
                'description' => "Cash deposit payment - {$description}",
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        } elseif ($this->payment_method === 'cheque' && $this->cheque_id) {
            // Get cheque issued account
            $chequeIssuedAccountId = (int) (\App\Models\SystemSetting::where('key', 'cheque_issued_account_id')->value('value') ?? 0);
            if (!$chequeIssuedAccountId) {
                // Fallback: try to find by name
                $chequeIssuedAccount = \App\Models\ChartAccount::where('account_name', 'LIKE', '%cheque issued%')
                    ->orWhere('account_name', 'LIKE', '%outstanding cheque%')
                    ->first();
                if (!$chequeIssuedAccount) {
                    throw new \Exception('Cheque Issued account not configured. Please set cheque_issued_account_id in system settings.');
                }
                $chequeIssuedAccountId = $chequeIssuedAccount->id;
            }
            
            // Credit Cheque Issued (Pending Clearance) - bank balance NOT reduced yet
            GlTransaction::create([
                'chart_account_id' => $chequeIssuedAccountId,
                'customer_id' => $this->customer_id,
                'supplier_id' => $this->supplier_id,
                'amount' => $bankCreditAmount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'payment',
                'date' => $date,
                'description' => "Cheque issued pending clearance - {$description}",
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        } else {
            // Credit bank/cash account (normal payment - bank transfer or cash)
            GlTransaction::create([
                'chart_account_id' => $creditAccountId,
                'customer_id' => $this->customer_id,
                'supplier_id' => $this->supplier_id,
                'amount' => $bankCreditAmount,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'payment',
                'date' => $date,
                'description' => $description,
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }

        // Credit WHT Payable (if WHT exists)
        // For purchase invoices, convert to LCY at payment rate (actual amount withheld)
        $whtAmountLCY = $finalWHT;
        if ($whtPayableAccountId > 0 && $finalWHT > 0) {
            if ($this->reference_type === 'purchase_invoice' && $this->supplier_id) {
                $purchaseInvoice = \App\Models\Purchase\PurchaseInvoice::where('invoice_number', $this->reference_number)
                    ->where('supplier_id', $this->supplier_id)
                    ->first();
                if ($purchaseInvoice) {
                    $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $purchaseInvoice->company->functional_currency ?? 'TZS');
                    $invoiceCurrency = $purchaseInvoice->currency ?? $functionalCurrency;
                    $paymentExchangeRate = $this->exchange_rate ?? $purchaseInvoice->exchange_rate ?? 1.000000;
                    if ($invoiceCurrency !== $functionalCurrency && $paymentExchangeRate != 1.000000) {
                        // Convert to LCY at payment rate (actual amount withheld)
                        $whtAmountLCY = round($finalWHT * $paymentExchangeRate, 2);
                    }
                }
            }
            
            GlTransaction::create([
                'chart_account_id' => $whtPayableAccountId,
                'customer_id' => $this->customer_id,
                'supplier_id' => $this->supplier_id,
                'amount' => $whtAmountLCY,
                'nature' => 'credit',
                'transaction_id' => $this->id,
                'transaction_type' => 'payment',
                'date' => $date,
                'description' => "WHT Payable - {$description}",
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }

        // Debit VAT Input (if VAT exists)
        // IMPORTANT: Skip VAT creation for bill purchases and purchase invoices
        // - Bills: VAT is already created when bill is created
        // - Purchase Invoices: VAT is already created when invoice is created (in postGlTransactions)
        // This matches the behavior of sales invoices where VAT is created at invoice creation, not payment
        $isBillPurchase = ($this->reference_type === 'Bill');
        $isPurchaseInvoice = ($this->reference_type === 'purchase_invoice');
        
        // Only create VAT Input for other payment types (not bills or purchase invoices)
        if (!$isBillPurchase && !$isPurchaseInvoice && $vatInputAccountId > 0 && $totalVAT > 0 && $paymentVatMode !== 'NONE') {
            GlTransaction::create([
                'chart_account_id' => $vatInputAccountId,
                'customer_id' => $this->customer_id,
                'supplier_id' => $this->supplier_id,
                'amount' => $totalVAT,
                'nature' => 'debit',
                'transaction_id' => $this->id,
                'transaction_type' => 'payment',
                'date' => $date,
                'description' => "VAT Input - {$description}",
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }
        
        // When VAT is INCLUSIVE, ensure totalBase is correctly calculated
        // If payment-level VAT is INCLUSIVE, recalculate totalBase from stored values
        if ($paymentVatMode === 'INCLUSIVE' && $totalVAT > 0) {
            // Always recalculate totalBase when VAT is INCLUSIVE to ensure accuracy
            // This handles cases where items might have incorrect base_amount values stored
            $totalBase = $this->amount - $totalVAT;
        }

        // For cash deposit payments, credit expense accounts (like sales invoices)
        // For other payment methods, debit expense accounts (normal payment voucher flow)
        $expenseNature = ($this->payment_method === 'cash_deposit') ? 'credit' : 'debit';
        
        // Debit each expense line (base amount only, WHT already handled)
        // When VAT is INCLUSIVE, we need to ensure expense uses base amount (excluding VAT)
        // For cash deposit: Credit expense accounts (money coming from customer deposit to pay expense)
        // For other methods: Debit expense accounts (normal payment flow)
        foreach ($this->paymentItems as $item) {
            $itemWHT = $item->wht_amount ?? 0;
            $itemTotalCost = $item->total_cost ?? $item->amount;
            $itemVatMode = $item->vat_mode ?? $paymentVatMode;
            $itemVatAmount = $item->vat_amount ?? 0;
            
            // Initialize itemBase - will be recalculated below if needed
            $itemBase = $item->base_amount ?? $item->amount;
            
            // If VAT is INCLUSIVE at payment level, ALWAYS recalculate base amount proportionally
            // This handles the case where VAT is inclusive at payment level but items don't have VAT
            // IMPORTANT: Ignore stored base_amount when VAT is INCLUSIVE at payment level
            if ($paymentVatMode === 'INCLUSIVE' && $totalVAT > 0) {
                // Payment-level VAT is INCLUSIVE - always allocate base amount proportionally
                // This ensures correct allocation regardless of how items were stored
                // When VAT is inclusive at payment level, item amounts include VAT, so we extract base proportionally
                if ($totalBase > 0 && $this->amount > 0 && $item->amount > 0) {
                    // Allocate base amount proportionally: (item_amount / total_amount) * total_base
                    // This correctly extracts the base amount (excluding VAT) from items that include VAT
                    // CRITICAL: Always recalculate, never use stored base_amount when VAT is INCLUSIVE at payment level
                    $itemBase = round(($item->amount / $this->amount) * $totalBase, 2);
                } else {
                    // Fallback: calculate from item amount if VAT rate is known
                    $itemVatRate = $item->vat_rate ?? 18;
                    $itemBase = round($item->amount / (1 + ($itemVatRate / 100)), 2);
                }
            } elseif ($itemVatMode === 'INCLUSIVE' && $itemVatAmount > 0) {
                // Item has VAT inclusive - use stored base_amount (which should exclude VAT)
                $itemBase = $item->base_amount ?? ($item->amount - $itemVatAmount);
            }
            
            // Determine expense amount: prioritize payment-level WHT if set, otherwise use item-level
            // IMPORTANT: For bill purchases, always debit credit_account with full payment amount
            // For Accounts Payable (purchase invoices), always use full payment amount
            // For expense accounts, use base amount (excluding VAT when VAT is INCLUSIVE)
            $isBillPurchase = ($this->reference_type === 'Bill');
            $isAccountsPayable = false;
            $apAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value') ?? 30);
            if ($item->chart_account_id == $apAccountId) {
                $isAccountsPayable = true;
            }
            
            // For bill purchases: always debit credit_account (could be Loan Payable, Accounts Payable, etc.) with full payment amount
            // This ensures the liability account balance matches the payment amount
            if ($isBillPurchase || $isAccountsPayable) {
                // For bill purchases or Accounts Payable: always debit with full payment amount (not base amount)
                // This ensures the liability account balance matches the payment amount
                // For purchase invoices, convert to LCY at PAYMENT rate (what we actually pay)
                // The FX gain/loss adjustment will handle the difference between payment rate and invoice rate
                if ($this->reference_type === 'purchase_invoice' && $this->supplier_id) {
                    $purchaseInvoice = \App\Models\Purchase\PurchaseInvoice::where('invoice_number', $this->reference_number)
                        ->where('supplier_id', $this->supplier_id)
                        ->first();
                    if ($purchaseInvoice) {
                        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $purchaseInvoice->company->functional_currency ?? 'TZS');
                        $invoiceCurrency = $purchaseInvoice->currency ?? $functionalCurrency;
                        $paymentExchangeRate = $this->exchange_rate ?? $purchaseInvoice->exchange_rate ?? 1.000000;
                        if ($invoiceCurrency !== $functionalCurrency && $paymentExchangeRate != 1.000000) {
                            // Convert to LCY at PAYMENT rate (what we actually pay)
                            $itemExpenseAmount = round($item->amount * $paymentExchangeRate, 2);
                        } else {
                            $itemExpenseAmount = $item->amount; // Already in functional currency
                        }
                    } else {
                        $itemExpenseAmount = $item->amount; // Fallback
                    }
                } else {
                $itemExpenseAmount = $item->amount; // Full payment amount
                }
            } elseif ($totalWHT > 0 && $this->wht_treatment && $this->wht_treatment !== 'NONE') {
                // Payment-level WHT is set - use payment-level logic (takes precedence)
                if ($this->wht_treatment === 'GROSS_UP') {
                    // For Gross-Up: expense = base + item's share of WHT
                    // Calculate item's share of total WHT proportionally
                    $itemWHTShare = ($itemBase / $this->amount) * $finalWHT;
                    $itemExpenseAmount = $itemBase + $itemWHTShare;
                } else {
                    // For Exclusive/Inclusive: expense = base amount (WHT never affects expense, only net payable)
                    $itemExpenseAmount = $itemBase;
                }
            } else if ($item->wht_treatment && $item->wht_treatment !== 'NONE' && $itemWHT > 0) {
                // No payment-level WHT, but item has its own WHT - use item's logic
                $itemExpenseAmount = ($item->wht_treatment === 'GROSS_UP') 
                    ? $itemTotalCost 
                    : $itemBase;
            } else {
                // No WHT at all - use base amount (which excludes VAT when VAT is INCLUSIVE)
                $itemExpenseAmount = $itemBase;
            }

            GlTransaction::create([
                'chart_account_id' => $item->chart_account_id,
                'customer_id' => $this->customer_id,
                'supplier_id' => $this->supplier_id,
                'amount' => $itemExpenseAmount,
                'nature' => $expenseNature, // 'credit' for cash deposit, 'debit' for other methods
                'transaction_id' => $this->id,
                'transaction_type' => 'payment',
                'date' => $date,
                'description' => $item->description ?: $description,
                'branch_id' => $branchId,
                'user_id' => $userId,
            ]);
        }
        
        // Handle FX Gain/Loss for purchase invoice payments
        if ($this->reference_type === 'purchase_invoice' && $this->supplier_id) {
            $this->handlePurchaseInvoiceFxGainLoss($date, $description, $branchId, $userId);
        }
        
        // Log activity for posting to GL
        $payeeName = $this->payee_name ?? ($this->supplier ? $this->supplier->name : ($this->customer ? $this->customer->name : 'N/A'));
        $this->logActivity('post', "Posted Payment {$this->reference} to General Ledger for {$payeeName}", [
            'Payment Reference' => $this->reference,
            'Payee' => $payeeName,
            'Payee Type' => ucfirst($this->payee_type ?? 'N/A'),
            'Payment Date' => $this->date ? $this->date->format('Y-m-d') : 'N/A',
            'Amount' => number_format($this->amount, 2),
            'Net Payable' => number_format($this->net_payable ?? 0, 2),
            'WHT Amount' => number_format($this->wht_amount ?? 0, 2),
            'VAT Amount' => number_format($this->vat_amount ?? 0, 2),
            'Payment Items Count' => count($this->paymentItems),
            'Payment Method' => $this->bankAccount ? 'Bank' : ($this->cashDeposit ? 'Cash Deposit' : 'Cash'),
            'Posted By' => auth()->user()->name ?? 'System',
            'Posted At' => now()->format('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Handle FX Gain/Loss for purchase invoice payments
     */
    private function handlePurchaseInvoiceFxGainLoss($paymentDate, $description, $branchId, $userId)
    {
        // Get the purchase invoice
        $purchaseInvoice = \App\Models\Purchase\PurchaseInvoice::where('invoice_number', $this->reference_number)
            ->where('supplier_id', $this->supplier_id)
            ->first();
            
        if (!$purchaseInvoice) {
            return;
        }
        
        // Get functional currency
        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', $purchaseInvoice->company->functional_currency ?? 'TZS');
        $invoiceCurrency = $purchaseInvoice->currency ?? $functionalCurrency;
        $invoiceExchangeRate = $purchaseInvoice->exchange_rate ?? 1.000000;
        
        // Get payment exchange rate (use payment rate if set, otherwise invoice rate)
        $paymentExchangeRate = $this->exchange_rate ?? $invoiceExchangeRate;
        
        // If invoice is in functional currency or rates are the same, no FX gain/loss
        if ($invoiceCurrency === $functionalCurrency || abs($invoiceExchangeRate - $paymentExchangeRate) < 0.000001) {
            return;
        }
        
        // Calculate FX difference
        // For purchase invoices: if we pay more in LCY than originally recorded, it's a loss
        // If we pay less in LCY than originally recorded, it's a gain
        $invoiceAmountInLCY = $this->amount * $invoiceExchangeRate;
        $paymentAmountInLCY = $this->amount * $paymentExchangeRate;
        $fxDifference = $paymentAmountInLCY - $invoiceAmountInLCY;
        
        // Only create GL entry if difference is significant
        if (abs($fxDifference) < 0.01) {
            return;
        }
        
        // Get FX Gain/Loss account IDs from Foreign Exchange Settings
        $fxGainAccountId = \App\Models\SystemSetting::getValue('fx_realized_gain_account_id');
        if (!$fxGainAccountId) {
            $fxGainAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Foreign Exchange Gain%Realized%')
                ->orWhere('account_name', 'like', '%FX Gain%Realized%')
                ->first();
            $fxGainAccountId = $fxGainAccount ? $fxGainAccount->id : null;
        } else {
            $fxGainAccountId = (int) $fxGainAccountId;
        }
        
        $fxLossAccountId = \App\Models\SystemSetting::getValue('fx_realized_loss_account_id');
        if (!$fxLossAccountId) {
            $fxLossAccount = \App\Models\ChartAccount::where('account_name', 'like', '%Foreign Exchange Loss%Realized%')
                ->orWhere('account_name', 'like', '%FX Loss%Realized%')
                ->first();
            $fxLossAccountId = $fxLossAccount ? $fxLossAccount->id : null;
        } else {
            $fxLossAccountId = (int) $fxLossAccountId;
        }
        
        // Get Accounts Payable account ID
        $apAccountId = (int) (\App\Models\SystemSetting::where('key', 'inventory_default_purchase_payable_account')->value('value') ?? 30);
        
        // Helper function to add currency info to description
        $addCurrencyInfo = function($desc) use ($invoiceCurrency, $functionalCurrency, $invoiceExchangeRate, $paymentExchangeRate) {
            return $desc . " [FCY: {$invoiceCurrency}, Invoice Rate: {$invoiceExchangeRate}, Payment Rate: {$paymentExchangeRate}, Converted to {$functionalCurrency}]";
        };
        
        if ($fxDifference < 0) {
            // FX Gain - We paid LESS in LCY than originally recorded (payment rate < invoice rate)
            // Example: Invoice at 2500, Payment at 2450 = we paid 50 TZS less = GAIN
            // Accounts Payable was debited at PAYMENT rate, but should match INVOICE rate
            // So we need to CREDIT Accounts Payable to reduce it from payment rate to invoice rate
            // And CREDIT FX Gain Account
            if ($fxGainAccountId) {
                // Credit Accounts Payable to adjust from payment rate to invoice rate
                GlTransaction::create([
                    'chart_account_id' => $apAccountId,
                    'supplier_id' => $this->supplier_id,
                    'amount' => abs($fxDifference),
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'payment',
                    'date' => $paymentDate,
                    'description' => $addCurrencyInfo("FX Gain Adjustment - {$description} - Adjust payable from payment rate to invoice rate"),
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                ]);
                
                // Credit FX Gain Account
                GlTransaction::create([
                    'chart_account_id' => $fxGainAccountId,
                    'supplier_id' => $this->supplier_id,
                    'amount' => abs($fxDifference),
                    'nature' => 'credit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'payment',
                    'date' => $paymentDate,
                    'description' => $addCurrencyInfo("FX Gain - {$description} - Rate difference: {$invoiceExchangeRate} to {$paymentExchangeRate}"),
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                ]);
            } else {
                \Log::warning('FX Gain account not configured in Foreign Exchange Settings. FX gain not posted.', [
                    'payment_id' => $this->id,
                    'reference' => $this->reference,
                    'fx_gain_amount' => abs($fxDifference)
                ]);
            }
        } else if ($fxDifference > 0) {
            // FX Loss - We paid MORE in LCY than originally recorded (payment rate > invoice rate)
            // Example: Invoice at 2450, Payment at 2500 = we paid 50 TZS more = LOSS
            // Accounts Payable was debited at PAYMENT rate, but should match INVOICE rate
            // So we need to DEBIT Accounts Payable to increase it from payment rate to invoice rate
            // And DEBIT FX Loss Account
            if ($fxLossAccountId) {
                // Debit Accounts Payable to adjust from payment rate to invoice rate
                GlTransaction::create([
                    'chart_account_id' => $apAccountId,
                    'supplier_id' => $this->supplier_id,
                    'amount' => $fxDifference,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'payment',
                    'date' => $paymentDate,
                    'description' => $addCurrencyInfo("FX Loss Adjustment - {$description} - Adjust payable from payment rate to invoice rate"),
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                ]);
                
                // Debit FX Loss Account
                GlTransaction::create([
                    'chart_account_id' => $fxLossAccountId,
                    'supplier_id' => $this->supplier_id,
                    'amount' => $fxDifference,
                    'nature' => 'debit',
                    'transaction_id' => $this->id,
                    'transaction_type' => 'payment',
                    'date' => $paymentDate,
                    'description' => $addCurrencyInfo("FX Loss - {$description} - Rate difference: {$invoiceExchangeRate} to {$paymentExchangeRate}"),
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                ]);
            } else {
                \Log::warning('FX Loss account not configured in Foreign Exchange Settings. FX loss not posted.', [
                    'payment_id' => $this->id,
                    'reference' => $this->reference,
                    'fx_loss_amount' => $fxDifference
                ]);
            }
        }
    }
}
