<?php

namespace App\Models\Intangible;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntangibleAssetCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'is_goodwill',
        'is_indefinite_life',
        'cost_account_id',
        'accumulated_amortisation_account_id',
        'accumulated_impairment_account_id',
        'amortisation_expense_account_id',
        'impairment_loss_account_id',
        'disposal_gain_loss_account_id',
        'settings',
    ];

    protected $casts = [
        'is_goodwill' => 'boolean',
        'is_indefinite_life' => 'boolean',
        'settings' => 'array',
    ];

    public function assets()
    {
        return $this->hasMany(IntangibleAsset::class, 'category_id');
    }

    public function costAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'cost_account_id');
    }

    public function accumulatedAmortisationAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'accumulated_amortisation_account_id');
    }

    public function accumulatedImpairmentAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'accumulated_impairment_account_id');
    }

    public function amortisationExpenseAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'amortisation_expense_account_id');
    }

    public function impairmentLossAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'impairment_loss_account_id');
    }

    public function disposalGainLossAccount()
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'disposal_gain_loss_account_id');
    }
}


