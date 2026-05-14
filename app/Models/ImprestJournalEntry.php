<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprestJournalEntry extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'imprest_request_id',
        'journal_number',
        'entry_type',
        'debit_account_id',
        'credit_account_id',
        'amount',
        'description',
        'transaction_date',
        'reference_number',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Relationships
    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'credit_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Methods
    public function getEntryTypeLabel(): string
    {
        return match($this->entry_type) {
            'issue' => 'Imprest Issue',
            'liquidation' => 'Liquidation',
            'replenishment' => 'Replenishment',
            'balance_return' => 'Balance Return',
            default => ucfirst(str_replace('_', ' ', $this->entry_type))
        };
    }

    // Generate unique journal number
    public static function generateJournalNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastJournal = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastJournal ? (int)substr($lastJournal->journal_number, -4) + 1 : 1;

        return 'IJE-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}