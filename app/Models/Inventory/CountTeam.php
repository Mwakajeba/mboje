<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountTeam extends Model
{
    use HasFactory;

    protected $table = 'inventory_count_teams';

    protected $fillable = [
        'count_session_id',
        'user_id',
        'role',
        'assigned_area',
        'assigned_at',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(CountSession::class, 'count_session_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }
}
