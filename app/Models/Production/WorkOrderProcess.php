<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderProcess extends Model
{
    use LogsActivity;
    protected $fillable = [
        'work_order_id',
        'process_stage',
        'started_at',
        'completed_at',
        'status',
        'operator_id',
        'machine_id',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REWORK_REQUIRED = 'rework_required';

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'operator_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(ProductionMachine::class, 'machine_id');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_PENDING => 'bg-secondary',
            self::STATUS_IN_PROGRESS => 'bg-warning',
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_REWORK_REQUIRED => 'bg-danger',
        ];

        $badgeClass = $badges[$this->status] ?? 'bg-secondary';
        
        return '<span class="badge ' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>';
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMinutes($this->completed_at);
        }
        return null;
    }
}