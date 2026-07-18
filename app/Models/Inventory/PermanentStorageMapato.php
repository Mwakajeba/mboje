<?php

namespace App\Models\Inventory;

use App\Models\Customer;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermanentStorageMapato extends Model
{
    use LogsActivity;

    protected $table = 'permanent_storage_mapato';

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'inventory_item_id',
        'sababu',
        'kiasi',
        'entry_date',
        'created_by',
    ];

    protected $casts = [
        'kiasi' => 'decimal:2',
        'entry_date' => 'date',
    ];

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
