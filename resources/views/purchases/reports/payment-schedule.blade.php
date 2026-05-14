@extends('layouts.main')

@section('title', 'Supplier Payment Schedule Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Payment Schedule', 'url' => '#', 'icon' => 'bx bx-calendar']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-calendar me-2"></i>Supplier Payment Schedule Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <label class="form-label">From</label>
                                <input type="date" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To</label>
                                <input type="date" name="date_to" value="{{ $dateTo->format('Y-m-d') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select select2-single">
                                    <option value="">All</option>
                                    @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Branch</label>
                                <select name="branch_id" class="form-select select2-single">
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ ($branchId == $b->id || ($branchId == 'all' && $b->id == 'all')) ? 'selected' : '' }}>{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-filter me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Invoices</h5>
                                        <h3 class="mb-0">{{ $summary['total_invoices'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Outstanding</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_outstanding'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Paid</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_paid'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Invoice Date</th>
                                        <th>Due Date</th>
                                        <th>Supplier</th>
                                        <th class="text-end">Invoice Amount</th>
                                        <th class="text-end">Paid Amount</th>
                                        <th class="text-end">Outstanding</th>
                                        <th>Days Until Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                        <tr>
                                            <td>{{ $row['invoice_no'] }}</td>
                                            <td>{{ Carbon\Carbon::parse($row['invoice_date'])->format('d-M-Y') }}</td>
                                            <td>
                                                <strong>{{ Carbon\Carbon::parse($row['due_date'])->format('d-M-Y') }}</strong>
                                            </td>
                                            <td><strong>{{ $row['supplier_name'] }}</strong></td>
                                            <td class="text-end">{{ number_format($row['invoice_amount'], 2) }}</td>
                                            <td class="text-end text-success">{{ number_format($row['paid_amount'], 2) }}</td>
                                            <td class="text-end fw-bold {{ $row['outstanding_amount'] > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($row['outstanding_amount'], 2) }} TZS
                                            </td>
                                            <td>
                                                @if($row['days_until_due'] < 0)
                                                    <span class="badge bg-danger">Overdue ({{ abs($row['days_until_due']) }} days)</span>
                                                @elseif($row['days_until_due'] <= 7)
                                                    <span class="badge bg-warning">{{ $row['days_until_due'] }} days</span>
                                                @else
                                                    <span class="badge bg-info">{{ $row['days_until_due'] }} days</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bx bx-info-circle fs-1"></i>
                                                <p class="mt-2">No scheduled payments found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end fw-bold">Total:</th>
                                        <th class="text-end fw-bold">{{ number_format($reportData->sum('invoice_amount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($reportData->sum('paid_amount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($summary['total_outstanding'], 2) }} TZS</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush
