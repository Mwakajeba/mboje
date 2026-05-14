<?php

namespace App\Models\Intangible;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntangibleImpairment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'intangible_asset_id',
        'company_id',
        'branch_id',
        'impairment_date',
        'carrying_amount_before',
        'recoverable_amount',
        'impairment_loss',
        'method',
        'assumptions',
        'is_reversal',
        'reversed_impairment_id',
        'status',
        'journal_id',
        'gl_posted',
    ];

    protected $casts = [
        'impairment_date' => 'date',
        'carrying_amount_before' => 'decimal:2',
        'recoverable_amount' => 'decimal:2',
        'impairment_loss' => 'decimal:2',
        'is_reversal' => 'boolean',
        'gl_posted' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(IntangibleAsset::class, 'intangible_asset_id');
    }
}


