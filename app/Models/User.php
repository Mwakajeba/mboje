<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\LogsActivity;
use App\Models\PasswordHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Vinkla\Hashids\Facades\Hashids;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    use HasRoles, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'locale',
        'password',
        'sms_verification_code',
        'sms_verified_at',
        'company_id',
        'password_changed_at',
        'password_expires_at',
        'password_expired',
        'force_password_change',
        'branch_id',
        'role',
        'is_active',
        'user_id',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches()
    {
        // Many-to-many assigned branches via pivot table `branch_user`
        return $this->belongsToMany(Branch::class, 'branch_user');
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function locations()
    {
        return $this->belongsToMany(\App\Models\InventoryLocation::class, 'location_user')
            ->withTimestamps()
            ->withPivot(['is_default']);
    }

    public function defaultLocation()
    {
        return $this->belongsToMany(\App\Models\InventoryLocation::class, 'location_user')
            ->wherePivot('is_default', true)
            ->limit(1);
    }

    /**
     * Get approval histories where this user is the approver.
     */
    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class, 'approver_id');
    }

    /**
     * Get password history for this user
     */
    public function passwordHistory(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to find user by phone number with flexible matching
     */
    public function scopeByPhone($query, $phone)
    {
        $normalized = normalize_phone_number($phone);

        // Try to find user with normalized phone number
        $user = $query->where('phone', $normalized)->first();

        if ($user) {
            return $user;
        }

        // If not found, try different variations
        $variations = [];

        // If it's a Tanzania number, try different formats
        if (strpos($normalized, '255') === 0 && strlen($normalized) === 12) {
            $number = substr($normalized, 3); // Remove 255 prefix

            $variations = [
                $normalized,                    // 255xxxxxxxxx
                '0' . $number,                  // 0xxxxxxxxx
                '+' . $normalized,              // +255xxxxxxxxx
                $number                         // xxxxxxxxx (9 digits)
            ];
        }

        // Try each variation
        foreach ($variations as $variation) {
            $user = $query->where('phone', $variation)->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Get the hash ID for the user
     *
     * @return string
     */
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'hash_id';
    }

    /**
     * Resolve the model from the route parameter
     *
     * @param string $value
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // Try to decode the hash ID first
        $decoded = Hashids::decode($value);

        if (!empty($decoded)) {
            return static::where('id', $decoded[0])->first();
        }

        // Fallback to regular ID lookup
        return static::where('id', $value)->first();
    }

    /**
     * Get the route key for the model
     *
     * @return string
     */
    public function getRouteKey()
    {
        return $this->hash_id;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = [
        'sms_verified_at',
        'password_changed_at',
        'password_expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

}
