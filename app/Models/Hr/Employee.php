<?php

namespace App\Models\Hr;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Purchase\DailyManunuziRecord;
use App\Models\Purchase\DailyMatumiziRecord;
use App\Models\Purchase\DailyMauzoRecord;
use App\Models\Purchase\DailyStooRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'hr_employees';

    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'status',
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

    public function dailyMauzoRecords(): HasMany
    {
        return $this->hasMany(DailyMauzoRecord::class);
    }

    public function dailyMatumiziRecords(): HasMany
    {
        return $this->hasMany(DailyMatumiziRecord::class);
    }

    public function dailyManunuziRecords(): HasMany
    {
        return $this->hasMany(DailyManunuziRecord::class);
    }

    public function dailyStooRecords(): HasMany
    {
        return $this->hasMany(DailyStooRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->join(' '));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCompanyBranch($query, int $companyId, ?int $branchId)
    {
        $query->where('company_id', $companyId);

        if ($branchId && Schema::hasColumn($this->getTable(), 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query;
    }
}
