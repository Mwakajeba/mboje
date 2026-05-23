<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStooLine extends Model
{
    protected $fillable = [
        'daily_stoo_record_id',
        'maelezo',
        'thamani',
        'sort_order',
    ];

    protected $casts = [
        'thamani' => 'decimal:2',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DailyStooRecord::class, 'daily_stoo_record_id');
    }
}
