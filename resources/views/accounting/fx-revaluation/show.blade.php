@extends('layouts.main')
@section('title', 'FX Revaluation Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'FX Revaluation', 'url' => route('accounting.fx-revaluation.index'), 'icon' => 'bx bx-refresh'],
            ['label' => 'Details', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">FX REVALUATION DETAILS</h6>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-show me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Revaluation Details</h5>
                                </div>
                                <p class="mb-0 text-muted">View detailed information about this revaluation entry</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('accounting.fx-revaluation.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revaluation Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Revaluation Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Revaluation Date:</th>
                                <td>{{ $revaluation->revaluation_date->format('F d, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Item Type:</th>
                                <td>
                                    <span class="badge bg-{{ $revaluation->item_type == 'AR' ? 'info' : ($revaluation->item_type == 'AP' ? 'warning' : ($revaluation->item_type == 'BANK' ? 'success' : 'secondary')) }}">
                                        {{ $revaluation->item_type }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Reference:</th>
                                <td><strong>{{ $revaluation->item_ref }}</strong></td>
                            </tr>
                            <tr>
                                <th>Currency:</th>
                                <td>{{ $revaluation->fcy_amount > 0 ? 'FCY' : 'LCY' }}</td>
                            </tr>
                            <tr>
                                <th>FCY Amount:</th>
                                <td class="fw-bold">{{ number_format(abs($revaluation->fcy_amount), 2) }}</td>
                            </tr>
                            <tr>
                                <th>Original Rate:</th>
                                <td>{{ number_format($revaluation->original_rate, 6) }}</td>
                            </tr>
                            <tr>
                                <th>Closing Rate:</th>
                                <td>{{ number_format($revaluation->closing_rate, 6) }}</td>
                            </tr>
                            <tr>
                                <th>Base Amount (LCY):</th>
                                <td class="fw-bold">{{ number_format($revaluation->base_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Gain/Loss:</th>
                                <td>
                                    <span class="fw-bold fs-5 {{ $revaluation->gain_loss >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $revaluation->formatted_gain_loss }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    @if($revaluation->is_reversed)
                                        <span class="badge bg-secondary">
                                            <i class="bx bx-check me-1"></i> Reversed
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            <i class="bx bx-check-circle me-1"></i> Active
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Branch:</th>
                                <td>{{ $revaluation->branch->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $revaluation->creator->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Created At:</th>
                                <td>{{ $revaluation->created_at->format('F d, Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Posted Journal Entry -->
                @if($revaluation->postedJournal)
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-book me-2"></i>Posted Journal Entry</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Journal Reference:</th>
                                <td>{{ $revaluation->postedJournal->reference }}</td>
                            </tr>
                            <tr>
                                <th>Journal Date:</th>
                                <td>{{ $revaluation->postedJournal->date->format('F d, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td>{{ $revaluation->postedJournal->description }}</td>
                            </tr>
                        </table>

                        <h6 class="mt-3 mb-2">Journal Items:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th>Nature</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($revaluation->postedJournal->items as $item)
                                        <tr>
                                            <td>
                                                @if($item->chartAccount)
                                                    {{ $item->chartAccount->account_code ?? '' }} - {{ $item->chartAccount->account_name ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $item->nature == 'debit' ? 'primary' : 'success' }}">
                                                    {{ strtoupper($item->nature) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Reversal Journal Entry -->
                @if($revaluation->reversalJournal)
                <div class="card radius-10 border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-undo me-2"></i>Reversal Journal Entry</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Journal Reference:</th>
                                <td>{{ $revaluation->reversalJournal->reference }}</td>
                            </tr>
                            <tr>
                                <th>Journal Date:</th>
                                <td>{{ $revaluation->reversalJournal->date->format('F d, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td>{{ $revaluation->reversalJournal->description }}</td>
                            </tr>
                        </table>

                        <h6 class="mt-3 mb-2">Reversal Journal Items:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Account</th>
                                        <th>Nature</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($revaluation->reversalJournal->items as $item)
                                        <tr>
                                            <td>
                                                @if($item->chartAccount)
                                                    {{ $item->chartAccount->account_code ?? '' }} - {{ $item->chartAccount->account_name ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $item->nature == 'debit' ? 'primary' : 'success' }}">
                                                    {{ strtoupper($item->nature) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ number_format($item->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                @if(!$revaluation->is_reversed)
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-cog me-2"></i>Actions</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('accounting.fx-revaluation.reverse', $revaluation->hash_id) }}" 
                              method="POST" class="reverse-form" 
                              data-item-ref="{{ $revaluation->item_ref }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Reversal Date</label>
                                <input type="date" name="reversal_date" class="form-control" 
                                       value="{{ now()->toDateString() }}" required>
                            </div>
                            <button type="button" class="btn btn-warning reverse-btn">
                                <i class="bx bx-undo me-1"></i> Reverse This Revaluation
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Handle reverse button click with SweetAlert
        $('.reverse-btn').on('click', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const itemRef = form.data('item-ref');
            
            Swal.fire({
                title: 'Reverse Revaluation?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to reverse this revaluation?</p>
                        <div class="alert alert-warning mb-0">
                            <strong>Reference:</strong> ${itemRef}<br>
                            <strong>Warning:</strong> This will create a reversal journal entry. This action cannot be undone.
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-undo me-1"></i> Yes, Reverse It',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Reversing...',
                        text: 'Please wait while we reverse the revaluation',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

