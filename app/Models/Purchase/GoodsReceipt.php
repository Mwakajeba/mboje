<?php

namespace App\Models\Purchase;

use App\Helpers\AmountInWords;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Warehouse;
use Vinkla\Hashids\Facades\Hashids;

class GoodsReceipt extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'purchase_order_id', 'grn_number', 'receipt_date', 'received_by',
        'total_quantity', 'total_amount', 'notes', 'status',
        'quality_check_status', 'quality_check_by', 'quality_check_date',
        'warehouse_id', 'company_id', 'branch_id'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'quality_check_date' => 'datetime',
        'total_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function qualityCheckedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'quality_check_by');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    // Helper Methods
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // Decode when binding by hash_id (or default)
        if ($field === 'hash_id' || $field === null) {
            $decoded = Hashids::decode($value);
            if (!empty($decoded)) {
                return static::find($decoded[0]);
            }
        }
        // Fallback to id lookup
        return static::find($value);
    }
    public function generateGRNNumber(): string
    {
        $prefix = 'GRN';
        $year = date('Y');
        $lastGRN = self::where('grn_number', 'like', $prefix . '-' . $year . '-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastGRN) {
            $lastNumber = intval(substr($lastGRN->grn_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'draft' => 'bg-secondary',
            'received' => 'bg-info',
            'quality_checked' => 'bg-warning',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    public function getQualityStatusBadgeClass(): string
    {
        return match($this->quality_check_status) {
            'pending' => 'bg-warning',
            'passed' => 'bg-success',
            'failed' => 'bg-danger',
            'partial' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    /**
     * Convert total_amount to words using shared helper.
     */
    public function getAmountInWords()
    {
        return AmountInWords::convert($this->total_amount);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
        $this->purchaseOrder?->updateReceiptStatus();
        $this->updateInventory();
    }

    private function updateInventory(): void
    {
        foreach ($this->items as $item) {
            $inventoryItem = optional($item->purchaseOrderItem)->inventoryItem;
            if ($inventoryItem) {
                // Stock is now tracked via movements, no need to update item directly
            }
        }
    }

    // Boot: auto-generate GRN number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (empty($receipt->grn_number)) {
                $receipt->grn_number = $receipt->generateGRNNumber();
            }
        });
    }
}


