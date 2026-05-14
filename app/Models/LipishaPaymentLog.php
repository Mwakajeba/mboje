<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LipishaPaymentLog extends Model
{
    use HasFactory;

    protected $table = 'lipisha_payment_logs';

    protected $fillable = [
        'bill_number',
        'amount',
        'receipt',
        'transaction_ref',
        'transaction_date',
        'bill_id',
        'payment_id',
        'metadata',
        'raw_payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'metadata' => 'array',
    ];
}












