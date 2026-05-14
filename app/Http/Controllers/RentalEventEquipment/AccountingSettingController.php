<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\AccountingSetting;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingSettingController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $settings = AccountingSetting::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->first();

        // Get chart accounts for dropdowns
        $incomeAccounts = ChartAccount::whereHas('accountClassGroup', function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->whereHas('accountClass', function($subQ) {
                          $subQ->whereRaw('LOWER(name) LIKE ?', ['%revenue%'])
                               ->orWhereRaw('LOWER(name) LIKE ?', ['%income%']);
                      });
            })
            ->orderBy('account_name')
            ->get();

        $liabilityAccounts = ChartAccount::whereHas('accountClassGroup', function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->whereHas('accountClass', function($subQ) {
                          $subQ->whereRaw('LOWER(name) LIKE ?', ['%liability%']);
                      });
            })
            ->orderBy('account_name')
            ->get();

        $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->whereHas('accountClass', function($subQ) {
                          $subQ->whereRaw('LOWER(name) LIKE ?', ['%expense%']);
                      });
            })
            ->orderBy('account_name')
            ->get();

        $assetAccounts = ChartAccount::whereHas('accountClassGroup', function($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->whereHas('accountClass', function($subQ) {
                          $subQ->whereRaw('LOWER(name) LIKE ?', ['%asset%']);
                      });
            })
            ->orderBy('account_name')
            ->get();

        // Ensure we always have a settings instance for the form
        if (! $settings) {
            $settings = new AccountingSetting([
                'company_id' => $companyId,
                'branch_id' => $branchId,
            ]);
        }

        // Helper closure for name matching
        $findAccount = function ($collection, array $keywords) {
            foreach ($keywords as $keyword) {
                $match = $collection->first(function ($acc) use ($keyword) {
                    return stripos($acc->account_name ?? '', $keyword) !== false;
                });
                if ($match) {
                    return $match;
                }
            }
            return $collection->first();
        };

        // Candidate accounts for each role
        $rentalIncome = $findAccount($incomeAccounts, ['rental income', 'hire income', 'rent income']);
        $serviceIncome = $findAccount($incomeAccounts, ['decoration service income', 'service income', 'decoration income', 'event service']);
        $damageRecoveryIncome = $findAccount($incomeAccounts, ['damage recovery income', 'damage recovery', 'recovery income', 'penalty']);

        // Customer deposits: explicitly prefer ChartAccount ID 28 (Customer Deposits liability)
        $depositsAccount = ChartAccount::find(28)
            ?? $findAccount($liabilityAccounts, ['customer deposits', 'deposits from customers', 'rental deposits'])
            ?? ChartAccount::where('account_name', 'Customer Deposits')
                ->orWhere('account_code', '2001')
                ->first();

        // Ensure the deposits account appears in the liability dropdown even if it was filtered out
        if ($depositsAccount && ! $liabilityAccounts->contains('id', $depositsAccount->id)) {
            $liabilityAccounts->push($depositsAccount);
            $liabilityAccounts = $liabilityAccounts->sortBy('account_name')->values();
        }

        $rentalEquipment = $findAccount($assetAccounts, ['rental & event equipment', 'rental and event equipment', 'event equipment', 'rental equipment']);
        $equipmentUnderRepair = $findAccount($assetAccounts, ['equipment under repair', 'wip equipment', 'work in progress']);
        $accountsReceivable = $findAccount($assetAccounts, ['accounts receivable', 'trade debtors', 'debtors']);

        // Prefer dedicated rental/event expense accounts we seeded, fall back to name matching
        $generalExpenses = ChartAccount::find(765)
            ?? $findAccount($expenseAccounts, ['rental equipment operating expenses', 'rental expenses', 'event expenses', 'operating expenses']);
        $repairsMaintenance = ChartAccount::find(766)
            ?? $findAccount($expenseAccounts, ['rental equipment maintenance expenses', 'repairs and maintenance', 'repair & maintenance', 'maintenance']);
        $lossOnEquipment = ChartAccount::find(764)
            ?? $findAccount($expenseAccounts, ['loss on equipment', 'equipment loss', 'asset write off', 'write-off']);

        // Only prefill fields that are currently empty so we don't override saved choices
        if (! $settings->rental_income_account_id) {
            $settings->rental_income_account_id = optional($rentalIncome)->id;
        }
        if (! $settings->service_income_account_id) {
            $settings->service_income_account_id = optional($serviceIncome)->id;
        }
        if (! $settings->deposits_account_id) {
            $settings->deposits_account_id = optional($depositsAccount)->id;
        }
        if (! $settings->expenses_account_id) {
            $settings->expenses_account_id = optional($generalExpenses)->id;
        }
        if (! $settings->rental_equipment_account_id) {
            $settings->rental_equipment_account_id = optional($rentalEquipment)->id;
        }
        if (! $settings->equipment_under_repair_account_id) {
            $settings->equipment_under_repair_account_id = optional($equipmentUnderRepair)->id;
        }
        if (! $settings->accounts_receivable_account_id) {
            $settings->accounts_receivable_account_id = optional($accountsReceivable)->id;
        }
        if (! $settings->damage_recovery_income_account_id) {
            $settings->damage_recovery_income_account_id = optional($damageRecoveryIncome)->id;
        }
        if (! $settings->repair_maintenance_expense_account_id) {
            $settings->repair_maintenance_expense_account_id = optional($repairsMaintenance)->id;
        }
        if (! $settings->loss_on_equipment_account_id) {
            $settings->loss_on_equipment_account_id = optional($lossOnEquipment)->id;
        }

        return view('rental-event-equipment.accounting-settings.index', compact('settings', 'incomeAccounts', 'liabilityAccounts', 'expenseAccounts', 'assetAccounts'));
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $request->validate([
            'rental_income_account_id' => 'nullable|exists:chart_accounts,id',
            'service_income_account_id' => 'nullable|exists:chart_accounts,id',
            'deposits_account_id' => 'nullable|exists:chart_accounts,id',
            'expenses_account_id' => 'nullable|exists:chart_accounts,id',
            'rental_equipment_account_id' => 'nullable|exists:chart_accounts,id',
            'equipment_under_repair_account_id' => 'nullable|exists:chart_accounts,id',
            'accounts_receivable_account_id' => 'nullable|exists:chart_accounts,id',
            'damage_recovery_income_account_id' => 'nullable|exists:chart_accounts,id',
            'repair_maintenance_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'loss_on_equipment_account_id' => 'nullable|exists:chart_accounts,id',
        ]);

        $settings = AccountingSetting::updateOrCreate(
            [
                'company_id' => $companyId,
                'branch_id' => $branchId,
            ],
            [
                'rental_income_account_id' => $request->rental_income_account_id,
                'service_income_account_id' => $request->service_income_account_id,
                'deposits_account_id' => $request->deposits_account_id,
                'expenses_account_id' => $request->expenses_account_id,
                'rental_equipment_account_id' => $request->rental_equipment_account_id,
                'equipment_under_repair_account_id' => $request->equipment_under_repair_account_id,
                'accounts_receivable_account_id' => $request->accounts_receivable_account_id,
                'damage_recovery_income_account_id' => $request->damage_recovery_income_account_id,
                'repair_maintenance_expense_account_id' => $request->repair_maintenance_expense_account_id,
                'loss_on_equipment_account_id' => $request->loss_on_equipment_account_id,
                'created_by' => Auth::id(),
            ]
        );

        return redirect()->route('rental-event-equipment.accounting-settings.index')
            ->with('success', 'Accounting settings saved successfully.');
    }
}
