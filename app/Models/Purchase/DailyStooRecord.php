<?php

namespace App\Models\Purchase;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyStooRecord extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'entry_date',
        'bidhaa',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DailyStooLine::class)->orderBy('sort_order')->orderBy('id');
    }

    public function totalThamani(): float
    {
        return (float) $this->lines->sum('thamani');
    }
}
