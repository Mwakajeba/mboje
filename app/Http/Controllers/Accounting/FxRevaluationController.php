<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\FxRevaluationService;
use App\Models\GlRevaluationHistory;
use App\Models\Branch;
use App\Models\Sales\SalesInvoice;
use App\Models\Purchase\PurchaseInvoice;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class FxRevaluationController extends Controller
{
    protected $revaluationService;

    public function __construct(FxRevaluationService $revaluationService)
    {
        $this->revaluationService = $revaluationService;
    }

    /**
     * Display a listing of revaluation history.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get branches for filter
        $branches = Branch::where('company_id', $user->company_id)->get();

        return view('accounting.fx-revaluation.index', compact('branches'));
    }

    /**
     * DataTables AJAX endpoint for revaluation history.
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        
        $query = GlRevaluationHistory::with(['postedJournal', 'reversalJournal', 'creator', 'branch'])
            ->where('company_id', $user->company_id)
            ->select('gl_revaluation_history.*');

        return DataTables::eloquent($query)
            ->filter(function ($query) use ($request) {
                // Apply filters from request
                if ($request->filled('branch_id')) {
                    $query->where('branch_id', $request->branch_id);
                }

                if ($request->filled('item_type')) {
                    $query->where('item_type', $request->item_type);
                }

                if ($request->filled('date_from')) {
                    $query->where('revaluation_date', '>=', $request->date_from);
                }

                if ($request->filled('date_to')) {
                    $query->where('revaluation_date', '<=', $request->date_to);
                }

                if ($request->filled('is_reversed')) {
                    $query->where('is_reversed', $request->is_reversed);
                }

                // Global search
                if ($request->filled('search.value')) {
                    $searchValue = $request->input('search.value');
                    $query->where(function($q) use ($searchValue) {
                        $q->where('item_ref', 'like', "%{$searchValue}%")
                          ->orWhere('item_type', 'like', "%{$searchValue}%")
                          ->orWhereHas('creator', function($creatorQuery) use ($searchValue) {
                              $creatorQuery->where('name', 'like', "%{$searchValue}%");
                          });
                    });
                }
            })
            ->addColumn('formatted_date', function ($revaluation) {
                return $revaluation->revaluation_date->format('Y-m-d');
            })
            ->addColumn('item_type_badge', function ($revaluation) {
                $badgeClass = match($revaluation->item_type) {
                    'AR' => 'info',
                    'AP' => 'warning',
                    'BANK' => 'success',
                    'LOAN' => 'secondary',
                    default => 'secondary'
                };
                return '<span class="badge bg-'.$badgeClass.'">'.$revaluation->item_type.'</span>';
            })
            ->addColumn('currency', function ($revaluation) {
                // Get currency from related model based on item_type
                $currency = 'N/A';
                try {
                    if ($revaluation->item_type === 'AR') {
                        $invoice = SalesInvoice::find($revaluation->item_id);
                        $currency = $invoice->currency ?? 'N/A';
                    } elseif ($revaluation->item_type === 'AP') {
                        $invoice = PurchaseInvoice::find($revaluation->item_id);
                        $currency = $invoice->currency ?? 'N/A';
                    } elseif ($revaluation->item_type === 'BANK') {
                        $bank = BankAccount::find($revaluation->item_id);
                        $currency = $bank->currency ?? 'N/A';
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to get currency for revaluation', [
                        'revaluation_id' => $revaluation->id,
                        'item_type' => $revaluation->item_type,
                        'item_id' => $revaluation->item_id,
                        'error' => $e->getMessage()
                    ]);
                    $currency = 'N/A';
                }
                return $currency;
            })
            ->addColumn('formatted_fcy_amount', function ($revaluation) {
                return number_format(abs($revaluation->fcy_amount), 2);
            })
            ->addColumn('formatted_original_rate', function ($revaluation) {
                return number_format($revaluation->original_rate, 6);
            })
            ->addColumn('formatted_closing_rate', function ($revaluation) {
                return number_format($revaluation->closing_rate, 6);
            })
            ->addColumn('formatted_gain_loss', function ($revaluation) {
                $class = $revaluation->gain_loss >= 0 ? 'text-success' : 'text-danger';
                return '<span class="fw-bold '.$class.'">'.$revaluation->formatted_gain_loss.'</span>';
            })
            ->addColumn('status_badge', function ($revaluation) {
                if ($revaluation->is_reversed) {
                    return '<span class="badge bg-secondary"><i class="bx bx-check me-1"></i> Reversed</span>';
                } else {
                    return '<span class="badge bg-success"><i class="bx bx-check-circle me-1"></i> Active</span>';
                }
            })
            ->addColumn('creator_name', function ($revaluation) {
                return $revaluation->creator->name ?? 'N/A';
            })
            ->addColumn('actions', function ($revaluation) {
                $actions = '<div class="btn-group">';
                $actions .= '<a href="'.route('accounting.fx-revaluation.show', $revaluation->hash_id).'" class="btn btn-sm btn-info" title="View Details"><i class="bx bx-show"></i></a>';
                
                if (!$revaluation->is_reversed) {
                    $actions .= '<form action="'.route('accounting.fx-revaluation.reverse', $revaluation->hash_id).'" method="POST" class="d-inline reverse-form" data-item-ref="'.$revaluation->item_ref.'">';
                    $actions .= csrf_field();
                    $actions .= '<button type="button" class="btn btn-sm btn-warning reverse-btn" title="Reverse"><i class="bx bx-undo"></i></button>';
                    $actions .= '</form>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['item_type_badge', 'formatted_gain_loss', 'status_badge', 'actions'])
            ->orderColumn('revaluation_date', 'revaluation_date $1')
            ->orderColumn('created_at', 'created_at $1')
            ->make(true);
    }

    /**
     * Show the form for creating a new revaluation.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get branches
        $branches = Branch::where('company_id', $user->company_id)->get();

        // Get functional currency
        $functionalCurrency = $user->company->functional_currency ?? 'TZS';

        return view('accounting.fx-revaluation.create', compact('branches', 'functionalCurrency'));
    }

    /**
     * Generate revaluation preview (AJAX).
     */
    public function preview(Request $request)
    {
        $request->validate([
            'revaluation_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = $request->branch_id;

        try {
            $preview = $this->revaluationService->generateRevaluationPreview(
                $companyId,
                $branchId,
                $request->revaluation_date
            );

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating preview: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created revaluation.
     */
    public function store(Request $request)
    {
        \Log::info('FX Revaluation store method called', [
            'request_data' => $request->except(['preview_data']),
            'has_preview_data' => $request->has('preview_data'),
            'preview_data_type' => gettype($request->preview_data),
            'preview_data_length' => is_string($request->preview_data) ? strlen($request->preview_data) : null,
        ]);

        $request->validate([
            'revaluation_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'preview_data' => 'required', // Can be string (JSON) or array
        ]);

        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = $request->branch_id;

        try {
            // Check if revaluation has already been posted for this period
            $revaluationDate = \Carbon\Carbon::parse($request->revaluation_date);
            $year = $revaluationDate->year;
            $month = $revaluationDate->month;
            
            $existingRevaluation = GlRevaluationHistory::where('company_id', $companyId)
                ->whereYear('revaluation_date', $year)
                ->whereMonth('revaluation_date', $month)
                ->where('is_reversed', false)
                ->where(function($query) use ($branchId) {
                    if ($branchId) {
                        // If branch is specified, check for revaluations for that specific branch
                        $query->where('branch_id', $branchId);
                    } else {
                        // If no branch is specified, check for company-level revaluations (branch_id is null)
                        $query->whereNull('branch_id');
                    }
                })
                ->exists();
            
            if ($existingRevaluation) {
                $periodName = $revaluationDate->format('F Y');
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', "A revaluation has already been posted for the period {$periodName}. Please reverse the existing revaluation before posting a new one for the same period.");
            }

            // Handle preview_data - it might be a JSON string or already an array
            $previewData = $request->preview_data;
            if (is_string($previewData)) {
                $previewData = json_decode($previewData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::error('Failed to decode preview_data JSON', [
                        'json_error' => json_last_error_msg(),
                        'preview_data_preview' => substr($request->preview_data, 0, 200),
                    ]);
                    throw new \Exception('Invalid preview data format: ' . json_last_error_msg());
                }
            }

            if (!is_array($previewData) || !isset($previewData['items'])) {
                \Log::error('Invalid preview data structure', [
                    'preview_data_type' => gettype($previewData),
                    'has_items_key' => isset($previewData['items']),
                ]);
                throw new \Exception('Invalid preview data structure. Expected array with items key.');
            }

            \Log::info('Preview data validated', [
                'items_count' => count($previewData['items'] ?? []),
            ]);

            // Generate preview to ensure data is current
            $preview = $this->revaluationService->generateRevaluationPreview(
                $companyId,
                $branchId,
                $request->revaluation_date
            );

            // Post revaluation
            $result = $this->revaluationService->postRevaluation(
                $companyId,
                $branchId,
                $request->revaluation_date,
                $preview,
                $user->id
            );

            return redirect()
                ->route('accounting.fx-revaluation.index')
                ->with('success', "Revaluation posted successfully. {$result['journals_created']} journal entries created.");
        } catch (\Exception $e) {
            \Log::error('FX Revaluation posting failed in controller', [
                'user_id' => $user->id,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'revaluation_date' => $request->revaluation_date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error posting revaluation: ' . $e->getMessage() . ($e->getPrevious() ? ' (' . $e->getPrevious()->getMessage() . ')' : ''));
        }
    }

    /**
     * Display the specified revaluation.
     */
    public function show($id)
    {
        $user = Auth::user();
        
        // Decode hash ID if needed
        $decodedId = \App\Helpers\HashIdHelper::decode($id);
        $actualId = $decodedId !== null ? $decodedId : $id;
        
        // Validate that we got a valid ID
        if (!is_numeric($actualId)) {
            abort(404, 'Revaluation not found.');
        }
        
        $revaluation = GlRevaluationHistory::with([
            'postedJournal.items.chartAccount',
            'reversalJournal.items.chartAccount',
            'creator',
            'branch',
            'company'
        ])
        ->where('company_id', $user->company_id)
        ->findOrFail($actualId);

        return view('accounting.fx-revaluation.show', compact('revaluation'));
    }

    /**
     * Reverse a specific revaluation manually.
     */
    public function reverse(Request $request, $id)
    {
        $user = Auth::user();
        
        // Decode hash ID if needed
        $decodedId = \App\Helpers\HashIdHelper::decode($id);
        $actualId = $decodedId !== null ? $decodedId : $id;
        
        // Validate that we got a valid ID
        if (!is_numeric($actualId)) {
            abort(404, 'Revaluation not found.');
        }
        
        $revaluation = GlRevaluationHistory::where('company_id', $user->company_id)
            ->findOrFail($actualId);

        if ($revaluation->is_reversed) {
            return redirect()
                ->back()
                ->with('error', 'This revaluation has already been reversed.');
        }

        try {
            $reversalDateString = $request->input('reversal_date', now()->toDateString());
            $reversalDate = \Carbon\Carbon::parse($reversalDateString);
            
            // Use reflection to call protected method or create a public wrapper
            // For now, we'll reverse just this one entry
            $originalJournal = $revaluation->postedJournal;
            if (!$originalJournal) {
                return redirect()
                    ->back()
                    ->with('error', 'Original journal entry not found.');
            }

            // Create reversal using the service's reverse method for a single entry
            DB::beginTransaction();
            
            // Get original journal items
            $originalItems = $originalJournal->items;
            
            // Generate reference
            $reflection = new \ReflectionClass($this->revaluationService);
            $method = $reflection->getMethod('generateJournalReference');
            $method->setAccessible(true);
            $reference = $method->invoke($this->revaluationService);

            // Create reversal journal
            $reversalJournal = \App\Models\Journal::create([
                'date' => $reversalDate->toDateString(),
                'reference' => $reference,
                'reference_type' => 'FX Revaluation Reversal',
                'description' => "FX Revaluation Reversal - {$revaluation->item_type} - {$revaluation->item_ref}",
                'branch_id' => $revaluation->branch_id,
                'user_id' => $user->id,
            ]);

            // Reverse each journal item
            foreach ($originalItems as $originalItem) {
                $reversalJournal->items()->create([
                    'chart_account_id' => $originalItem->chart_account_id,
                    'amount' => $originalItem->amount,
                    'nature' => $originalItem->nature === 'debit' ? 'credit' : 'debit',
                    'description' => "Reversal: " . ($originalItem->description ?? ''),
                ]);

                // Create GL transaction
                \App\Models\GlTransaction::create([
                    'chart_account_id' => $originalItem->chart_account_id,
                    'amount' => $originalItem->amount,
                    'nature' => $originalItem->nature === 'debit' ? 'credit' : 'debit',
                    'transaction_id' => $reversalJournal->id,
                    'transaction_type' => 'journal',
                    'date' => $reversalDate->toDateString(),
                    'description' => "Reversal: " . ($originalItem->description ?? ''),
                    'branch_id' => $revaluation->branch_id,
                    'user_id' => $user->id,
                ]);
            }

            $revaluation->markAsReversed($reversalJournal->id);
            
            // Log activity
            $revaluation->logActivity('reverse', "Reversed FX Revaluation for {$revaluation->item_type} item ({$revaluation->item_ref})", [
                'Item Type' => $revaluation->item_type,
                'Item Reference' => $revaluation->item_ref,
                'Revaluation Date' => $revaluation->revaluation_date ? $revaluation->revaluation_date->format('Y-m-d') : 'N/A',
                'Original Gain/Loss' => number_format($revaluation->gain_loss ?? 0, 2),
                'Reversal Journal' => $reversalJournal->reference,
                'Reversal Date' => $reversalDate->format('Y-m-d'),
                'Reversed By' => Auth::user()->name,
                'Reversed At' => now()->format('Y-m-d H:i:s')
            ]);
            
            DB::commit();

            return redirect()
                ->route('accounting.fx-revaluation.show', $revaluation->hash_id)
                ->with('success', 'Revaluation reversed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Error reversing revaluation: ' . $e->getMessage());
        }
    }
}

