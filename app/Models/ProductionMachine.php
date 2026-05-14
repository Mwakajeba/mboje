<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionMachine extends Model
{
    protected $fillable = [
        'machine_name',
        'purchased_date',
        'status',
        'location',
        'production_stage',
        'gauge',
    ];

    protected $casts = [
        'purchased_date' => 'date',
    ];

    const STAGE_KNITTING = 'KNITTING';
    const STAGE_CUTTING = 'CUTTING';
    const STAGE_JOINING = 'JOINING';
    const STAGE_EMBROIDERY = 'EMBROIDERY';
    const STAGE_IRONING_FINISHING = 'IRONING_FINISHING';
    const STAGE_PACKAGING = 'PACKAGING';

    public static function getProductionStages()
    {
        return [
            self::STAGE_KNITTING => 'Knitting',
            self::STAGE_CUTTING => 'Cutting',
            self::STAGE_JOINING => 'Joining/Stitching',
            self::STAGE_EMBROIDERY => 'Embroidery',
            self::STAGE_IRONING_FINISHING => 'Ironing/Finishing',
            self::STAGE_PACKAGING => 'Packaging',
        ];
    }

    public function processes(): HasMany
    {
        return $this->hasMany(\App\Models\Production\WorkOrderProcess::class, 'machine_id');
    }

    public function productionRecords(): HasMany
    {
        return $this->hasMany(\App\Models\Production\ProductionRecord::class, 'machine_id');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'active' => 'bg-success',
            'maintenance' => 'bg-warning',
            'inactive' => 'bg-danger',
        ];

        $badgeClass = $badges[$this->status] ?? 'bg-secondary';
        
        return '<span class="badge ' . $badgeClass . '">' . ucfirst($this->status) . '</span>';
    }

    public function getStageBadgeAttribute()
    {
        if (!$this->production_stage) {
            return '<span class="badge bg-secondary">No Stage</span>';
        }

        $badges = [
            self::STAGE_KNITTING => 'bg-primary',
            self::STAGE_CUTTING => 'bg-warning',
            self::STAGE_JOINING => 'bg-info',
            self::STAGE_EMBROIDERY => 'bg-purple',
            self::STAGE_IRONING_FINISHING => 'bg-secondary',
            self::STAGE_PACKAGING => 'bg-dark',
        ];

        $badgeClass = $badges[$this->production_stage] ?? 'bg-secondary';
        $stageLabel = self::getProductionStages()[$this->production_stage] ?? $this->production_stage;
        
        return '<span class="badge ' . $badgeClass . '">' . $stageLabel . '</span>';
    }
}
