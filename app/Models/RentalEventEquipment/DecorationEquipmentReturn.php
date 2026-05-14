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

class DecorationEquipmentReturn extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'decoration_equipment_returns';

    protected $fillable = [
        'return_number',
        'issue_id',
        'decoration_job_id',
        'return_date',
        'notes',
        'status',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(DecorationEquipmentIssue::class, 'issue_id');
    }

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

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DecorationEquipmentReturnItem::class, 'return_id');
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

    /**
     * Get the route key for the model.
     */
    public function getRouteKey()
    {
        return Hashids::encode($this->getKey());
    }

    /**
     * Retrieve the model for a bound value.
     */
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

