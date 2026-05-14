<?php

namespace App\Models\Purchase;

use App\Models\Branch;
use App\Models\Budget;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class PurchaseRequisition extends Model
{
    use LogsActivity;

    protected $fillable = [
        'pr_no',
        'company_id',
        'branch_id',
        'department_id',
        'requestor_id',
        'preferred_supplier_id',
        'required_date',
        'justification',
        'status',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'purchase_order_id',
        'budget_id',
        'total_amount',
        'currency',
        'exchange_rate',
    ];

    protected $casts = [
        'required_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (PurchaseRequisition $pr) {
            if (empty($pr->pr_no)) {
                $pr->pr_no = self::generatePrNumber();
            }
        });
    }

    public static function generatePrNumber(): string
    {
        $prefix = 'PR';
        $year = date('Y');
        $month = date('m');

        $last = static::where('pr_no', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('pr_no', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->pr_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $year . $month . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /** Relationships */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        // Department (cost center)
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Supplier::class, 'preferred_supplier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionLine::class, 'purchase_requisition_id');
    }

    /** Accessors */

    public function getHashIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKey()
    {
        return $this->hash_id;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        if (!empty($decoded)) {
            return $this->findOrFail($decoded[0]);
        }
        return $this->findOrFail($value);
    }
}


