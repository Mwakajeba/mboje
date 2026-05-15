<?php

namespace App\Models\Purchase;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierAdvanceDeduction extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'supplier_advance_id',
        'amount',
        'deduction_date',
        'source_type',
        'source_id',
        'description',
        'user_id',
    ];

    protected $casts = [
        'deduction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierAdvance(): BelongsTo
    {
        return $this->belongsTo(SupplierAdvance::class, 'supplier_advance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
