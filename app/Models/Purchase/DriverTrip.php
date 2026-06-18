<?php

namespace App\Models\Purchase;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverTrip extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'trip_name',
        'driver_name',
        'vehicle_info',
        'trip_price',
        'trip_date',
        'user_id',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'trip_price' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mapatoRecords(): HasMany
    {
        return $this->hasMany(DriverTripMapatoRecord::class);
    }

    public function matumiziRecords(): HasMany
    {
        return $this->hasMany(DriverTripMatumiziRecord::class);
    }

    public function scopeForCompanyBranch($query, int $companyId, ?int $branchId)
    {
        $query->where('company_id', $companyId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
