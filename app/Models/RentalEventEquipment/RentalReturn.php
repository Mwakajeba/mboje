<?php

namespace App\Models\RentalEventEquipment;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class RentalReturn extends Model
{
    use LogsActivity;
    protected $table = 'rental_returns';

    protected $fillable = [
        'return_number',
        'dispatch_id',
        'contract_id',
        'customer_id',
        'return_date',
        'notes',
        'status',
        'company_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(RentalDispatch::class, 'dispatch_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalReturnItem::class, 'return_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (in_array($value, ['create', 'edit', 'data', 'index'])) {
            return null;
        }
        $decoded = Hashids::decode($value);
        $id = $decoded[0] ?? null;
        return $id ? static::find($id) : null;
    }
}
