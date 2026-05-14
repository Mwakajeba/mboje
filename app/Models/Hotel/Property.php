<?php

namespace App\Models\Hotel;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Property\Lease;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Property extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'type',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'purchase_date',
        'purchase_price',
        'current_value',
        'status',
        'description',
        'amenities',
        'contact_person',
        'contact_phone',
        'contact_email',
        'branch_id',
        'company_id',
        'created_by'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'amenities' => 'array'
    ];

    // Relationships
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

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(Booking::class, Room::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getFullAddressAttribute()
    {
        $address = $this->address;
        if ($this->city) $address .= ', ' . $this->city;
        if ($this->state) $address .= ', ' . $this->state;
        if ($this->country) $address .= ', ' . $this->country;
        if ($this->postal_code) $address .= ' ' . $this->postal_code;
        return $address;
    }

    public function getTotalRoomsAttribute()
    {
        return $this->rooms()->count();
    }

    public function getAvailableRoomsAttribute()
    {
        return $this->rooms()->where('status', 'available')->count();
    }

    public function getOccupiedRoomsAttribute()
    {
        return $this->rooms()->where('status', 'occupied')->count();
    }

    // Methods
    public function calculateOccupancyRate()
    {
        $totalRooms = $this->total_rooms;
        if ($totalRooms == 0) return 0;
        
        $occupiedRooms = $this->occupied_rooms;
        return round(($occupiedRooms / $totalRooms) * 100, 2);
    }

    public function getTotalRevenue($startDate = null, $endDate = null)
    {
        $query = $this->bookings()->where('status', '!=', 'cancelled');
        
        if ($startDate) {
            $query->where('check_in', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('check_out', '<=', $endDate);
        }
        
        return $query->sum('total_amount');
    }
}