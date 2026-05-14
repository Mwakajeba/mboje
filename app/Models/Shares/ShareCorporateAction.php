<?php

namespace App\Models\Shares;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class ShareCorporateAction extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'share_corporate_actions';

    protected $fillable = [
        'company_id',
        'share_class_id',
        'action_type',
        'reference_number',
        'record_date',
        'ex_date',
        'effective_date',
        'ratio_numerator',
        'ratio_denominator',
        'price_per_share',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'executed_by',
        'updated_by',
    ];

    protected $casts = [
        'record_date' => 'date',
        'ex_date' => 'date',
        'effective_date' => 'date',
        'ratio_numerator' => 'decimal:6',
        'ratio_denominator' => 'decimal:6',
        'price_per_share' => 'decimal:6',
    ];

    /**
     * Get the encoded ID attribute.
     */
    public function getEncodedIdAttribute(): string
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the company that owns this corporate action.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the share class for this action.
     */
    public function shareClass(): BelongsTo
    {
        return $this->belongsTo(ShareClass::class);
    }

    /**
     * Get the user who created this action.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this action.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who executed this action.
     */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * Get all dividends linked to this corporate action.
     */
    public function dividends(): HasMany
    {
        return $this->hasMany(ShareDividend::class, 'corporate_action_id');
    }

    /**
     * Check if this action has been executed.
     */
    public function getIsExecutedAttribute(): bool
    {
        return $this->status === 'executed';
    }

    /**
     * Scope: Executed actions only.
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }
}

