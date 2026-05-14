<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetReallocation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'budget_id',
        'from_account_id',
        'to_account_id',
        'amount',
        'reason',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the budget that owns this reallocation.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * Get the source account (from account).
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'from_account_id');
    }

    /**
     * Get the destination account (to account).
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(ChartAccount::class, 'to_account_id');
    }

    /**
     * Get the user who performed the reallocation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the formatted amount attribute.
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }
}
