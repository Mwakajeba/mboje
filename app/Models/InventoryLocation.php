<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class InventoryLocation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'manager_id',
        'is_active',
        'branch_id',
        'company_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'location_user')
            ->withTimestamps()
            ->withPivot(['is_default']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Accessors
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }
    public function getStatusBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-danger">Inactive</span>';
    }

    public function getManagerNameAttribute()
    {
        return $this->manager ? $this->manager->name : 'No Manager Assigned';
    }

    public function getActionsAttribute()
    {
        $actions = '<div class="btn-group" role="group">';
        $actions .= '<a href="' . route('settings.inventory.locations.show', $this->hash_id) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
        $actions .= '<a href="' . route('settings.inventory.locations.edit', $this->hash_id) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $this->hash_id . '" title="Delete"><i class="bx bx-trash"></i></button>';
        $actions .= '</div>';
        
        return $actions;
    }
}
