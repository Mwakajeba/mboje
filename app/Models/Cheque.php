<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class Cheque extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'cheque_number',
        'cheque_date',
        'bank_account_id',
        'payee_name',
        'amount',
        'status',
        'payment_reference_type',
        'payment_reference_id',
        'payment_reference_number',
        'module_origin',
        'payment_type',
        'description',
        'signature_authorization',
        'cheque_template',
        'cleared_date',
        'bounced_date',
        'cancelled_date',
        'cancellation_reason',
        'bounce_reason',
        'issued_by',
        'cleared_by',
        'cancelled_by',
        'printed_at',
        'is_printed',
        'is_voided',
        'void_reason',
        'voided_by',
        'voided_at',
        'company_id',
        'branch_id',
        'issue_journal_id',
        'clear_journal_id',
        'bounce_journal_id',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'cleared_date' => 'date',
        'bounced_date' => 'date',
        'cancelled_date' => 'date',
        'printed_at' => 'datetime',
        'voided_at' => 'datetime',
        'amount' => 'decimal:2',
        'is_printed' => 'boolean',
        'is_voided' => 'boolean',
    ];

    // Relationships
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function issueJournal()
    {
        return $this->belongsTo(Journal::class, 'issue_journal_id');
    }

    public function clearJournal()
    {
        return $this->belongsTo(Journal::class, 'clear_journal_id');
    }

    public function bounceJournal()
    {
        return $this->belongsTo(Journal::class, 'bounce_journal_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Scopes
    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }

    public function scopeCleared($query)
    {
        return $query->where('status', 'cleared');
    }

    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeStale($query)
    {
        return $query->where('status', 'stale');
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['issued']);
    }

    public function scopeForBankAccount($query, $bankAccountId)
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    // Helper methods
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function isOutstanding()
    {
        return $this->status === 'issued';
    }

    public function canBeCleared()
    {
        return $this->status === 'issued' && !$this->is_voided && !$this->clear_journal_id;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['issued']) && !$this->is_voided;
    }

    public function canBeVoided()
    {
        return !$this->is_voided && in_array($this->status, ['issued', 'cancelled']);
    }

    public function isStale($days = 180)
    {
        if ($this->status !== 'issued') {
            return false;
        }
        
        return $this->cheque_date->diffInDays(now()) > $days;
    }

    /**
     * Check if cheque number is unique for the bank account
     */
    public static function isChequeNumberUnique($chequeNumber, $bankAccountId, $excludeId = null)
    {
        $query = static::where('cheque_number', $chequeNumber)
            ->where('bank_account_id', $bankAccountId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return !$query->exists();
    }
}
