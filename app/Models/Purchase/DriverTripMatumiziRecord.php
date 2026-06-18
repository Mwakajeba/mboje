<?php

namespace App\Models\Purchase;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverTripMatumiziRecord extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'driver_trip_id',
        'entry_date',
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(DriverTrip::class, 'driver_trip_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DriverTripMatumiziLine::class)->orderBy('sort_order')->orderBy('id');
    }
}
