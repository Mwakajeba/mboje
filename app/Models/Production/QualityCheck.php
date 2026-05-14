<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheck extends Model
{
    use LogsActivity;
    protected $fillable = [
        'work_order_id',
        'result',
        'defect_codes',
        'measurements',
        'seam_strength_ok',
        'logo_position_ok',
        'rework_notes',
        'inspector_id',
        'inspected_at',
    ];

    protected $casts = [
        'defect_codes' => 'array',
        'measurements' => 'array',
        'seam_strength_ok' => 'boolean',
        'logo_position_ok' => 'boolean',
        'inspected_at' => 'datetime',
    ];

    const RESULT_PASS = 'pass';
    const RESULT_FAIL = 'fail';
    const RESULT_REWORK_REQUIRED = 'rework_required';

    public static function getResults()
    {
        return [
            self::RESULT_PASS => 'Pass',
            self::RESULT_FAIL => 'Fail',
            self::RESULT_REWORK_REQUIRED => 'Rework Required',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'inspector_id');
    }

    public function getResultBadgeAttribute()
    {
        $badges = [
            self::RESULT_PASS => 'bg-success',
            self::RESULT_FAIL => 'bg-danger',
            self::RESULT_REWORK_REQUIRED => 'bg-warning',
        ];

        $badgeClass = $badges[$this->result] ?? 'bg-secondary';
        $resultLabel = self::getResults()[$this->result] ?? $this->result;
        
        return '<span class="badge ' . $badgeClass . '">' . $resultLabel . '</span>';
    }

    public function getDefectCodesStringAttribute()
    {
        return $this->defect_codes ? implode(', ', $this->defect_codes) : 'None';
    }
}
