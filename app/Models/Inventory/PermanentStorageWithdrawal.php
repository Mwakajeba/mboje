<?php

namespace App\Models\Inventory;

use App\Models\Customer;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermanentStorageWithdrawal extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'inventory_item_id',
        'quantity',
        'reason',
        'notes',
        'withdrawn_date',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'withdrawn_date' => 'date',
    ];

    /** @return array<string, string> */
    public static function reasonOptions(): array
    {
        return [
            'kukoboa' => 'Kukoboa',
            'kuuza' => 'Kuuza',
            'kuhamisha' => 'Kuhamisha',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'inventory_item_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
