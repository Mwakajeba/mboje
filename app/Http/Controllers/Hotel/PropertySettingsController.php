<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertySettingsController extends Controller
{
    public function index()
    {
        $this->authorize('view hotel property settings');
        
        // Get chart accounts for dropdowns
        $revenueAccounts = ChartAccount::whereHas('accountClassGroup', function($query) {
            $query->whereIn('name', ['Sales Revenue', 'Other Income']);
        })->orderBy('account_name')->get();
        
        // Expense accounts, including Selling & Distribution and Cost of Sales (for discount / operating costs)
        $expenseAccounts = ChartAccount::whereHas('accountClassGroup', function($query) {
            $query->whereIn('name', [
                'Administrative Expenses',
                'Selling & Distribution',
                'Cost of Sales',        // ensure cost-of-sales accounts are included
                'Financial Expenses',
            ]);
        })->orderBy('account_name')->get();
        
        $assetAccounts = ChartAccount::whereHas('accountClassGroup', function($query) {
            $query->whereIn('name', ['Non Current Assets', 'Current Assets']);
        })->orderBy('account_name')->get();
        
        $liabilityAccounts = ChartAccount::whereHas('accountClassGroup', function($query) {
            $query->whereIn('name', ['Current Liabilities', 'Non Current Liabilities']);
        })->orderBy('account_name')->get();

        // Get current system settings for hotel & property management
        $currentSettings = [
            'hotel_room_revenue_account_id' => SystemSetting::getValue('hotel_room_revenue_account_id'),
            'hotel_service_revenue_account_id' => SystemSetting::getValue('hotel_service_revenue_account_id'),
            'hotel_food_beverage_account_id' => SystemSetting::getValue('hotel_food_beverage_account_id'),
            'hotel_operating_expense_account_id' => SystemSetting::getValue('hotel_operating_expense_account_id'),
            'hotel_maintenance_expense_account_id' => SystemSetting::getValue('hotel_maintenance_expense_account_id'),
            'hotel_marketing_expense_account_id' => SystemSetting::getValue('hotel_marketing_expense_account_id'),
            'hotel_discount_expense_account_id' => SystemSetting::getValue('hotel_discount_expense_account_id'),
            'property_rental_income_account_id' => SystemSetting::getValue('property_rental_income_account_id'),
            'property_service_charge_account_id' => SystemSetting::getValue('property_service_charge_account_id'),
            'property_late_fee_account_id' => SystemSetting::getValue('property_late_fee_account_id'),
            'property_operating_expense_account_id' => SystemSetting::getValue('property_operating_expense_account_id'),
            'property_maintenance_expense_account_id' => SystemSetting::getValue('property_maintenance_expense_account_id'),
            'property_utilities_expense_account_id' => SystemSetting::getValue('property_utilities_expense_account_id'),
            'property_management_fee_account_id' => SystemSetting::getValue('property_management_fee_account_id'),
            'property_asset_account_id' => SystemSetting::getValue('property_asset_account_id'),
            'furniture_fixtures_account_id' => SystemSetting::getValue('furniture_fixtures_account_id'),
            'accumulated_depreciation_account_id' => SystemSetting::getValue('accumulated_depreciation_account_id'),
            'security_deposit_liability_account_id' => SystemSetting::getValue('security_deposit_liability_account_id'),
        ];

        // Ensure a sensible default for Hotel Marketing Expense Account:
        // prefer chart account with code 5603 / name "Hotel Marketing Expenses".
        if (empty($currentSettings['hotel_marketing_expense_account_id'])) {
            $defaultMarketingAccount = ChartAccount::where('account_code', '5603')
                ->orWhere('account_name', 'Hotel Marketing Expenses')
                ->first();

            if ($defaultMarketingAccount) {
                $currentSettings['hotel_marketing_expense_account_id'] = $defaultMarketingAccount->id;
            }
        }

        return view('hotel.property.settings', compact(
            'revenueAccounts',
            'expenseAccounts', 
            'assetAccounts',
            'liabilityAccounts',
            'currentSettings'
        ));
    }

    public function update(Request $request)
    {
        $this->authorize('edit hotel property settings');
        
        $request->validate([
            // Hotel Accounts
            'hotel_room_revenue_account_id' => 'nullable|exists:chart_accounts,id',
            'hotel_service_revenue_account_id' => 'nullable|exists:chart_accounts,id',
            'hotel_food_beverage_account_id' => 'nullable|exists:chart_accounts,id',
            'hotel_operating_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'hotel_maintenance_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'hotel_marketing_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'hotel_discount_expense_account_id' => 'nullable|exists:chart_accounts,id',
            
            // Property Accounts
            'property_rental_income_account_id' => 'nullable|exists:chart_accounts,id',
            'property_service_charge_account_id' => 'nullable|exists:chart_accounts,id',
            'property_late_fee_account_id' => 'nullable|exists:chart_accounts,id',
            'property_operating_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'property_maintenance_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'property_utilities_expense_account_id' => 'nullable|exists:chart_accounts,id',
            'property_management_fee_account_id' => 'nullable|exists:chart_accounts,id',
            
            // Asset Accounts
            'property_asset_account_id' => 'nullable|exists:chart_accounts,id',
            'furniture_fixtures_account_id' => 'nullable|exists:chart_accounts,id',
            'accumulated_depreciation_account_id' => 'nullable|exists:chart_accounts,id',
            'security_deposit_liability_account_id' => 'nullable|exists:chart_accounts,id',
        ]);

        DB::beginTransaction();
        
        try {
            // Update hotel settings
            $hotelSettings = [
                'hotel_room_revenue_account_id',
                'hotel_service_revenue_account_id',
                'hotel_food_beverage_account_id',
                'hotel_operating_expense_account_id',
                'hotel_maintenance_expense_account_id',
                'hotel_marketing_expense_account_id',
                'hotel_discount_expense_account_id',
            ];
            
            foreach ($hotelSettings as $setting) {
                SystemSetting::setValue(
                    $setting,
                    $request->input($setting),
                    'integer',
                    'hotel_property',
                    SystemSetting::getValue($setting . '_label', ucwords(str_replace('_', ' ', $setting)))
                );
            }
            
            // Update property settings
            $propertySettings = [
                'property_rental_income_account_id',
                'property_service_charge_account_id',
                'property_late_fee_account_id',
                'property_operating_expense_account_id',
                'property_maintenance_expense_account_id',
                'property_utilities_expense_account_id',
                'property_management_fee_account_id',
            ];
            
            foreach ($propertySettings as $setting) {
                SystemSetting::setValue(
                    $setting,
                    $request->input($setting),
                    'integer',
                    'hotel_property',
                    SystemSetting::getValue($setting . '_label', ucwords(str_replace('_', ' ', $setting)))
                );
            }
            
            // Update asset settings
            $assetSettings = [
                'property_asset_account_id',
                'furniture_fixtures_account_id',
                'accumulated_depreciation_account_id',
                'security_deposit_liability_account_id',
            ];
            
            foreach ($assetSettings as $setting) {
                SystemSetting::setValue(
                    $setting,
                    $request->input($setting),
                    'integer',
                    'hotel_property',
                    SystemSetting::getValue($setting . '_label', ucwords(str_replace('_', ' ', $setting)))
                );
            }
            
            DB::commit();
            
            return redirect()->back()->with('success', 'Hotel & Property settings updated successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }
}