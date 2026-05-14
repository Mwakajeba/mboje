<?php

namespace App\Http\Controllers\Accounting\PettyCash;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ChartAccount;
use App\Models\PettyCash\PettyCashUnit;
use App\Models\PettyCash\PettyCashExpenseCategory;
use App\Models\BankAccount;
use App\Models\Journal;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\Storage;

class PettyCashUnitController extends Controller
{
    /**
     * Display a listing of petty cash units
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Handle AJAX request for DataTables
        if ($request->ajax()) {
            // Get petty cash settings for minimum balance trigger
            $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
            $minimumBalanceTrigger = $settings->minimum_balance_trigger;
            
            $query = PettyCashUnit::forCompany($companyId)
                ->with(['branch', 'custodian', 'supervisor', 'pettyCashAccount']);
            
            if ($request->filled('branch_id')) {
                $query->forBranch($request->branch_id);
            }
            
            return datatables($query)
                ->filter(function ($query) use ($request) {
                    if ($request->filled('search.value')) {
                        $searchValue = $request->input('search.value');
                        $query->where(function($q) use ($searchValue) {
                            $q->where('name', 'like', "%{$searchValue}%")
                              ->orWhere('code', 'like', "%{$searchValue}%")
                              ->orWhereHas('branch', function($branchQuery) use ($searchValue) {
                                  $branchQuery->where('name', 'like', "%{$searchValue}%");
                              })
                              ->orWhereHas('custodian', function($custodianQuery) use ($searchValue) {
                                  $custodianQuery->where('name', 'like', "%{$searchValue}%");
                              });
                        });
                    }
                })
                ->addColumn('branch_name', function ($unit) {
                    return $unit->branch->name ?? 'N/A';
                })
                ->addColumn('custodian_name', function ($unit) {
                    return $unit->custodian->name ?? 'N/A';
                })
                ->addColumn('supervisor_name', function ($unit) {
                    return $unit->supervisor->name ?? 'N/A';
                })
                ->addColumn('float_amount_formatted', function ($unit) {
                    return number_format($unit->float_amount, 2) . ' TZS';
                })
                ->addColumn('current_balance_formatted', function ($unit) use ($minimumBalanceTrigger) {
                    $balance = number_format($unit->current_balance, 2) . ' TZS';
                    
                    // Check against minimum balance trigger if set, otherwise use 20% of float
                    $isLowBalance = false;
                    if ($minimumBalanceTrigger && $unit->current_balance < $minimumBalanceTrigger) {
                        $isLowBalance = true;
                    } elseif (!$minimumBalanceTrigger && $unit->current_balance < ($unit->float_amount * 0.2)) {
                        $isLowBalance = true;
                    }
                    
                    $class = $isLowBalance ? 'text-danger' : 'text-success';
                    $alertIcon = $isLowBalance ? ' <i class="bx bx-error-circle" title="Balance below minimum threshold"></i>' : '';
                    return '<span class="' . $class . ' fw-bold">' . $balance . $alertIcon . '</span>';
                })
                ->addColumn('status_badge', function ($unit) {
                    $badgeClass = $unit->is_active ? 'success' : 'secondary';
                    $statusText = $unit->is_active ? 'Active' : 'Inactive';
                    return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
                })
                ->addColumn('actions', function ($unit) {
                    $encodedId = $unit->encoded_id;
                    $actions = '<div class="btn-group" role="group">';
                    
                    $actions .= '<a href="' . route('accounting.petty-cash.units.show', $encodedId) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bx bx-show"></i>
                    </a>';
                    
                    $actions .= '<a href="' . route('accounting.petty-cash.units.edit', $encodedId) . '" class="btn btn-sm btn-warning" title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>';
                    
                    $canDelete = $unit->transactions()->count() == 0;
                    $disabledClass = $canDelete ? '' : ' disabled';
                    $onclick = $canDelete ? 'onclick="deleteUnit(\'' . $encodedId . '\')"' : '';
                    
                    $actions .= '<button type="button" class="btn btn-sm btn-danger' . $disabledClass . '" ' . $onclick . ' title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>';
                    
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['current_balance_formatted', 'status_badge', 'actions'])
                ->make(true);
        }
        
        // Calculate stats
        $totalUnits = PettyCashUnit::forCompany($companyId)->count();
        $activeUnits = PettyCashUnit::forCompany($companyId)->active()->count();
        $totalFloat = PettyCashUnit::forCompany($companyId)->sum('float_amount');
        $totalBalance = PettyCashUnit::forCompany($companyId)->sum('current_balance');
        
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        
        // Get petty cash settings for minimum balance trigger
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
        $minimumBalanceTrigger = $settings->minimum_balance_trigger;
        
        return view('accounting.petty-cash.units.index', compact('totalUnits', 'activeUnits', 'totalFloat', 'totalBalance', 'branches', 'minimumBalanceTrigger'));
    }

    /**
     * Show the form for creating a new petty cash unit
     */
    public function create()
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        $users = \App\Models\User::where('company_id', $companyId)->orderBy('name')->get();
        
        // Get asset accounts for petty cash
        $pettyCashAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereHas('accountClass', function($q2) {
                  $q2->where('name', 'LIKE', '%asset%');
              });
        })->orderBy('account_code')->get();
        
        // Get bank accounts
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->with('chartAccount')
        ->orderBy('name')
        ->get();
        
        // Get petty cash settings to pre-populate defaults
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
        
        return view('accounting.petty-cash.units.create', compact('branches', 'users', 'pettyCashAccounts', 'bankAccounts', 'settings'));
    }

    /**
     * Store a newly created petty cash unit
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Get petty cash settings to validate against maximum limit
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
        $systemMaximumLimit = $settings ? $settings->maximum_limit : null;
        
        // Build validation rules
        $validationRules = [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('petty_cash_units', 'code')->whereNull('deleted_at')
            ],
            'branch_id' => 'nullable|exists:branches,id',
            'custodian_id' => 'required|exists:users,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'float_amount' => 'required|numeric|min:0',
            'maximum_limit' => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'petty_cash_account_id' => 'required|exists:chart_accounts,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'notes' => 'nullable|string',
        ];
        
        // Add validation to ensure float_amount doesn't exceed system maximum limit
        if ($systemMaximumLimit && $systemMaximumLimit > 0) {
            $validationRules['float_amount'] .= '|max:' . $systemMaximumLimit;
        }
        
        $validated = $request->validate($validationRules);
        
        // Additional validation: Check if float_amount exceeds the effective maximum limit
        // (either the unit's maximum_limit or the system's maximum_limit)
        $effectiveMaximumLimit = $validated['maximum_limit'] ?? $systemMaximumLimit;
        if ($effectiveMaximumLimit && $effectiveMaximumLimit > 0) {
            if ($validated['float_amount'] > $effectiveMaximumLimit) {
                return back()->withInput()->withErrors([
                    'float_amount' => "Float amount (TZS " . number_format($validated['float_amount'], 2) . ") cannot exceed the maximum limit of TZS " . number_format($effectiveMaximumLimit, 2) . "."
                ]);
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Use maximum_limit from settings as default if not provided
            if (empty($validated['maximum_limit']) && $settings && $settings->maximum_limit) {
                $validated['maximum_limit'] = $settings->maximum_limit;
            }
            
            // Use max_transaction_amount from settings as default for approval_threshold if not provided
            if (empty($validated['approval_threshold']) && $settings && $settings->max_transaction_amount) {
                $validated['approval_threshold'] = $settings->max_transaction_amount;
            }
            
            $validated['company_id'] = $companyId;
            $validated['current_balance'] = $validated['float_amount'];
            $validated['is_active'] = true;
            
            // Resolve branch_id
            $branchId = $validated['branch_id'] ?? session('branch_id') ?? $user->branch_id;
            
            // Get bank account and its chart account
            $bankAccount = BankAccount::with('chartAccount')->findOrFail($validated['bank_account_id']);
            $bankChartAccountId = $bankAccount->chart_account_id;
            
            if (!$bankChartAccountId) {
                throw new \Exception('Bank account does not have a chart account assigned.');
            }
            
            // Get petty cash chart account
            $pettyCashChartAccountId = $validated['petty_cash_account_id'];
            
            // Create petty cash unit
            $unit = PettyCashUnit::create($validated);
            
            // Initialize approval workflow for the unit
            $unit->initializeApprovalWorkflow();
            
            // Only create journal and activate if auto-approved
            if ($unit->isFullyApproved()) {
            // Create opening balance register entry
            try {
                \App\Services\PettyCashModeService::createOpeningBalanceEntry($unit);
            } catch (\Exception $e) {
                \Log::warning('Failed to create opening balance register entry', [
                    'unit_id' => $unit->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Create journal entry for GL double entry
            $nextId = Journal::max('id') + 1;
            $reference = 'PCU-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
            
            $journal = Journal::create([
                'date' => now()->toDateString(),
                'description' => 'Petty Cash Unit Setup: ' . $unit->name . ' - Initial Float',
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'reference_type' => 'Petty Cash Unit Setup',
                'reference' => $reference,
            ]);
            
            // Debit: Petty Cash Account (increasing petty cash asset)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $pettyCashChartAccountId,
                'amount' => $validated['float_amount'],
                'nature' => 'debit',
                'description' => 'Petty Cash Float - ' . $unit->name,
            ]);
            
            // Credit: Bank Account (decreasing bank balance)
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $bankChartAccountId,
                'amount' => $validated['float_amount'],
                'nature' => 'credit',
                'description' => 'Petty Cash Float - ' . $unit->name,
            ]);
            
            // Initialize approval workflow (will create GL transactions if auto-approved)
            $journal->initializeApprovalWorkflow();
            
            DB::commit();
            
            return redirect()->route('accounting.petty-cash.units.index')
                ->with('success', 'Petty cash unit created successfully with GL entries.');
            } else {
                // Unit requires approval - don't create journal yet
                DB::commit();
                
                return redirect()->route('accounting.petty-cash.units.index')
                    ->with('success', 'Petty cash unit created successfully. It is pending approval and will be activated once approved.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create petty cash unit: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified petty cash unit
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $unit = PettyCashUnit::with([
            'branch', 'custodian', 'supervisor', 'pettyCashAccount', 'suspenseAccount',
            'transactions' => function($q) {
                $q->latest()->limit(10);
            },
            'replenishments' => function($q) {
                $q->latest()->limit(10);
            }
        ])->findOrFail($id);
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $employees = collect();
        
        // Get petty cash settings for minimum balance trigger and maximum limit
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
        $minimumBalanceTrigger = $settings->minimum_balance_trigger;
        $systemMaximumLimit = $settings ? $settings->maximum_limit : null;
        
        return view('accounting.petty-cash.units.show', compact('unit', 'employees', 'minimumBalanceTrigger', 'systemMaximumLimit'));
    }

    /**
     * Export Petty Cash Voucher to PDF
     */
    public function exportPdf($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $unit = PettyCashUnit::with([
            'branch', 
            'custodian', 
            'supervisor', 
            'pettyCashAccount', 
            'suspenseAccount',
            'company'
        ])->findOrFail($id);
        
        if ($unit->company_id != Auth::user()->company_id) {
            abort(403);
        }

        // Get recent transactions (last 20)
        $recentTransactions = $unit->transactions()
            ->with(['items.chartAccount', 'createdBy', 'approvedBy'])
            ->latest()
            ->limit(20)
            ->get();

        // Get recent replenishments (last 10)
        $recentReplenishments = $unit->replenishments()
            ->with(['requestedBy', 'approvedBy', 'payment'])
            ->latest()
            ->limit(10)
            ->get();

        // Calculate statistics
        $totalTransactions = $unit->transactions()->count();
        $totalReplenishments = $unit->replenishments()->count();
        $totalDisbursed = $unit->transactions()
            ->where('status', '!=', 'rejected')
            ->sum('amount');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('accounting.petty-cash.units.exports.voucher-pdf', compact(
            'unit', 'recentTransactions', 'recentReplenishments', 'totalTransactions', 'totalReplenishments', 'totalDisbursed'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('petty-cash-voucher-' . $unit->code . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get transactions for DataTable (AJAX)
     */
    public function getTransactions(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['error' => 'Invalid unit ID'], 404);
        }

        $unit = PettyCashUnit::findOrFail($id);
        
        $query = $unit->transactions()
            ->with(['expenseCategory', 'items.chartAccount', 'createdBy', 'approvedBy'])
            ->latest();

        return datatables($query)
            ->addIndexColumn()
            ->addColumn('transaction_number_link', function ($transaction) {
                return '<a href="#" class="text-primary fw-bold">' . $transaction->transaction_number . '</a>';
            })
            ->addColumn('formatted_date', function ($transaction) {
                return $transaction->transaction_date->format('M d, Y');
            })
            ->addColumn('category_name', function ($transaction) {
                // If transaction has line items, show account names from items
                if ($transaction->items && $transaction->items->count() > 0) {
                    $accountNames = $transaction->items->map(function($item) {
                        return $item->chartAccount->account_name ?? 'N/A';
                    })->unique()->take(2);
                    
                    $count = $transaction->items->count();
                    if ($count > 2) {
                        return $accountNames->implode(', ') . ' (+' . ($count - 2) . ' more)';
                    }
                    return $accountNames->implode(', ');
                }
                
                // Fallback to expense category if no line items
                return $transaction->expenseCategory->name ?? 'N/A';
            })
            ->addColumn('description_with_payee', function ($transaction) {
                $html = '<div class="fw-bold">' . \Str::limit($transaction->description, 40) . '</div>';
                if ($transaction->payee) {
                    $html .= '<small class="text-muted">Payee: ' . $transaction->payee . '</small>';
                }
                return $html;
            })
            ->addColumn('formatted_amount', function ($transaction) {
                return '<span class="fw-bold text-danger">-TZS ' . number_format($transaction->amount, 2) . '</span>';
            })
            ->addColumn('status_badge', function ($transaction) {
                $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'approved' => 'success',
                    'posted' => 'primary',
                    'rejected' => 'danger'
                ];
                $color = $statusColors[$transaction->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($transaction->status) . '</span>';
            })
            ->addColumn('formatted_balance_after', function ($transaction) {
                return '<span class="fw-bold">TZS ' . number_format($transaction->balance_after ?? 0, 2) . '</span>';
            })
            ->addColumn('actions', function ($transaction) {
                $encodedId = $transaction->encoded_id;
                $buttons = '<div class="d-flex order-actions gap-1">';
                
                // View button
                $buttons .= '<a href="' . route('accounting.petty-cash.transactions.show', $encodedId) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                
                // Edit button (only if can be edited)
                if ($transaction->canBeEdited()) {
                    $buttons .= '<a href="' . route('accounting.petty-cash.transactions.edit', $encodedId) . '" class="btn btn-sm btn-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                // Approve button (if can be approved - status is submitted)
                if ($transaction->canBeApproved()) {
                    $buttons .= '<a href="javascript:void(0);" onclick="approveTransaction(\'' . $encodedId . '\')" class="btn btn-sm btn-success" title="Approve"><i class="bx bx-check"></i></a>';
                }
                
                // Post to GL button (if approved but not posted)
                if ($transaction->canBePosted() || ($transaction->status === 'approved' && !$transaction->payment_id)) {
                    $buttons .= '<a href="javascript:void(0);" onclick="postTransactionToGL(\'' . $encodedId . '\')" class="btn btn-sm btn-success" title="Post to GL"><i class="bx bx-check-circle"></i></a>';
                }
                
                // Delete button (only if can be deleted - not posted to GL)
                if ($transaction->canBeDeleted()) {
                    $buttons .= '<a href="javascript:void(0);" onclick="deleteTransaction(\'' . $encodedId . '\')" class="btn btn-sm btn-danger" title="Delete"><i class="bx bx-trash"></i></a>';
                }
                
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['transaction_number_link', 'description_with_payee', 'formatted_amount', 'status_badge', 'formatted_balance_after', 'actions'])
            ->make(true);
    }

    /**
     * Get replenishments for DataTable (AJAX)
     */
    public function getReplenishments(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            return response()->json(['error' => 'Invalid unit ID'], 404);
        }

        $unit = PettyCashUnit::findOrFail($id);
        
        $query = $unit->replenishments()
            ->with(['requestedBy', 'approvedBy', 'sourceAccount'])
            ->latest();

        return datatables($query)
            ->addIndexColumn()
            ->addColumn('replenishment_number_link', function ($replenishment) {
                return '<a href="#" class="text-primary fw-bold">' . $replenishment->replenishment_number . '</a>';
            })
            ->addColumn('formatted_request_date', function ($replenishment) {
                return $replenishment->request_date->format('M d, Y');
            })
            ->addColumn('formatted_requested_amount', function ($replenishment) {
                return 'TZS ' . number_format($replenishment->requested_amount, 2);
            })
            ->addColumn('formatted_approved_amount', function ($replenishment) {
                return $replenishment->approved_amount 
                    ? 'TZS ' . number_format($replenishment->approved_amount, 2) 
                    : '-';
            })
            ->addColumn('status_badge', function ($replenishment) {
                $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'approved' => 'success',
                    'paid' => 'primary',
                    'posted' => 'primary',
                    'rejected' => 'danger'
                ];
                $color = $statusColors[$replenishment->status] ?? 'secondary';
                return '<span class="badge bg-' . $color . '">' . ucfirst($replenishment->status) . '</span>';
            })
            ->addColumn('requested_by_name', function ($replenishment) {
                return $replenishment->requestedBy->name ?? 'N/A';
            })
            ->addColumn('actions', function ($replenishment) {
                $encodedId = $replenishment->encoded_id;
                $buttons = '<div class="d-flex order-actions gap-1">';
                
                // View button (if approved or posted)
                if ($replenishment->status === 'approved' || $replenishment->status === 'posted' || $replenishment->journal_id) {
                    $buttons .= '<a href="' . route('accounting.petty-cash.replenishments.show', $encodedId) . '" class="btn btn-sm btn-info" title="View"><i class="bx bx-show"></i></a>';
                }
                
                // Approve button (if can be approved - status is submitted)
                if ($replenishment->canBeApproved()) {
                    $buttons .= '<a href="javascript:void(0);" onclick="approveReplenishment(\'' . $encodedId . '\')" class="btn btn-sm btn-success" title="Approve"><i class="bx bx-check"></i></a>';
                    $buttons .= '<a href="javascript:void(0);" onclick="rejectReplenishment(\'' . $encodedId . '\')" class="btn btn-sm btn-danger" title="Reject"><i class="bx bx-x"></i></a>';
                }
                
                // Post to GL button (if approved but not posted)
                if ($replenishment->status === 'approved' && !$replenishment->journal_id) {
                    $buttons .= '<a href="javascript:void(0);" onclick="postReplenishmentToGL(\'' . $encodedId . '\')" class="btn btn-sm btn-primary" title="Post to GL"><i class="bx bx-check-circle"></i></a>';
                }
                
                // Edit button (if can be edited OR if submitted but not approved yet)
                if ($replenishment->canBeEdited() || ($replenishment->status === 'submitted' && !$replenishment->approved_by)) {
                    $buttons .= '<a href="javascript:void(0);" onclick="editReplenishment(\'' . $encodedId . '\')" class="btn btn-sm btn-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                }
                
                // Delete button (only if can be deleted - draft, rejected, or submitted but not approved)
                if ($replenishment->canBeEdited() || ($replenishment->status === 'submitted' && !$replenishment->approved_by)) {
                    $buttons .= '<a href="javascript:void(0);" onclick="deleteReplenishment(\'' . $encodedId . '\')" class="btn btn-sm btn-danger" title="Delete"><i class="bx bx-trash"></i></a>';
                }
                
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['replenishment_number_link', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for editing the specified petty cash unit
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $unit = PettyCashUnit::findOrFail($id);
        $user = Auth::user();
        $companyId = $user->company_id;
        
        $branches = Branch::where('company_id', $companyId)->orderBy('name')->get();
        $users = \App\Models\User::where('company_id', $companyId)->orderBy('name')->get();
        
        $pettyCashAccounts = ChartAccount::whereHas('accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->whereHas('accountClass', function($q2) {
                  $q2->where('name', 'LIKE', '%asset%');
              });
        })->orderBy('account_code')->get();
        
        // Get bank accounts
        $bankAccounts = BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->with('chartAccount')
        ->orderBy('name')
        ->get();
        
        return view('accounting.petty-cash.units.edit', compact('unit', 'branches', 'users', 'pettyCashAccounts', 'bankAccounts'));
    }

    /**
     * Update the specified petty cash unit
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }
        
        $unit = PettyCashUnit::findOrFail($id);
        
        $user = Auth::user();
        $companyId = $user->company_id;
        
        // Get petty cash settings to validate against maximum limit
        $settings = \App\Models\PettyCash\PettyCashSettings::getForCompany($companyId);
        $systemMaximumLimit = $settings ? $settings->maximum_limit : null;
        
        // Build validation rules
        $validationRules = [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('petty_cash_units', 'code')
                    ->ignore($id)
                    ->whereNull('deleted_at')
            ],
            'branch_id' => 'nullable|exists:branches,id',
            'custodian_id' => 'required|exists:users,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'float_amount' => 'required|numeric|min:0',
            'maximum_limit' => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'petty_cash_account_id' => 'required|exists:chart_accounts,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
        
        // Add validation to ensure float_amount doesn't exceed system maximum limit
        if ($systemMaximumLimit && $systemMaximumLimit > 0) {
            $validationRules['float_amount'] .= '|max:' . $systemMaximumLimit;
        }
        
        $validated = $request->validate($validationRules);
        
        // Additional validation: Check if float_amount exceeds the effective maximum limit
        // (either the unit's maximum_limit or the system's maximum_limit)
        $effectiveMaximumLimit = $validated['maximum_limit'] ?? $systemMaximumLimit;
        if ($effectiveMaximumLimit && $effectiveMaximumLimit > 0) {
            if ($validated['float_amount'] > $effectiveMaximumLimit) {
                return back()->withInput()->withErrors([
                    'float_amount' => "Float amount (TZS " . number_format($validated['float_amount'], 2) . ") cannot exceed the maximum limit of TZS " . number_format($effectiveMaximumLimit, 2) . "."
                ]);
            }
        }
        
        try {
            DB::beginTransaction();
            
            // Handle is_active checkbox (if not present in request, set to false)
            $validated['is_active'] = $request->has('is_active') ? (bool) $request->is_active : false;
            
            $unit->update($validated);
            
            DB::commit();
            
            return redirect()->route('accounting.petty-cash.units.show', $unit->encoded_id)
                ->with('success', 'Petty cash unit updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update petty cash unit: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified petty cash unit
     */
    public function destroy($encodedId, Request $request)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Petty cash unit not found.'], 404);
            }
            abort(404);
        }
        
        $unit = PettyCashUnit::findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Get all transactions for this unit
            $transactions = $unit->transactions()->get();
            
            // Delete all transactions (this will cascade delete their GL transactions, payments, etc.)
            foreach ($transactions as $transaction) {
                // If transaction was posted to GL (has payment_id), delete payment and GL transactions
                if ($transaction->payment_id) {
                    $payment = \App\Models\Payment::find($transaction->payment_id);
                    if ($payment) {
                        // Delete GL transactions
                        \App\Models\GlTransaction::where('transaction_id', $payment->id)
                            ->where('transaction_type', 'payment')
                            ->delete();
                        
                        // Delete payment items
                        $payment->paymentItems()->delete();
                        
                        // Delete payment
                        $payment->delete();
                    }
                    
                    // Reverse balance (balance was decremented when posted)
                    $unit->increment('current_balance', $transaction->amount);
                } elseif ($transaction->status === 'approved' && !$transaction->payment_id) {
                    // If transaction was approved but not posted, reverse balance
                    $unit->increment('current_balance', $transaction->amount);
                }
                
                // Delete register entries
                \App\Models\PettyCash\PettyCashRegister::where('petty_cash_transaction_id', $transaction->id)->delete();
                
                // Delete transaction items
                $transaction->items()->delete();
                
                // Delete receipt file
                if ($transaction->receipt_attachment) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($transaction->receipt_attachment);
                }
                
                // Delete transaction
                $transaction->forceDelete();
            }
            
            // Get all replenishments for this unit
            $replenishments = $unit->replenishments()->get();
            
            // Delete all replenishments and their GL transactions
            foreach ($replenishments as $replenishment) {
                // Delete journal and GL transactions if replenishment was posted
                if ($replenishment->journal_id) {
                    $journal = Journal::find($replenishment->journal_id);
                    if ($journal) {
                        // Delete GL transactions from journal
                        \App\Models\GlTransaction::where('transaction_id', $journal->id)
                            ->where('transaction_type', 'journal')
                            ->delete();
                        
                        // Delete journal items
                        $journal->items()->delete();
                        
                        // Delete journal
                        $journal->delete();
                    }
                }
                
                // Delete register entries
                \App\Models\PettyCash\PettyCashRegister::where('petty_cash_replenishment_id', $replenishment->id)->delete();
                
                // Delete replenishment
                $replenishment->delete();
            }
            
            // Delete all register entries for this unit
            \App\Models\PettyCash\PettyCashRegister::where('petty_cash_unit_id', $unit->id)->delete();
            
            // Delete journals created for unit setup (reference_type = 'Petty Cash Unit Setup')
            // Find journals that reference this unit by checking journal items for the petty cash account
            $setupJournals = Journal::where('reference_type', 'Petty Cash Unit Setup')
                ->whereHas('items', function($query) use ($unit) {
                    $query->where('chart_account_id', $unit->petty_cash_account_id)
                          ->where('description', 'like', '%' . $unit->name . '%');
                })
                ->get();
            
            foreach ($setupJournals as $journal) {
                // Delete GL transactions from journal
                \App\Models\GlTransaction::where('transaction_id', $journal->id)
                    ->where('transaction_type', 'journal')
                    ->delete();
                
                // Delete journal items
                $journal->items()->delete();
                
                // Delete journal
                $journal->delete();
            }
            
            // Collect all payment IDs and journal IDs that were linked to this unit
            $paymentIds = [];
            $journalIds = [];
            
            // Get payment IDs from deleted transactions (before they were deleted)
            foreach ($transactions as $transaction) {
                if ($transaction->payment_id) {
                    $paymentIds[] = $transaction->payment_id;
                }
            }
            
            // Get journal IDs from deleted replenishments (before they were deleted)
            foreach ($replenishments as $replenishment) {
                if ($replenishment->journal_id) {
                    $journalIds[] = $replenishment->journal_id;
                }
            }
            
            // Add setup journal IDs
            foreach ($setupJournals as $journal) {
                $journalIds[] = $journal->id;
            }
            
            // Delete GL transactions ONLY for payments and journals linked to this unit
            if (!empty($paymentIds)) {
                \App\Models\GlTransaction::where('transaction_type', 'payment')
                    ->whereIn('transaction_id', $paymentIds)
                    ->delete();
            }
            
            if (!empty($journalIds)) {
                \App\Models\GlTransaction::where('transaction_type', 'journal')
                    ->whereIn('transaction_id', $journalIds)
                    ->delete();
            }
            
            // Log the deletion activity before hard deleting
            // This ensures the activity log is created even though we're doing a hard delete
            $unit->logActivity('delete', "Deleted Petty Cash Unit: {$unit->name} ({$unit->code}) | Float Amount: " . number_format($unit->float_amount, 2) . " TZS");
            
            // Hard delete the unit (forceDelete) so the code can be reused
            $unit->forceDelete();
            
            DB::commit();
            
            $message = 'Petty cash unit and all related GL transactions deleted successfully.';
            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->route('accounting.petty-cash.units.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            $message = 'Failed to delete petty cash unit: ' . $e->getMessage();
            \Log::error('Failed to delete petty cash unit', [
                'unit_id' => $unit->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * Download Petty Cash User Guide
     */
    public function downloadGuide()
    {
        $filePath = base_path('PETTY_CASH_SYSTEM_FULL_IMPLEMENTATION.docx');
        
        if (!file_exists($filePath)) {
            return redirect()->back()
                ->with('error', 'User guide file not found.');
        }

        return response()->download($filePath, 'PETTY_CASH_SYSTEM_FULL_IMPLEMENTATION.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}

