@extends('layouts.main')

@section('title','Purchase Order Register')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'PO Register', 'url' => '#', 'icon' => 'bx bx-file-blank']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-file-blank me-2"></i>Purchase Order Register
                            </h4>
                        </div>

                        <!-- Report Importance Information -->
                        <div class="alert alert-info border-0 border-start border-4 border-info mb-4">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-4 me-3 mt-1"></i>
                                <div>
                                    <h6 class="alert-heading mb-2">Why This Report Matters</h6>
                                    <p class="mb-2">The Purchase Order Register provides a comprehensive view of all purchase orders, enabling you to:</p>
                                    <ul class="mb-0">
                                        <li><strong>Track Purchase Commitments:</strong> Monitor all outstanding purchase orders and their total value to manage cash flow and budget planning</li>
                                        <li><strong>Supplier Performance:</strong> Analyze order patterns, delivery timelines, and supplier reliability</li>
                                        <li><strong>Financial Control:</strong> Review approved purchase orders to ensure compliance with procurement policies and budget limits</li>
                                        <li><strong>Audit Trail:</strong> Maintain a complete record of all purchase orders for internal audits and compliance requirements</li>
                                        <li><strong>Decision Making:</strong> Identify trends, peak ordering periods, and supplier relationships to optimize procurement strategies</li>
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
                            <div class="col-md-3">
                                <label class="form-label">Supplier</label>
                                <select name="supplier_id" class="form-select select2-single">
                                    <option value="">All</option>
                                    @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All</option>
                                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                                    <option value="closed" {{ request('status')=='closed'?'selected':'' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i>Filter</button>
                                <a href="{{ route('purchases.reports.purchase-order-register') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Export Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <a href="{{ route('purchases.reports.purchase-order-register.export.pdf', request()->query()) }}" 
                                       class="btn btn-danger">
                                        <i class="bx bx-file-pdf me-1"></i>Export PDF
                                    </a>
                                    <a href="{{ route('purchases.reports.purchase-order-register.export.excel', request()->query()) }}" 
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
                                        <h5 class="card-title text-primary">Total POs</h5>
                                        <h3 class="mb-0">{{ $totalPos }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Value</h5>
                                        <h3 class="mb-0">{{ number_format((float)$totalValue, 2) }} TZS</h3>
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
                                        <th>Description</th>
                                        <th class="text-end">Ordered Qty</th>
                                        <th class="text-end">Unit Cost</th>
                                        <th class="text-end">PO Value</th>
                                        <th>Status</th>
                                        <th>Expected Delivery</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $item)
                                    <tr>
                                        <td><strong>{{ $item['po_number'] }}</strong></td>
                                        <td>{{ $item['po_date'] }}</td>
                                        <td>{{ $item['supplier'] }}</td>
                                        <td>{{ $item['item_code'] }}</td>
                                        <td>{{ $item['description'] }}</td>
                                        <td class="text-end">{{ number_format($item['ordered_qty'], 2) }}</td>
                                        <td class="text-end">{{ number_format($item['unit_cost'], 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($item['po_value'], 2) }}</td>
                                        <td>
                                            @if($item['status'] == 'Approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($item['status'] == 'Closed')
                                                <span class="badge bg-secondary">Closed</span>
                                            @else
                                                <span class="badge bg-warning">Draft</span>
                                            @endif
                                        </td>
                                        <td>{{ $item['expected_delivery'] }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="10" class="text-center text-muted">No purchase orders found.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">{{ number_format($reportData->sum('po_value'), 2) }} TZS</td>
                                        <td colspan="2"></td>
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
document.addEventListener('DOMContentLoaded', function(){
    if (window.jQuery && $.fn.select2) {
        $('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
    }
});
</script>
@endpush


