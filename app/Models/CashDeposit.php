<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class CashDeposit extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'branch_id',
        'company_id',
        'customer_id',
        'type_id',
        'amount',
    ];

    // Relationships

    // A CashDeposit belongs to a Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // A CashDeposit belongs to a Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // A CashDeposit belongs to a Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // A CashDeposit belongs to a CashDepositAccount
    public function type()
    {
        return $this->belongsTo(CashDepositAccount::class, 'type_id');
    }
    
    public static function getCashDepositBalance(int $customerId): float
    {
        $record = self::where('customer_id', $customerId)->first();
        return round($record?->amount ?? 0, 2);
    }

    /**
     * Get the hash ID for the cash deposit
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
