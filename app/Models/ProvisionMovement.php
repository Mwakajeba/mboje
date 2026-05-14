<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisionMovement extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'provision_movements';

    protected $fillable = [
        'provision_id',
        'movement_date',
        'movement_type',
        'description',
        'currency_code',
        'fx_rate',
        'foreign_amount',
        'home_amount',
        'balance_after_movement',
        'journal_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'fx_rate' => 'decimal:6',
        'foreign_amount' => 'decimal:2',
        'home_amount' => 'decimal:2',
        'balance_after_movement' => 'decimal:2',
    ];

    public function provision(): BelongsTo
    {
        return $this->belongsTo(Provision::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}


