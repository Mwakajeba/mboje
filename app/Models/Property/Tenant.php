<?php

namespace App\Models\Property;

use App\Models\Company;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'id_number',
        'id_type',
        'date_of_birth',
        'gender',
        'nationality',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'employer_name',
        'employer_phone',
        'monthly_income',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'references',
        'status',
        'notes',
        'company_id',
        'created_by'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'monthly_income' => 'decimal:2'
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('tenant_number', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFullAddressAttribute()
    {
        $address = $this->address;
        if ($this->city) $address .= ', ' . $this->city;
        if ($this->state) $address .= ', ' . $this->state;
        if ($this->country) $address .= ', ' . $this->country;
        if ($this->postal_code) $address .= ' ' . $this->postal_code;
        return $address;
    }

    public function getCurrentLeaseAttribute()
    {
        return $this->leases()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    public function getTotalLeasesAttribute()
    {
        return $this->leases()->count();
    }

    public function getTotalRentPaidAttribute()
    {
        return $this->leases()->sum('monthly_rent');
    }

    public function getIsCurrentlyRentingAttribute()
    {
        return $this->current_lease !== null;
    }

    // Methods
    public function generateTenantNumber()
    {
        $lastTenant = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastTenant ? $lastTenant->id + 1 : 1;
        return 'TENANT' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function getRentalHistory()
    {
        return $this->leases()
            ->with(['property', 'room'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getPaymentHistory()
    {
        // This would integrate with your payment system
        // For now, return empty collection
        return collect();
    }

    public function getCreditScore()
    {
        // This would integrate with your credit scoring system
        // For now, return a basic score based on payment history
        $currentLease = $this->current_lease;
        
        if (!$currentLease) return 'N/A';
        
        // Basic scoring logic - you can enhance this
        $score = 100; // Start with perfect score
        
        // Deduct points for late payments (if you have payment tracking)
        // Deduct points for lease violations
        // Add points for long-term tenancy
        
        if ($score >= 80) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 50) return 'Poor';
        
        return 'Very Poor';
    }

    public function getAge()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function getIncomeToRentRatio($rentAmount)
    {
        if (!$this->monthly_income || $rentAmount <= 0) return null;
        
        return round(($rentAmount / $this->monthly_income) * 100, 2);
    }
}