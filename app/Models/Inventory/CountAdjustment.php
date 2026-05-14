<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountAdjustment extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_count_adjustments';

    protected $fillable = [
        'count_session_id',
        'variance_id',
        'item_id',
        'inventory_location_id',
        'adjustment_number',
        'adjustment_quantity',
        'adjustment_value',
        'adjustment_type',
        'reason_code',
        'reason_description',
        'supporting_documents',
        'supervisor_comments',
        'finance_comments',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'journal_id',
        'movement_id',
    ];

    protected $casts = [
        'adjustment_quantity' => 'decimal:2',
        'adjustment_value' => 'decimal:2',
        'supporting_documents' => 'array',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function session()
    {
        return $this->belongsTo(CountSession::class, 'count_session_id');
    }

    public function variance()
    {
        return $this->belongsTo(CountVariance::class, 'variance_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'inventory_location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    public function journal()
    {
        return $this->belongsTo(\App\Models\Journal::class);
    }

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }

    public function approvals()
    {
        return $this->hasMany(CountAdjustmentApproval::class, 'count_adjustment_id');
    }

    /**
     * Get the current approval level (next pending approval)
     */
    public function getCurrentApprovalLevel()
    {
        // Ensure session is loaded
        if (!$this->relationLoaded('session')) {
            $this->load('session');
        }
        
        // Get configured approval levels from settings
        $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($this->session->company_id);
        $configuredLevels = (int) ($approvalSettings->approval_levels ?? 1);
        
        // Only consider approvals up to configured levels
        $pendingApproval = $this->approvals()
            ->where('status', 'pending')
            ->where('approval_level', '<=', $configuredLevels)
            ->orderBy('approval_level')
            ->first();

        return $pendingApproval ? $pendingApproval->approval_level : null;
    }

    /**
     * Check if all approvals are complete
     */
    public function isFullyApproved()
    {
        // Ensure session is loaded
        if (!$this->relationLoaded('session')) {
            $this->load('session');
        }
        
        // Get configured approval levels from settings
        $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany($this->session->company_id);
        $configuredLevels = (int) ($approvalSettings->approval_levels ?? 1);
        
        // Only check approvals up to configured levels
        $relevantApprovals = $this->approvals()->where('approval_level', '<=', $configuredLevels)->get();
        $totalLevels = $relevantApprovals->count();
        $approvedLevels = $relevantApprovals->where('status', 'approved')->count();
        
        return $totalLevels > 0 && $approvedLevels === $totalLevels;
    }

    /**
     * Check if any approval was rejected
     */
    public function hasRejection()
    {
        return $this->approvals()->where('status', 'rejected')->exists();
    }
}
