<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class CustomerDeposit extends Model
{
    protected $table = 'rental_customer_deposits';

    protected $fillable = [
        'deposit_number',
        'contract_id',
        'customer_id',
        'deposit_date',
        'amount',
        'payment_method',
        'bank_account_id',
        'reference_number',
        'notes',
        'attachment',
        'status',
        'company_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'deposit_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get invoices that used deposits from this contract
     */
    public function getInvoicesUsingDeposits()
    {
        if (!$this->contract_id) {
            return collect();
        }

        return \App\Models\RentalEventEquipment\RentalInvoice::where('contract_id', $this->contract_id)
            ->where('deposit_applied', '>', 0)
            ->with(['customer', 'creator'])
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * Get total deposits for a customer (including drafts)
     */
    public static function getTotalDepositsForCustomer($customerId, $companyId = null, $branchId = null)
    {
        $query = self::where('customer_id', $customerId);
        // Include all deposits including drafts so they show up immediately after creation

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        return $query->sum('amount');
    }

    /**
     * Get total used deposits for a customer (from invoices)
     */
    public static function getTotalUsedDepositsForCustomer($customerId, $companyId = null, $branchId = null)
    {
        $query = \App\Models\RentalEventEquipment\RentalInvoice::where('customer_id', $customerId)
            ->where('deposit_applied', '>', 0);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        return $query->sum('deposit_applied');
    }

    /**
     * Get all deposit movements for a customer (deposits and usages)
     */
    public static function getDepositMovementsForCustomer($customerId, $companyId = null, $branchId = null)
    {
        $movements = collect();

        // Get all deposits (including drafts)
        $depositsQuery = self::where('customer_id', $customerId)
            ->with(['contract', 'bankAccount']);

        if ($companyId) {
            $depositsQuery->where('company_id', $companyId);
        }

        if ($branchId) {
            $depositsQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        $deposits = $depositsQuery->get();

        foreach ($deposits as $deposit) {
            $movements->push([
                'date' => $deposit->deposit_date,
                'type' => 'deposit',
                'reference' => $deposit->deposit_number,
                'contract' => $deposit->contract ? $deposit->contract->contract_number : null,
                'amount' => $deposit->amount,
                'description' => 'Customer Deposit',
                'status' => $deposit->status,
            ]);
        }

        // Get all usages (from invoices)
        $invoicesQuery = \App\Models\RentalEventEquipment\RentalInvoice::where('customer_id', $customerId)
            ->where('deposit_applied', '>', 0)
            ->with(['contract', 'creator']);

        if ($companyId) {
            $invoicesQuery->where('company_id', $companyId);
        }

        if ($branchId) {
            $invoicesQuery->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        }

        $invoices = $invoicesQuery->get();

        foreach ($invoices as $invoice) {
            $movements->push([
                'date' => $invoice->invoice_date,
                'type' => 'usage',
                'reference' => $invoice->invoice_number,
                'contract' => $invoice->contract ? $invoice->contract->contract_number : null,
                'amount' => $invoice->deposit_applied,
                'description' => 'Deposit Applied to Invoice',
                'status' => $invoice->status,
            ]);
        }

        // Sort by date descending
        return $movements->sortByDesc(function ($movement) {
            $date = $movement['date'];
            if ($date instanceof \Carbon\Carbon) {
                return $date->timestamp;
            }
            return is_string($date) ? strtotime($date) : 0;
        })->values();
    }

    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (in_array($value, ['create', 'edit', 'data', 'index'])) {
            return null;
        }
        $decoded = Hashids::decode($value);
        $id = $decoded[0] ?? null;
        return $id ? static::find($id) : null;
    }
}
