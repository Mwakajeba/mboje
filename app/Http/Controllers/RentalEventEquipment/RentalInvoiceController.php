<?php

namespace App\Http\Controllers\RentalEventEquipment;

use App\Http\Controllers\Controller;
use App\Models\RentalEventEquipment\RentalInvoice;
use App\Models\RentalEventEquipment\RentalInvoiceItem;
use App\Models\RentalEventEquipment\RentalContract;
use App\Models\RentalEventEquipment\RentalReturn;
use App\Models\RentalEventEquipment\RentalDamageCharge;
use App\Models\RentalEventEquipment\CustomerDeposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class RentalInvoiceController extends Controller
{
    public function index()
    {
        return view('rental-event-equipment.rental-invoices.index');
    }

    public function data(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $query = RentalInvoice::with(['customer', 'contract'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('customer_name', function ($invoice) {
                return $invoice->customer->name ?? 'N/A';
            })
            ->addColumn('contract_number', function ($invoice) {
                return $invoice->contract->contract_number ?? 'N/A';
            })
            ->addColumn('invoice_date_formatted', function ($invoice) {
                return $invoice->invoice_date->format('M d, Y');
            })
            ->addColumn('total_amount_formatted', function ($invoice) {
                return 'TZS ' . number_format($invoice->total_amount, 2);
            })
            ->addColumn('paid_amount_formatted', function ($invoice) {
                // Calculate paid amount from deposits applied
                $paidAmount = $invoice->deposit_applied ?? 0;
                return 'TZS ' . number_format($paidAmount, 2);
            })
            ->addColumn('balance_due_formatted', function ($invoice) {
                $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);
                $badgeClass = $balanceDue > 0 ? 'warning' : 'success';
                return '<span class="badge bg-' . $badgeClass . '">TZS ' . number_format($balanceDue, 2) . '</span>';
            })
            ->addColumn('status_badge', function ($invoice) {
                $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);
                $badgeClass = match($invoice->status) {
                    'draft' => 'secondary',
                    'sent' => 'info',
                    'paid' => 'success',
                    'cancelled' => 'danger',
                    default => $balanceDue > 0 ? 'warning' : 'success'
                };
                $statusText = $balanceDue <= 0 && $invoice->status !== 'paid' ? 'Paid' : ucfirst($invoice->status);
                return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->addColumn('actions', function ($invoice) {
                $encodedId = Hashids::encode($invoice->id);
                $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('rental-event-equipment.rental-invoices.show', $encodedId) . '" class="btn btn-sm btn-outline-info" title="View">
                        <i class="bx bx-show"></i>
                </a>';
                
                // Edit and Delete buttons for draft invoices
                if ($invoice->status === 'draft') {
                    $actions .= '<a href="' . route('rental-event-equipment.rental-invoices.edit', $encodedId) . '" class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="bx bx-edit"></i>
                    </a>';
                    $actions .= '<button type="button" class="btn btn-sm btn-outline-danger delete-invoice-btn" title="Delete" 
                        data-invoice-id="' . $encodedId . '" 
                        data-invoice-number="' . htmlspecialchars($invoice->invoice_number, ENT_QUOTES) . '">
                        <i class="bx bx-trash"></i>
                    </button>';
                }
                
                // Payment button - only show if not draft and has balance
                if ($invoice->status !== 'draft' && $balanceDue > 0 && $invoice->status !== 'cancelled') {
                    $actions .= '<a href="' . route('rental-event-equipment.rental-invoices.payment', $encodedId) . '" class="btn btn-sm btn-outline-success" title="Add Payment">
                        <i class="bx bx-money"></i>
                    </a>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'balance_due_formatted', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $contractId = $request->get('contract_id');
        // Get all contracts and filter out fully paid ones
        $allContracts = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->where('status', '!=', 'cancelled')
            ->with(['customer', 'items.equipment'])
            ->orderBy('contract_number', 'desc')
            ->get();

        // Filter out fully paid contracts
        $contracts = $allContracts->filter(function ($contract) {
            // Get total invoices for this contract
            $totalInvoiced = RentalInvoice::where('contract_id', $contract->id)
                ->sum('total_amount');
            
            // Get total deposits for this contract
            $totalDeposits = CustomerDeposit::where('contract_id', $contract->id)
                ->where('status', 'confirmed')
                ->sum('amount');
            
            // Get total payments (if payment system exists)
            // For now, we'll consider a contract fully paid if total deposits >= total invoiced
            // This is a simple check - you may want to enhance this based on your payment system
            $isFullyPaid = $totalDeposits >= $totalInvoiced && $totalInvoiced > 0;
            
            return !$isFullyPaid;
        })->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $contract->customer->name ?? 'N/A',
                    'contract_date' => $contract->contract_date->format('M d, Y'),
                    'encoded_id' => Hashids::encode($contract->id),
                ];
            });

        $selectedContract = null;
        if ($contractId) {
            try {
                $decoded = Hashids::decode($contractId);
                $id = $decoded[0] ?? null;
                if ($id) {
                    $selectedContract = RentalContract::with(['items.equipment', 'customer'])
                        ->find($id);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not load selected contract', ['error' => $e->getMessage()]);
            }
        }

        return view('rental-event-equipment.rental-invoices.create', compact('contracts', 'selectedContract'));
    }

    public function getContractInvoiceData(Request $request, $contractId)
    {
        try {
            $decoded = Hashids::decode($contractId);
            $id = $decoded[0] ?? null;
            
            if (!$id) {
                return response()->json(['error' => 'Invalid contract ID'], 400);
            }

            $companyId = Auth::user()->company_id;
            $branchId = session('branch_id') ?: Auth::user()->branch_id;

            $contract = RentalContract::where('company_id', $companyId)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereNull('branch_id');
                })
                ->with(['items.equipment', 'customer'])
                ->find($id);

            if (!$contract) {
                return response()->json(['error' => 'Contract not found'], 404);
            }

            // Get rental items from contract
            $rentalItems = $contract->items->map(function ($item) {
                // Calculate unit price from rental_rate and rental_days
                $unitPrice = $item->rental_rate ?? 0;
                $quantity = $item->quantity ?? 1;
                $lineTotal = $item->total_amount ?? ($unitPrice * $quantity);
                
                return [
                    'id' => $item->id,
                    'equipment_id' => $item->equipment_id,
                    'equipment_name' => $item->equipment->name ?? 'N/A',
                    'equipment_code' => $item->equipment->equipment_code ?? 'N/A',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'item_type' => 'equipment',
                    'description' => $item->equipment->name ?? 'N/A',
                ];
            });

            // Get damage charges for this contract
            $damageCharges = RentalDamageCharge::where('contract_id', $contract->id)
                ->where('status', '!=', 'cancelled')
                ->with(['items.equipment'])
                ->get()
                ->flatMap(function ($charge) {
                    return $charge->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'equipment_id' => $item->equipment_id,
                            'equipment_name' => $item->equipment->name ?? 'N/A',
                            'equipment_code' => $item->equipment->equipment_code ?? 'N/A',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_charge,
                            'line_total' => $item->total_charge,
                            'item_type' => $item->charge_type === 'damage' ? 'damage_charge' : 'loss_charge',
                            'description' => $item->description ?? '',
                        ];
                    });
                });

            // Get deposits for this contract
            $deposits = CustomerDeposit::where('contract_id', $contract->id)
                ->where('status', 'confirmed')
                ->get()
                ->map(function ($deposit) {
                    return [
                        'id' => $deposit->id,
                        'deposit_number' => $deposit->deposit_number,
                        'amount' => $deposit->amount,
                        'deposit_date' => $deposit->deposit_date->format('M d, Y'),
                    ];
                });

            $totalDeposits = $deposits->sum('amount');

            return response()->json([
                'success' => true,
                'contract' => [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'customer_name' => $contract->customer->name ?? 'N/A',
                ],
                'rental_items' => $rentalItems,
                'damage_charges' => $damageCharges,
                'deposits' => $deposits,
                'total_deposits' => $totalDeposits,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching contract invoice data', ['error' => $e->getMessage(), 'contract_id' => $contractId]);
            return response()->json(['error' => 'Failed to fetch contract data: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        // Decode contract_id if it's encoded
        $contractId = $request->contract_id;
        try {
            $decoded = Hashids::decode($contractId);
            if (!empty($decoded)) {
                $contractId = $decoded[0];
            }
        } catch (\Exception $e) {
            // If decode fails, use original value (might be numeric ID)
        }

        $request->validate([
            'contract_id' => 'required',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'deposit_applied' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'nullable|exists:equipment,id',
            'items.*.item_type' => 'required|in:equipment,damage_charge,loss_charge,service',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        // Validate deposit amount doesn't exceed available
        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->find($contractId);
        
        if ($contract) {
            $totalDeposits = CustomerDeposit::where('contract_id', $contract->id)
                ->where('status', 'confirmed')
                ->sum('amount');
            
            $depositApplied = $request->deposit_applied ?? 0;
            if ($depositApplied > $totalDeposits) {
                return back()->withInput()->with('error', "Deposit amount cannot exceed available deposits of " . number_format($totalDeposits, 2) . " TZS.");
            }
        }

        // Filter out unchecked items
        $items = array_filter($request->items, function($item) {
            return isset($item['quantity']) && $item['quantity'] > 0 && 
                   isset($item['unit_price']) && $item['unit_price'] > 0;
        });

        if (empty($items)) {
            return back()->withInput()->with('error', 'At least one item must be selected with quantity and unit price greater than 0.');
        }

        $contract = RentalContract::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer'])
            ->findOrFail($contractId);

        DB::beginTransaction();
        try {
            $invoiceNumber = 'INV-RENT-' . date('Y') . '-' . str_pad((RentalInvoice::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);

            $rentalCharges = 0;
            $damageCharges = 0;
            $lossCharges = 0;
            $subtotal = 0;

            foreach ($items as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $lineTotal;

                if ($item['item_type'] === 'equipment' || $item['item_type'] === 'service') {
                    $rentalCharges += $lineTotal;
                } elseif ($item['item_type'] === 'damage_charge') {
                    $damageCharges += $lineTotal;
                } elseif ($item['item_type'] === 'loss_charge') {
                    $lossCharges += $lineTotal;
                }
            }

            // Get total deposits for this contract
            $totalDeposits = CustomerDeposit::where('contract_id', $contract->id)
                ->where('status', 'confirmed')
                ->sum('amount');

            $depositApplied = min($request->deposit_applied ?? 0, $totalDeposits, $subtotal);
            $taxAmount = 0; // Calculate tax if needed
            $totalAmount = $subtotal + $taxAmount - $depositApplied;

            $invoice = RentalInvoice::create([
                'invoice_number' => $invoiceNumber,
                'contract_id' => $contractId,
                'customer_id' => $contract->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date ?? null,
                'rental_charges' => $rentalCharges,
                'damage_charges' => $damageCharges,
                'loss_charges' => $lossCharges,
                'deposit_applied' => $depositApplied,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'status' => 'draft',
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['unit_price'] ?? 0);
                $lineTotal = $quantity * $unitPrice;
                
                RentalInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'equipment_id' => $item['equipment_id'] ?? null,
                    'item_type' => $item['item_type'],
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-invoices.index')
                ->with('success', 'Rental invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create invoice: ' . $e->getMessage());
        }
    }

    public function show(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'items.equipment', 'creator'])
            ->findOrFail($id);

        // Get receipts for this invoice
        $receipts = \App\Models\Receipt::where('reference_type', 'rental_invoice_payment')
            ->where(function($q) use ($invoice) {
                $q->where('reference', $invoice->id)
                  ->orWhere('reference_number', $invoice->invoice_number);
            })
            ->with(['user', 'bankAccount'])
            ->orderBy('date', 'desc')
            ->get();

        return view('rental-event-equipment.rental-invoices.show', compact('invoice', 'receipts'));
    }

    public function showPaymentForm(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract'])
            ->findOrFail($id);

        $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);
        
        if ($balanceDue <= 0) {
            return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
                ->with('error', 'This invoice is already fully paid.');
        }

        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.rental-invoices.payment', compact('invoice', 'balanceDue', 'bankAccounts'));
    }

    public function storePayment(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0);

        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01|max:' . $balanceDue,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:bank_transfer',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $paymentAmount = $request->payment_amount;
            $newDepositApplied = ($invoice->deposit_applied ?? 0) + $paymentAmount;
            $newBalanceDue = $invoice->total_amount - $newDepositApplied;

            // Update invoice
            $invoice->update([
                'deposit_applied' => $newDepositApplied,
                'status' => $newBalanceDue <= 0 ? 'paid' : 'sent',
            ]);

            // Create receipt entry (similar to sales invoices)
            $receipt = \App\Models\Receipt::create([
                'reference' => $invoice->id,
                'reference_type' => 'rental_invoice_payment',
                'reference_number' => $invoice->invoice_number,
                'payee_id' => $invoice->customer_id,
                'payee_type' => 'customer',
                'amount' => $paymentAmount,
                'date' => $request->payment_date,
                'description' => $request->notes ?? "Payment for Invoice #{$invoice->invoice_number}",
                'bank_account_id' => $request->bank_account_id ?? null,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
                ->with('success', 'Payment recorded successfully! Amount: TZS ' . number_format($paymentAmount, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    public function edit(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'items.equipment'])
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
                ->with('error', 'Only draft invoices can be edited.');
        }

        // Get contract for reference
        $contract = $invoice->contract;
        if ($contract) {
            $contracts = collect([$contract])->map(function ($c) {
                return [
                    'id' => $c->id,
                    'contract_number' => $c->contract_number,
                    'customer_name' => $c->customer->name ?? 'N/A',
                    'contract_date' => $c->contract_date->format('M d, Y'),
                    'encoded_id' => Hashids::encode($c->id),
                ];
            });
        } else {
            $contracts = collect();
        }

        return view('rental-event-equipment.rental-invoices.edit', compact('invoice', 'contracts'));
    }

    public function update(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $request->validate([
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'deposit_applied' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.equipment_id' => 'nullable|exists:equipment,id',
            'items.*.item_type' => 'required|in:equipment,damage_charge,loss_charge,service',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $items = array_filter($request->items, function($item) {
                return isset($item['quantity']) && $item['quantity'] > 0 && 
                       isset($item['unit_price']) && $item['unit_price'] > 0;
            });

            if (empty($items)) {
                return back()->withInput()->with('error', 'At least one item must be selected with quantity and unit price greater than 0.');
            }

            $rentalCharges = 0;
            $damageCharges = 0;
            $lossCharges = 0;
            $subtotal = 0;

            foreach ($items as $item) {
                $lineTotal = floatval($item['quantity']) * floatval($item['unit_price']);
                $subtotal += $lineTotal;

                if ($item['item_type'] === 'equipment' || $item['item_type'] === 'service') {
                    $rentalCharges += $lineTotal;
                } elseif ($item['item_type'] === 'damage_charge') {
                    $damageCharges += $lineTotal;
                } elseif ($item['item_type'] === 'loss_charge') {
                    $lossCharges += $lineTotal;
                }
            }

            // Get total deposits for this contract
            $totalDeposits = CustomerDeposit::where('contract_id', $invoice->contract_id)
                ->where('status', 'confirmed')
                ->sum('amount');

            $depositApplied = min($request->deposit_applied ?? 0, $totalDeposits, $subtotal);
            $taxAmount = 0;
            $totalAmount = $subtotal + $taxAmount - $depositApplied;

            // Update invoice
            $invoice->update([
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date ?? null,
                'rental_charges' => $rentalCharges,
                'damage_charges' => $damageCharges,
                'loss_charges' => $lossCharges,
                'deposit_applied' => $depositApplied,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);

            // Delete existing items and create new ones
            $invoice->items()->delete();

            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['unit_price'] ?? 0);
                $lineTotal = $quantity * $unitPrice;
                
                RentalInvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'equipment_id' => $item['equipment_id'] ?? null,
                    'item_type' => $item['item_type'],
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
                ->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
                ->with('error', 'Only draft invoices can be approved.');
        }

        $request->validate([
            'status' => 'required|in:sent,paid,cancelled',
        ]);

        $invoice->update([
            'status' => $request->status,
        ]);

        return redirect()->route('rental-event-equipment.rental-invoices.show', $encodedId)
            ->with('success', 'Invoice status updated successfully.');
    }

    public function editReceipt(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $receiptId = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $receipt = \App\Models\Receipt::where('reference_type', 'rental_invoice_payment')
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['bankAccount'])
            ->findOrFail($receiptId);

        // Get the invoice
        $invoice = RentalInvoice::findOrFail($receipt->reference);

        $bankAccounts = \App\Models\BankAccount::whereHas('chartAccount.accountClassGroup', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where(function ($query) use ($branchId) {
                $query->where('is_all_branches', true);
                if ($branchId) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->with('chartAccount')
            ->orderBy('name')
            ->get();

        return view('rental-event-equipment.rental-invoices.edit-receipt', compact('receipt', 'invoice', 'bankAccounts'));
    }

    public function updateReceipt(Request $request, string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $receiptId = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $receipt = \App\Models\Receipt::where('reference_type', 'rental_invoice_payment')
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($receiptId);

        $invoice = RentalInvoice::findOrFail($receipt->reference);
        $balanceDue = $invoice->total_amount - ($invoice->deposit_applied ?? 0) + $receipt->amount; // Add back the old receipt amount

        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01|max:' . $balanceDue,
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldAmount = $receipt->amount;
            $newAmount = $request->payment_amount;
            $amountDifference = $newAmount - $oldAmount;

            // Update receipt
            $receipt->update([
                'amount' => $newAmount,
                'date' => $request->payment_date,
                'bank_account_id' => $request->bank_account_id,
                'reference_number' => $request->reference_number,
                'description' => $request->notes ?? $receipt->description,
            ]);

            // Update invoice deposit_applied
            $newDepositApplied = ($invoice->deposit_applied ?? 0) + $amountDifference;
            $newBalanceDue = $invoice->total_amount - $newDepositApplied;

            $invoice->update([
                'deposit_applied' => $newDepositApplied,
                'status' => $newBalanceDue <= 0 ? 'paid' : 'sent',
            ]);

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-invoices.show', $invoice->getRouteKey())
                ->with('success', 'Receipt updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update receipt: ' . $e->getMessage());
        }
    }

    public function deleteReceipt(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $receiptId = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $receipt = \App\Models\Receipt::where('reference_type', 'rental_invoice_payment')
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($receiptId);

        $invoice = RentalInvoice::findOrFail($receipt->reference);

        DB::beginTransaction();
        try {
            $amount = $receipt->amount;

            // Delete GL transactions if they exist
            $receipt->glTransactions()->delete();

            // Update invoice deposit_applied
            $newDepositApplied = max(0, ($invoice->deposit_applied ?? 0) - $amount);
            $newBalanceDue = $invoice->total_amount - $newDepositApplied;

            $invoice->update([
                'deposit_applied' => $newDepositApplied,
                'status' => $newBalanceDue <= 0 ? 'paid' : 'sent',
            ]);

            // Delete receipt
            $receipt->delete();

            DB::commit();

            return redirect()->route('rental-event-equipment.rental-invoices.show', $invoice->getRouteKey())
                ->with('success', 'Receipt deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rental-event-equipment.rental-invoices.show', $invoice->getRouteKey())
                ->with('error', 'Failed to delete receipt: ' . $e->getMessage());
        }
    }

    public function exportPdf(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'items.equipment', 'company', 'branch'])
            ->findOrFail($id);

        $company = $invoice->company;
        $branch = $invoice->branch;

        $pdf = \PDF::loadView('rental-event-equipment.rental-invoices.export-pdf', compact('invoice', 'company', 'branch'));
        $filename = $invoice->invoice_number . '.pdf';
        return $pdf->download($filename);
    }

    public function exportReceiptPdf(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->with(['customer', 'contract', 'items.equipment', 'company', 'branch'])
            ->findOrFail($id);

        $company = $invoice->company;
        $branch = $invoice->branch;

        $pdf = \PDF::loadView('rental-event-equipment.rental-invoices.export-receipt-pdf', compact('invoice', 'company', 'branch'));
        $filename = 'Receipt_' . $invoice->invoice_number . '.pdf';
        return $pdf->download($filename);
    }

    public function destroy(string $encodedId)
    {
        $decoded = Hashids::decode($encodedId);
        $id = $decoded[0] ?? null;

        $companyId = Auth::user()->company_id;
        $branchId = session('branch_id') ?: Auth::user()->branch_id;

        $invoice = RentalInvoice::where('company_id', $companyId)
            ->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            })
            ->findOrFail($id);

        if ($invoice->status !== 'draft') {
            return redirect()->route('rental-event-equipment.rental-invoices.index')
                ->with('error', 'Only draft invoices can be deleted.');
        }

        DB::beginTransaction();
        try {
            // Delete invoice items first
            $invoice->items()->delete();
            
            // Delete invoice
        $invoice->delete();
            
            DB::commit();

        return redirect()->route('rental-event-equipment.rental-invoices.index')
            ->with('success', 'Rental invoice deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('rental-event-equipment.rental-invoices.index')
                ->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }
}
