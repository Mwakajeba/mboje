<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashFlowLineItem extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cash_flow_line_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cash_flow_category_id',
        'name',
        'description',
        'sort_order',
        'is_subtotal',
        'is_total',
        'parent_id',
        'account_code_prefix',
        'transaction_types',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_subtotal' => 'boolean',
        'is_total' => 'boolean',
        'is_active' => 'boolean',
        'transaction_types' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the cash flow category that owns this line item.
     */
    public function cashFlowCategory(): BelongsTo
    {
        return $this->belongsTo(CashFlowCategory::class, 'cash_flow_category_id');
    }

    /**
     * Get the parent line item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CashFlowLineItem::class, 'parent_id');
    }

    /**
     * Get the child line items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(CashFlowLineItem::class, 'parent_id');
    }

    /**
     * Get the chart accounts linked to this line item.
     */
    public function chartAccounts(): HasMany
    {
        return $this->hasMany(ChartAccount::class, 'cash_flow_line_item_id');
    }

    /**
     * Scope to get only active line items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
