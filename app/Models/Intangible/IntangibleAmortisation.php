<?php

namespace App\Models\Intangible;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntangibleAmortisation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'intangible_asset_id',
        'company_id',
        'branch_id',
        'amortisation_date',
        'amount',
        'accumulated_amortisation_after',
        'nbv_after',
        'journal_id',
        'gl_posted',
    ];

    protected $casts = [
        'amortisation_date' => 'date',
        'amount' => 'decimal:2',
        'accumulated_amortisation_after' => 'decimal:2',
        'nbv_after' => 'decimal:2',
        'gl_posted' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(IntangibleAsset::class, 'intangible_asset_id');
    }
}


