@extends('layouts.main')

@section('title','GRN vs Invoice Variance')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'GRN vs Invoice', 'url' => '#', 'icon' => 'bx bx-error']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-error me-2"></i>GRN vs Invoice Variance Report
                            </h4>
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">The GRN vs Invoice Variance Report highlights discrepancies between what was received and what was invoiced, critical for:</p>
                                    <ul class="mb-0">
                                        <li><strong>Prevent Overpayment:</strong> Identify items invoiced but not received, or over-invoiced quantities, protecting against billing errors and fraud</li>
                                        <li><strong>Ensure Complete Billing:</strong> Find items received but not invoiced to ensure all goods are properly accounted for and paid</li>
                                        <li><strong>Financial Accuracy:</strong> Maintain accurate accounts payable records and prevent discrepancies in financial statements</li>
                                        <li><strong>Audit Compliance:</strong> Meet internal and external audit requirements by demonstrating proper three-way matching (PO, GRN, Invoice)</li>
                                        <li><strong>Supplier Management:</strong> Identify suppliers with frequent invoicing errors and take corrective action</li>
                                        <li><strong>Cash Flow Management:</strong> Avoid paying for goods not received and ensure timely payment for goods actually received</li>
                                        <li><strong>Inventory Accuracy:</strong> Ensure inventory records match invoiced quantities for accurate cost accounting and stock valuation</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">From</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select select2-single">
                                    <option value="">All</option>
                                    @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">GRN Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                                    <option value="received" {{ request('status')=='received'?'selected':'' }}>Received</option>
                                    <option value="qc_passed" {{ request('status')=='qc_passed'?'selected':'' }}>QC Passed</option>
                                    <option value="qc_failed" {{ request('status')=='qc_failed'?'selected':'' }}>QC Failed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Variance</label>
                                <select name="variance_status" class="form-select">
                                    <option value="all" {{ request('variance_status')=='all'?'selected':'' }}>All</option>
                                    <option value="matched" {{ request('variance_status')=='matched'?'selected':'' }}>Matched</option>
                                    <option value="not_invoiced" {{ request('variance_status')=='not_invoiced'?'selected':'' }}>Not Invoiced</option>
                                    <option value="under_invoiced" {{ request('variance_status')=='under_invoiced'?'selected':'' }}>Under Invoiced</option>
                                    <option value="over_invoiced" {{ request('variance_status')=='over_invoiced'?'selected':'' }}>Over Invoiced</option>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i>Filter</button>
                                <a href="{{ route('purchases.reports.grn-variance') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Export Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('purchases.reports.grn-variance.export.pdf', request()->query()) }}" 
                                       class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </a>
                                    <a href="{{ route('purchases.reports.grn-variance.export.excel', request()->query()) }}" 
                                       class="btn btn-success">
                                        <i class="bx bx-file me-1"></i>Export Excel
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Items</h5>
                                        <h3 class="mb-0">{{ $totalItems }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Matched</h5>
                                        <h3 class="mb-0">{{ $matched }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Not Invoiced</h5>
                                        <h3 class="mb-0">{{ $notInvoiced }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Under Invoiced</h5>
                                        <h3 class="mb-0">{{ $underInvoiced }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Over Invoiced</h5>
                                        <h3 class="mb-0">{{ $overInvoiced }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Total Received Qty</h5>
                                        <h3 class="mb-0">{{ number_format($totalReceivedQty, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Invoiced Qty</h5>
                                        <h3 class="mb-0">{{ number_format($totalInvoicedQty, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-{{ $totalVarianceQty >= 0 ? 'warning' : 'danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-{{ $totalVarianceQty >= 0 ? 'warning' : 'danger' }}">Variance Qty</h5>
                                        <h3 class="mb-0">{{ number_format($totalVarianceQty, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Variance Value</h5>
                                        <h3 class="mb-0">{{ number_format($totalVarianceValue, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Supplier</th>
                                        <th>GRN No</th>
                                        <th>Invoice No</th>
                                        <th class="text-end">Received Qty</th>
                                        <th class="text-end">Invoiced Qty</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">Variance Qty</th>
                                        <th class="text-end">Variance Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td><strong>{{ $row['supplier_name'] }}</strong></td>
                                        <td>{{ $row['grn_number'] }}</td>
                                        <td>{{ $row['invoice_no'] }}</td>
                                        <td class="text-end">{{ number_format($row['received_qty'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['invoiced_qty'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['unit_cost'], 2) }}</td>
                                        <td class="text-end {{ $row['variance_qty'] > 0 ? 'text-danger' : ($row['variance_qty'] < 0 ? 'text-warning' : 'text-success') }}">
                                            {{ number_format($row['variance_qty'], 2) }}
                                        </td>
                                        <td class="text-end fw-bold {{ $row['variance_value'] > 0 ? 'text-danger' : ($row['variance_value'] < 0 ? 'text-warning' : 'text-success') }}">
                                            {{ number_format($row['variance_value'], 2) }} TZS
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8" class="text-center text-muted">No data found for the selected criteria.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">{{ number_format($totalReceivedQty, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($totalInvoicedQty, 2) }}</td>
                                        <td></td>
                                        <td class="text-end fw-bold">{{ number_format($totalVarianceQty, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($totalVarianceValue, 2) }} TZS</td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="7" class="text-end">TOTALS:</th>
                                        <th class="text-end">{{ number_format($totalReceivedQty, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalInvoicedQty, 2) }}</th>
                                        <th class="text-end {{ $totalVariance >= 0 ? 'text-warning' : 'text-danger' }}">{{ number_format($totalVariance, 2) }}</th>
                                        <th class="text-end"></th>
                                        <th></th>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function(){
    if (window.jQuery && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
    }
});
</script>
@endpush
