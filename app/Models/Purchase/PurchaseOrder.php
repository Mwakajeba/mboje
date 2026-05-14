<?php

namespace App\Models\Purchase;

use App\Helpers\AmountInWords;
use App\Models\Supplier;
use App\Models\Inventory\Item;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_number',
        'purchase_requisition_id',
        'quotation_id',
        'supplier_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'current_approval_level',
        'submitted_by',
        'submitted_at',
        'payment_terms',
        'payment_days',
        'subtotal',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'tax_amount',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'total_amount',
        'hide_cost_price',
        'notes',
        'terms_conditions',
        'attachment',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'branch_id',
        'company_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'hide_cost_price' => 'boolean',
        'payment_days' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchaseOrder) {
            if (empty($purchaseOrder->order_number)) {
                $purchaseOrder->order_number = self::generateOrderNumber();
            }
        });
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $year = date('Y');
        $month = date('m');
        
        $lastOrder = self::where('order_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(PurchaseQuotation::class, 'quotation_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scopes
     */
    public function scopeForBranch(Builder $query, $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus(Builder $query, $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval(Builder $query): Builder
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeInProduction(Builder $query): Builder
    {
        return $query->where('status', 'in_production');
    }

    public function scopeReadyForDelivery(Builder $query): Builder
    {
        return $query->where('status', 'ready_for_delivery');
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', 'delivered');
    }

    public function scopeBySupplier(Builder $query, $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    /**
     * Accessors
     */
    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 2);
    }

    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 2);
    }

    public function getFormattedVatAmountAttribute()
    {
        return number_format($this->vat_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute()
    {
        return number_format($this->discount_amount, 2);
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }

    public function getStatusBadgeAttribute()
    {
        // Bootstrap 5 badge background utilities
        $badges = [
            'draft' => 'bg-secondary',
            'pending_approval' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'in_production' => 'bg-info text-dark',
            'ready_for_delivery' => 'bg-primary',
            'delivered' => 'bg-success',
            'cancelled' => 'bg-danger',
            'on_hold' => 'bg-warning text-dark',
        ];

        return $badges[$this->status] ?? 'bg-secondary';
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'in_production' => 'In Production',
            'ready_for_delivery' => 'Ready for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'on_hold' => 'On Hold',
        ];

        return $labels[$this->status] ?? 'Unknown';
    }

    /**
     * Check if order can be approved
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, ['draft', 'pending_approval']);
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['delivered', 'cancelled']);
    }

    /**
     * Check if order can be converted to goods receipt
     */
    public function canBeConvertedToGoodsReceipt(): bool
    {
        return in_array($this->status, ['approved', 'in_production', 'ready_for_delivery']);
    }

    /**
     * Get the encoded ID for this purchase order
     */
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve the model from the route parameter
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);
        
        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }
        
        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKey()
    {
        return $this->encoded_id;
    }
}
