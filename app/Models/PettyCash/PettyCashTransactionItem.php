<?php

namespace App\Models\PettyCash;

use App\Models\ChartAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'petty_cash_transaction_id',
        'chart_account_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PettyCashTransaction::class, 'petty_cash_transaction_id');
    }

    public function chartAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class);
    }
}

