<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\ChartAccount;
use App\Models\GlTransaction;
use App\Models\Branch;
use App\Traits\GetsCurrenciesFromFxRates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Vinkla\Hashids\Facades\Hashids;

class BankAccountController extends Controller
{
    use GetsCurrenciesFromFxRates;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Calculate statistics
        $totalAccounts = BankAccount::count();

        // Calculate balances from GL transactions for statistics
        $allBankAccounts = BankAccount::with('chartAccount')->get()->map(function ($bankAccount) {
            $debits = GlTransaction::where('chart_account_id', $bankAccount->chart_account_id)
                ->where('nature', 'debit')
                ->sum('amount');
            $credits = GlTransaction::where('chart_account_id', $bankAccount->chart_account_id)
                ->where('nature', 'credit')
                ->sum('amount');
            $bankAccount->balance = $debits - $credits;
            return $bankAccount;
        });

        $totalBalance = $allBankAccounts->sum('balance');
        $positiveBalanceAccounts = $allBankAccounts->where('balance', '>', 0)->count();
        $negativeBalanceAccounts = $allBankAccounts->where('balance', '<', 0)->count();

        return view('bank-accounts.index', compact('totalAccounts', 'totalBalance', 'positiveBalanceAccounts', 'negativeBalanceAccounts'));
    }

    /**
     * Get bank accounts data for DataTables AJAX
     */
    public function getData(Request $request)
    {
        $query = BankAccount::with('chartAccount.accountClassGroup.accountClass')
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('account_number', 'like', "%{$searchValue}%")
                  ->orWhereHas('chartAccount', function($subQ) use ($searchValue) {
                      $subQ->where('account_name', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Get total count before pagination
        $totalRecords = BankAccount::count();
        $filteredRecords = $query->count();

        // Apply pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $bankAccounts = $query->skip($start)->take($length)->get();

        // Calculate balance for each bank account
        $bankAccounts->transform(function ($bankAccount) {
            $debits = GlTransaction::where('chart_account_id', $bankAccount->chart_account_id)
                ->where('nature', 'debit')
                ->sum('amount');
            $credits = GlTransaction::where('chart_account_id', $bankAccount->chart_account_id)
                ->where('nature', 'credit')
                ->sum('amount');
            $bankAccount->balance = $debits - $credits;
            return $bankAccount;
        });

        // Prepare data for DataTables
        $data = [];
        foreach ($bankAccounts as $index => $bankAccount) {
            // Concatenate bank account name with currency
            $nameWithCurrency = $bankAccount->name;
            if ($bankAccount->currency) {
                $nameWithCurrency .= ' - ' . $bankAccount->currency;
            }
            
            $data[] = [
                'DT_RowIndex' => $start + $index + 1,
                'name' => $nameWithCurrency,
                'account_number' => $bankAccount->account_number,
                'chart_account' => $bankAccount->chartAccount->account_name ?? 'N/A',
                'account_class' => $bankAccount->chartAccount->accountClassGroup->accountClass->name ?? 'N/A',
                'account_group' => $bankAccount->chartAccount->accountClassGroup->name ?? 'N/A',
                'balance' => $bankAccount->balance,
                'created_at' => format_date($bankAccount->created_at, 'M d, Y'),
                'actions' => $this->getActionButtons($bankAccount)
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Generate action buttons for DataTables
     */
    private function getActionButtons($bankAccount)
    {
        $actions = '';
        
        // View button
        if (auth()->user()->can('view bank account details')) {
            $actions .= '<a href="' . route('accounting.bank-accounts.show', Hashids::encode($bankAccount->id)) . '" class="btn btn-sm btn-info me-1">View</a>';
        }

        // Edit button
        if (auth()->user()->can('edit bank account')) {
            $actions .= '<a href="' . route('accounting.bank-accounts.edit', Hashids::encode($bankAccount->id)) . '" class="btn btn-sm btn-primary me-1">Edit</a>';
        }

        // Delete button
        if (auth()->user()->can('delete bank account')) {
            $isLocked = $bankAccount->glTransactions()->exists();
            if ($isLocked) {
                $actions .= '<button class="btn btn-sm btn-outline-secondary" title="Bank account is used in GL Transactions and cannot be deleted" disabled><i class="bx bx-lock"></i> Locked</button>';
            } else {
                $actions .= '<form action="' . route('accounting.bank-accounts.destroy', Hashids::encode($bankAccount->id)) . '" method="POST" class="d-inline delete-form">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger" data-name="' . $bankAccount->name . '">Delete</button>
                </form>';
            }
        }

        return $actions;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $chartAccounts = ChartAccount::with('accountClassGroup.accountClass')
            ->whereHas('accountClassGroup.accountClass', function($q) {
                $q->where('name', 'Assets');
            })
            ->orderBy('account_name')
            ->get();

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        // Branch scope options - all branches or specific branch
        $branches = Branch::where('company_id', $user->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('bank-accounts.create', compact('chartAccounts', 'currencies', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
        
        $request->validate([
            'chart_account_id' => 'required|exists:chart_accounts,id',
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number',
            'currency' => 'nullable|string|size:3',
            'revaluation_required' => 'nullable|boolean',
            'branch_scope' => 'required|in:all,specific',
            'branch_id' => 'nullable|required_if:branch_scope,specific|exists:branches,id',
        ]);

        $data = $request->all();
        $data['company_id'] = Auth::user()->company_id;
        $data['currency'] = $data['currency'] ?? $functionalCurrency;
        // Branch scoping
        $data['is_all_branches'] = $data['branch_scope'] === 'all';
        $data['branch_id'] = $data['is_all_branches'] ? null : $data['branch_id'];
        // Only require revaluation if currency is different from functional currency
        if ($data['currency'] === $functionalCurrency) {
            $data['revaluation_required'] = false;
        }

        BankAccount::create($data);

        return redirect()->route('accounting.bank-accounts')
            ->with('success', 'Bank account created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.bank-accounts')->withErrors(['Bank account not found.']);
        }

        $bankAccount = BankAccount::findOrFail($decoded[0]);
        $bankAccount->load('chartAccount.accountClassGroup.accountClass');

        return view('bank-accounts.show', compact('bankAccount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.bank-accounts')->withErrors(['Bank account not found.']);
        }

        $bankAccount = BankAccount::findOrFail($decoded[0]);
        $chartAccounts = ChartAccount::with('accountClassGroup.accountClass')
            ->orderBy('account_name')
            ->get();

        // Get currencies from FX RATES MANAGEMENT
        $currencies = $this->getCurrenciesFromFxRates();

        // Branch scope options - all branches or specific branch
        $branches = Branch::where('company_id', Auth::user()->company_id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('bank-accounts.edit', compact('bankAccount', 'chartAccounts', 'currencies', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.bank-accounts')->withErrors(['Bank account not found.']);
        }

        $bankAccount = BankAccount::findOrFail($decoded[0]);

        $functionalCurrency = \App\Models\SystemSetting::getValue('functional_currency', Auth::user()->company->functional_currency ?? 'TZS');
        
        $request->validate([
            'chart_account_id' => 'required|exists:chart_accounts,id',
            'name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255|unique:bank_accounts,account_number,' . $bankAccount->id,
            'currency' => 'nullable|string|size:3',
            'revaluation_required' => 'nullable|boolean',
            'branch_scope' => 'required|in:all,specific',
            'branch_id' => 'nullable|required_if:branch_scope,specific|exists:branches,id',
        ]);

        $data = $request->all();
        $data['currency'] = $data['currency'] ?? $functionalCurrency;
        // Branch scoping
        $data['is_all_branches'] = $data['branch_scope'] === 'all';
        $data['branch_id'] = $data['is_all_branches'] ? null : $data['branch_id'];
        // Only require revaluation if currency is different from functional currency
        if ($data['currency'] === $functionalCurrency) {
            $data['revaluation_required'] = false;
        }

        $bankAccount->update($data);

        return redirect()->route('accounting.bank-accounts')
            ->with('success', 'Bank account updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        // Decode the ID
        $decoded = Hashids::decode($encodedId);
        if (empty($decoded)) {
            return redirect()->route('accounting.bank-accounts')->withErrors(['Bank account not found.']);
        }

        $bankAccount = BankAccount::findOrFail($decoded[0]);

        // Prevent delete if used in GL Transactions
        $hasGlTransactions = $bankAccount->glTransactions()->exists();
        if ($hasGlTransactions) {
            return redirect()->route('accounting.bank-accounts')->withErrors(['This bank account cannot be deleted because its chart account is used in GL Transactions.']);
        }

        try {
            $bankAccount->delete();
            return redirect()->route('accounting.bank-accounts')->with('success', 'Bank account deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete bank account. Please try again.');
        }
    }
}