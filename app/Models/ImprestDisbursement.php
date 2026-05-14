<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImprestDisbursement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'imprest_request_id',
        'disbursement_number',
        'amount_issued',
        'payment_mode',
        'reference_number',
        'bank_account_id',
        'issued_by',
        'issued_at',
        'disbursement_notes',
        'cheque_number',
    ];

    protected $casts = [
        'amount_issued' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    // Relationships
    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // Methods
    public function getPaymentModeLabel(): string
    {
        return match($this->payment_mode) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'mobile_money' => 'Mobile Money',
            default => ucfirst(str_replace('_', ' ', $this->payment_mode))
        };
    }

    // Generate unique disbursement number
    public static function generateDisbursementNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastDisbursement = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDisbursement ? (int)substr($lastDisbursement->disbursement_number, -4) + 1 : 1;

        return 'DIS-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}