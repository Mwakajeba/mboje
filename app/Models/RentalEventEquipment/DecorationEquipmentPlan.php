<?php

namespace App\Models\RentalEventEquipment;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class DecorationEquipmentPlan extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'decoration_equipment_plans';

    protected $fillable = [
        'decoration_job_id',
        'status',
        'notes',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(DecorationJob::class, 'decoration_job_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DecorationEquipmentPlanItem::class, 'plan_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        });
    }

    public function getRouteKey()
    {
        return Hashids::encode($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $reserved = ['create', 'edit', 'data', 'index'];
        if (in_array($value, $reserved)) {
            return null;
        }

        try {
            $decoded = Hashids::decode($value);
            $id = $decoded[0] ?? null;

            if ($id === null) {
                return null;
            }

            return $this->where('id', $id)->firstOrFail();
        } catch (\Exception $e) {
            return null;
        }
    }
}

