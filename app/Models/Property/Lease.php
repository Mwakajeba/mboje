<?php

namespace App\Models\Property;

use App\Models\Hotel\Property;
use App\Models\Hotel\Room;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Lease extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'lease_number',
        'property_id',
        'room_id',
        'tenant_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'security_deposit',
        'paid_deposit',
        'deposit_balance',
        'late_fee_amount',
        'late_fee_grace_days',
        'rent_due_day',
        'status',
        'payment_status',
        'terms_conditions',
        'special_conditions',
        'termination_date',
        'termination_reason',
        'termination_fee',
        'notes',
        'branch_id',
        'company_id',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'paid_deposit' => 'decimal:2',
        'deposit_balance' => 'decimal:2',
        'late_fee_amount' => 'decimal:2',
        'termination_fee' => 'decimal:2',
        'termination_date' => 'date'
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
                    ->where('end_date', '<=', now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('payment_status', 'overdue');
    }

    public function scopeCurrent($query)
    {
        return $query->where('status', 'active')
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    // Accessors
    public function getDurationInMonthsAttribute()
    {
        return $this->start_date->diffInMonths($this->end_date);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    public function getIsExpiredAttribute()
    {
        return $this->end_date < now();
    }

    public function getIsExpiringSoonAttribute()
    {
        return $this->is_active && $this->end_date <= now()->addDays(30);
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->is_active) return 0;
        
        return now()->diffInDays($this->end_date);
    }

    public function getTotalRentAmountAttribute()
    {
        return $this->monthly_rent * $this->duration_in_months;
    }

    public function getRentalUnitAttribute()
    {
        if ($this->room) {
            return $this->room->full_room_name;
        }
        return $this->property->name;
    }

    // Methods
    public function generateLeaseNumber()
    {
        $lastLease = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastLease ? $lastLease->id + 1 : 1;
        return 'LEASE' . now()->format('Y') . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateDepositBalance()
    {
        return $this->security_deposit - $this->paid_deposit;
    }

    public function getNextRentDueDate()
    {
        if (!$this->is_active) return null;
        
        $currentMonth = now()->startOfMonth();
        $dueDay = $this->rent_due_day;
        
        // If we're past the due day this month, next due date is next month
        if (now()->day > $dueDay) {
            $currentMonth->addMonth();
        }
        
        return $currentMonth->day($dueDay);
    }

    public function isRentOverdue()
    {
        $nextDueDate = $this->getNextRentDueDate();
        
        if (!$nextDueDate) return false;
        
        return now() > $nextDueDate->addDays($this->late_fee_grace_days);
    }

    public function calculateLateFee()
    {
        if (!$this->isRentOverdue()) return 0;
        
        $nextDueDate = $this->getNextRentDueDate();
        $overdueDays = now()->diffInDays($nextDueDate->addDays($this->late_fee_grace_days));
        
        return $overdueDays * $this->late_fee_amount;
    }

    public function renew($newEndDate, $newRent = null)
    {
        if ($this->status !== 'active') return false;
        
        $this->update([
            'end_date' => $newEndDate,
            'monthly_rent' => $newRent ?: $this->monthly_rent,
            'status' => 'renewed'
        ]);
        
        // Create new lease record
        $newLease = static::create([
            'lease_number' => $this->generateLeaseNumber(),
            'property_id' => $this->property_id,
            'room_id' => $this->room_id,
            'tenant_id' => $this->tenant_id,
            'start_date' => $this->end_date->addDay(),
            'end_date' => $newEndDate,
            'monthly_rent' => $newRent ?: $this->monthly_rent,
            'security_deposit' => $this->security_deposit,
            'paid_deposit' => 0, // New lease, deposit needs to be paid again
            'deposit_balance' => $this->security_deposit,
            'late_fee_amount' => $this->late_fee_amount,
            'late_fee_grace_days' => $this->late_fee_grace_days,
            'rent_due_day' => $this->rent_due_day,
            'status' => 'active',
            'payment_status' => 'current',
            'terms_conditions' => $this->terms_conditions,
            'special_conditions' => $this->special_conditions,
            'branch_id' => $this->branch_id,
            'company_id' => $this->company_id,
            'created_by' => auth()->id()
        ]);
        
        return $newLease;
    }

    public function terminate($reason = null, $fee = 0)
    {
        if ($this->status !== 'active') return false;
        
        $this->update([
            'status' => 'terminated',
            'termination_date' => now(),
            'termination_reason' => $reason,
            'termination_fee' => $fee
        ]);
        
        return true;
    }

    public function getRentHistory()
    {
        // This would integrate with your payment system
        // For now, return empty collection
        return collect();
    }

    public function getTotalRentCollected()
    {
        // This would integrate with your payment system
        // For now, return 0
        return 0;
    }
}