<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprestLiquidationItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'imprest_liquidation_id',
        'expense_category',
        'description',
        'amount',
        'expense_date',
        'receipt_number',
        'supplier_name',
        'chart_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    // Relationships
    public function imprestLiquidation(): BelongsTo
    {
        return $this->belongsTo(ImprestLiquidation::class);
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }
}