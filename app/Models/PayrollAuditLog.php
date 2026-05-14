<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAuditLog extends Model
{
    protected $fillable = [
        'payroll_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'remarks',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by action
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted old value
     */
    public function getFormattedOldValueAttribute()
    {
        if (is_null($this->old_value)) {
            return null;
        }
        
        $decoded = json_decode($this->old_value, true);
        return $decoded !== null ? $decoded : $this->old_value;
    }

    /**
     * Get formatted new value
     */
    public function getFormattedNewValueAttribute()
    {
        if (is_null($this->new_value)) {
            return null;
        }
        
        $decoded = json_decode($this->new_value, true);
        return $decoded !== null ? $decoded : $this->new_value;
    }
}
