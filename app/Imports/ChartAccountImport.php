<?php

namespace App\Imports;

use App\Models\AccountClassGroup;
use App\Models\ChartAccount;
use App\Models\MainGroup;
use App\Models\CashFlowCategory;
use App\Models\EquityCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class ChartAccountImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $companyId = auth()->user()->company_id;

        foreach ($rows as $row) {
            if (empty($row['account_code']) || empty($row['account_name'])) {
                continue;
            }

            // Skip sample data if it exists
            if (in_array($row['account_code'], ['1000', '1100']) && in_array($row['account_name'], ['Fixed Assets', 'Motor Vehicles'])) {
                continue;
            }

            DB::transaction(function () use ($row, $companyId) {
                // Resolve Account Class Group
                $classGroup = AccountClassGroup::where('name', $row['account_class_group'])
                    ->where('company_id', $companyId)
                    ->first();
                
                if (!$classGroup) {
                    // Try to resolve by main group name first if provided
                    $mainGroup = MainGroup::where('name', $row['main_group'])
                        ->where('company_id', $companyId)
                        ->first();
                    
                    if ($mainGroup) {
                        $classGroup = AccountClassGroup::where('name', $row['account_class_group'])
                            ->where('main_group_id', $mainGroup->id)
                            ->where('company_id', $companyId)
                            ->first();
                    }
                }

                if (!$classGroup) {
                    throw new \Exception("Account Class Group '{$row['account_class_group']}' not found for your company.");
                }

                // Resolve Parent Account
                $parentId = null;
                if (strtolower($row['type_parentchild']) === 'child' && !empty($row['parent_account_code_required_if_type_is_child'])) {
                    $parent = ChartAccount::where('account_code', $row['parent_account_code_required_if_type_is_child'])
                        ->whereHas('accountClassGroup', function($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        })
                        ->first();
                    if ($parent) {
                        $parentId = $parent->id;
                    }
                }

                // Resolve Cash Flow Category
                $cashFlowCategoryId = null;
                if (strtolower($row['has_cash_flow_yesno']) === 'yes' && !empty($row['cash_flow_category_optional'])) {
                    $cfCategory = CashFlowCategory::where('name', $row['cash_flow_category_optional'])->first();
                    $cashFlowCategoryId = $cfCategory ? $cfCategory->id : null;
                }

                // Resolve Equity Category
                $equityCategoryId = null;
                if (strtolower($row['has_equity_yesno']) === 'yes' && !empty($row['equity_category_optional'])) {
                    $eqCategory = EquityCategory::where('name', $row['equity_category_optional'])->first();
                    $equityCategoryId = $eqCategory ? $eqCategory->id : null;
                }

                ChartAccount::updateOrCreate(
                    ['account_code' => $row['account_code']],
                    [
                        'account_name' => $row['account_name'],
                        'account_class_group_id' => $classGroup->id,
                        'account_type' => strtolower($row['type_parentchild']) === 'parent' ? 'parent' : 'child',
                        'parent_id' => $parentId,
                        'has_cash_flow' => strtolower($row['has_cash_flow_yesno']) === 'yes',
                        'has_equity' => strtolower($row['has_equity_yesno']) === 'yes',
                        'cash_flow_category_id' => $cashFlowCategoryId,
                        'equity_category_id' => $equityCategoryId,
                    ]
                );
            });
        }
    }
}
