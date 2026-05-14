<?php

namespace App\Models\Inventory;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Branch;
use Vinkla\Hashids\Facades\Hashids;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'inventory_categories';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // protected $dates = ['deleted_at'];

    // Relationships
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }



    public function items()
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getEncodedIdAttribute()
    {
        return Hashids::encode($this->id);
    }
}
