<?php

namespace App\Models\Production;

use App\Models\InventoryLocation;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vinkla\Hashids\Facades\Hashids;

class WorkOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'wo_number',
        'customer_id',
        'product_name',
        'style',
        'sizes_quantities',
        'due_date',
        'start_date',
        'completion_date',
        'status',
        'requires_logo',
        'notes',
        'inventory_location_id',
        'require_knitting',
        'work_order_type',
        'created_by',
    ];

    protected $casts = [
        'sizes_quantities' => 'array',
        'inventory_location_id' => 'integer',
        'due_date' => 'date',
        'start_date' => 'date',
        'completion_date' => 'date',
        'requires_logo' => 'boolean',
    ];

    // Status constants
    const STATUS_PLANNED = 'PLANNED';
    const STATUS_MATERIAL_ISSUED = 'MATERIAL_ISSUED';
    const STATUS_KNITTING = 'KNITTING';
    const STATUS_CUTTING = 'CUTTING';
    const STATUS_JOINING = 'JOINING';
    const STATUS_EMBROIDERY = 'EMBROIDERY';
    const STATUS_IRONING_FINISHING = 'IRONING_FINISHING';
    const STATUS_QC = 'QC';
    const STATUS_PACKAGING = 'PACKAGING';
    const STATUS_DISPATCHED = 'DISPATCHED';
    const STATUS_CANCELLED = 'CANCELLED';

    public static function getStatuses()
    {
        return [
            self::STATUS_PLANNED => 'Planning',
            self::STATUS_MATERIAL_ISSUED => 'Material Issued',
            self::STATUS_KNITTING => 'Knitting',
            self::STATUS_CUTTING => 'Cutting',
            self::STATUS_JOINING => 'Joining/Stitching',
            self::STATUS_EMBROIDERY => 'Embroidery',
            self::STATUS_IRONING_FINISHING => 'Ironing/Finishing',
            self::STATUS_QC => 'Quality Check',
            self::STATUS_PACKAGING => 'Packaging',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_PLANNED => 'bg-secondary',
            self::STATUS_MATERIAL_ISSUED => 'bg-info',
            self::STATUS_KNITTING => 'bg-primary',
            self::STATUS_CUTTING => 'bg-warning',
            self::STATUS_JOINING => 'bg-warning',
            self::STATUS_EMBROIDERY => 'bg-info',
            self::STATUS_IRONING_FINISHING => 'bg-warning',
            self::STATUS_QC => 'bg-info',
            self::STATUS_PACKAGING => 'bg-primary',
            self::STATUS_DISPATCHED => 'bg-success',
            self::STATUS_CANCELLED => 'bg-danger',
        ];

        $badgeClass = $badges[$this->status] ?? 'bg-secondary';
        $statusLabel = self::getStatuses()[$this->status] ?? $this->status;

        return '<span class="badge ' . $badgeClass . '">' . $statusLabel . '</span>';
    }

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getTotalQuantityAttribute()
    {
        if (!$this->sizes_quantities) {
            return 0;
        }

        // Handle both string (JSON) and array formats
        $quantities = $this->sizes_quantities;
        if (is_string($quantities)) {
            $quantities = json_decode($quantities, true) ?? [];
        }

        return collect($quantities)->sum(function ($quantity) {
            return is_numeric($quantity) ? (int) $quantity : 0;
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function bom(): HasMany
    {
        return $this->hasMany(WorkOrderBom::class);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(WorkOrderProcess::class);
    }

    public function materialIssues(): HasMany
    {
        return $this->hasMany(MaterialIssue::class);
    }

    public function productionRecords(): HasMany
    {
        return $this->hasMany(ProductionRecord::class);
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class);
    }

    public function packagingRecords(): HasMany
    {
        return $this->hasMany(PackagingRecord::class);
    }

    // inventory location
    /**
     * Get the associated inventory location for the work order.
     * Only applies when work_order_type is 'inventory_location'.
     */
    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class);
    }



    // Methods
    public function canAdvanceToNextStage()
    {
        $currentStage = $this->status;
        $nextStages = [
            self::STATUS_PLANNED => self::STATUS_MATERIAL_ISSUED,
            self::STATUS_MATERIAL_ISSUED => self::STATUS_KNITTING,
            self::STATUS_KNITTING => self::STATUS_CUTTING,
            self::STATUS_CUTTING => self::STATUS_JOINING,
            self::STATUS_JOINING => $this->requires_logo ? self::STATUS_EMBROIDERY : self::STATUS_IRONING_FINISHING,
            self::STATUS_EMBROIDERY => self::STATUS_IRONING_FINISHING,
            self::STATUS_IRONING_FINISHING => self::STATUS_QC,
            self::STATUS_QC => self::STATUS_PACKAGING,
            self::STATUS_PACKAGING => self::STATUS_DISPATCHED,
        ];

        return isset($nextStages[$currentStage]);
    }

    public function getNextStage()
    {
        $nextStages = [
            self::STATUS_PLANNED => self::STATUS_MATERIAL_ISSUED,
            self::STATUS_MATERIAL_ISSUED => self::STATUS_KNITTING,
            self::STATUS_KNITTING => self::STATUS_CUTTING,
            self::STATUS_CUTTING => self::STATUS_JOINING,
            self::STATUS_JOINING => $this->requires_logo ? self::STATUS_EMBROIDERY : self::STATUS_IRONING_FINISHING,
            self::STATUS_EMBROIDERY => self::STATUS_IRONING_FINISHING,
            self::STATUS_IRONING_FINISHING => self::STATUS_QC,
            self::STATUS_QC => self::STATUS_PACKAGING,
            self::STATUS_PACKAGING => self::STATUS_DISPATCHED,
        ];

        return $nextStages[$this->status] ?? null;
    }

    public function getCurrentProcess()
    {
        return $this->processes()->where('process_stage', $this->status)->first();
    }

    public function getProgressPercentage()
    {
        $statuses = array_keys(self::getStatuses());
        $currentIndex = array_search($this->status, $statuses);
        $totalStages = count($statuses) - 1; // Exclude cancelled

        if ($currentIndex === false || $this->status === self::STATUS_CANCELLED) {
            return 0;
        }

        return round(($currentIndex / $totalStages) * 100);
    }
}
