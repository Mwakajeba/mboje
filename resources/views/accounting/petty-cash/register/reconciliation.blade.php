@extends('layouts.main')

@section('title', 'Petty Cash Reconciliation')

@push('styles')
<style>
    .reconciliation-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    
    .reconciliation-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .reconciliation-card.border-primary { border-left-color: #0d6efd; }
    .reconciliation-card.border-success { border-left-color: #198754; }
    .reconciliation-card.border-danger { border-left-color: #dc3545; }
    .reconciliation-card.border-warning { border-left-color: #ffc107; }
    .reconciliation-card.border-info { border-left-color: #0dcaf0; }
    
    .variance-positive {
        color: #198754;
        font-weight: bold;
    }
    
    .variance-negative {
        color: #dc3545;
        font-weight: bold;
    }
    
    .variance-zero {
        color: #6c757d;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Management', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Petty Cash Units', 'url' => route('accounting.petty-cash.units.index'), 'icon' => 'bx bx-wallet'],
            ['label' => $unit->name, 'url' => route('accounting.petty-cash.units.show', $unit->encoded_id), 'icon' => 'bx bx-show'],
            ['label' => 'Reconciliation', 'url' => '#', 'icon' => 'bx bx-check-square']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-0">Petty Cash Reconciliation</h4>
                        <p class="text-muted mb-0">{{ $unit->name }} ({{ $unit->code }})</p>
                    </div>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('accounting.petty-cash.reconciliation.index') }}?as_of_date={{ $asOfDate }}" class="btn btn-warning">
                            <i class="bx bx-table me-1"></i>View All Reconciliations
                        </a>
                        <button type="button" class="btn btn-danger" onclick="exportReconciliation('pdf')">
                            <i class="bx bx-file-blank me-1"></i>Export PDF
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportReconciliation('excel')">
                            <i class="bx bx-spreadsheet me-1"></i>Export Excel
                        </button>
                        <a href="{{ route('accounting.petty-cash.register.index', $unit->encoded_id) }}" class="btn btn-primary">
                            <i class="bx bx-list-ul me-1"></i>View Register
                        </a>
                        <a href="{{ route('accounting.petty-cash.units.show', $unit->encoded_id) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Unit
                        </a>
                    </div>
                </div>
                <hr>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Reconciliation Form -->
        <form action="{{ route('accounting.petty-cash.register.reconciliation.save', $unit->encoded_id) }}" method="POST">
            @csrf
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">As of Date <span class="text-danger">*</span></label>
                    <input type="date" 
                           class="form-control @error('as_of_date') is-invalid @enderror" 
                           name="as_of_date" 
                           id="as_of_date"
                           value="{{ old('as_of_date', $asOfDate) }}"
                           required
                           onchange="loadReconciliation()">
                    @error('as_of_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <!-- <div class="col-md-4">
                    <label class="form-label">Cash Counted <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">TZS</span>
                        <input type="number" 
                               class="form-control @error('cash_counted') is-invalid @enderror" 
                               name="cash_counted" 
                               id="cash_counted"
                               value="{{ old('cash_counted', session('reconciliation_data.cash_counted')) }}"
                               step="0.01" 
                               min="0"
                               required
                               onchange="calculateVariance()">
                    </div>
                    @error('cash_counted')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div> -->
                <div class="col-md-4">
                    <label class="form-label">Variance</label>
                    <div class="input-group">
                        <span class="input-group-text">TZS</span>
                        <input type="text" 
                               class="form-control" 
                               id="variance"
                               readonly
                               value="{{ number_format(session('reconciliation_data.variance') ?? 0, 2) }}">
                    </div>
                    <small class="text-muted">Difference between counted and calculated</small>
                </div>
            </div>

            <!-- Reconciliation Summary -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card reconciliation-card border-primary">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Opening Balance</h6>
                            <h4 class="mb-0">TZS {{ number_format($reconciliation['opening_balance'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card reconciliation-card border-danger">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Disbursed</h6>
                            <h4 class="mb-0">TZS {{ number_format($reconciliation['total_disbursed'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card reconciliation-card border-success">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Replenished</h6>
                            <h4 class="mb-0">TZS {{ number_format($reconciliation['total_replenished'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card reconciliation-card border-info">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Closing Cash (Calculated)</h6>
                            <h4 class="mb-0" id="closing_cash">TZS {{ number_format($reconciliation['closing_cash'] ?? 0, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Balance vs Calculated -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>System Balance</h6>
                        </div>
                        <div class="card-body">
                            <h3 class="mb-0">TZS {{ number_format($reconciliation['system_balance'] ?? 0, 2) }}</h3>
                            <small class="text-muted">Current balance in the system</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Calculated Balance</h6>
                        </div>
                        <div class="card-body">
                            <h3 class="mb-0">TZS {{ number_format($reconciliation['closing_cash'] ?? 0, 2) }}</h3>
                            <small class="text-muted">Opening + Replenished - Disbursed</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Outstanding Vouchers -->
            @if($outstandingVouchers && $outstandingVouchers->count() > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0"><i class="bx bx-error-circle me-2"></i>Outstanding Vouchers (Pending Receipts)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PCV Number</th>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                            <th>Requested By</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalOutstanding = 0; @endphp
                                        @foreach($outstandingVouchers as $voucher)
                                            @php $totalOutstanding += $voucher->amount; @endphp
                                            <tr>
                                                <td>{{ $voucher->pcv_number ?? 'Null' }}</td>
                                                <td>{{ $voucher->register_date->format('M d, Y') }}</td>
                                                <td>{{ $voucher->description }}</td>
                                                <td class="text-end">TZS {{ number_format($voucher->amount ? , 2) }}</td>
                                                <td>{{ $voucher->requestedBy->name ?? 'N/A' }}</td>
                                                <td><span class="badge bg-warning">{{ ucfirst($voucher->status) }}</span></td>
                                            </tr>
                                        @endforeach
                                        <tr class="table-light">
                                            <td colspan="3"><strong>Total Outstanding</strong></td>
                                            <td class="text-end"><strong>TZS {{ number_format($totalOutstanding, 2) }}</strong></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Notes -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-note me-2"></i>Reconciliation Notes</h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      name="notes" 
                                      id="notes" 
                                      rows="4" 
                                      placeholder="Enter any notes or explanations for the reconciliation...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('accounting.petty-cash.register.index', $unit->encoded_id) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Save Reconciliation
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function calculateVariance() {
    const cashCounted = parseFloat($('#cash_counted').val()) || 0;
    const closingCash = parseFloat('{{ $reconciliation['closing_cash'] ?? 0 }}');
    const variance = cashCounted - closingCash;
    
    $('#variance').val(variance.toFixed(2));
    
    // Update variance color
    $('#variance').removeClass('variance-positive variance-negative variance-zero');
    if (variance > 0) {
        $('#variance').addClass('variance-positive');
    } else if (variance < 0) {
        $('#variance').addClass('variance-negative');
    } else {
        $('#variance').addClass('variance-zero');
    }
}

function loadReconciliation() {
    const asOfDate = $('#as_of_date').val();
    if (asOfDate) {
        window.location.href = '{{ route("accounting.petty-cash.register.reconciliation", $unit->encoded_id) }}?as_of_date=' + asOfDate;
    }
}

function exportReconciliation(format) {
    const asOfDate = $('#as_of_date').val() || '{{ $asOfDate }}';
    const cashCounted = $('#cash_counted').val() || '';
    const notes = $('#notes').val() || '';
    
    let url = '';
    if (format === 'pdf') {
        url = '{{ route("accounting.petty-cash.register.reconciliation.export.pdf", $unit->encoded_id) }}';
    } else {
        url = '{{ route("accounting.petty-cash.register.reconciliation.export.excel", $unit->encoded_id) }}';
    }
    
    url += '?as_of_date=' + asOfDate;
    if (cashCounted) {
        url += '&cash_counted=' + cashCounted;
    }
    if (notes) {
        url += '&notes=' + encodeURIComponent(notes);
    }
    
    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
}

$(document).ready(function() {
    calculateVariance();
});
</script>
@endpush


