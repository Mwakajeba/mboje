<?php

namespace App\Models\Purchase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyManunuziLine extends Model
{
    protected $fillable = [
        'daily_manunuzi_record_id',
        'maelezo',
        'kiasi',
        'sort_order',
    ];

    protected $casts = [
        'kiasi' => 'decimal:2',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(DailyManunuziRecord::class, 'daily_manunuzi_record_id');
    }
}
