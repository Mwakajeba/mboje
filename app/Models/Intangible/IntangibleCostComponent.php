<?php

namespace App\Models\Intangible;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntangibleCostComponent extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'intangible_asset_id',
        'company_id',
        'date',
        'type',
        'description',
        'amount',
        'source_document_id',
        'source_document_type',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function asset()
    {
        return $this->belongsTo(IntangibleAsset::class, 'intangible_asset_id');
    }
}


