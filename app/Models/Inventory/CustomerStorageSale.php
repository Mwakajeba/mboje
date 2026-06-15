<?php

namespace App\Models\Inventory;

use App\Models\Customer;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerStorageSale extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'inventory_item_id',
        'quantity',
        'price',
        'total',
        'withdrawal_id',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(CustomerStorageWithdrawal::class, 'withdrawal_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
