<?php

namespace App\Models\Purchase;

use App\Traits\LogsActivity;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'supplier_opening_balances';

    protected $fillable = [
        'supplier_id',
        'branch_id',
        'company_id',
        'opening_date',
        'currency',
        'exchange_rate',
        'amount',
        'paid_amount',
        'balance_due',
        'status',
        'reference',
        'notes',
        'purchase_invoice_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function invoice() { return $this->belongsTo(\App\Models\Purchase\PurchaseInvoice::class, 'purchase_invoice_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function getEncodedIdAttribute()
    {
        return \Vinkla\Hashids\Facades\Hashids::encode($this->id);
    }
}
