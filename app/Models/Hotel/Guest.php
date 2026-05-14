<?php

namespace App\Models\Hotel;

use App\Traits\LogsActivity;
use App\Models\Company;
use App\Models\User;
use App\Helpers\HashIdHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Guest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasApiTokens;

    protected $fillable = [
        'guest_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
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
        'emergency_contact_name',
        'emergency_contact_phone',
        'special_requests',
        'status',
        'notes',
        'company_id',
        'created_by'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'date_of_birth' => 'date'
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

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
              ->orWhere('guest_number', 'like', "%{$search}%");
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

    public function getTotalBookingsAttribute()
    {
        return $this->bookings()->count();
    }

    public function getTotalSpentAttribute()
    {
        return $this->bookings()->where('status', '!=', 'cancelled')->sum('total_amount');
    }

    public function getLastBookingAttribute()
    {
        return $this->bookings()->latest('check_in')->first();
    }

    // Methods
    public function generateGuestNumber()
    {
        $lastGuest = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastGuest ? $lastGuest->id + 1 : 1;
        return 'GUEST' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function getLoyaltyLevel()
    {
        $totalBookings = $this->total_bookings;
        
        if ($totalBookings >= 50) return 'Platinum';
        if ($totalBookings >= 20) return 'Gold';
        if ($totalBookings >= 10) return 'Silver';
        if ($totalBookings >= 5) return 'Bronze';
        
        return 'Regular';
    }

    public function getAverageStayDuration()
    {
        $bookings = $this->bookings()->where('status', '!=', 'cancelled')->get();
        
        if ($bookings->isEmpty()) return 0;
        
        $totalNights = $bookings->sum('nights');
        return round($totalNights / $bookings->count(), 1);
    }

    /**
     * Resolve the model instance for route model binding using hash ID.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is hash_id or null, decode the hash ID
        if ($field === 'hash_id' || $field === null) {
            $id = HashIdHelper::decode($value);
            if ($id !== null) {
                return $this->findOrFail($id);
            }
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Get the hash ID for this model.
     */
    public function getHashIdAttribute()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKey()
    {
        return HashIdHelper::encode($this->id);
    }

    /**
     * Get the route key name for the model.
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }
}