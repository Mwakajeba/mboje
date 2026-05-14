@extends('layouts.main')

@section('title', 'Sales Return Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-bar-chart-alt-2'],
                ['label' => 'Sales Reports', 'url' => route('sales.reports.index'), 'icon' => 'bx bx-trending-up'],
                ['label' => 'Sales Return', 'url' => '#', 'icon' => 'bx bx-undo']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-undo me-2"></i>Sales Return Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('sales.reports.sales-return.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bx-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('sales.reports.sales-return.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bx-file me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $dateFrom instanceof \Carbon\Carbon ? $dateFrom->format('Y-m-d') : $dateFrom }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $dateTo instanceof \Carbon\Carbon ? $dateTo->format('Y-m-d') : $dateTo }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select class="form-select" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ ($branchId == $branch->id || ($branchId == 'all' && $branch->id == 'all')) ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Return Reason</label>
                                <select class="form-select" name="reason">
                                    <option value="">All Reasons</option>
                                    <option value="defective" {{ $reason == 'defective' ? 'selected' : '' }}>Defective</option>
                                    <option value="wrong_item" {{ $reason == 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
                                    <option value="damaged" {{ $reason == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                    <option value="customer_request" {{ $reason == 'customer_request' ? 'selected' : '' }}>Customer Request</option>
                                    <option value="quality_issue" {{ $reason == 'quality_issue' ? 'selected' : '' }}>Quality Issue</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Total Return Value</h5>
                                        <h3 class="mb-0">{{ number_format($totalReturnValue, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Total Returns</h5>
                                        <h3 class="mb-0">{{ number_format($totalReturns) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Return Rate %</h5>
                                        <h3 class="mb-0">{{ number_format($returnRate, 2) }}%</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Net Sales</h5>
                                        <h3 class="mb-0">{{ number_format($netSales, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Return Analysis by Reason -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border border-info">
                                    <div class="card-body">
                                        <h5 class="card-title text-info">
                                            <i class="bx bx-pie-chart me-2"></i>Returns by Reason
                                        </h5>
                                        <div class="row">
                                            @foreach($returnsByReason as $reasonData)
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <h6 class="text-muted">{{ ucfirst(str_replace('_', ' ', $reasonData->reason)) }}</h6>
                                                        <h4 class="text-{{ $reasonData->count >= 10 ? 'danger' : ($reasonData->count >= 5 ? 'warning' : 'info') }}">
                                                            {{ number_format($reasonData->count) }}
                                                        </h4>
                                                        <small class="text-muted">{{ number_format($reasonData->percentage, 1) }}%</small>
                                                    </div>
                                                </div>
                                            @endforeach
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
                                        <th>Return Date</th>
                                        <th>Original Invoice</th>
                                        <th>Customer</th>
                                        <th>Item</th>
                                        <th class="text-end">Quantity</th>
                                        <th class="text-end">Unit Price (TZS)</th>
                                        <th class="text-end">Return Value (TZS)</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($returnData as $index => $return)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $return->return_date->format('d/m/Y') }}</td>
                                            <td>
                                                <a href="{{ route('sales.invoices.show', $return->original_invoice_id) }}" class="text-primary">
                                                    {{ $return->original_invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $return->customer_name ?? 'Unknown Customer' }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $return->item_name }}</div>
                                                <small class="text-muted">{{ $return->item_code }}</small>
                                            </td>
                                            <td class="text-end">{{ number_format($return->quantity) }}</td>
                                            <td class="text-end">{{ number_format($return->unit_price, 2) }}</td>
                                            <td class="text-end">
                                                <span class="text-danger fw-bold">{{ number_format($return->return_value, 2) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $return->reason == 'defective' ? 'danger' : ($return->reason == 'wrong_item' ? 'warning' : 'info') }}">
                                                    {{ ucfirst(str_replace('_', ' ', $return->reason)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $return->status == 'processed' ? 'success' : ($return->status == 'pending' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($return->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No return data available for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="7">Total</th>
                                        <th class="text-end">{{ number_format($totalReturnValue, 2) }}</th>
                                        <th colspan="2">-</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Return Impact Analysis -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border border-warning">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning">
                                            <i class="bx bx-trending-down me-2"></i>Return Impact Analysis
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Return Rate</h6>
                                                    <div class="progress mb-2" style="height: 20px;">
                                                        <div class="progress-bar bg-{{ $returnRate <= 2 ? 'success' : ($returnRate <= 5 ? 'warning' : 'danger') }}" 
                                                             role="progressbar" 
                                                             style="width: {{ min($returnRate, 10) }}%" 
                                                             aria-valuenow="{{ $returnRate }}" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="10">
                                                            {{ number_format($returnRate, 1) }}%
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        @if($returnRate <= 2)
                                                            Excellent - Low return rate
                                                        @elseif($returnRate <= 5)
                                                            Good - Monitor closely
                                                        @else
                                                            High - Review quality control
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Top Return Reason</h6>
                                                    <h4 class="text-danger">
                                                        {{ $topReturnReason->reason ?? 'N/A' }}
                                                    </h4>
                                                    <small class="text-muted">
                                                        {{ $topReturnReason->count ?? 0 }} returns
                                                        ({{ number_format($topReturnReason->percentage ?? 0, 1) }}%)
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6 class="text-muted">Recommendation</h6>
                                                    @if($returnRate <= 2)
                                                        <span class="badge bg-success">Maintain current quality</span>
                                                    @elseif($returnRate <= 5)
                                                        <span class="badge bg-warning">Improve quality control</span>
                                                    @else
                                                        <span class="badge bg-danger">Urgent quality review needed</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
