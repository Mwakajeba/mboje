<?php

namespace App\Models\PettyCash;

use App\Models\ChartAccount;
use App\Models\ImprestRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashRegister extends Model
{
    use HasFactory;

    protected $table = 'petty_cash_register';

    protected $fillable = [
        'petty_cash_unit_id',
        'petty_cash_transaction_id',
        'petty_cash_replenishment_id',
        'imprest_request_id',
        'register_date',
        'pcv_number',
        'description',
        'amount',
        'entry_type',
        'nature',
        'gl_account_id',
        'requested_by',
        'approved_by',
        'status',
        'balance_after',
        'notes',
    ];

    protected $casts = [
        'register_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Get the petty cash unit
     */
    public function pettyCashUnit(): BelongsTo
    {
        return $this->belongsTo(PettyCashUnit::class);
    }

    /**
     * Get the transaction (if linked)
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PettyCashTransaction::class, 'petty_cash_transaction_id');
    }

    /**
     * Get the replenishment (if linked)
     */
    public function replenishment(): BelongsTo
    {
        return $this->belongsTo(PettyCashReplenishment::class, 'petty_cash_replenishment_id');
    }

    /**
     * Get the imprest request (if in sub-imprest mode)
     */
    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class);
    }

    /**
     * Get the GL account
     */
    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'gl_account_id');
    }

    /**
     * Get the user who requested
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate PCV number
     */
    public static function generatePcvNumber($unitCode): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastPcv = static::where('pcv_number', 'like', "PCV-{$unitCode}-{$year}{$month}%")
            ->orderByDesc('pcv_number')
            ->first();
        
        if ($lastPcv) {
            $lastNumber = (int) substr($lastPcv->pcv_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('PCV-%s-%s%02d%04d', $unitCode, $year, $month, $nextNumber);
    }
}


