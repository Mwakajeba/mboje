<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'reference', 'company_id', 'branch_id', 'from_location_id', 'to_location_id',
        'status', 'reason', 'notes', 'requested_by', 'approved_by', 'approved_at', 'approval_notes'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(TransferRequestItem::class);
    }
}
