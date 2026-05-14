<?php

namespace App\Models\Purchase;

use App\Models\Supplier;
use App\Models\Inventory\Item as InventoryItem;
use App\Models\Inventory\Movement as InventoryMovement;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Models\GlTransaction;
use App\Models\SystemSetting;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Vinkla\Hashids\Facades\Hashids;

class DebitNote extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'debit_note_number',
        'purchase_invoice_id',
        'reference_invoice_id',
        'supplier_id',
        'debit_note_date',
        'status',
        'type',
        'reason_code',
        'reason',
        'notes',
        'terms_conditions',
        'attachment',
        'refund_now',
        'return_to_stock',
        'restocking_fee_percentage',
        'restocking_fee_amount',
        'restocking_fee_vat',
        'currency',
        'exchange_rate',
        'fx_gain_loss',
        'reference_document',
        'warehouse_id',
        'approval_notes',
        'submitted_at',
        'submitted_by',
        'tax_calculation_details',
        'posting_details',
        'document_series',
        'subtotal',
        'vat_amount',
        'discount_amount',
        'total_amount',
        'original_amount',
        'net_debit_amount',
        'gross_debit_amount',
        'applied_amount',
        'remaining_amount',
        'vat_rate',
        'vat_type',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'refund_now' => 'boolean',
        'return_to_stock' => 'boolean',
        'debit_note_date' => 'date',
        'restocking_fee_percentage' => 'decimal:2',
        'restocking_fee_amount' => 'decimal:2',
        'restocking_fee_vat' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'fx_gain_loss' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'tax_calculation_details' => 'array',
        'posting_details' => 'array',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'net_debit_amount' => 'decimal:2',
        'gross_debit_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($debitNote) {
            if (empty($debitNote->debit_note_number)) {
                $debitNote->debit_note_number = self::generateDebitNoteNumber();
            }
        });
    }

    /**
     * Generate unique debit note number
     */
    public static function generateDebitNoteNumber(): string
    {
        $prefix = 'DN';
        $year = date('Y');
        $month = date('m');
        
        $lastDebitNote = self::where('debit_note_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('debit_note_number', 'desc')
            ->first();

        if ($lastDebitNote) {
            $lastNumber = (int) substr($lastDebitNote->debit_note_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function referenceInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'reference_invoice_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(DebitNoteApplication::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'warehouse_id');
    }

    /**
     * Scopes
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'issued');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeApplied(Builder $query): Builder
    {
        return $query->where('status', 'applied');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Helper methods
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'issued' => 'bg-warning text-dark',
            'approved' => 'bg-info',
            'applied' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'issued' => 'Issued',
            'approved' => 'Approved',
            'applied' => 'Applied',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }

    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'return' => 'Return',
            'discount' => 'Discount',
            'correction' => 'Correction',
            'overbilling' => 'Overbilling',
            'service_adjustment' => 'Service Adjustment',
            'post_invoice_discount' => 'Post Invoice Discount',
            'refund' => 'Refund',
            'restocking_fee' => 'Restocking Fee',
            'scrap_writeoff' => 'Scrap Write-off',
            'advance_refund' => 'Advance Refund',
            'fx_adjustment' => 'FX Adjustment',
            'other' => 'Other',
            default => 'Unknown'
        };
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'issued']);
    }

    public function canDelete(): bool
    {
        return $this->status === 'draft';
    }

    public function canApprove(): bool
    {
        // Allow approval from Draft or Issued
        return in_array($this->status, ['draft', 'issued']);
    }

    public function canApply(): bool
    {
        return $this->status === 'approved';
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'issued', 'approved']);
    }

    /**
     * Hash ID for routing
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (is_numeric($value)) {
            return $this->where($field ?? $this->getRouteKeyName(), $value)->first();
        }
        
        $decodedId = Hashids::decode($value);
        if (!empty($decodedId)) {
            return $this->where($field ?? $this->getRouteKeyName(), $decodedId[0])->first();
        }
        
        return null;
    }
}
