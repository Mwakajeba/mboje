<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccrualJournal extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'accrual_journals';

    protected $fillable = [
        'accrual_schedule_id',
        'period', // YYYY-MM
        'period_start_date',
        'period_end_date',
        'days_in_period',
        'amortisation_amount',
        'fx_rate',
        'home_currency_amount',
        'fx_difference',
        'fx_gain_loss_account_id',
        'journal_id',
        'reversal_journal_id', // Reference to auto-reversal journal
        'status', // pending, posted, reversed, cancelled
        'posted_at',
        'posted_by',
        'narration',
        'notes',
        'company_id',
        'branch_id',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'posted_at' => 'datetime',
        'amortisation_amount' => 'decimal:2',
        'fx_rate' => 'decimal:6',
        'home_currency_amount' => 'decimal:2',
        'fx_difference' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AccrualSchedule::class, 'accrual_schedule_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function reversalJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversal_journal_id');
    }

    public function fxGainLossAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'fx_gain_loss_account_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeForPeriod($query, $period)
    {
        return $query->where('period', $period);
    }
}
