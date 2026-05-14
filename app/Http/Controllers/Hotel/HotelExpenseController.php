<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Hotel\Property;
use App\Models\Hotel\Room;
use App\Helpers\HashIdHelper;

class HotelExpenseController extends Controller
{
    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $expenses = Payment::where('branch_id', $branchId)
            ->where('payee_type', 'hotel')
            ->orderByDesc('id')
            ->paginate(20);

        return view('hotel.expenses.index', compact('expenses'));
    }

    public function create()
    {
        $properties = Property::orderBy('name')->get(['id', 'name']);
        $rooms = Room::orderBy('room_number')->get(['id', 'room_number', 'room_name', 'property_id']);
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get(['id', 'name']);
        // Expense accounts
        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup.accountClass', function ($q) {
                $q->where('name', 'like', '%expense%')
                  ->orWhere('name', 'like', '%cost%')
                  ->orWhere('name', 'like', '%expenditure%');
            })
            ->orderBy('account_name')
            ->get(['id','account_name','account_code']);

        return view('hotel.expenses.create', compact('properties', 'rooms', 'bankAccounts', 'chartAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'line_items' => ['required','array','min:1'],
            'line_items.*.chart_account_id' => ['required','integer','exists:chart_accounts,id'],
            'line_items.*.amount' => ['required','numeric','min:0.01'],
            'line_items.*.description' => ['nullable','string','max:255'],
        ]);

        if (empty($validated['property_id']) && empty($validated['room_id'])) {
            return back()->withErrors(['property_id' => 'Select a Property for general expense or a specific Room.'])->withInput();
        }

        $user = auth()->user();

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['line_items'])->sum(function($li){ return (float)$li['amount']; });
            $branchId = session('branch_id') ?? Auth::user()->branch_id ?? 1;
            $paymentNumber = $this->generatePaymentNumber();
            $payment = Payment::create([
                'reference' => $paymentNumber,
                'reference_type' => 'hotel_expense',
                'reference_number' => $paymentNumber,
                'amount' => $totalAmount,
                'date' => $validated['expense_date'],
                'description' => $validated['description'],
                'bank_account_id' => $validated['bank_account_id'],
                'payee_type' => 'hotel',
                'payee_id' => null, // Not tied to external party
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);

            foreach ($validated['line_items'] as $li) {
                // Build description with property/room info if available
                $itemDescription = $li['description'] ?? $validated['description'];
                if ($validated['property_id']) {
                    $property = Property::find($validated['property_id']);
                    if ($property) {
                        $itemDescription .= ' (Property: ' . $property->name . ')';
                    }
                }
                if ($validated['room_id']) {
                    $room = Room::find($validated['room_id']);
                    if ($room) {
                        $itemDescription .= ' (Room: ' . $room->room_number . ($room->room_name ? ' - ' . $room->room_name : '') . ')';
                    }
                }
                
                PaymentItem::create([
                    'payment_id' => $payment->id,
                    'chart_account_id' => $li['chart_account_id'],
                    'description' => $itemDescription,
                    'amount' => $li['amount'],
                ]);
            }

            // GL transactions: reuse Payment model hooks/services already established
            if (method_exists($payment, 'createGlEntries')) {
                $payment->createGlEntries();
            }

            DB::commit();

            return redirect()->route('hotel.expenses.index')->with('success', 'Hotel expense recorded successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to record hotel expense', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to save expense: ' . $e->getMessage())->withInput();
        }
    }

    public function show($encodedId)
    {
        $expense = $this->resolveExpense($encodedId);
        if (!$expense) {
            return redirect()->route('hotel.expenses.index')->withErrors(['Hotel expense not found.']);
        }
        
        $expense->load([
            'bankAccount', 
            'user', 
            'branch', 
            'paymentItems.chartAccount',
            'glTransactions.chartAccount'
        ]);
        
        return view('hotel.expenses.show', compact('expense'));
    }

    public function edit($encodedId)
    {
        $expense = $this->resolveExpense($encodedId);
        if (!$expense) {
            return redirect()->route('hotel.expenses.index')->withErrors(['Hotel expense not found.']);
        }
        
        // Prevent editing if approved
        if ($expense->approved) {
            return redirect()->route('hotel.expenses.show', $encodedId)
                ->with('error', 'Cannot edit an approved expense.');
        }
        
        $properties = Property::orderBy('name')->get(['id', 'name']);
        $rooms = Room::orderBy('room_number')->get(['id', 'room_number', 'room_name', 'property_id']);
        $bankAccounts = \App\Models\BankAccount::orderBy('name')->get(['id', 'name']);
        $chartAccounts = \App\Models\ChartAccount::whereHas('accountClassGroup.accountClass', function ($q) {
                $q->where('name', 'like', '%expense%')
                  ->orWhere('name', 'like', '%cost%')
                  ->orWhere('name', 'like', '%expenditure%');
            })
            ->orderBy('account_name')
            ->get(['id','account_name','account_code']);
        
        $expense->load(['paymentItems']);
        
        return view('hotel.expenses.edit', compact('expense', 'properties', 'rooms', 'bankAccounts', 'chartAccounts'));
    }

    public function update(Request $request, $encodedId)
    {
        $expense = $this->resolveExpense($encodedId);
        if (!$expense) {
            return redirect()->route('hotel.expenses.index')->withErrors(['Hotel expense not found.']);
        }
        
        // Prevent updating if approved
        if ($expense->approved) {
            return redirect()->route('hotel.expenses.show', $encodedId)
                ->with('error', 'Cannot update an approved expense.');
        }
        
        $validated = $request->validate([
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'line_items' => ['required','array','min:1'],
            'line_items.*.chart_account_id' => ['required','integer','exists:chart_accounts,id'],
            'line_items.*.amount' => ['required','numeric','min:0.01'],
            'line_items.*.description' => ['nullable','string','max:255'],
        ]);

        if (empty($validated['property_id']) && empty($validated['room_id'])) {
            return back()->withErrors(['property_id' => 'Select a Property for general expense or a specific Room.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['line_items'])->sum(function($li){ return (float)$li['amount']; });
            
            // Update payment
            $expense->update([
                'amount' => $totalAmount,
                'date' => $validated['expense_date'],
                'description' => $validated['description'],
                'bank_account_id' => $validated['bank_account_id'],
            ]);

            // Delete existing payment items
            $expense->paymentItems()->delete();
            
            // Delete existing GL transactions
            $expense->glTransactions()->delete();

            // Create new payment items
            foreach ($validated['line_items'] as $li) {
                $itemDescription = $li['description'] ?? $validated['description'];
                if ($validated['property_id']) {
                    $property = Property::find($validated['property_id']);
                    if ($property) {
                        $itemDescription .= ' (Property: ' . $property->name . ')';
                    }
                }
                if ($validated['room_id']) {
                    $room = Room::find($validated['room_id']);
                    if ($room) {
                        $itemDescription .= ' (Room: ' . $room->room_number . ($room->room_name ? ' - ' . $room->room_name : '') . ')';
                    }
                }
                
                PaymentItem::create([
                    'payment_id' => $expense->id,
                    'chart_account_id' => $li['chart_account_id'],
                    'description' => $itemDescription,
                    'amount' => $li['amount'],
                ]);
            }

            // Recreate GL transactions
            if (method_exists($expense, 'createGlEntries')) {
                $expense->createGlEntries();
            }

            DB::commit();

            return redirect()->route('hotel.expenses.show', $encodedId)->with('success', 'Hotel expense updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to update hotel expense', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Failed to update expense: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($encodedId)
    {
        $expense = $this->resolveExpense($encodedId);
        if (!$expense) {
            return redirect()->route('hotel.expenses.index')->withErrors(['Hotel expense not found.']);
        }
        
        // Prevent deleting if approved
        if ($expense->approved) {
            return redirect()->route('hotel.expenses.show', $encodedId)
                ->with('error', 'Cannot delete an approved expense.');
        }

        try {
            DB::beginTransaction();
            
            // Delete attachment if exists
            if ($expense->attachment && Storage::disk('public')->exists($expense->attachment)) {
                Storage::disk('public')->delete($expense->attachment);
            }

            // Delete related records
            $expense->paymentItems()->delete();
            $expense->glTransactions()->delete();
            $expense->delete();

            DB::commit();

            return redirect()->route('hotel.expenses.index')
                ->with('success', 'Hotel expense deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete expense: ' . $e->getMessage()]);
        }
    }

    public function exportPdf($encodedId)
    {
        $expense = $this->resolveExpense($encodedId);
        if (!$expense) {
            return redirect()->route('hotel.expenses.index')->withErrors(['Hotel expense not found.']);
        }
        
        try {
            $expense->load([
                'bankAccount.chartAccount',
                'user.company',
                'branch',
                'paymentItems.chartAccount',
                'glTransactions.chartAccount'
            ]);

            // Generate PDF using DomPDF
            $pdf = \PDF::loadView('hotel.expenses.export-pdf', compact('expense'));

            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');

            // Generate filename
            $filename = 'hotel_expense_' . $expense->reference_number . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // Return PDF for download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to export PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper method to resolve expense from encoded ID
     */
    protected function resolveExpense($encodedId)
    {
        $decodedId = HashIdHelper::decode($encodedId);
        if ($decodedId === null) {
            return null;
        }
        
        return Payment::where('id', $decodedId)
            ->where('payee_type', 'hotel')
            ->first();
    }

    private function generatePaymentNumber(): string
    {
        $prefix = 'HEX' . now()->format('Ymd');
        $last = Payment::where('reference_number', 'like', $prefix . '%')->orderByDesc('id')->value('reference_number');
        $seq = 1;
        if ($last && preg_match('/(\d{4})$/', $last, $m)) {
            $seq = intval($m[1]) + 1;
        }
        return sprintf('%s%04d', $prefix, $seq);
    }
}


