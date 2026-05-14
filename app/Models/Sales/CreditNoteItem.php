<?php

namespace App\Models\Sales;

use App\Models\Inventory\Item as InventoryItem;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'credit_note_id',
        'sales_invoice_item_id',
        'linked_invoice_line_id',
        'inventory_item_id',
        'warehouse_id',
        'item_name',
        'item_code',
        'description',
        'unit_of_measure',
        'quantity',
        'original_quantity',
        'unit_price',
        'original_unit_price',
        'line_total',
        'cogs_cost_at_sale',
        'current_avg_cost',
        'return_to_stock',
        'return_condition',
        'is_replacement',
        'revenue_account_id',
        'cogs_account_id',
        'vat_type',
        'vat_rate',
        'vat_amount',
        'tax_code',
        'tax_calculation_details',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'restocking_fee_amount',
        'restocking_fee_vat',
        'exchange_rate',
        'fx_gain_loss',
        'available_stock',
        'reserved_stock',
        'stock_available',
        'notes',
        'item_condition_notes',
        'posting_details',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'original_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'original_unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cogs_cost_at_sale' => 'decimal:2',
        'current_avg_cost' => 'decimal:2',
        'return_to_stock' => 'boolean',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'tax_calculation_details' => 'array',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'restocking_fee_amount' => 'decimal:2',
        'restocking_fee_vat' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'fx_gain_loss' => 'decimal:2',
        'available_stock' => 'decimal:2',
        'reserved_stock' => 'decimal:2',
        'stock_available' => 'boolean',
        'posting_details' => 'array',
    ];

    protected $dates = ['deleted_at'];

    /**
     * Relationships
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function salesInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function linkedInvoiceLine(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class, 'linked_invoice_line_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'warehouse_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'revenue_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'cogs_account_id');
    }

    /**
     * Accessors
     */
    public function getVatTypeTextAttribute()
    {
        return ucfirst($this->vat_type);
    }

    public function getDiscountTypeTextAttribute()
    {
        return ucfirst($this->discount_type ?? 'none');
    }

    public function getStockStatusAttribute()
    {
        if ($this->stock_available) {
            return '<span class="badge bg-success">In Stock</span>';
        } else {
            return '<span class="badge bg-danger">Out of Stock</span>';
        }
    }

    public function getReturnConditionBadgeAttribute()
    {
        $badges = [
            'resellable' => '<span class="badge bg-success">Resellable</span>',
            'damaged' => '<span class="badge bg-warning">Damaged</span>',
            'scrap' => '<span class="badge bg-danger">Scrap</span>',
            'refurbish' => '<span class="badge bg-info">Refurbish</span>',
        ];

        return $badges[$this->return_condition] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    public function getReturnToStockBadgeAttribute()
    {
        return $this->return_to_stock 
            ? '<span class="badge bg-success">Return to Stock</span>'
            : '<span class="badge bg-secondary">No Return</span>';
    }

    /**
     * Calculate line total
     */
    public function calculateLineTotal()
    {
        $lineTotal = $this->quantity * $this->unit_price;
        
        // Apply discount
        if ($this->discount_type === 'percentage') {
            $discountAmount = $lineTotal * ($this->discount_rate / 100);
        } elseif ($this->discount_type === 'fixed') {
            $discountAmount = $this->discount_rate;
        } else {
            $discountAmount = 0;
        }
        
        $this->discount_amount = $discountAmount;
        $lineTotal -= $discountAmount;
        
        // Calculate VAT
        if ($this->vat_type === 'exclusive') {
            $this->vat_amount = $lineTotal * ($this->vat_rate / 100);
            $lineTotal += $this->vat_amount;
        } else {
            // VAT inclusive - extract VAT from total
            $this->vat_amount = $lineTotal * ($this->vat_rate / (100 + $this->vat_rate));
        }
        
        $this->line_total = $lineTotal;
        return $this;
    }

    /**
     * Update stock information
     */
    public function updateStockInfo()
    {
        if ($this->inventoryItem) {
            $this->available_stock = $this->inventoryItem->current_stock;
            $this->reserved_stock = $this->inventoryItem->reserved_stock ?? 0;
            $this->stock_available = $this->available_stock > 0;
        }
        
        return $this;
    }
}
