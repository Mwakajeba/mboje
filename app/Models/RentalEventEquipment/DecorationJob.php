<?php

namespace App\Models\RentalEventEquipment;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class DecorationJob extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'decoration_jobs';

    protected $fillable = [
        'job_number',
        'customer_id',
        'event_date',
        'event_location',
        'event_theme',
        'package_name',
        'service_description',
        'agreed_price',
        'status',
        'notes',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'agreed_price' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
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
