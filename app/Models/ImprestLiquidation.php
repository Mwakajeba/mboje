<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImprestLiquidation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'imprest_request_id',
        'liquidation_number',
        'total_spent',
        'balance_returned',
        'liquidation_date',
        'liquidation_notes',
        'status',
        'submitted_by',
        'submitted_at',
        'verified_by',
        'verified_at',
        'verification_notes',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'balance_returned' => 'decimal:2',
        'liquidation_date' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function imprestRequest(): BelongsTo
    {
        return $this->belongsTo(ImprestRequest::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function liquidationItems(): HasMany
    {
        return $this->hasMany(ImprestLiquidationItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ImprestDocument::class);
    }

    // Methods
    public function canBeVerified(): bool
    {
        return $this->status === 'submitted';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'verified';
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'submitted' => 'badge bg-warning',
            'verified' => 'badge bg-info',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            default => 'badge bg-light text-dark'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'submitted' => 'Awaiting Verification',
            'verified' => 'Verified - Awaiting Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status)
        };
    }

    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->liquidationItems()->sum('amount');
    }

    // Generate unique liquidation number
    public static function generateLiquidationNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        $lastLiquidation = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastLiquidation ? (int)substr($lastLiquidation->liquidation_number, -4) + 1 : 1;

        return 'LIQ-' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}