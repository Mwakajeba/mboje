<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalLevel extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'module',
        'level',
        'level_name',
        'is_required',
        'approval_order',
        'company_id',
    ];

    protected $casts = [
        'level' => 'integer',
        'is_required' => 'boolean',
        'approval_order' => 'integer',
        'company_id' => 'integer',
    ];

    /**
     * Get the company that owns this approval level.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the assignments for this approval level.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ApprovalLevelAssignment::class);
    }

    /**
     * Get the approval histories for this level.
     */
    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter required levels only.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope to order by approval order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('approval_order');
    }

    /**
     * Get the next approval level.
     */
    public function getNextLevel(): ?self
    {
        return static::where('module', $this->module)
            ->where('company_id', $this->company_id)
            ->where('approval_order', '>', $this->approval_order)
            ->where('is_required', true)
            ->orderBy('approval_order')
            ->first();
    }

    /**
     * Get the previous approval level.
     */
    public function getPreviousLevel(): ?self
    {
        return static::where('module', $this->module)
            ->where('company_id', $this->company_id)
            ->where('approval_order', '<', $this->approval_order)
            ->where('is_required', true)
            ->orderByDesc('approval_order')
            ->first();
    }

    /**
     * Check if this is the first level.
     */
    public function isFirstLevel(): bool
    {
        return $this->getPreviousLevel() === null;
    }

    /**
     * Check if this is the last level.
     */
    public function isLastLevel(): bool
    {
        return $this->getNextLevel() === null;
    }
}
