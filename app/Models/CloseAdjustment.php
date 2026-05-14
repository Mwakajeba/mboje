<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloseAdjustment extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'adj_id';
    protected $table = 'close_adjustments';

    protected $fillable = [
        'close_id',
        'adj_date',
        'gl_debit_account',
        'gl_credit_account',
        'amount',
        'description',
        'source_document',
        'created_by',
        'posted_journal_id',
    ];

    protected $casts = [
        'adj_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function closeBatch(): BelongsTo
    {
        return $this->belongsTo(CloseBatch::class, 'close_id');
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'gl_debit_account');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'gl_credit_account');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'posted_journal_id');
    }
}
