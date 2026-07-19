<?php

namespace App\Models\Inventory;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermanentStorageBalance extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'inventory_item_id',
        'quantity_on_hand',
        'status',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:2',
    ];

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Inaendelea',
            self::STATUS_INACTIVE => 'Imeisha',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status ?? self::STATUS_ACTIVE] ?? 'Inaendelea';
    }

    public function isActive(): bool
    {
        return ($this->status ?? self::STATUS_ACTIVE) === self::STATUS_ACTIVE;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }
}
