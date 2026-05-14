@extends('layouts.main')

@section('title','PO vs GRN (Fulfillment)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'PO vs GRN', 'url' => '#', 'icon' => 'bx bx-transfer']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-transfer me-2"></i>PO vs GRN (Fulfillment Report)
                            </h4>
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">The PO vs GRN Fulfillment Report compares what you ordered versus what you actually received, helping you to:</p>
                                    <ul class="mb-0">
                                        <li><strong>Identify Delivery Issues:</strong> Quickly spot items that haven't been received or are partially received, enabling proactive follow-up with suppliers</li>
                                        <li><strong>Inventory Planning:</strong> Understand fulfillment rates to better forecast inventory levels and avoid stockouts</li>
                                        <li><strong>Supplier Accountability:</strong> Track supplier performance and delivery reliability to make informed supplier selection decisions</li>
                                        <li><strong>Financial Accuracy:</strong> Ensure inventory records match actual receipts, preventing discrepancies in stock valuation</li>
                                        <li><strong>Operational Efficiency:</strong> Identify bottlenecks in the procurement-to-receipt cycle and improve supply chain management</li>
                                        <li><strong>Cost Control:</strong> Monitor partial deliveries that may impact production schedules or service delivery</li>
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
                                <label class="form-label">PO Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                                    <option value="closed" {{ request('status')=='closed'?'selected':'' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fulfillment</label>
                                <select name="fulfillment_status" class="form-select">
                                    <option value="all" {{ request('fulfillment_status')=='all'?'selected':'' }}>All</option>
                                    <option value="fully_received" {{ request('fulfillment_status')=='fully_received'?'selected':'' }}>Fully Received</option>
                                    <option value="partially_received" {{ request('fulfillment_status')=='partially_received'?'selected':'' }}>Partially Received</option>
                                    <option value="not_received" {{ request('fulfillment_status')=='not_received'?'selected':'' }}>Not Received</option>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i>Filter</button>
                                <a href="{{ route('purchases.reports.po-vs-grn') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Export Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('purchases.reports.po-vs-grn.export.pdf', request()->query()) }}" 
                                       class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </a>
                                    <a href="{{ route('purchases.reports.po-vs-grn.export.excel', request()->query()) }}" 
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
                                        <h5 class="card-title text-success">Fully Received</h5>
                                        <h3 class="mb-0">{{ $fullyReceived }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Partially Received</h5>
                                        <h3 class="mb-0">{{ $partiallyReceived }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Not Received</h5>
                                        <h3 class="mb-0">{{ $notReceived }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card border border-info">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-info">Total Ordered Qty</h5>
                                        <h3 class="mb-0">{{ number_format($totalOrderedQty, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Received Qty</h5>
                                        <h3 class="mb-0">{{ number_format($totalReceivedQty, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-{{ $totalVariance >= 0 ? 'warning' : 'danger' }}">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-{{ $totalVariance >= 0 ? 'warning' : 'danger' }}">Variance</h5>
                                        <h3 class="mb-0">{{ number_format($totalVariance, 2) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-secondary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-secondary">Fulfillment %</h5>
                                        <h3 class="mb-0">{{ $totalOrderedQty > 0 ? number_format(($totalReceivedQty / $totalOrderedQty) * 100, 2) : 0 }}%</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>PO Number</th>
                                        <th>PO Date</th>
                                        <th>Supplier</th>
                                        <th>Item Code</th>
                                        <th>Ordered Qty</th>
                                        <th>Received Qty</th>
                                        <th>Pending Qty</th>
                                        <th>GRN No</th>
                                        <th class="text-end">Fulfillment %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $row)
                                    <tr>
                                        <td><strong>{{ $row['po_number'] }}</strong></td>
                                        <td>{{ $row['po_date'] ? \Carbon\Carbon::parse($row['po_date'])->format('d-M-Y') : 'N/A' }}</td>
                                        <td>{{ $row['supplier_name'] }}</td>
                                        <td>{{ $row['item_code'] }}</td>
                                        <td class="text-end">{{ number_format($row['ordered_quantity'], 2) }}</td>
                                        <td class="text-end">{{ number_format($row['received_quantity'], 2) }}</td>
                                        <td class="text-end {{ $row['pending_qty'] > 0 ? 'text-warning' : 'text-success' }}">
                                            {{ number_format($row['pending_qty'], 2) }}
                                        </td>
                                        <td>{{ $row['grn_number'] }}</td>
                                        <td class="text-end fw-bold">{{ number_format($row['fulfillment_percentage'], 1) }}%</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="9" class="text-center text-muted">No data found for the selected criteria.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">{{ number_format($reportData->sum('ordered_quantity'), 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($reportData->sum('received_quantity'), 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($reportData->sum('pending_qty'), 2) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6" class="text-end">TOTALS:</th>
                                        <th class="text-end">{{ number_format($totalOrderedQty, 2) }}</th>
                                        <th class="text-end">{{ number_format($totalReceivedQty, 2) }}</th>
                                        <th class="text-end {{ $totalVariance >= 0 ? 'text-warning' : 'text-danger' }}">{{ number_format($totalVariance, 2) }}</th>
                                        <th class="text-end">{{ $totalOrderedQty > 0 ? number_format(($totalReceivedQty / $totalOrderedQty) * 100, 2) : 0 }}%</th>
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
