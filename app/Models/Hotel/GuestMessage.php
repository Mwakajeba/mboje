<?php

namespace App\Models\Hotel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestMessage extends Model
{
    use HasFactory;

    protected $table = 'guest_messages';

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'branch_id',
        'source',
        'is_read',
        'read_at',
        'read_by',
        'response',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function readBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'read_by');
    }

    public function respondedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'responded_by');
    }
}
