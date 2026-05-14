<?php

namespace App\Models\Intangible;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntangibleDisposal extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'intangible_asset_id',
        'company_id',
        'branch_id',
        'disposal_date',
        'proceeds',
        'nbv_at_disposal',
        'gain_loss',
        'status',
        'journal_id',
        'gl_posted',
        'reason',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'proceeds' => 'decimal:2',
        'nbv_at_disposal' => 'decimal:2',
        'gain_loss' => 'decimal:2',
        'gl_posted' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(IntangibleAsset::class, 'intangible_asset_id');
    }
}


