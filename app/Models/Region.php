<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
