<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodSnapshot extends Model
{
    use HasFactory;

    protected $primaryKey = 'snapshot_id';
    protected $table = 'period_snapshots';

    protected $fillable = [
        'close_id',
        'account_id',
        'period_id',
        'opening_balance',
        'period_debits',
        'period_credits',
        'closing_balance',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'period_debits' => 'decimal:2',
        'period_credits' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    // Relationships
    public function closeBatch(): BelongsTo
    {
        return $this->belongsTo(CloseBatch::class, 'close_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'account_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
}
