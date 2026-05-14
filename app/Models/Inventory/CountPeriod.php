<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountPeriod extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_count_periods';

    protected $fillable = [
        'company_id',
        'branch_id',
        'period_name',
        'count_type',
        'frequency',
        'count_start_date',
        'count_end_date',
        'inventory_location_id',
        'responsible_staff_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'count_start_date' => 'date',
        'count_end_date' => 'date',
    ];

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'inventory_location_id');
    }

    public function responsibleStaff()
    {
        return $this->belongsTo(\App\Models\User::class, 'responsible_staff_id');
    }

    public function sessions()
    {
        return $this->hasMany(CountSession::class, 'count_period_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
