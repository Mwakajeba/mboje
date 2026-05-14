<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferRequestItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'transfer_request_id', 'item_id', 'quantity', 'unit_cost', 'total_cost'
    ];

    public function request()
    {
        return $this->belongsTo(TransferRequest::class, 'transfer_request_id');
    }
}
