<?php

namespace App\Models\RentalEventEquipment;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class RentalInvoice extends Model
{
    use LogsActivity;
    protected $table = 'rental_invoices';

    protected $fillable = [
        'invoice_number',
        'contract_id',
        'dispatch_id',
        'return_id',
        'damage_charge_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'rental_charges',
        'damage_charges',
        'loss_charges',
        'deposit_applied',
        'subtotal',
        'tax_amount',
        'total_amount',
        'notes',
        'status',
        'company_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'rental_charges' => 'decimal:2',
        'damage_charges' => 'decimal:2',
        'loss_charges' => 'decimal:2',
        'deposit_applied' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(RentalContract::class, 'contract_id');
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(RentalDispatch::class, 'dispatch_id');
    }

    public function return(): BelongsTo
    {
        return $this->belongsTo(RentalReturn::class, 'return_id');
    }

    public function damageCharge(): BelongsTo
    {
        return $this->belongsTo(RentalDamageCharge::class, 'damage_charge_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalInvoiceItem::class, 'invoice_id');
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
