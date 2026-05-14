<?php

namespace App\Models\Production;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductionMachine extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'machine_name',
        'purchased_date',
        'status',
        'location',
        'production_stage',
        'gauge'
    ];

    protected $casts = [
        'purchased_date' => 'date',
    ];

    /**
     * Get the badge for the production stage
     */
    public function getStageBadgeAttribute()
    {
        if (!$this->production_stage) {
            return '<span class="badge bg-secondary">No Stage</span>';
        }

        $badges = [
            'KNITTING' => '<span class="badge bg-primary">Knitting</span>',
            'CUTTING' => '<span class="badge bg-warning">Cutting</span>',
            'JOINING' => '<span class="badge bg-info">Joining</span>',
            'EMBROIDERY' => '<span class="badge bg-success">Embroidery</span>',
            'IRONING_FINISHING' => '<span class="badge bg-secondary">Ironing/Finishing</span>',
            'PACKAGING' => '<span class="badge bg-dark">Packaging</span>',
        ];

        return $badges[$this->production_stage] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get the formatted gauge display
     */
    public function getFormattedGaugeAttribute()
    {
        if (!$this->gauge) {
            return 'N/A';
        }
        
        return $this->gauge;
    }

    /**
     * Scope to filter by production stage
     */
    public function scopeByProductionStage($query, $stage)
    {
        return $query->where('production_stage', $stage);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by location
     */
    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', '%' . $location . '%');
    }

    /**
     * Get available production stages
     */
    public static function getProductionStages()
    {
        return [
            'KNITTING' => 'Knitting',
            'CUTTING' => 'Cutting',
            'JOINING' => 'Joining',
            'EMBROIDERY' => 'Embroidery',
            'IRONING_FINISHING' => 'Ironing/Finishing',
            'PACKAGING' => 'Packaging',
        ];
    }

    /**
     * Get available statuses
     */
    public static function getStatuses()
    {
        return [
            'new' => 'New',
            'used' => 'Used',
        ];
    }

    /**
     * Check if machine requires gauge (for knitting machines)
     */
    public function requiresGauge()
    {
        return $this->production_stage === 'KNITTING';
    }

    /**
     * Get machines for a specific production stage
     */
    public static function getForStage($stage)
    {
        return static::where('production_stage', $stage)->get();
    }
}