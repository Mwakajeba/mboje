<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Vinkla\Hashids\Facades\Hashids;

class Branch extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'company_id', 'name', 'email', 'phone', 'address',
        'branch_id', 'branch_name', 'location', 'manager_name', 'manager_id', 'logo', 'status'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->withTimestamps();
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the hash ID for the branch
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
        return 'id';
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
}
