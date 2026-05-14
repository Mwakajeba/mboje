@extends('layouts.main')

@section('title', 'Payables Aging Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
                ['label' => 'Payables Aging', 'url' => '#', 'icon' => 'bx bx-time-five']
            ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-time-five me-2"></i>Payables Aging Report
                            </h4>
                            <div class="btn-group">
                                <a href="{{ route('purchases.reports.payables-aging.export.pdf', request()->query()) }}" 
                                   class="btn btn-danger">
                                    <i class="bx bxs-file-pdf me-1"></i>Export PDF
                                </a>
                                <a href="{{ route('purchases.reports.payables-aging.export.excel', request()->query()) }}" 
                                   class="btn btn-success">
                                    <i class="bx bxs-file-excel me-1"></i>Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">The Payables Aging Report groups supplier invoice balances into aging buckets, essential for:</p>
                                    <ul class="mb-0">
                                        <li><strong>Cash Flow Planning:</strong> Identify upcoming payment obligations by aging bucket to plan cash flow and avoid liquidity issues</li>
                                        <li><strong>Payment Prioritization:</strong> Focus on overdue invoices first, ensuring critical suppliers are paid on time to maintain relationships</li>
                                        <li><strong>Supplier Relationship Management:</strong> Monitor aging balances to proactively address payment delays and maintain good supplier relationships</li>
                                        <li><strong>Financial Risk Assessment:</strong> Identify high-risk aging buckets that may indicate cash flow problems or operational issues</li>
                                        <li><strong>Budget Compliance:</strong> Track actual payables against budgeted amounts by aging category for better financial control</li>
                                        <li><strong>Audit and Compliance:</strong> Provide detailed aging analysis for internal audits and demonstrate proper accounts payable management</li>
                                        <li><strong>Trend Analysis:</strong> Monitor changes in aging patterns over time to identify improving or deteriorating payment situations</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">As of Date</label>
                                <input type="date" class="form-control" name="as_of_date" value="{{ \Carbon\Carbon::parse($asOfDate)->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" name="supplier_id">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
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
                            <div class="col-md-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="view_type">
                                    <option value="summary" {{ $viewType === 'summary' ? 'selected' : '' }}>Summary</option>
                                    <option value="detailed" {{ $viewType === 'detailed' ? 'selected' : '' }}>Detailed</option>
                                    <option value="trend" {{ $viewType === 'trend' ? 'selected' : '' }}>Trend</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </form>

                        <!-- 1. Executive Summary -->
                        @if($viewType === 'summary')
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bx bx-list-ul me-2"></i>Executive Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Aging Category</th>
                                                <th class="text-end">No. of Invoices</th>
                                                <th class="text-end">Outstanding Amount (TZS)</th>
                                                <th class="text-end">% of Total Payables</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $labels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
                                                // Totals of overdue-only (match summary)
                                                $sumCount = $summaryTotalCount ?? 0;
                                                $sumAmount = $summaryTotalAmount ?? 0.0;
                                            @endphp
                                            @foreach($labels as $key => $label)
                                                    @php
                                                        $row = $agingSummary[$key] ?? ['count' => 0, 'total_amount' => 0];
                                                        $pct = ($summaryTotalAmount ?? 0) > 0 ? ($row['total_amount'] / $summaryTotalAmount) * 100 : 0;
                                                    @endphp
                                                <tr>
                                                    <td>{{ $label }}</td>
                                                    <td class="text-end">{{ number_format($row['count']) }}</td>
                                                    <td class="text-end">{{ number_format($row['total_amount'], 2) }}</td>
                                                    <td class="text-end">{{ number_format($pct, 1) }}%</td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <th>Total Outstanding</th>
                                                <th class="text-end">{{ number_format($sumCount) }}</th>
                                                <th class="text-end">{{ number_format($sumAmount, 2) }}</th>
                                                <th class="text-end">100.0%</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- 2. Detailed Invoice Aging -->
                        @if($viewType === 'detailed')
                            @foreach($detailedAllBuckets as $bucketData)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="bx bx-time me-2"></i>Detailed Invoice Aging ({{ $bucketData['label'] }})
                                            <span class="badge bg-secondary ms-2">{{ number_format($bucketData['bucket_total'], 2) }} TZS</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Supplier Name</th>
                                                        <th>Invoice #</th>
                                                        <th>Invoice Date</th>
                                                        <th>Due Date</th>
                                                        <th class="text-end">Amount (TZS)</th>
                                                        <th>Days Overdue</th>
                                                        <th>Status</th>
                                                        <th>Payment Note / Remark</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $bucketGrand = 0; @endphp
                                                    @foreach($bucketData['groups'] as $group)
                                                        @php $first = true; @endphp
                                                        @foreach($group['invoices'] as $inv)
                                                            @php
                                                                $invDate = \Carbon\Carbon::parse($inv['invoice_date']);
                                                                $dueDate = isset($inv['due_date']) ? \Carbon\Carbon::parse($inv['due_date']) : $invDate->copy()->addDays(30);
                                                                $daysText = ($inv['days_overdue'] ?? 0) > 0 ? ($inv['days_overdue']) : 'Not yet due';
                                                                $status = ucfirst($inv['status'] ?? 'draft');
                                                                $bucketGrand += $inv['outstanding_amount'];
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $first ? $group['supplier_name'] : '' }}</td>
                                                                <td>{{ $inv['invoice_number'] }}</td>
                                                                <td>{{ $invDate->format('d-M-Y') }}</td>
                                                                <td>{{ $dueDate->format('d-M-Y') }}</td>
                                                                <td class="text-end">{{ number_format($inv['outstanding_amount'], 2) }}</td>
                                                                <td>{{ is_numeric($daysText) ? $daysText : $daysText }}</td>
                                                                <td>{{ $status }}</td>
                                                                <td></td>
                                                            </tr>
                                                            @php $first = false; @endphp
                                                        @endforeach
                                                        <tr class="table-light">
                                                            <td colspan="4">Subtotal — {{ $group['supplier_name'] }}</td>
                                                            <td class="text-end">{{ number_format($group['subtotal'], 2) }}</td>
                                                            <td colspan="3"></td>
                                                        </tr>
                                                    @endforeach
                                                    <tr class="table-light">
                                                        <td colspan="4">TOTAL OUTSTANDING ({{ $bucketData['label'] }})</td>
                                                        <td class="text-end">{{ number_format($bucketGrand, 2) }}</td>
                                                        <td colspan="3"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <!-- 3. Aging Trend Comparison -->
                        @if($viewType === 'trend')
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bx bx-line-chart me-2"></i>Aging Trend Comparison</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Aging Bucket</th>
                                                <th class="text-end">Current Month (TZS)</th>
                                                <th class="text-end">Previous Month (TZS)</th>
                                                <th class="text-end">% Change</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $bucketLabels = ['0-30' => '0 – 30 Days', '31-60' => '31 – 60 Days', '61-90' => '61 – 90 Days', '90+' => 'Over 90 Days'];
                                                $currentTotal = collect($trend)->sum('current');
                                                $prevTotal = collect($trend)->sum('previous');
                                                $totalChange = $prevTotal>0 ? (($currentTotal-$prevTotal)/$prevTotal)*100 : ($currentTotal>0?100:0);
                                            @endphp
                                            @foreach($trend as $key => $row)
                                                <tr>
                                                    <td>{{ $bucketLabels[$key] ?? $key }}</td>
                                                    <td class="text-end">{{ number_format($row['current'], 2) }}</td>
                                                    <td class="text-end">{{ number_format($row['previous'], 2) }}</td>
                                                    <td class="text-end">{{ ($row['pct_change']>0?'+':'') . number_format($row['pct_change'], 1) }}%</td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <th>Total</th>
                                                <th class="text-end">{{ number_format($currentTotal, 2) }}</th>
                                                <th class="text-end">{{ number_format($prevTotal, 2) }}</th>
                                                <th class="text-end">{{ ($totalChange>0?'+':'') . number_format($totalChange, 1) }}% overall</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(collect($agingSummary)->sum('count') === 0)
                            <div class="text-center text-muted py-5">
                                <i class="bx bx-receipt display-4"></i>
                                <h5 class="mt-3">No Outstanding Invoices</h5>
                                <p>All invoices are paid or no invoices found for the selected criteria.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
