<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMatumiziLine extends Model
{
    protected $fillable = [
        'daily_matumizi_record_id',
        'maelezo',
        'kiasi',
        'sort_order',
    ];

    protected $casts = [
        'kiasi' => 'decimal:2',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DailyMatumiziRecord::class, 'daily_matumizi_record_id');
    }
}
