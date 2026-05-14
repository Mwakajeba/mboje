<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountSession extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_count_sessions';

    protected $fillable = [
        'count_period_id',
        'company_id',
        'inventory_location_id',
        'session_number',
        'snapshot_date',
        'count_start_time',
        'count_end_time',
        'status',
        'is_blind_count',
        'created_by',
        'supervisor_id',
        'notes',
    ];

    protected $casts = [
        'snapshot_date' => 'datetime',
        'count_start_time' => 'datetime',
        'count_end_time' => 'datetime',
        'is_blind_count' => 'boolean',
    ];

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function period()
    {
        return $this->belongsTo(CountPeriod::class, 'count_period_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'inventory_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\User::class, 'supervisor_id');
    }

    public function entries()
    {
        return $this->hasMany(CountEntry::class, 'count_session_id');
    }

    public function teams()
    {
        return $this->hasMany(CountTeam::class, 'count_session_id');
    }

    public function adjustments()
    {
        return $this->hasMany(CountAdjustment::class, 'count_session_id');
    }

    public function approval()
    {
        return $this->hasOne(CountSessionApproval::class, 'count_session_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Check if session is approved
     */
    public function isApproved()
    {
        return $this->approval && $this->approval->isApproved();
    }

    /**
     * Check if session is pending approval
     */
    public function isPendingApproval()
    {
        return $this->status === 'completed' && (!$this->approval || $this->approval->isPending());
    }

    /**
     * Check if session is rejected
     */
    public function isRejected()
    {
        return $this->approval && $this->approval->isRejected();
    }
}
