<?php

namespace App\Models\Hotel;

use App\Traits\LogsActivity;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelExpense extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'property_id',
        'room_id',
        'expense_date',
        'category',
        'amount',
        'reference',
        'notes',
        'branch_id',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function room(): BelongsTo { return $this->belongsTo(Room::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}


