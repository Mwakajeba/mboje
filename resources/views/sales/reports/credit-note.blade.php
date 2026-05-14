@extends('layouts.main')

@section('title', 'Credit Note Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Credit Note Report', 'url' => '#', 'icon' => 'bx bx-credit-card']
            ]" />
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-credit-card me-2"></i>Credit Note Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.credit-note.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.credit-note.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Customer</label>
                                <select class="form-select" name="customer_id">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reason</label>
                                <select class="form-select" name="reason">
                                    <option value="">All Reasons</option>
                                    <option value="return" {{ $reason == 'return' ? 'selected' : '' }}>Return</option>
                                    <option value="discount" {{ $reason == 'discount' ? 'selected' : '' }}>Discount</option>
                                    <option value="adjustment" {{ $reason == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                                    <option value="damage" {{ $reason == 'damage' ? 'selected' : '' }}>Damage</option>
                                    <option value="other" {{ $reason == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Credit Notes</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_credit_notes']) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Credit Value</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_credit_value'], 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Average Credit</h5>
                                        <h3 class="mb-0">{{ number_format($summary['total_credit_notes'] > 0 ? $summary['total_credit_value'] / $summary['total_credit_notes'] : 0, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Unique Customers</h5>
                                        <h3 class="mb-0">{{ number_format($creditNotes->pluck('customer_id')->unique()->count()) }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Analysis -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border border-warning">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">
                                            <i class="bx bx-pie-chart me-2"></i>Credit Analysis
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Return Rate</h6>
                                                    <h4 class="text-warning">{{ number_format($summary['return_rate'], 1) }}%</h4>
                                                    <small class="text-muted">Of total sales</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Top Reason</h6>
                                                    <h4 class="text-info">{{ ucfirst($summary['top_reason'] ?? 'N/A') }}</h4>
                                                    <small class="text-muted">Most common cause</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Impact on Revenue</h6>
                                                    <h4 class="text-danger">{{ number_format($summary['total_credit_value'], 2) }} TZS</h4>
                                                    <small class="text-muted">Revenue reduction</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Status</h6>
                                                    @if($summary['return_rate'] < 5)
                                                        <span class="badge bg-success">Low Returns</span>
                                                    @elseif($summary['return_rate'] < 10)
                                                        <span class="badge bg-warning">Moderate Returns</span>
                                                    @else
                                                        <span class="badge bg-danger">High Returns</span>
                                                    @endif
                                                    <small class="text-muted d-block">Return level</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Credit Note #</th>
                                        <th>Customer</th>
                                        <th>Original Invoice</th>
                                        <th>Date</th>
                                        <th class="text-end">Credit Amount (TZS)</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                        <th>Applied Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($creditNotes as $index => $creditNote)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $creditNote->credit_note_number }}</div>
                                                <small class="text-muted">{{ $creditNote->reference_number ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $creditNote->customer->name ?? 'Unknown Customer' }}</div>
                                                <small class="text-muted">{{ $creditNote->customer->email ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $creditNote->original_invoice_number ?? 'N/A' }}</div>
                                                <small class="text-muted">{{ $creditNote->original_invoice_date ? $creditNote->original_invoice_date->format('d/m/Y') : 'N/A' }}</small>
                                            </td>
                                            <td>{{ $creditNote->credit_note_date->format('d/m/Y') }}</td>
                                            <td class="text-end">
                                                <span class="text-danger fw-bold">{{ number_format($creditNote->total_amount, 2) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ ucfirst(str_replace('_', ' ', $creditNote->reason_code ?? 'N/A')) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($creditNote->status == 'approved')
                                                    <span class="badge bg-success">
                                                        <i class="bx bx-check-circle me-1"></i>Approved
                                                    </span>
                                                @elseif($creditNote->status == 'pending')
                                                    <span class="badge bg-warning">
                                                        <i class="bx bx-time me-1"></i>Pending
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <i class="bx bx-x-circle me-1"></i>{{ ucfirst($creditNote->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold {{ $creditNote->applied_amount > 0 ? 'text-success' : 'text-muted' }}">
                                                    {{ number_format($creditNote->applied_amount, 2) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No credit notes found for the selected criteria</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">Total</th>
                                        <th class="text-end">{{ number_format($creditNotes->sum('total_amount'), 2) }}</th>
                                        <th colspan="2">-</th>
                                        <th class="text-end">{{ number_format($creditNotes->sum('applied_amount'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
