<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Backup;
use App\Models\ChartAccount;
use App\Models\SystemSetting;
use App\Services\BackupService;
use App\Services\AiAssistantService;
use App\Services\PasswordService;
use App\Rules\PasswordValidation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\ApprovalLevel;
use App\Models\ApprovalLevelAssignment;

class SettingsController extends Controller
{
    public function __construct()
    {
        // Middleware is applied in routes/web.php
    }

    public function index()
    {
        $company = auth()->user()->company;
        $branches = Branch::where('company_id', auth()->user()->company_id)->active()->get();

        return view('settings.index', compact('company', 'branches'));
    }

    public function companySettings()
    {
        $company = auth()->user()->company;

        return view('settings.company', compact('company'));
    }

    /**
     * API: Get company settings for public website
     * GET /api/settings/company
     */
    public function getCompanySettingsApi()
    {
        // Get default company (ID 1) or first available company
        $company = \App\Models\Company::find(1) ?? \App\Models\Company::first();
        
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'logo' => $company->logo ? asset('storage/' . $company->logo) : null,
            ],
        ]);
    }

    public function updateCompanySettings(Request $request)
    {
        $company = auth()->user()->company;

        // Custom validation for email to handle existing email
        $emailRules = 'required|email';
        if ($request->email !== $company->email) {
            $emailRules .= '|unique:companies,email,' . $company->id . ',id';
        }

        // Custom validation for license_number to handle existing license
        $licenseRules = 'required|string';
        if ($request->license_number !== $company->license_number) {
            $licenseRules .= '|unique:companies,license_number,' . $company->id . ',id';
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRules,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'license_number' => $licenseRules,
            'registration_date' => 'required|date',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bg_color' => 'nullable|string|max:7',
            'txt_color' => 'nullable|string|max:7',
        ]);

        $data = $request->except('logo');

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }

            $logo = $request->file('logo');
            $logoName = 'company_' . $company->id . '_' . time() . '.' . $logo->getClientOriginalExtension();
            $logoPath = $logo->storeAs('uploads/companies', $logoName, 'public');
            $data['logo'] = $logoPath;
        }

        $company->update($data);

        return redirect()->route('settings.company')->with('success', 'Company settings updated successfully!');
    }

    public function branchSettings(Request $request)
    {
        // If this is an AJAX request for DataTables, return JSON
        if ($request->ajax()) {
            return $this->branchesData($request);
        }

        return view('settings.branches');
    }

    public function branchesData(Request $request)
    {
        $query = Branch::with('manager')->where('company_id', auth()->user()->company_id);

        // Apply search filter
        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->where(function($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination
        $totalRecords = Branch::where('company_id', auth()->user()->company_id)->count();
        $filteredRecords = $query->count();

        // Apply ordering
        if ($request->has('order') && !empty($request->order)) {
            $orderColumn = $request->order[0]['column'] ?? 0;
            $orderDir = $request->order[0]['dir'] ?? 'asc';
            // Columns must match the DataTables columns (location removed from index view)
            $columns = ['branch_name', 'phone', 'email', 'manager_name', 'status'];
            $orderBy = $columns[$orderColumn] ?? 'branch_name';
            $query->orderBy($orderBy, $orderDir);
        } else {
            $query->orderBy('branch_name', 'asc');
        }

        // Apply pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $branches = $query->skip($start)->take($length)->get();

        $data = $branches->map(function($branch) {
            $statusBadge = match($branch->status) {
                'active' => '<span class="badge bg-success">Active</span>',
                'inactive' => '<span class="badge bg-warning">Inactive</span>',
                default => '<span class="badge bg-danger">Suspended</span>',
            };

            $actions = '<div class="d-flex gap-1">';
            $actions .= '<a href="' . route('settings.branches.edit', $branch) . '" class="btn btn-sm btn-primary" title="Edit">';
            $actions .= '<i class="bx bx-edit"></i>';
            $actions .= '</a>';
            $actions .= '<form action="' . route('settings.branches.destroy', $branch) . '" method="POST" style="display:inline-block;" class="delete-form">';
            $actions .= csrf_field();
            $actions .= method_field('DELETE');
            $actions .= '<button type="submit" class="btn btn-sm btn-danger" data-name="' . e($branch->branch_name) . '" title="Delete">';
            $actions .= '<i class="bx bx-trash"></i>';
            $actions .= '</button>';
            $actions .= '</form>';
            $actions .= '</div>';

            return [
                'branch_name' => e($branch->branch_name),
                'phone' => e($branch->phone ?? '-'),
                'email' => e($branch->email ?? '-'),
                'manager_name' => e($branch->manager->name ?? ($branch->manager_name ?? '-')),
                'status' => $statusBadge,
                'actions' => $actions,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function createBranch()
    {
        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        
        return view('settings.branches.create', compact('users'));
    }

    public function storeBranch(Request $request)
    {
        // Normalize manager_id - convert empty string to null
        if ($request->has('manager_id') && $request->manager_id === '') {
            $request->merge(['manager_id' => null]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:branches,email,NULL,id,company_id,' . auth()->user()->company_id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'manager_id' => 'nullable|exists:users,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'company_id' => auth()->user()->company_id,
            'name' => $request->name,
            'branch_name' => $request->branch_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'manager_id' => $request->manager_id,
            'branch_id' => \Illuminate\Support\Str::uuid(),
            'status' => $request->status,
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logoName = time() . '_' . $logo->getClientOriginalName();
            $logoPath = $logo->storeAs('branches/logos', $logoName, 'public');
            $data['logo'] = $logoPath;
        }

        $branch = Branch::create($data);

        return redirect()->route('settings.branches')->with('success', 'Branch created successfully!');
    }

    public function editBranch(Branch $branch)
    {
        // Ensure branch belongs to current company
        if ($branch->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        $users = \App\Models\User::where('company_id', auth()->user()->company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('settings.branches.edit', compact('branch', 'users'));
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        // Ensure branch belongs to current company
        if ($branch->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Normalize manager_id - convert empty string to null
        if ($request->has('manager_id') && $request->manager_id === '') {
            $request->merge(['manager_id' => null]);
        }

        // Custom validation for email to handle existing email
        $emailRules = 'nullable|email';
        if ($request->email !== $branch->email) {
            $emailRules .= '|unique:branches,email,' . $branch->id . ',id,company_id,' . auth()->user()->company_id;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'email' => $emailRules,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'manager_id' => 'nullable|exists:users,id',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'name' => $request->name,
            'branch_name' => $request->branch_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'manager_id' => $request->manager_id,
            'status' => $request->status,
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($branch->logo && Storage::disk('public')->exists($branch->logo)) {
                Storage::disk('public')->delete($branch->logo);
            }
            
            $logo = $request->file('logo');
            $logoName = time() . '_' . $logo->getClientOriginalName();
            $logoPath = $logo->storeAs('branches/logos', $logoName, 'public');
            $data['logo'] = $logoPath;
        }

        $branch->update($data);

        return redirect()->route('settings.branches')->with('success', 'Branch updated successfully!');
    }

    public function destroyBranch(Branch $branch)
    {
        // Ensure branch belongs to current company
        if ($branch->company_id !== auth()->user()->company_id) {
            abort(403, 'Unauthorized access.');
        }

        // Check if branch has users (using pivot table)
        if ($branch->users()->count() > 0) {
            return redirect()->route('settings.branches')->with('error', 'Cannot delete branch with active users.');
        }

        // Check for other related records that might prevent deletion
        $relatedRecords = [];
        
        // Check departments (branch-scoped)
        if (\Schema::hasTable('departments') && \DB::table('departments')->where('branch_id', $branch->id)->exists()) {
            $relatedRecords[] = 'Departments';
        }
        
        // Check if there are any other tables with branch_id foreign key
        // Add more checks as needed based on your schema
        
        if (!empty($relatedRecords)) {
            $recordsList = implode(', ', $relatedRecords);
            return redirect()->route('settings.branches')->with('error', "Cannot delete branch. It is being used in: {$recordsList}. Please remove or reassign these records first.");
        }

        try {
            $branch->delete();
            return redirect()->route('settings.branches')->with('success', 'Branch deleted successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle foreign key constraint violation
            if ($e->getCode() == 23000) {
                // Extract table name from error message if possible
                $errorMessage = $e->getMessage();
                $tableName = 'related records';
                
                // Try to extract table name from error message
                // Pattern: CONSTRAINT `table_name_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
                if (preg_match("/CONSTRAINT `(\w+)_branch_id_foreign`/", $errorMessage, $matches)) {
                    $tableName = $matches[1];
                } elseif (preg_match("/REFERENCES `(\w+)`/", $errorMessage, $matches)) {
                    $tableName = $matches[1];
                }
                
                // Convert table name to readable format
                $readableTableName = str_replace('_', ' ', $tableName);
                $readableTableName = ucwords($readableTableName);
                
                // Map common table names to user-friendly names
                $tableNameMap = [
                    'hr departments' => 'HR Departments',
                    'departments' => 'Departments',
                    'chart accounts' => 'Chart Accounts',
                    'budgets' => 'Budgets',
                ];
                
                $readableTableName = $tableNameMap[strtolower($readableTableName)] ?? $readableTableName;
                
                return redirect()->route('settings.branches')->with('error', "Cannot delete branch. It is being used in {$readableTableName}. Please remove or reassign these records first.");
            }
            
            // Re-throw if it's a different database error
            throw $e;
        }
    }

    public function userSettings()
    {
        $user = auth()->user();
        $user->load(['branch', 'company', 'roles']);

        return view('settings.user', compact('user'));
    }

    public function updateUserSettings(Request $request)
    {
        $user = auth()->user();

        // Custom validation for email to handle existing email
        $emailRules = 'nullable|email';
        if ($request->email !== $user->email) {
            $emailRules .= '|unique:users,email,' . $user->id . ',id,company_id,' . auth()->user()->company_id;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id . ',id,company_id,' . auth()->user()->company_id,
            'email' => $emailRules,
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed', new PasswordValidation($user)],
        ]);

        // Verify current password if changing password
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }
        }

        $userData = [
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
        ];

        $user->update($userData);

        // Update password if provided using PasswordService
        if ($request->filled('new_password')) {
            $passwordService = new PasswordService();
            $passwordService->updatePassword($user, $request->new_password);
        }

        return redirect()->route('settings.user')->with('success', 'User settings updated successfully!');
    }

    public function systemSettings()
    {
        // Check permissions for system configurations
        if (!auth()->user()->can('view system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to view system configurations.');
        }

        $groups = [
            'general' => 'General Settings',
            'email' => 'Email Configuration',
            'security' => 'Security Settings',
            'backup' => 'Backup Configuration',
            'maintenance' => 'Maintenance Settings',
            'company' => 'Company Settings',
            'sales' => 'Sales Settings',
            'documents' => 'Document & Print Settings',
            'subscription' => 'Subscription Settings'
        ];

        $groupIcons = [
            'general' => 'bx-cog',
            'email' => 'bx-envelope',
            'security' => 'bx-shield',
            'backup' => 'bx-data',
            'maintenance' => 'bx-wrench',
            'company' => 'bx-money',
            'sales' => 'bx-cart',
            'documents' => 'bx-printer',
            'subscription' => 'bx-calendar-check'
        ];

        $timezones = [
            'Africa/Dar_es_Salaam',
            'Africa/Nairobi',
            'Africa/Kampala',
            'Africa/Kigali',
            'Africa/Bujumbura',
            'UTC',
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'Europe/London',
            'Europe/Paris',
            'Europe/Berlin',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Asia/Kolkata',
            'Australia/Sydney',
            'Africa/Cairo',
            'Africa/Lagos',
            'America/Sao_Paulo',
            'Pacific/Auckland'
        ];

        $settings = [];
        foreach ($groups as $groupKey => $groupName) {
            $settings[$groupKey] = \App\Models\SystemSetting::getByGroup($groupKey);
        }

        return view('settings.system', compact('groups', 'groupIcons', 'timezones', 'settings'));
    }

    /**
     * Inventory Settings page
     */
    public function inventorySettings()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Build current settings array with defaults
        $get = fn($key, $default = null) => SystemSetting::where('key', $key)->value('value') ?? $default;

        $currentSettings = [
            'low_stock_threshold' => (int) ($get('inventory_low_stock_threshold', 10)),
            'auto_reorder_point' => (int) ($get('inventory_auto_reorder_point', 5)),
            'default_unit' => (string) ($get('inventory_default_unit', 'pieces')),
            'default_location' => (int) ($get('inventory_default_location', 1)),
            'cost_method' => (string) ($get('inventory_cost_method', 'fifo')),
            'barcode_prefix' => (string) ($get('inventory_barcode_prefix', 'INV')),
            'enable_batch_tracking' => (bool) ($get('inventory_enable_batch_tracking', true)),
            'enable_expiry_tracking' => (bool) ($get('inventory_enable_expiry_tracking', true)),
            'enable_serial_tracking' => (bool) ($get('inventory_enable_serial_tracking', false)),
            'global_expiry_warning_days' => (int) ($get('inventory_global_expiry_warning_days', 30)),
            'default_vat_type' => (string) ($get('inventory_default_vat_type', 'inclusive')),
            'default_vat_rate' => (float) ($get('inventory_default_vat_rate', 18.00)),
            'default_invoice_due_days' => (int) ($get('inventory_default_invoice_due_days', 30)),
            'default_inventory_account' => (int) ($get('inventory_default_inventory_account')),
            'default_sales_account' => (int) ($get('inventory_default_sales_account')),
            'default_cost_account' => (int) ($get('inventory_default_cost_account')),
            'default_opening_balance_account' => (int) ($get('inventory_default_opening_balance_account')),
            'default_vat_account' => (int) ($get('inventory_default_vat_account')),
            'default_withholding_tax_account' => (int) ($get('inventory_default_withholding_tax_account')),
            'default_withholding_tax_expense_account' => (int) ($get('inventory_default_withholding_tax_expense_account')),
            'default_purchase_payable_account' => (int) ($get('inventory_default_purchase_payable_account')),
            'default_discount_account' => (int) ($get('inventory_default_discount_account')),
            'default_discount_income_account' => (int) ($get('inventory_default_discount_income_account')),
            'default_is_withholding_receivable' => (bool) ($get('inventory_default_is_withholding_receivable', false)),
            'default_early_payment_discount_account' => (int) ($get('inventory_default_early_payment_discount_account')),
            'default_late_payment_fees_account' => (int) ($get('inventory_default_late_payment_fees_account')),
            'default_receivable_account' => (int) ($get('inventory_default_receivable_account')),
            'default_cash_account' => (int) ($get('inventory_default_cash_account')),
            'default_transport_revenue_account' => (int) ($get('inventory_default_transport_revenue_account')),
            'cheque_issued_account_id' => (int) ($get('cheque_issued_account_id')),
            'cheques_in_transit_account_id' => (int) ($get('cheques_in_transit_account_id')),
            'inventory_loss_expense_account' => (int) ($get('inventory_loss_expense_account')),
            'inventory_gain_income_account' => (int) ($get('inventory_gain_income_account')),
            'variance_value_threshold' => (float) ($get('inventory_variance_value_threshold', 50000)),
            'variance_percentage_threshold' => (float) ($get('inventory_variance_percentage_threshold', 5)),
        ];

        // Get expense and income accounts for inventory count adjustments
        $expenseAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $incomeAccounts = ChartAccount::query()->orderBy('account_code')->get();

        // Fetch account lists by common classes/groups if available
        $inventoryAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $salesAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $costAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $openingBalanceAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $vatAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $withholdingTaxAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $withholdingTaxExpenseAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $purchasePayableAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $discountAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $discountIncomeAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $earlyPaymentDiscountAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $latePaymentFeesAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $receivableAccounts = ChartAccount::query()->orderBy('account_code')->get();
        
        // Filter cash accounts - only show accounts from "Cash & Cash Equivalents" group
        // This includes: Cash on Hand (1001), Petty Cash (1002), and bank accounts
        // Find the group by name since the ID may vary by company
        $cashGroup = \App\Models\AccountClassGroup::where('company_id', $companyId)
            ->where('name', 'LIKE', '%Cash%')
            ->first();
        
        if ($cashGroup) {
            $cashAccounts = ChartAccount::where('account_class_group_id', $cashGroup->id)
                ->orderBy('account_code')
                ->get();
        } else {
            // Fallback: filter by account codes starting with 1001, 1002, or account names containing "Cash"
            $cashAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->where(function($query) {
                    $query->where('account_code', 'LIKE', '1001%')
                          ->orWhere('account_code', 'LIKE', '1002%')
                          ->orWhere('account_name', 'LIKE', '%Cash%')
                          ->orWhere('account_name', 'LIKE', '%Bank%');
                })
                ->orderBy('account_code')
                ->get();
        }
            
        $transportRevenueAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $chequeIssuedAccounts = ChartAccount::query()->orderBy('account_code')->get();
        $chequesInTransitAccounts = ChartAccount::query()->orderBy('account_code')->get();

        // Inventory locations for default storage location select
        $inventoryLocations = \App\Models\InventoryLocation::query()
            ->active()
            ->when(auth()->user()->branch_id, function ($q) {
                $q->where('branch_id', auth()->user()->branch_id);
            })
            ->orderBy('name')
            ->get();

        // Get count session approval settings
        $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany(auth()->user()->company_id);

        return view('settings.inventory.index', compact(
            'currentSettings',
            'inventoryAccounts',
            'salesAccounts',
            'costAccounts',
            'openingBalanceAccounts',
            'vatAccounts',
            'withholdingTaxAccounts',
            'withholdingTaxExpenseAccounts',
            'purchasePayableAccounts',
            'discountAccounts',
            'discountIncomeAccounts',
            'earlyPaymentDiscountAccounts',
            'latePaymentFeesAccounts',
            'receivableAccounts',
            'cashAccounts',
            'transportRevenueAccounts',
            'chequeIssuedAccounts',
            'chequesInTransitAccounts',
            'inventoryLocations',
            'expenseAccounts',
            'incomeAccounts',
            'approvalSettings'
        ));
    }

    public function updateInventorySettings(Request $request)
    {
        // Validate incoming data
        $request->validate([
            'low_stock_threshold' => 'required|integer|min:0',
            'auto_reorder_point' => 'required|integer|min:0',
            'default_unit' => 'required|string|max:50',
            'default_location' => 'nullable|integer|exists:inventory_locations,id',
            'cost_method' => 'required|in:fifo,weighted_average',
            'barcode_prefix' => 'nullable|string|max:10',
            'global_expiry_warning_days' => 'required|integer|min:1|max:365',
            'default_vat_type' => 'required|in:inclusive,exclusive,no_vat',
            'default_vat_rate' => 'required|numeric|min:0|max:100',
            'default_invoice_due_days' => 'nullable|integer|min:0',

            'default_inventory_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_sales_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_cost_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_opening_balance_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_vat_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_withholding_tax_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_withholding_tax_expense_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_purchase_payable_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_discount_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_discount_income_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_early_payment_discount_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_late_payment_fees_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_receivable_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_cash_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_transport_revenue_account' => 'nullable|integer|exists:chart_accounts,id',
            'default_is_withholding_receivable' => 'required|in:0,1',
            'cheque_issued_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'cheques_in_transit_account_id' => 'nullable|integer|exists:chart_accounts,id',
            'inventory_loss_expense_account' => 'nullable|integer|exists:chart_accounts,id',
            'inventory_gain_income_account' => 'nullable|integer|exists:chart_accounts,id',
            'variance_value_threshold' => 'required|numeric|min:0',
            'variance_percentage_threshold' => 'required|numeric|min:0|max:100',
        ]);

        // Booleans from switches
        $enableBatch = $request->boolean('enable_batch_tracking');
        $enableExpiry = $request->boolean('enable_expiry_tracking');
        $enableSerial = $request->boolean('enable_serial_tracking');

        // Persist settings
        $set = function (string $key, $value, string $type, string $label) {
            SystemSetting::setValue($key, $value, $type, 'inventory', $label);
        };

        $set('inventory_low_stock_threshold', (int) $request->input('low_stock_threshold'), 'integer', 'Low Stock Threshold');
        $set('inventory_auto_reorder_point', (int) $request->input('auto_reorder_point'), 'integer', 'Auto Reorder Point');
        $set('inventory_default_unit', (string) $request->input('default_unit'), 'string', 'Default Unit');
        $set('inventory_default_location', (int) ($request->input('default_location', 1)), 'integer', 'Default Location');
        $set('inventory_cost_method', (string) $request->input('cost_method'), 'string', 'Cost Method');
        $set('inventory_barcode_prefix', (string) $request->input('barcode_prefix'), 'string', 'Barcode Prefix');

        $set('inventory_enable_batch_tracking', $enableBatch, 'boolean', 'Enable Batch Tracking');
        $set('inventory_enable_expiry_tracking', $enableExpiry, 'boolean', 'Enable Expiry Tracking');
        $set('inventory_enable_serial_tracking', $enableSerial, 'boolean', 'Enable Serial Tracking');
        $set('inventory_global_expiry_warning_days', (int) $request->input('global_expiry_warning_days'), 'integer', 'Global Expiry Warning Days');
        $set('inventory_default_vat_type', (string) $request->input('default_vat_type'), 'string', 'Default VAT Type');
        $set('inventory_default_vat_rate', (float) $request->input('default_vat_rate'), 'decimal', 'Default VAT Rate');
        $set('inventory_default_invoice_due_days', (int) $request->input('default_invoice_due_days', 30), 'integer', 'Default Invoice Due Days');

        $set('inventory_default_inventory_account', $request->input('default_inventory_account'), 'integer', 'Default Inventory Account (Asset)');
        $set('inventory_default_sales_account', $request->input('default_sales_account'), 'integer', 'Default Sales Account (Revenue)');
        $set('inventory_default_cost_account', $request->input('default_cost_account'), 'integer', 'Default Cost Account (COGS)');
        $set('inventory_default_opening_balance_account', $request->input('default_opening_balance_account'), 'integer', 'Default Opening Balance Account (Retained Earnings)');
        $set('inventory_default_vat_account', $request->input('default_vat_account'), 'integer', 'Default VAT Account (Liability)');
        $set('inventory_default_withholding_tax_account', $request->input('default_withholding_tax_account'), 'integer', 'Default Withholding Tax Account (Liability)');
        $set('inventory_default_withholding_tax_expense_account', $request->input('default_withholding_tax_expense_account'), 'integer', 'Default Withholding Tax Expense Account (Expense)');
        $set('inventory_default_purchase_payable_account', $request->input('default_purchase_payable_account'), 'integer', 'Default Purchase Payable Account (Liability)');
        $set('inventory_default_discount_account', $request->input('default_discount_account'), 'integer', 'Default Discount Account (Expense)');
        $set('inventory_default_discount_income_account', $request->input('default_discount_income_account'), 'integer', 'Default Discount Income Account (Revenue)');
        $set('inventory_default_early_payment_discount_account', $request->input('default_early_payment_discount_account'), 'integer', 'Default Early Payment Discount Account (Expense)');
        $set('inventory_default_late_payment_fees_account', $request->input('default_late_payment_fees_account'), 'integer', 'Default Late Payment Fees Account (Revenue)');
        $set('inventory_default_receivable_account', $request->input('default_receivable_account'), 'integer', 'Default Accounts Receivable Account (Asset)');
        $set('inventory_default_cash_account', $request->input('default_cash_account'), 'integer', 'Default Cash Account (Cash on Hand)');
        $set('inventory_default_transport_revenue_account', $request->input('default_transport_revenue_account'), 'integer', 'Default Transport Revenue Account (Revenue)');
        $set('inventory_default_is_withholding_receivable', (bool) ((int) $request->input('default_is_withholding_receivable')), 'boolean', 'Default Withholding Tax Type');
        $set('cheque_issued_account_id', $request->input('cheque_issued_account_id'), 'integer', 'Cheque Issued Account (Liability)');
        $set('cheques_in_transit_account_id', $request->input('cheques_in_transit_account_id'), 'integer', 'Cheques in Transit Account (Asset)');
        $set('inventory_loss_expense_account', $request->input('inventory_loss_expense_account'), 'integer', 'Inventory Loss Expense Account');
        $set('inventory_gain_income_account', $request->input('inventory_gain_income_account'), 'integer', 'Inventory Gain Income Account');
        $set('inventory_variance_value_threshold', (float) $request->input('variance_value_threshold'), 'decimal', 'Variance Value Threshold');
        $set('inventory_variance_percentage_threshold', (float) $request->input('variance_percentage_threshold'), 'decimal', 'Variance Percentage Threshold');

        SystemSetting::clearCache();

        // Save count session approval settings
        if ($request->has('approval_levels')) {
            $approvalSettings = \App\Models\Inventory\CountSessionApprovalSetting::getForCompany(auth()->user()->company_id);
            
            $approvalSettings->update([
                'approval_levels' => (int) $request->input('approval_levels', 1),
                'require_approval_for_all' => $request->boolean('require_approval_for_all'),
                'escalation_time' => 24, // Default
            ]);

            // Update each level
            for ($i = 1; $i <= 5; $i++) {
                if ($request->has("level{$i}_name")) {
                    $approvalSettings->update([
                        "level{$i}_name" => $request->input("level{$i}_name"),
                        "level{$i}_approval_type" => $request->input("level{$i}_approval_type", 'role'),
                        "level{$i}_approvers" => $request->input("level{$i}_approvers", []),
                    ]);
                }
            }
        }

        return redirect()->route('settings.inventory')->with('success', 'Inventory settings updated successfully.');
    }

    /** Inventory Locations - list */
    public function inventoryLocations()
    {
        if (request()->ajax()) {
            $currentBranchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
            $locations = \App\Models\InventoryLocation::with(['manager'])
                // ->where('company_id', auth()->user()->company_id)
                ->when($currentBranchId, function($q) use ($currentBranchId){
                    $q->where('branch_id', $currentBranchId);
                })
                ->select('inventory_locations.*');

            return \Yajra\DataTables\Facades\DataTables::of($locations)
                ->addColumn('manager_name', function($loc){ return $loc->manager_name; })
                ->addColumn('status_badge', function($loc){ return $loc->status_badge; })
                ->addColumn('actions', function($loc){
                    $hashId = \Vinkla\Hashids\Facades\Hashids::encode($loc->id);
                    $name = e($loc->name);
                    return '<div class="d-flex gap-1">'
                        . '<a href="'.route('settings.inventory.locations.edit', $hashId).'" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>'
                        . '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="'.$hashId.'" data-name="'.$name.'" title="Delete"><i class="bx bx-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['status_badge','actions'])
                ->make(true);
        }
        return view('settings.inventory.locations.index');
    }

    /** Inventory Locations - create */
    public function createInventoryLocation()
    {
        $users = \App\Models\User::forCompany()->active()->orderBy('name')->get();
        $branches = \App\Models\Branch::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('settings.inventory.locations.create', compact('users','branches'));
    }

    /** Inventory Locations - store */
    public function storeInventoryLocation(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branch = \App\Models\Branch::findOrFail($request->branch_id);

        \App\Models\InventoryLocation::create([
            'name' => $request->name,
            'description' => $request->description,
            'manager_id' => $request->manager_id,
            'is_active' => $request->boolean('is_active', true),
            'branch_id' => $branch->id,
            'company_id' => $branch->company_id,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('settings.inventory.locations.index')->with('success', 'Location created successfully.');
    }

    /** Inventory Locations - show */
    public function showInventoryLocation($hashId)
    {
        $users = \App\Models\User::forCompany()->active()->orderBy('name')->get();
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);
        $locationId = $decoded[0] ?? null;
        $locationModel = \App\Models\InventoryLocation::findOrFail($locationId);
        return view('settings.inventory.locations.show', ['location' => $locationModel, 'users' => $users]);
    }

    /** Inventory Locations - edit */
    public function editInventoryLocation($hashId)
    {
        $users = \App\Models\User::forCompany()->active()->orderBy('name')->get();
        $branches = \App\Models\Branch::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);
        $locationId = $decoded[0] ?? null;
        $locationModel = \App\Models\InventoryLocation::findOrFail($locationId);
        return view('settings.inventory.locations.edit', ['location' => $locationModel, 'users' => $users, 'branches' => $branches]);
    }

    /** Inventory Locations - update */
    public function updateInventoryLocation(Request $request, $hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);
        $location = $decoded[0] ?? null;
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $loc = \App\Models\InventoryLocation::findOrFail($location);
        // Ensure same company
        if ($loc->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $branch = \App\Models\Branch::findOrFail($request->branch_id);
        if ($branch->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $loc->update([
            'name' => $request->name,
            'description' => $request->description,
            'manager_id' => $request->manager_id,
            'is_active' => $request->boolean('is_active', true),
            'branch_id' => $branch->id,
            'company_id' => $branch->company_id,
        ]);

        return redirect()->route('settings.inventory.locations.index')->with('success', 'Location updated successfully.');
    }

    /** Inventory Locations - destroy */
    public function destroyInventoryLocation($hashId)
    {
        try {
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);
            $location = $decoded[0] ?? null;
            if (!$location) {
                return response()->json(['success' => false, 'message' => 'Invalid location identifier.'], 404);
            }
            $loc = \App\Models\InventoryLocation::findOrFail($location);
            // Ensure same branch (use session branch when available)
            $currentBranchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
            if ($currentBranchId && $loc->branch_id !== $currentBranchId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: You can only delete locations in your current branch.'], 403);
            }
            // Prevent deletion if location has items (stock levels)
            $hasStockLevels = \App\Models\Inventory\StockLevel::where('inventory_location_id', $loc->id)->exists();
            if ($hasStockLevels) {
                // Check if there are items with quantity > 0
                $hasItemsInStock = \App\Models\Inventory\StockLevel::where('inventory_location_id', $loc->id)
                    ->where('quantity', '>', 0)
                    ->exists();
                
                if ($hasItemsInStock) {
                    $itemCount = \App\Models\Inventory\StockLevel::where('inventory_location_id', $loc->id)
                        ->where('quantity', '>', 0)
                        ->count();
                    
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot delete location. It currently has {$itemCount} item(s) in stock. Please transfer or remove all items before deleting this location."
                    ], 422);
                } else {
                    // Has stock level records but all quantities are 0
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete location because it has inventory item records. Please remove all item associations first.'
                    ], 422);
                }
            }
            
            // Prevent deletion if used by any inventory movements
            $inUse = \App\Models\Inventory\Movement::where('location_id', $loc->id)->exists();
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete location because it is referenced by inventory movements.'
                ], 422);
            }
            $loc->delete();
            return response()->json(['success' => true, 'message' => 'Location deleted successfully.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Location not found.'], 404);
        } catch (\Throwable $e) {
            \Log::error('Delete location failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Failed to delete location.'], 500);
        }
    }

    public function updateSystemSettings(Request $request)
    {
        // Check permissions for editing system configurations
        if (!auth()->user()->can('edit system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to edit system configurations.');
        }

        $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        try {
            foreach ($request->settings as $key => $value) {
                $setting = \App\Models\SystemSetting::where('key', $key)->first();

                if ($setting) {
                    // Handle different input types
                    if ($setting->type === 'boolean') {
                        $value = $value === '1' || $value === 'true' || $value === 'on';
                    } elseif ($setting->type === 'integer') {
                        $value = (int) $value;
                    }

                    $setting->update(['value' => $value]);
                }
            }

            // Clear cache
            \App\Models\SystemSetting::clearCache();

            // Apply security settings to configuration
            $this->applySecuritySettings();

            return redirect()->route('settings.system')->with('success', 'System settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.system')->with('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    /**
     * Apply security settings to Laravel configuration
     */
    private function applySecuritySettings()
    {
        $securityConfig = \App\Services\SystemSettingService::getSecurityConfig();
        
        // Apply session lifetime
        if (isset($securityConfig['session_lifetime'])) {
            config(['session.lifetime' => $securityConfig['session_lifetime']]);
        }

        // Apply rate limiting settings to config
        $rateLimitingConfig = \App\Services\SystemSettingService::getRateLimitingConfig();
        config(['rate-limiting' => $rateLimitingConfig]);
    }

    public function resetSystemSettings()
    {
        // Check permissions for managing system configurations
        if (!auth()->user()->can('manage system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to reset system configurations.');
        }

        try {
            \App\Models\SystemSetting::truncate();
            \App\Models\SystemSetting::initializeDefaults();

            return redirect()->route('settings.system')->with('success', 'System settings reset to defaults successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.system')->with('error', 'Failed to reset settings: ' . $e->getMessage());
        }
    }



    /**
     * Test email configuration
     */
    public function testEmailConfig()
    {
        try {
            $result = \App\Services\SystemSettingService::testEmailConfig();

            if ($result['success']) {
                return response()->json(['success' => true, 'message' => $result['message']]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message']], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Email test failed: ' . $e->getMessage()], 500);
        }
    }

    public function backupSettings()
    {
        // Check permissions for backup settings
        if (!auth()->user()->can('view backup settings') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to view backup settings.');
        }

        $backupService = new BackupService();
        $backups = Backup::forCompany()->orderBy('created_at', 'desc')->paginate(10);
        $stats = $backupService->getBackupStats();

        return view('settings.backup', compact('backups', 'stats'));
    }

    public function createBackup(Request $request)
    {
        // Check permissions for creating backups
        if (!auth()->user()->can('create backup') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to create backups.');
        }

        $request->validate([
            'type' => 'required|in:database,files,full',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            // Create backup record with 'in_progress' status immediately
            $backup = Backup::create([
                'name' => ucfirst($request->type) . ' Backup - ' . date('Y-m-d H:i:s'),
                'filename' => 'pending',
                'file_path' => 'pending',
                'type' => $request->type,
                'size' => 0,
                'description' => $request->description,
                'status' => 'in_progress',
                'created_by' => auth()->id(),
                'company_id' => current_company_id(),
            ]);

            // Dispatch job to process backup in background
            \App\Jobs\CreateBackupJob::dispatch(
                $backup->id,
                $request->type,
                $request->description
            );

            return redirect()->route('settings.backup')->with('success', ucfirst($request->type) . ' backup is being created in the background. Please refresh the page in a few moments to see the status.');

        } catch (\Exception $e) {
            return redirect()->route('settings.backup')->with('error', 'Failed to initiate backup: ' . $e->getMessage());
        }
    }

    public function restoreBackup(Request $request)
    {
        // Check permissions for restoring backups
        if (!auth()->user()->can('restore backup') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to restore backups.');
        }

        $request->validate([
            'backup_id' => 'required|exists:backups,id',
        ]);

        try {
            $backup = Backup::forCompany()->findOrFail($request->backup_id);
            $backupService = new BackupService();

            $backupService->restoreBackup($backup);

            return redirect()->route('settings.backup')->with('success', 'Backup restored successfully!');

        } catch (\Exception $e) {
            return redirect()->route('settings.backup')->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    public function downloadBackup($hash_id)
    {
        // Check permissions for downloading backups
        if (!auth()->user()->can('view backup settings') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to download backups.');
        }

        // Decode hash ID to get backup ID
        $id = Hashids::decode($hash_id);
        if (empty($id)) {
            abort(404, 'Backup not found.');
        }

        $backup = Backup::forCompany()->find($id[0]);
        if (!$backup) {
            abort(404, 'Backup not found.');
        }

        $fullPath = storage_path('app/' . $backup->file_path);
        if (!file_exists($fullPath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($fullPath, $backup->filename);
    }

    public function deleteBackup($hash_id)
    {
        // Check permissions for deleting backups
        if (!auth()->user()->can('delete backup') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to delete backups.');
        }

        // Decode hash ID to get backup ID
        $id = Hashids::decode($hash_id);
        if (empty($id)) {
            abort(404, 'Backup not found.');
        }

        $backup = Backup::forCompany()->find($id[0]);
        if (!$backup) {
            abort(404, 'Backup not found.');
        }

        try {
            $backup->deleteFile();
            $backup->delete();

            return redirect()->route('settings.backup')->with('success', 'Backup deleted successfully!');

        } catch (\Exception $e) {
            return redirect()->route('settings.backup')->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    public function cleanOldBackups(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        try {
            $backupService = new BackupService();
            $deletedCount = $backupService->cleanOldBackups($request->days);

            return redirect()->route('settings.backup')->with('success', "{$deletedCount} old backups cleaned successfully!");

        } catch (\Exception $e) {
            return redirect()->route('settings.backup')->with('error', 'Clean failed: ' . $e->getMessage());
        }
    }

    /**
     * AI Assistant Settings
     */
    public function aiAssistantSettings()
    {
        return view('settings.ai-assistant');
    }

    /**
     * Handle AI chat requests
     */
    public function aiChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $aiService = new AiAssistantService();
            $response = $aiService->processMessage($request->message);

            return response()->json([
                'success' => true,
                'response' => $response
            ]);

        } catch (\Exception $e) {
            \Log::error('AI Chat Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'AI processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Penalty Settings
     */
    public function penaltySettings()
    {
        return view('settings.penalty');
    }

    /**
     * Update Penalty Settings
     */
    public function updatePenaltySettings(Request $request)
    {
        $request->validate([
            'late_payment_penalty' => 'required|numeric|min:0|max:100',
            'penalty_grace_period' => 'required|integer|min:0|max:365',
            'penalty_calculation_method' => 'required|in:percentage,fixed',
            'penalty_currency' => 'required|string|max:10',
        ]);

        try {
            // Update penalty settings logic here
            // This would typically save to a settings table or config file

            return redirect()->route('settings.penalty')->with('success', 'Penalty settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.penalty')->with('error', 'Failed to update penalty settings: ' . $e->getMessage());
        }
    }

    /**
     * Fees Settings
     */
    public function feesSettings()
    {
        return view('settings.fees');
    }

    /**
     * Update Fees Settings
     */
    public function updateFeesSettings(Request $request)
    {
        $request->validate([
            'service_fee_percentage' => 'required|numeric|min:0|max:100',
            'transaction_fee' => 'required|numeric|min:0',
            'minimum_fee' => 'required|numeric|min:0',
            'maximum_fee' => 'required|numeric|min:0',
            'fee_currency' => 'required|string|max:10',
        ]);

        try {
            // Update fees settings logic here
            // This would typically save to a settings table or config file

            return redirect()->route('settings.fees')->with('success', 'Fees settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.fees')->with('error', 'Failed to update fees settings: ' . $e->getMessage());
        }
    }

    /**
     * Subscription Settings
     */
    public function subscriptionSettings()
    {
        return view('settings.subscription');
    }

    /**
     * Update Subscription Settings
     */
    public function updateSubscriptionSettings(Request $request)
    {
        $request->validate([
            'subscription_plan' => 'required|string|in:basic,premium,enterprise',
            'billing_cycle' => 'required|string|in:monthly,quarterly,yearly',
            'auto_renewal' => 'boolean',
            'payment_method' => 'required|string|in:credit_card,bank_transfer,mobile_money',
            'billing_email' => 'required|email',
            'billing_address' => 'required|string',
        ]);

        try {
            // Update subscription settings logic here
            // This would typically save to a settings table or config file

            return redirect()->route('settings.subscription')->with('success', 'Subscription settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.subscription')->with('error', 'Failed to update subscription settings: ' . $e->getMessage());
        }
    }

    /**
     * Payment Voucher Approval Settings
     */
    public function paymentVoucherApprovalSettings()
    {
        $user = Auth::user();
        
        // Load roles and users for dropdowns
        $roles = \Spatie\Permission\Models\Role::all();
        $users = \App\Models\User::where('company_id', $user->company_id)->get();
        
        // Load existing approval settings
        $settings = \App\Models\PaymentVoucherApprovalSetting::where('company_id', $user->company_id)->first();
        
        return view('settings.payment-voucher-approval', compact('roles', 'users', 'settings'));
    }
    /**
     * Update Payment Voucher Approval Settings
     */
    public function updatePaymentVoucherApprovalSettings(Request $request)
    {
        $requireAll = $request->has('require_approval_for_all');

        $baseRules = [
            'require_approval_for_all' => 'boolean',
        ];

        $approvalRules = [
            'approval_levels' => 'required|integer|min:1|max:5',
            'level1_approval_type' => 'required|in:role,user',
            'level1_approvers' => 'required|array|min:1',
            'level2_approval_type' => 'nullable|in:role,user',
            'level2_approvers' => 'nullable|array',
            'level3_approval_type' => 'nullable|in:role,user',
            'level3_approvers' => 'nullable|array',
            'level4_approval_type' => 'nullable|in:role,user',
            'level4_approvers' => 'nullable|array',
            'level5_approval_type' => 'nullable|in:role,user',
            'level5_approvers' => 'nullable|array',
        ];

        $rules = $requireAll ? array_merge($baseRules, $approvalRules) : $baseRules;
        $request->validate($rules);

        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            // Find or create approval settings for the company
            $settings = \App\Models\PaymentVoucherApprovalSetting::firstOrCreate(
                ['company_id' => $companyId],
                [
                    'approval_levels' => 1,
                    'require_approval_for_all' => false,
                ]
            );

            // Update settings
            $updateData = [
                'require_approval_for_all' => $requireAll,
            ];
            if ($requireAll) {
                // Only approval_levels is strictly needed; keep others as previously configured
                $updateData = array_merge($updateData, [
                    'approval_levels' => $request->approval_levels,
                ]);
            }
            $settings->update($updateData);

            // Update approval assignments
            if ($requireAll) {
                $approvalLevels = (int) $request->approval_levels;
                
                for ($level = 1; $level <= $approvalLevels; $level++) {
                    $approvalType = $request->{"level{$level}_approval_type"};
                    $approvers = $request->{"level{$level}_approvers"} ?? [];

                    if ($approvalType && !empty($approvers)) {
                        // Process approver IDs - extract actual IDs from "user_X" or "role_X" format
                        $processedApprovers = [];
                        foreach ($approvers as $approver) {
                            if (str_starts_with($approver, 'user_')) {
                                $userId = (int) str_replace('user_', '', $approver);
                                $processedApprovers[] = $userId;
                            } elseif (str_starts_with($approver, 'role_')) {
                                $roleName = str_replace('role_', '', $approver);
                                $processedApprovers[] = $roleName;
                            }
                        }

                        $settings->update([
                            "level{$level}_approval_type" => $approvalType,
                            "level{$level}_approvers" => $processedApprovers,
                        ]);
                    }
                }
                
                // Clear unused levels
                for ($level = $approvalLevels + 1; $level <= 5; $level++) {
                    $settings->update([
                        "level{$level}_approval_type" => null,
                        "level{$level}_approvers" => null,
                    ]);
                }
            } else {
                // When approvals disabled, clear approval configuration
                $settings->update([
                    'approval_levels' => 0,
                    'level1_approval_type' => null,
                    'level1_approvers' => null,
                    'level2_approval_type' => null,
                    'level2_approvers' => null,
                    'level3_approval_type' => null,
                    'level3_approvers' => null,
                    'level4_approval_type' => null,
                    'level4_approvers' => null,
                    'level5_approval_type' => null,
                    'level5_approvers' => null,
                ]);
            }

            return redirect()->route('settings.payment-voucher-approval')->with('success', 'Payment voucher approval settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.payment-voucher-approval')->with('error', 'Failed to update payment voucher approval settings: ' . $e->getMessage());
        }
    }

    /**
     * Account Transfer Approval Settings
     */
    public function accountTransferApprovalSettings()
    {
        $user = Auth::user();
        
        // Load roles and users for dropdowns
        $roles = \Spatie\Permission\Models\Role::all();
        $users = \App\Models\User::where('company_id', $user->company_id)->get();
        
        // Load existing approval settings
        $settings = \App\Models\AccountTransferApprovalSetting::where('company_id', $user->company_id)->first();
        
        return view('settings.account-transfer-approval', compact('roles', 'users', 'settings'));
    }

    /**
     * Update Account Transfer Approval Settings
     */
    public function updateAccountTransferApprovalSettings(Request $request)
    {
        $requireAll = $request->has('require_approval_for_all');

        $baseRules = [
            'require_approval_for_all' => 'boolean',
        ];

        $approvalRules = [
            'approval_levels' => 'required|integer|min:1|max:5',
            'level1_approval_type' => 'required|in:role,user',
            'level1_approvers' => 'required|array|min:1',
            'level2_approval_type' => 'nullable|in:role,user',
            'level2_approvers' => 'nullable|array',
            'level3_approval_type' => 'nullable|in:role,user',
            'level3_approvers' => 'nullable|array',
            'level4_approval_type' => 'nullable|in:role,user',
            'level4_approvers' => 'nullable|array',
            'level5_approval_type' => 'nullable|in:role,user',
            'level5_approvers' => 'nullable|array',
        ];

        $rules = $requireAll ? array_merge($baseRules, $approvalRules) : $baseRules;
        $request->validate($rules);

        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            // Find or create approval settings for the company
            $settings = \App\Models\AccountTransferApprovalSetting::firstOrCreate(
                ['company_id' => $companyId],
                [
                    'approval_levels' => 1,
                    'require_approval_for_all' => false,
                ]
            );

            // Update settings
            $updateData = [
                'require_approval_for_all' => $requireAll,
            ];
            if ($requireAll) {
                $updateData = array_merge($updateData, [
                    'approval_levels' => $request->approval_levels,
                ]);
            }
            $settings->update($updateData);

            // Update approval assignments
            if ($requireAll) {
                $approvalLevels = (int) $request->approval_levels;
                
                for ($level = 1; $level <= $approvalLevels; $level++) {
                    $approvalType = $request->{"level{$level}_approval_type"};
                    $approvers = $request->{"level{$level}_approvers"} ?? [];

                    if ($approvalType && !empty($approvers)) {
                        // Process approver IDs - extract actual IDs from "user_X" or "role_X" format
                        $processedApprovers = [];
                        foreach ($approvers as $approver) {
                            if (str_starts_with($approver, 'user_')) {
                                $userId = (int) str_replace('user_', '', $approver);
                                $processedApprovers[] = $userId;
                            } elseif (str_starts_with($approver, 'role_')) {
                                $roleName = str_replace('role_', '', $approver);
                                $processedApprovers[] = $roleName;
                            }
                        }

                        $settings->update([
                            "level{$level}_approval_type" => $approvalType,
                            "level{$level}_approvers" => $processedApprovers,
                        ]);
                    }
                }
                
                // Clear unused levels
                for ($level = $approvalLevels + 1; $level <= 5; $level++) {
                    $settings->update([
                        "level{$level}_approval_type" => null,
                        "level{$level}_approvers" => null,
                    ]);
                }
            } else {
                // When approvals disabled, clear approval configuration
                $settings->update([
                    'approval_levels' => 0,
                    'level1_approval_type' => null,
                    'level1_approvers' => null,
                    'level2_approval_type' => null,
                    'level2_approvers' => null,
                    'level3_approval_type' => null,
                    'level3_approvers' => null,
                    'level4_approval_type' => null,
                    'level4_approvers' => null,
                    'level5_approval_type' => null,
                    'level5_approvers' => null,
                ]);
            }

            return redirect()->route('settings.account-transfer-approval')->with('success', 'Account transfer approval settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.account-transfer-approval')->with('error', 'Failed to update account transfer approval settings: ' . $e->getMessage());
        }
    }

    /**
     * Display SMS Settings page
     */
    public function smsSettings()
    {
        return view('settings.sms');
    }

    /**
     * Update SMS Settings
     */
    public function updateSmsSettings(Request $request)
    {
        $request->validate([
            'sms_url' => 'required|url',
            'sms_senderid' => 'required|string|max:255',
            'sms_key' => 'required|string|max:255',
            'sms_token' => 'required|string|max:255',
            'test_phone' => 'nullable|string|max:20',
        ]);

        try {
            // Update .env file with SMS settings
            $envKeys = [
                'BEEM_SMS_URL' => $request->sms_url,
                'BEEM_SENDER_ID' => $request->sms_senderid,
                'BEEM_API_KEY' => $request->sms_key,
                'BEEM_SECRET_KEY' => $request->sms_token,
            ];

            // Also set fallback SMS_* keys
            $envKeys['SMS_URL'] = $request->sms_url;
            $envKeys['SMS_SENDERID'] = $request->sms_senderid;
            $envKeys['SMS_KEY'] = $request->sms_key;
            $envKeys['SMS_TOKEN'] = $request->sms_token;

            foreach ($envKeys as $key => $value) {
                if (!update_env_file($key, $value)) {
                    throw new \Exception("Failed to update {$key} in .env file");
                }
            }

            // Clear config cache to reload .env values
            \Artisan::call('config:clear');

            // If test phone is provided, send test SMS
            if ($request->filled('test_phone')) {
                // Temporarily update config to use new values for testing
                config([
                    'services.sms.senderid' => $request->sms_senderid,
                    'services.sms.token' => $request->sms_token,
                    'services.sms.key' => $request->sms_key,
                    'services.sms.url' => $request->sms_url,
                ]);

                $testResult = \App\Helpers\SmsHelper::test($request->test_phone);
                
                if ($testResult['success'] ?? false) {
                    return redirect()->route('settings.sms')->with('success', 'SMS settings updated and test SMS sent successfully!');
                } else {
                    return redirect()->route('settings.sms')
                        ->with('success', 'SMS settings updated successfully!')
                        ->with('warning', 'Test SMS failed: ' . ($testResult['error'] ?? 'Unknown error'));
                }
            }

            return redirect()->route('settings.sms')->with('success', 'SMS settings updated successfully! Please note that you may need to restart your application server for changes to take full effect.');
        } catch (\Exception $e) {
            \Log::error('SMS Settings Update Error: ' . $e->getMessage());
            return redirect()->route('settings.sms')->with('error', 'Failed to update SMS settings: ' . $e->getMessage());
        }
    }

    /**
     * Test SMS Configuration
     */
    public function testSmsSettings(Request $request)
    {
        try {
            $request->validate([
                'test_phone' => 'required|string|max:20',
                'sms_url' => 'nullable|url',
                'sms_senderid' => 'nullable|string|max:255',
                'sms_key' => 'nullable|string|max:255',
                'sms_token' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()['test_phone'] ?? ['Invalid input'])
            ], 422);
        }

        try {
            // If form values are provided, use them temporarily for testing
            if ($request->filled('sms_url') && $request->filled('sms_senderid') && 
                $request->filled('sms_key') && $request->filled('sms_token')) {
                // Temporarily update config to use form values
                config([
                    'services.sms.senderid' => $request->sms_senderid,
                    'services.sms.token' => $request->sms_token,
                    'services.sms.key' => $request->sms_key,
                    'services.sms.url' => $request->sms_url,
                ]);
            }

            $result = \App\Helpers\SmsHelper::test($request->test_phone);
            
            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test SMS sent successfully! Please check the recipient phone.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Failed to send test SMS'
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('SMS Test Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Journal Entry Approval Settings
     */
    public function journalEntryApprovalSettings()
    {
        $user = Auth::user();
        
        // Load roles and users for dropdowns
        $roles = \Spatie\Permission\Models\Role::all();
        $users = \App\Models\User::where('company_id', $user->company_id)->get();
        
        // Load existing approval settings
        $settings = \App\Models\JournalEntryApprovalSetting::where('company_id', $user->company_id)->first();
        
        return view('settings.journal-entry-approval', compact('roles', 'users', 'settings'));
    }

    /**
     * Update Journal Entry Approval Settings
     */
    public function updateJournalEntryApprovalSettings(Request $request)
    {
        $requireAll = $request->has('require_approval_for_all');

        $baseRules = [
            'require_approval_for_all' => 'boolean',
        ];

        $approvalRules = [
            'approval_levels' => 'required|integer|min:1|max:5',
            'level1_approval_type' => 'required|in:role,user',
            'level1_approvers' => 'required|array|min:1',
            'level2_approval_type' => 'nullable|in:role,user',
            'level2_approvers' => 'nullable|array',
            'level3_approval_type' => 'nullable|in:role,user',
            'level3_approvers' => 'nullable|array',
            'level4_approval_type' => 'nullable|in:role,user',
            'level4_approvers' => 'nullable|array',
            'level5_approval_type' => 'nullable|in:role,user',
            'level5_approvers' => 'nullable|array',
        ];

        $rules = $requireAll ? array_merge($baseRules, $approvalRules) : $baseRules;
        $request->validate($rules);

        try {
            $user = Auth::user();
            $companyId = $user->company_id;

            // Find or create approval settings for the company
            $settings = \App\Models\JournalEntryApprovalSetting::firstOrCreate(
                ['company_id' => $companyId],
                [
                    'approval_levels' => 1,
                    'require_approval_for_all' => false,
                ]
            );

            // Update settings
            $updateData = [
                'require_approval_for_all' => $requireAll,
            ];
            if ($requireAll) {
                $updateData = array_merge($updateData, [
                    'approval_levels' => $request->approval_levels,
                ]);
            }
            $settings->update($updateData);

            // Update approval assignments
            if ($requireAll) {
                $approvalLevels = (int) $request->approval_levels;
                
                for ($level = 1; $level <= $approvalLevels; $level++) {
                    $approvalType = $request->{"level{$level}_approval_type"};
                    $approvers = $request->{"level{$level}_approvers"} ?? [];

                    if ($approvalType && !empty($approvers)) {
                        // Process approver IDs - extract actual IDs from "user_X" or "role_X" format
                        $processedApprovers = [];
                        foreach ($approvers as $approver) {
                            if (str_starts_with($approver, 'user_')) {
                                $userId = (int) str_replace('user_', '', $approver);
                                $processedApprovers[] = $userId;
                            } elseif (str_starts_with($approver, 'role_')) {
                                $roleName = str_replace('role_', '', $approver);
                                $processedApprovers[] = $roleName;
                            }
                        }

                        $settings->update([
                            "level{$level}_approval_type" => $approvalType,
                            "level{$level}_approvers" => $processedApprovers,
                        ]);
                    }
                }
                
                // Clear approvers for levels beyond the selected number
                for ($level = $approvalLevels + 1; $level <= 5; $level++) {
                    $settings->update([
                        "level{$level}_approval_type" => null,
                        "level{$level}_approvers" => null,
                    ]);
                }
            } else {
                // Clear all approval settings when disabled
                $settings->update([
                    'approval_levels' => 1,
                    'level1_approval_type' => null,
                    'level1_approvers' => null,
                    'level2_approval_type' => null,
                    'level2_approvers' => null,
                    'level3_approval_type' => null,
                    'level3_approvers' => null,
                    'level4_approval_type' => null,
                    'level4_approvers' => null,
                    'level5_approval_type' => null,
                    'level5_approvers' => null,
                ]);
            }

            return redirect()->route('settings.journal-entry-approval')->with('success', 'Journal entry approval settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.journal-entry-approval')->with('error', 'Failed to update journal entry approval settings: ' . $e->getMessage());
        }
    }

    /**
     * IAS 37 Provision Approval Settings
     */
    public function provisionApprovalSettings()
    {
        $user = Auth::user();

        $roles = \Spatie\Permission\Models\Role::all();
        $users = \App\Models\User::where('company_id', $user->company_id)->get();

        // Load existing approval levels for module 'provision'
        $levels = ApprovalLevel::with('assignments')
            ->where('module', 'provision')
            ->where('company_id', $user->company_id)
            ->orderBy('approval_order')
            ->get();

        return view('settings.provision-approval', [
            'roles' => $roles,
            'users' => $users,
            'levels' => $levels,
        ]);
    }

    /**
     * Update IAS 37 Provision Approval Settings
     */
    public function updateProvisionApprovalSettings(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        // First validate the number of levels only
        $base = $request->validate([
            'approval_levels' => 'required|integer|min:0|max:5',
        ]);

        $requestedLevels = (int) $base['approval_levels'];

        // Build per-level validation rules so only active levels are required
        $rules = [
            'levels' => 'array',
        ];

        for ($i = 1; $i <= $requestedLevels; $i++) {
            $rules["levels.$i.level_name"] = 'required|string|max:255';
            $rules["levels.$i.approval_type"] = 'required|in:role,user';
            $rules["levels.$i.approvers"] = 'required|array|min:1';
        }

        // Extra levels (above requestedLevels) are optional if present
        for ($i = $requestedLevels + 1; $i <= 5; $i++) {
            $rules["levels.$i.level_name"] = 'nullable|string|max:255';
            $rules["levels.$i.approval_type"] = 'nullable|in:role,user';
            $rules["levels.$i.approvers"] = 'nullable|array';
        }

        $data = $request->validate($rules);

        try {
            \DB::beginTransaction();

            // First, clear existing provision approval levels and assignments
            $existingLevels = ApprovalLevel::where('module', 'provision')
                ->where('company_id', $companyId)
                ->get();

            $levelIds = $existingLevels->pluck('id')->all();
            if (!empty($levelIds)) {
                ApprovalLevelAssignment::whereIn('approval_level_id', $levelIds)->delete();
            }
            ApprovalLevel::whereIn('id', $levelIds)->delete();

            // Recreate levels and assignments
            for ($order = 1; $order <= $requestedLevels; $order++) {
                $levelData = $data['levels'][$order] ?? null;
                if (!$levelData) {
                    continue;
                }

                $level = ApprovalLevel::create([
                    'module' => 'provision',
                    'company_id' => $companyId,
                    'level' => $order,
                    'approval_order' => $order,
                    'level_name' => $levelData['level_name'],
                    'is_required' => true,
                ]);

                $approvalType = $levelData['approval_type'];
                $approvers = $levelData['approvers'] ?? [];

                foreach ($approvers as $approver) {
                    $assignment = new ApprovalLevelAssignment();
                    $assignment->approval_level_id = $level->id;

                    if ($approvalType === 'role' && str_starts_with($approver, 'role_')) {
                        $roleName = str_replace('role_', '', $approver);
                        $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                        if ($role) {
                            $assignment->role_id = $role->id;
                        }
                    } elseif ($approvalType === 'user' && str_starts_with($approver, 'user_')) {
                        $userId = (int) str_replace('user_', '', $approver);
                        $assignment->user_id = $userId;
                    }

                    if ($assignment->role_id || $assignment->user_id) {
                        $assignment->save();
                    }
                }
            }

            \DB::commit();

            return redirect()
                ->route('settings.provision-approval')
                ->with('success', 'Provision approval settings updated successfully!');
        } catch (\Throwable $e) {
            \DB::rollBack();
            return redirect()
                ->route('settings.provision-approval')
                ->with('error', 'Failed to update provision approval settings: ' . $e->getMessage());
        }
    }

    /**
     * Show petty cash settings page
     */
    public function pettyCashSettings()
    {
        // Check permissions
        if (!auth()->user()->can('view system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to view petty cash settings.');
        }

        $user = auth()->user();
        $companyId = $user->company_id;
        
        // Get or create petty cash settings
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
        
        // Get expense accounts for allowed categories
        $expenseAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereHas('accountClass', function($q2) {
                  $q2->where('name', 'LIKE', '%Expense%')
                     ->orWhere('name', 'LIKE', '%Cost%');
              });
        })->orderBy('account_code')->get();

        return view('settings.petty-cash', compact('settings', 'expenseAccounts'));
    }

    /**
     * Update petty cash settings
     */
    public function updatePettyCashSettings(Request $request)
    {
        // Check permissions
        if (!auth()->user()->can('edit system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to edit petty cash settings.');
        }

        $user = auth()->user();
        $companyId = $user->company_id;

        $request->validate([
            'operation_mode' => 'required|in:sub_imprest,standalone',
            'default_float_amount' => 'nullable|numeric|min:0',
            'max_transaction_amount' => 'nullable|numeric|min:0',
            'maximum_limit' => 'nullable|numeric|min:0',
            'allowed_expense_categories' => 'nullable|array',
            'allowed_expense_categories.*' => 'exists:chart_accounts,id',
            'require_receipt' => 'nullable|boolean',
            'minimum_balance_trigger' => 'nullable|numeric|min:0',
            'auto_approve_below_threshold' => 'nullable|boolean',
            'approval_required' => 'nullable|boolean',
            'approval_levels' => 'nullable|integer|min:1|max:5',
            'auto_approval_limit' => 'nullable|numeric|min:0',
            'approval_threshold_1' => 'nullable|numeric|min:0',
            'approval_threshold_2' => 'nullable|numeric|min:0',
            'approval_threshold_3' => 'nullable|numeric|min:0',
            'approval_threshold_4' => 'nullable|numeric|min:0',
            'approval_threshold_5' => 'nullable|numeric|min:0',
            'escalation_time' => 'nullable|integer|min:1',
            'require_approval_for_all' => 'nullable|boolean',
            'level1_approval_type' => 'nullable|in:role,user',
            'level1_approvers' => 'nullable|array',
            'level2_approval_type' => 'nullable|in:role,user',
            'level2_approvers' => 'nullable|array',
            'level3_approval_type' => 'nullable|in:role,user',
            'level3_approvers' => 'nullable|array',
            'level4_approval_type' => 'nullable|in:role,user',
            'level4_approvers' => 'nullable|array',
            'level5_approval_type' => 'nullable|in:role,user',
            'level5_approvers' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
            
            $updateData = [
                'operation_mode' => $request->operation_mode,
                'default_float_amount' => $request->default_float_amount,
                'max_transaction_amount' => $request->max_transaction_amount,
                'maximum_limit' => $request->maximum_limit,
                'allowed_expense_categories' => $request->allowed_expense_categories ?? [],
                'require_receipt' => $request->has('require_receipt'),
                'minimum_balance_trigger' => $request->minimum_balance_trigger,
                'auto_approve_below_threshold' => $request->has('auto_approve_below_threshold'),
                'approval_required' => $request->has('approval_required'),
                'approval_levels' => $request->approval_levels ?? 2,
                'auto_approval_limit' => $request->auto_approval_limit,
                'approval_threshold_1' => $request->approval_threshold_1,
                'approval_threshold_2' => $request->approval_threshold_2,
                'approval_threshold_3' => $request->approval_threshold_3,
                'approval_threshold_4' => $request->approval_threshold_4,
                'approval_threshold_5' => $request->approval_threshold_5,
                'escalation_time' => $request->escalation_time ?? 24,
                'require_approval_for_all' => $request->has('require_approval_for_all'),
                'notes' => $request->notes,
            ];

            // Process approval level settings
            for ($level = 1; $level <= 5; $level++) {
                $approvalType = $request->{"level{$level}_approval_type"};
                $approvers = $request->{"level{$level}_approvers"} ?? [];
                
                $updateData["level{$level}_approval_type"] = $approvalType;
                
                // Process approvers - store role names or user IDs as integers
                if ($approvalType && !empty($approvers)) {
                    $processedApprovers = [];
                    foreach ($approvers as $approver) {
                        if ($approvalType === 'user') {
                            // Store user ID as integer
                            $processedApprovers[] = (int) $approver;
                        } else {
                            // Store role name as string
                            $processedApprovers[] = $approver;
                        }
                    }
                    $updateData["level{$level}_approvers"] = $processedApprovers;
                } else {
                    $updateData["level{$level}_approvers"] = [];
                }
            }

            $settings->update($updateData);

            return redirect()->route('settings.petty-cash')->with('success', 'Petty cash settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.petty-cash')->with('error', 'Failed to update petty cash settings: ' . $e->getMessage());
        }
    }

    /**
     * Show budget settings page
     */
    public function budgetSettings()
    {
        // Check permissions
        if (!auth()->user()->can('view system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to view budget settings.');
        }

        $budgetCheckEnabled = SystemSetting::getValue('budget_check_enabled', false);
        $budgetOverBudgetPercentage = SystemSetting::getValue('budget_over_budget_percentage', 10);
        $budgetRequireAllocation = SystemSetting::getValue('budget_require_allocation', false);

        return view('settings.budget', compact('budgetCheckEnabled', 'budgetOverBudgetPercentage', 'budgetRequireAllocation'));
    }

    /**
     * Update budget settings
     */
    public function updateBudgetSettings(Request $request)
    {
        // Check permissions
        if (!auth()->user()->can('edit system configurations') && 
            !auth()->user()->can('manage system settings') && 
            !auth()->user()->hasRole('admin')) {
            abort(403, 'You do not have permission to edit budget settings.');
        }

        $request->validate([
            'budget_check_enabled' => 'nullable|boolean',
            'budget_over_budget_percentage' => 'required|integer|min:0|max:100',
            'budget_require_allocation' => 'nullable|boolean',
        ]);

        try {
            // Update budget check enabled setting
            SystemSetting::setValue(
                'budget_check_enabled',
                $request->has('budget_check_enabled') ? '1' : '0',
                'boolean',
                'budget',
                'Enable Budget Checking',
                'Enable or disable budget checking for expenses. When enabled, the system will check if expenses exceed budget limits.'
            );

            // Update over budget percentage setting
            SystemSetting::setValue(
                'budget_over_budget_percentage',
                $request->budget_over_budget_percentage,
                'integer',
                'budget',
                'Over Budget Percentage Allowed',
                'Percentage over budget that is allowed before blocking expenses. For example, 10 means expenses can exceed budget by up to 10%.'
            );

            // Update require allocation setting
            SystemSetting::setValue(
                'budget_require_allocation',
                $request->has('budget_require_allocation') ? '1' : '0',
                'boolean',
                'budget',
                'Require Budget Allocation',
                'When enabled, payment vouchers for accounts not included in the budget will be blocked. When disabled, they will be allowed with a warning.'
            );

            // Clear cache
            SystemSetting::clearCache();

            return redirect()->route('settings.budget')->with('success', 'Budget settings updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('settings.budget')->with('error', 'Failed to update budget settings: ' . $e->getMessage());
        }
    }

    /**
     * LIPISHA Payment Gateway Settings
     */
    public function lipishaSettings()
    {
        // Get from .env first, then fallback to SystemSetting
        $settings = [
            'enabled' => env('LIPISHA_ENABLED', SystemSetting::getValue('lipisha_enabled', false)),
            'business_id' => env('LIPISHA_BUSINESS_ID', SystemSetting::getValue('lipisha_business_id', '')),
            'business_name' => env('LIPISHA_BUSINESS_NAME', SystemSetting::getValue('lipisha_business_name', '')),
            'business_key' => env('LIPISHA_BUSINESS_KEY', SystemSetting::getValue('lipisha_business_key', '')),
            'verify_token' => env('LIPISHA_VERIFY_TOKEN', SystemSetting::getValue('lipisha_verify_token', '')),
        ];

        return view('settings.lipisha', compact('settings'));
    }

    /**
     * Update LIPISHA Settings
     */
    public function updateLipishaSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'nullable|boolean',
            'business_id' => 'required_if:enabled,1|string|max:255',
            'business_name' => 'required_if:enabled,1|string|max:255',
            'business_key' => 'required_if:enabled,1|string|max:255',
            'verify_token' => 'required_if:enabled,1|string|max:255',
        ]);

        try {
            $enabled = $request->has('enabled') && $request->enabled == '1';
            
            // Save enabled status
            SystemSetting::setValue(
                'lipisha_enabled',
                $enabled ? '1' : '0',
                'boolean',
                'payment',
                'LIPISHA Integration Enabled',
                'Enable or disable LIPISHA payment gateway integration'
            );

            // Only save credentials if enabled
            if ($enabled) {
                // Save settings
                SystemSetting::setValue(
                    'lipisha_business_id',
                    $request->business_id,
                    'string',
                    'payment',
                    'LIPISHA Business ID',
                    'Your LIPISHA business identifier'
                );

                SystemSetting::setValue(
                    'lipisha_business_name',
                    $request->business_name,
                    'string',
                    'payment',
                    'LIPISHA Business Name',
                    'Your registered business name with LIPISHA'
                );

                SystemSetting::setValue(
                    'lipisha_business_key',
                    $request->business_key,
                    'string',
                    'payment',
                    'LIPISHA Business Key',
                    'Your LIPISHA API business key'
                );

                SystemSetting::setValue(
                    'lipisha_verify_token',
                    $request->verify_token,
                    'string',
                    'payment',
                    'LIPISHA Verify Token',
                    'Token for verifying webhook requests from LIPISHA'
                );
            }

            // Clear cache
            SystemSetting::clearCache();

            // Also update .env file
            $this->updateEnvFile([
                'LIPISHA_ENABLED' => $enabled ? 'true' : 'false',
                'LIPISHA_BUSINESS_ID' => $request->business_id ?? '',
                'LIPISHA_BUSINESS_NAME' => $request->business_name ?? '',
                'LIPISHA_BUSINESS_KEY' => $request->business_key ?? '',
                'LIPISHA_VERIFY_TOKEN' => $request->verify_token ?? '',
            ]);

            $message = $enabled 
                ? 'LIPISHA integration enabled and settings updated successfully!' 
                : 'LIPISHA integration disabled successfully!';
            
            return redirect()->route('settings.lipisha')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('settings.lipisha')->with('error', 'Failed to update LIPISHA settings: ' . $e->getMessage());
        }
    }

    /**
     * Update .env file with LIPISHA settings
     */
    private function updateEnvFile($data)
    {
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            return; // .env file doesn't exist, skip
        }

        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            // Escape special characters in value
            $escapedValue = str_replace('"', '\"', $value);
            
            // Check if key exists in .env
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}=\"{$escapedValue}\"",
                    $envContent
                );
            } else {
                // Add new key at the end
                $envContent .= "\n{$key}=\"{$escapedValue}\"\n";
            }
        }

        file_put_contents($envFile, $envContent);
    }

    /**
     * Test LIPISHA Network Connection and Generate Token
     */
    public function testLipishaNetwork(Request $request)
    {
        try {
            // Get from .env first, then fallback to SystemSetting
            $businessId = env('LIPISHA_BUSINESS_ID', SystemSetting::getValue('lipisha_business_id', ''));
            $businessName = env('LIPISHA_BUSINESS_NAME', SystemSetting::getValue('lipisha_business_name', ''));
            $businessKey = env('LIPISHA_BUSINESS_KEY', SystemSetting::getValue('lipisha_business_key', ''));

            if (empty($businessId) || empty($businessName) || empty($businessKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please configure LIPISHA Business ID, Business Name, and Business Key first.'
                ]);
            }

            // Use the correct LIPISHA API endpoint for token generation
            $url = "https://lipisha.co/v1/api/auth/generate-token";

            $postData = [
                "business_id" => $businessId,
                "business_name" => $businessName,
                "business_key" => $businessKey
            ];

            $headers = [
                "Content-Type: application/json"
            ];

            // Initialize cURL
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Network error: ' . $curlError
                ]);
            }

            if ($httpCode !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'API returned HTTP code: ' . $httpCode . '. Please check your credentials and API endpoint.'
                ]);
            }

            $result = json_decode($response, true);

            // Log the response for debugging
            \Log::info('LIPISHA API Response:', ['response' => $result]);

            // Check if response is successful - LIPISHA might return different response structures
            $isSuccess = false;
            $token = null;
            $message = '';

            // Check various possible success indicators
            if (isset($result['status']) && ($result['status'] == 'SUCCESS' || $result['status'] == 'success')) {
                $isSuccess = true;
                $token = $result['token'] ?? $result['data']['token'] ?? null;
                $message = $result['message'] ?? 'Token generated successfully';
            } elseif (isset($result['token'])) {
                // If token exists directly, consider it success
                $isSuccess = true;
                $token = $result['token'];
                $message = $result['message'] ?? 'Token generated successfully';
            } elseif (isset($result['data']['token'])) {
                // Token might be nested in data
                $isSuccess = true;
                $token = $result['data']['token'];
                $message = $result['message'] ?? 'Token generated successfully';
            } elseif (isset($result['message']) && stripos($result['message'], 'success') !== false) {
                // If message contains "success", treat as success
                $isSuccess = true;
                $token = $result['token'] ?? $result['data']['token'] ?? null;
                $message = $result['message'];
            }

            if ($isSuccess) {
                return response()->json([
                    'success' => true,
                    'message' => 'LIPISHA connection test successful! ' . $message,
                    'token' => $token,
                    'data' => $result
                ]);
            } else {
                $errorMessage = isset($result['message']) 
                    ? $result['message'] 
                    : (isset($result['error']) ? $result['error'] : (isset($result['status']) ? $result['status'] : 'Unknown error'));
                    
                return response()->json([
                    'success' => false,
                    'message' => 'LIPISHA API error: ' . $errorMessage,
                    'data' => $result
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate LIPISHA Token (for use in other parts of the application)
     */
    public function generateLipishaToken()
    {
        try {
            $businessId = env('LIPISHA_BUSINESS_ID', SystemSetting::getValue('lipisha_business_id', ''));
            $businessName = env('LIPISHA_BUSINESS_NAME', SystemSetting::getValue('lipisha_business_name', ''));
            $businessKey = env('LIPISHA_BUSINESS_KEY', SystemSetting::getValue('lipisha_business_key', ''));

            if (empty($businessId) || empty($businessName) || empty($businessKey)) {
                return null;
            }

            $url = "https://lipisha.co/v1/api/auth/generate-token";

            $postData = [
                "business_id" => $businessId,
                "business_name" => $businessName,
                "business_key" => $businessKey
            ];

            $headers = [
                "Content-Type: application/json"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['status']) && $result['status'] == 'SUCCESS') {
                return $result['token'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('LIPISHA token generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Start Queue Worker
     * Allows starting queue worker from web interface
     */
    public function startQueueWorker(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('manage system settings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to start queue worker.'
            ], 403);
        }

        try {
            $basePath = base_path();
            $command = 'php artisan queue:work --tries=15 --timeout=180 --max-jobs=1000 --max-time=3600';
            
            // Check if running on Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: Start process in background using PowerShell
                $psCommand = "Start-Process powershell -ArgumentList '-NoProfile', '-Command', 'cd \"$basePath\"; $command' -WindowStyle Hidden";
                $fullCommand = "powershell -Command \"$psCommand\"";
            } else {
                // Linux/Mac: Start process in background
                $fullCommand = "cd $basePath && nohup $command > /dev/null 2>&1 &";
            }

            // Execute command
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: Use exec with output
                exec($fullCommand, $output, $returnVar);
            } else {
                // Linux/Mac: Use shell_exec
                shell_exec($fullCommand);
                $returnVar = 0;
            }

            \Log::info('Queue worker start command executed', [
                'command' => $fullCommand,
                'return_var' => $returnVar,
                'output' => $output ?? []
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Queue worker started successfully. It will process jobs in the background.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to start queue worker: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start queue worker: ' . $e->getMessage()
            ], 500);
        }
    }
}
