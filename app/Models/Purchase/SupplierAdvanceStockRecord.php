<?php

namespace App\Models\Purchase;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierAdvanceStockRecord extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'supplier_id',
        'bidhaa',
        'entry_date',
        'description',
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierAdvanceStockLine::class, 'stock_record_id');
    }

    public function totalThamani(): float
    {
        return (float) $this->lines->sum('thamani');
    }
}
