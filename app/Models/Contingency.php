<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class Contingency extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'contingencies';

    protected $fillable = [
        'contingency_number',
        'contingency_type',
        'title',
        'description',
        'provision_id',
        'probability',
        'probability_percent',
        'currency_code',
        'fx_rate_at_creation',
        'expected_amount',
        'status',
        'resolution_outcome',
        'resolution_date',
        'resolution_notes',
        'company_id',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fx_rate_at_creation' => 'decimal:6',
        'expected_amount' => 'decimal:2',
        'probability_percent' => 'decimal:2',
        'resolution_date' => 'date',
    ];

    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function provision(): BelongsTo
    {
        return $this->belongsTo(Provision::class);
    }
}


