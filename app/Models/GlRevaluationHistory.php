<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlRevaluationHistory extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'gl_revaluation_history';

    protected $fillable = [
        'revaluation_date',
        'item_type',
        'item_ref',
        'item_id',
        'original_rate',
        'closing_rate',
        'base_amount',
        'fcy_amount',
        'gain_loss',
        'posted_journal_id',
        'reversal_journal_id',
        'is_reversed',
        'company_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'revaluation_date' => 'date',
        'original_rate' => 'decimal:6',
        'closing_rate' => 'decimal:6',
        'base_amount' => 'decimal:2',
        'fcy_amount' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'is_reversed' => 'boolean',
    ];

    /**
     * Get the company that owns the revaluation history.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who created the revaluation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the posted journal entry.
     */
    public function postedJournal()
    {
        return $this->belongsTo(Journal::class, 'posted_journal_id');
    }

    /**
     * Get the reversal journal entry.
     */
    public function reversalJournal()
    {
        return $this->belongsTo(Journal::class, 'reversal_journal_id');
    }

    /**
     * Scope to filter by item type.
     */
    public function scopeItemType($query, $itemType)
    {
        return $query->where('item_type', $itemType);
    }

    /**
     * Scope to filter by revaluation date.
     */
    public function scopeRevaluationDate($query, $date)
    {
        return $query->where('revaluation_date', $date);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by branch.
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get only reversed entries.
     */
    public function scopeReversed($query)
    {
        return $query->where('is_reversed', true);
    }

    /**
     * Scope to get only unreversed entries.
     */
    public function scopeUnreversed($query)
    {
        return $query->where('is_reversed', false);
    }

    /**
     * Mark as reversed.
     */
    public function markAsReversed($reversalJournalId = null)
    {
        $this->is_reversed = true;
        if ($reversalJournalId) {
            $this->reversal_journal_id = $reversalJournalId;
        }
        $this->save();
        return $this;
    }

    /**
     * Get gain/loss as formatted string.
     */
    public function getFormattedGainLossAttribute()
    {
        $sign = $this->gain_loss >= 0 ? '+' : '';
        return $sign . number_format($this->gain_loss, 2);
    }

    /**
     * Check if this is a gain.
     */
    public function isGain()
    {
        return $this->gain_loss > 0;
    }

    /**
     * Check if this is a loss.
     */
    public function isLoss()
    {
        return $this->gain_loss < 0;
    }

    /**
     * Get the hash ID for the revaluation history
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return \App\Helpers\HashIdHelper::encode($this->id);
    }

    /**
     * Resolve the model from the route parameter.
     *
     * @param string $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = \App\Helpers\HashIdHelper::decode($value);
        
        if ($decoded !== null) {
            return static::where('id', $decoded)->first();
        }
        
        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }
}

