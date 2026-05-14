@extends('layouts.main')

@section('title', 'Three-Way Matching Exception Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Three-Way Matching Exception', 'url' => '#', 'icon' => 'bx bx-error']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-error me-2"></i>Three-Way Matching Exception Report
                            </h4>
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-warning border-0 border-start border-4 border-warning mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">This report identifies invoices that do not match their Purchase Orders and/or Goods Receipt Notes, critical for:</p>
                                    <ul class="mb-0">
                                        <li><strong>Fraud Prevention:</strong> Detect unauthorized invoices or over-billing before payment</li>
                                        <li><strong>Cost Control:</strong> Identify pricing or quantity discrepancies that increase costs</li>
                                        <li><strong>Process Compliance:</strong> Ensure proper three-way matching process is followed</li>
                                        <li><strong>Audit Trail:</strong> Maintain records of exceptions for audit purposes</li>
                                    </ul>
                                </div>
                            </div>
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
                                        <h5 class="card-title text-primary">Total Invoices Checked</h5>
                                        <h3 class="mb-0">{{ $summary['total_invoices'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Invoices with Exceptions</h5>
                                        <h3 class="mb-0">{{ $summary['total_exceptions'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Exception Items</h5>
                                        <h3 class="mb-0">{{ $summary['total_exception_items'] }}</h3>
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
                                        <th>Supplier</th>
                                        <th>Invoice Amount</th>
                                        <th>Exception Count</th>
                                        <th>Exceptions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                        <tr>
                                            <td><strong>{{ $row['invoice_no'] }}</strong></td>
                                            <td>{{ Carbon\Carbon::parse($row['invoice_date'])->format('d-M-Y') }}</td>
                                            <td><strong>{{ $row['supplier_name'] }}</strong></td>
                                            <td class="text-end fw-bold">{{ number_format($row['invoice_amount'], 2) }} TZS</td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ $row['exception_count'] }}</span>
                                            </td>
                                            <td>
                                                <small class="text-danger">{{ $row['exception_summary'] }}</small>
                                                @if(count($row['exceptions']) > 3)
                                                    <br><small class="text-muted">... and {{ count($row['exceptions']) - 3 }} more</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bx bx-check-circle fs-1 text-success"></i>
                                                <p class="mt-2">No exceptions found! All invoices match their POs and GRNs.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
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

