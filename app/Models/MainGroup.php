<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class MainGroup extends Model
{
    use LogsActivity;
    protected $fillable = [
        'class_id',
        'name',
        'description',
        'status',
        'company_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function accountClass()
    {
        return $this->belongsTo(AccountClass::class, 'class_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountClassGroups()
    {
        return $this->hasMany(AccountClassGroup::class, 'main_group_id');
    }

    /**
     * Check if this main group is being used by any account class groups
     */
    public function isInUse(): bool
    {
        return $this->accountClassGroups()->exists();
    }
}
