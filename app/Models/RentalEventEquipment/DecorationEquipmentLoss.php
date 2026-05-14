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

class DecorationEquipmentLoss extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'decoration_equipment_losses';

    protected $fillable = [
        'loss_number',
        'decoration_job_id',
        'equipment_id',
        'responsible_employee_id',
        'loss_type',
        'quantity_lost',
        'loss_date',
        'reason',
        'status',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'loss_date' => 'date',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(DecorationJob::class, 'decoration_job_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DecorationEquipmentLossItem::class, 'loss_id');
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_employee_id');
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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

