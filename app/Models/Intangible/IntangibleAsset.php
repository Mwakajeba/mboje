<?php

namespace App\Models\Intangible;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IntangibleAsset extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id',
        'branch_id',
        'category_id',
        'code',
        'name',
        'source_type',
        'acquisition_date',
        'cost',
        'accumulated_amortisation',
        'accumulated_impairment',
        'nbv',
        'useful_life_months',
        'is_indefinite_life',
        'is_goodwill',
        'status',
        'description',
        'recognition_checks',
        'initial_journal_id',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'cost' => 'decimal:2',
        'accumulated_amortisation' => 'decimal:2',
        'accumulated_impairment' => 'decimal:2',
        'nbv' => 'decimal:2',
        'is_indefinite_life' => 'boolean',
        'is_goodwill' => 'boolean',
        'recognition_checks' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(IntangibleAssetCategory::class, 'category_id');
    }

    public function costComponents()
    {
        return $this->hasMany(IntangibleCostComponent::class);
    }

    public function amortisations()
    {
        return $this->hasMany(IntangibleAmortisation::class);
    }

    public function impairments()
    {
        return $this->hasMany(IntangibleImpairment::class);
    }

    public function disposals()
    {
        return $this->hasMany(IntangibleDisposal::class);
    }

    public function scopeForBranch($query, $branchId)
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function recalculateNbv(): void
    {
        $this->nbv = max(
            0,
            (float) $this->cost
                - (float) $this->accumulated_amortisation
                - (float) $this->accumulated_impairment
        );
    }
}


