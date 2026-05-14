<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SystemSettingService;

class FxSettingsController extends Controller
{
    /**
     * Display FX settings form.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get FX settings
        $settings = [
            'fx_realized_gain_account_id' => SystemSetting::getValue('fx_realized_gain_account_id'),
            'fx_realized_loss_account_id' => SystemSetting::getValue('fx_realized_loss_account_id'),
            'fx_unrealized_gain_account_id' => SystemSetting::getValue('fx_unrealized_gain_account_id'),
            'fx_unrealized_loss_account_id' => SystemSetting::getValue('fx_unrealized_loss_account_id'),
            'functional_currency' => SystemSetting::getValue('functional_currency', $user->company->functional_currency ?? 'TZS'),
            'fx_rate_override_threshold' => SystemSetting::getValue('fx_rate_override_threshold', 5),
            'fx_revaluation_approval_required' => SystemSetting::getValue('fx_revaluation_approval_required', false),
        ];

        // Get chart accounts for dropdowns
        $chartAccounts = ChartAccount::whereHas('accountClassGroup', function ($query) use ($user) {
                $query->where('company_id', $user->company_id);
            })
            ->where('account_type', 'child') // Only child accounts can be used
            ->orderBy('account_name')
            ->get();

        // Get Revenue accounts for FX Realized Gain Account (All income/revenue accounts)
        // Using case-insensitive LIKE to match "Revenue" or any variation
        // Note: Not filtering by account_type since some companies may only have parent accounts
        $revenueAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $user->company_id)
            ->where(function($q) {
                $q->whereRaw('LOWER(account_class.name) LIKE ?', ['%revenue%'])
                  ->orWhereRaw('LOWER(account_class.name) LIKE ?', ['%income%']);
            })
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_name')
            ->get();

        // Get Expense accounts for FX Realized Loss Account (All expense accounts)
        // Using case-insensitive LIKE to match "Expenses" or any variation
        // Note: Not filtering by account_type since some companies may only have parent accounts
        $expenseAccounts = ChartAccount::join('account_class_groups', 'chart_accounts.account_class_group_id', '=', 'account_class_groups.id')
            ->join('account_class', 'account_class_groups.class_id', '=', 'account_class.id')
            ->where('account_class_groups.company_id', $user->company_id)
            ->whereRaw('LOWER(account_class.name) LIKE ?', ['%expense%'])
            ->select('chart_accounts.*')
            ->orderBy('chart_accounts.account_name')
            ->get();

        return view('accounting.fx-settings.index', compact('settings', 'chartAccounts', 'revenueAccounts', 'expenseAccounts'));
    }

    /**
     * Update FX settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'fx_realized_gain_account_id' => 'nullable|exists:chart_accounts,id',
            'fx_realized_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'fx_unrealized_gain_account_id' => 'nullable|exists:chart_accounts,id',
            'fx_unrealized_loss_account_id' => 'nullable|exists:chart_accounts,id',
            'functional_currency' => 'required|string|max:3',
            'fx_rate_override_threshold' => 'nullable|numeric|min:0|max:100',
            'fx_revaluation_approval_required' => 'nullable|in:on,1,true',
        ]);

        try {
            // Update settings
            SystemSetting::setValue(
                'fx_realized_gain_account_id',
                $request->fx_realized_gain_account_id,
                'integer',
                'fx',
                'FX Realized Gain Account',
                'Chart of account for recording realized foreign exchange gains'
            );

            SystemSetting::setValue(
                'fx_realized_loss_account_id',
                $request->fx_realized_loss_account_id,
                'integer',
                'fx',
                'FX Realized Loss Account',
                'Chart of account for recording realized foreign exchange losses'
            );

            SystemSetting::setValue(
                'fx_unrealized_gain_account_id',
                $request->fx_unrealized_gain_account_id,
                'integer',
                'fx',
                'FX Unrealized Gain Account',
                'Chart of account for recording unrealized foreign exchange gains'
            );

            SystemSetting::setValue(
                'fx_unrealized_loss_account_id',
                $request->fx_unrealized_loss_account_id,
                'integer',
                'fx',
                'FX Unrealized Loss Account',
                'Chart of account for recording unrealized foreign exchange losses'
            );

            SystemSetting::setValue(
                'functional_currency',
                $request->functional_currency,
                'string',
                'fx',
                'Functional Currency',
                'Company\'s functional currency (base currency for reporting)'
            );

            SystemSetting::setValue(
                'fx_rate_override_threshold',
                $request->fx_rate_override_threshold ?? 5,
                'decimal',
                'fx',
                'FX Rate Override Threshold (%)',
                'Percentage threshold for rate override approval. If rate override exceeds this percentage, approval is required.'
            );

            // Convert checkbox value to boolean (checkbox sends "on" when checked, nothing when unchecked)
            // $request->has() returns true if checkbox is checked, false if unchecked
            $approvalRequired = $request->has('fx_revaluation_approval_required');
            
            SystemSetting::setValue(
                'fx_revaluation_approval_required',
                $approvalRequired ? '1' : '0', // Store as string '1' or '0' for boolean type
                'boolean',
                'fx',
                'FX Revaluation Approval Required',
                'Require approval before posting FX revaluation journal entries'
            );

            // Clear cache
            SystemSetting::clearCache();

            return redirect()
                ->route('accounting.fx-settings.index')
                ->with('success', 'FX settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error updating FX settings: ' . $e->getMessage());
        }
    }
}

