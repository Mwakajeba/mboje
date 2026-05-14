<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'password_hash',
        'created_at',
    ];

    public $timestamps = false;

    protected $dates = ['created_at'];

    /**
     * Get the user that owns this password history.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
