<?php

namespace App\Models\RentalEventEquipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class AccountingSetting extends Model
{
    protected $table = 'rental_accounting_settings';

    protected $fillable = [
        'company_id',
        'branch_id',
        'rental_income_account_id',
        'service_income_account_id',
        'deposits_account_id',
        'expenses_account_id',
        'rental_equipment_account_id',
        'equipment_under_repair_account_id',
        'accounts_receivable_account_id',
        'damage_recovery_income_account_id',
        'repair_maintenance_expense_account_id',
        'loss_on_equipment_account_id',
        'created_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function rentalIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'rental_income_account_id');
    }

    public function serviceIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'service_income_account_id');
    }

    public function depositsAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'deposits_account_id');
    }

    public function expensesAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'expenses_account_id');
    }

    public function rentalEquipmentAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'rental_equipment_account_id');
    }

    public function equipmentUnderRepairAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'equipment_under_repair_account_id');
    }

    public function accountsReceivableAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'accounts_receivable_account_id');
    }

    public function damageRecoveryIncomeAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'damage_recovery_income_account_id');
    }

    public function repairMaintenanceExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'repair_maintenance_expense_account_id');
    }

    public function lossOnEquipmentAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ChartAccount::class, 'loss_on_equipment_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getRouteKey()
    {
        return Hashids::encode($this->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if (in_array($value, ['create', 'edit', 'data', 'index'])) {
            return null;
        }
        $decoded = Hashids::decode($value);
        $id = $decoded[0] ?? null;
        return $id ? static::find($id) : null;
    }
}
