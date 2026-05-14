<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRecord extends Model
{
    use LogsActivity;
    protected $fillable = [
        'work_order_id',
        'stage',
        'input_materials',
        'output_data',
        'wastage_data',
        'yield_percentage',
        'operator_id',
        'machine_id',
        'operator_time_minutes',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'input_materials' => 'array',
        'output_data' => 'array',
        'wastage_data' => 'array',
        'yield_percentage' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

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

    public function getStageBadgeAttribute()
    {
        $badges = [
            'KNITTING' => 'bg-primary',
            'CUTTING' => 'bg-warning',
            'JOINING' => 'bg-info',
            'EMBROIDERY' => 'bg-purple',
            'IRONING_FINISHING' => 'bg-secondary',
            'QC' => 'bg-success',
            'PACKAGING' => 'bg-dark',
        ];

        $badgeClass = $badges[$this->stage] ?? 'bg-secondary';
        $stageLabel = ucfirst(str_replace('_', ' ', $this->stage));
        
        return '<span class="badge ' . $badgeClass . '">' . $stageLabel . '</span>';
    }

    public function getFormattedOperatorTimeAttribute()
    {
        if (!$this->operator_time_minutes) {
            return 'N/A';
        }

        $hours = floor($this->operator_time_minutes / 60);
        $minutes = $this->operator_time_minutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm';
        }

        return $minutes . 'm';
    }
}