<?php

namespace App\Models\Purchase;

use App\Helpers\AmountInWords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Supplier;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use App\Traits\LogsActivity;
use App\Models\Purchase\PurchaseOrder;
use Vinkla\Hashids\Facades\Hashids;

class PurchaseQuotation extends Model
{
    use LogsActivity;
    
    protected $table = 'purchase_quotation';

    protected $fillable = [
        'purchase_requisition_id',
        'supplier_id',
        'start_date',
        'due_date',
        'status',
        'is_request_for_quotation',
        'reference',
        'discount_type',
        'discount_amount',
        'total_amount',
        'attachment',
        'branch_id',
        'createdby',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'is_request_for_quotation' => 'boolean',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Purchase\PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createdby');
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(PurchaseQuotationItem::class, 'purchase_id');
    }

    // Alias expected by consumers
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseQuotationItem::class, 'purchase_id');
    }

    // Related purchase orders created from this quotation
    public function orders(): EloquentHasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'quotation_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getFormattedTotalAmountAttribute()
    {
        return number_format($this->total_amount, 2);
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

    /**
     * Get subtotal from items (sum of item totals before discount)
     */
    public function getSubtotalAttribute()
    {
        return $this->quotationItems->sum('total_amount');
    }

    /**
     * Get VAT amount from items
     */
    public function getVatAmountAttribute()
    {
        return $this->quotationItems->sum('tax_amount');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'bg-secondary',
            'sent' => 'bg-info',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'expired' => 'bg-warning',
        ];

        $badgeClass = $badges[$this->status] ?? 'bg-secondary';
        return "<span class='badge {$badgeClass}'>" . ucfirst($this->status) . "</span>";
    }

    // Hash ID helpers using Vinkla\Hashids
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::decode($value);
        if (!empty($decoded)) {
            return $this->findOrFail($decoded[0]);
        }
        return $this->findOrFail($value);
    }

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }
}
