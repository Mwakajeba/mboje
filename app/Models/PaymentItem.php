<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'payment_id',
        'chart_account_id',
        'amount',
        'wht_treatment',
        'wht_rate',
        'wht_amount',
        'base_amount',
        'net_payable',
        'total_cost',
        'vat_mode',
        'vat_amount',
        'description',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function chartAccount()
    {
        return $this->belongsTo(ChartAccount::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }
}
