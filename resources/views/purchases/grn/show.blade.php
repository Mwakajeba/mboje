@extends('layouts.main')

@section('title', 'Goods Receipt Note - ' . ($grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT))))

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'GRN', 'url' => route('purchases.grn.index'), 'icon' => 'bx bx-package'],
            ['label' => ($grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT))), 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">GOODS RECEIPT NOTE DETAILS</h6>
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

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">{{ $grn->grn_number ?? ('GRN-' . str_pad($grn->id, 6, '0', STR_PAD_LEFT)) }}</h4>
                        <p class="text-muted mb-0">Received on {{ $grn->receipt_date?->format('M d, Y') }}</p>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $grn->getStatusBadgeClass() }} me-2">{{ strtoupper(str_replace('_',' ',$grn->status)) }}</span>
                        @if($grn->quality_check_status)
                            <span class="badge {{ $grn->getQualityStatusBadgeClass() }}">QC: {{ strtoupper(str_replace('_',' ',$grn->quality_check_status)) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Supplier & Order</h6>
                        <p class="mb-1"><strong>Supplier:</strong> {{ optional(optional($grn->purchaseOrder)->supplier)->name ?? 'N/A' }}</p>
                        @if($grn->purchaseOrder)
                            <p class="mb-1"><strong>Order:</strong> <a href="{{ route('purchases.orders.show', $grn->purchaseOrder->encoded_id) }}">{{ $grn->purchaseOrder->order_number ?? ('PO-' . str_pad($grn->purchaseOrder->id, 6, '0', STR_PAD_LEFT)) }}</a></p>
                        @else
                            <p class="mb-1"><strong>Order:</strong> Standalone GRN</p>
                        @endif
                        <p class="mb-1"><strong>Received By:</strong> {{ $grn->receivedByUser->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Location:</strong> {{ $grn->warehouse->name ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Branch:</strong> {{ $grn->branch->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">GRN Summary</h6>
                        <div class="border-top pt-3">
                            <div class="row mb-2">
                                <div class="col-6"><strong>Total Items:</strong></div>
                                <div class="col-6 text-end">{{ $grn->items->count() }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Total Quantity:</strong></div>
                                <div class="col-6 text-end">{{ number_format($grn->total_quantity ?? $grn->items->sum('quantity_received'), 2) }}</div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6"><strong>Total Amount:</strong></div>
                                <div class="col-6 text-end"><span class="fw-bold">TZS {{ number_format($grn->total_amount, 2) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                @if($grn->quality_check_status)
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-primary mb-3">Quality Check</h6>
                        <p class="mb-1"><strong>Status:</strong> <span class="badge {{ $grn->getQualityStatusBadgeClass() }}">{{ strtoupper(str_replace('_',' ',$grn->quality_check_status)) }}</span></p>
                        <p class="mb-1"><strong>Checked By:</strong> {{ $grn->qualityCheckedByUser->name ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Checked On:</strong> {{ $grn->quality_check_date?->format('M d, Y H:i') ?? 'N/A' }}</p>
                        @can('edit purchase orders')
                        <hr>
                        @if(!($alreadyConverted ?? false))
                        @php
                            $qcCurrent = $grn->quality_check_status ?? 'pending';
                            // Align UI with controller transitions: pending -> passed|failed|partial; partial -> passed|failed; passed/failed -> none
                            $canSetPassed = !in_array($qcCurrent, ['passed','failed']) && in_array($qcCurrent, ['pending','partial']);
                            $canSetFailed = !in_array($qcCurrent, ['passed','failed']) && in_array($qcCurrent, ['pending','partial']);
                            $canSetPartial = $qcCurrent === 'pending';
                        @endphp
                        <form method="POST" action="{{ route('purchases.grn.qc.update', $grn->id) }}" class="d-flex gap-2 flex-wrap align-items-center" onsubmit="return confirm('Confirm quality check update?');">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="quality_check_status" id="qc_status_input" value="{{ $grn->quality_check_status ?? 'pending' }}">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-success" onclick="submitQc('passed')" {{ $canSetPassed ? '' : 'disabled' }}>Mark Passed</button>
                                <button type="button" class="btn btn-outline-danger" onclick="submitQc('failed')" {{ $canSetFailed ? '' : 'disabled' }}>Mark Failed</button>
                                <button type="button" class="btn btn-outline-info" onclick="submitQc('partial')" {{ $canSetPartial ? '' : 'disabled' }}>Mark Partial</button>
                            </div>
                        </form>
                        <script nonce="{{ $cspNonce ?? '' }}">
                            function submitQc(status){
                                const input = document.getElementById('qc_status_input');
                                input.value = status;
                                input.closest('form').submit();
                            }
                        </script>
                        @else
                        <p class="text-muted mb-0"><small><i class="bx bx-info-circle me-1"></i>QC status cannot be changed as this GRN has been converted to an invoice.</small></p>
                        @endif
                        @endcan
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($grn->notes)
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="text-primary mb-3">Notes</h6>
                <p class="mb-0">{{ $grn->notes }}</p>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <h6 class="text-primary mb-3">Items</h6>
                @php $isPartial = ($grn->quality_check_status ?? 'pending') === 'partial'; @endphp
                @if($isPartial && auth()->user()->can('edit purchase orders') && !($alreadyConverted ?? false))
                <form method="POST" action="{{ route('purchases.grn.qc-items.update', $grn->id) }}">
                    @csrf
                    @method('PUT')
                @endif
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Qty Ordered</th>
                                <th>Qty Received</th>
                                @if($isPartial)
                                <th>Accepted Qty</th>
                                <th>Item QC</th>
                                @endif
                                <th>Unit Cost</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grn->items as $it)
                            <tr>
                                <td>{{ optional($it->inventoryItem)->name ?? '-' }}</td>
                                <td>{{ number_format($it->quantity_ordered, 2) }}</td>
                                <td>{{ number_format($it->quantity_received, 2) }}</td>
                                @if($isPartial)
                                <td style="max-width:140px;">
                                    @can('edit purchase orders')
                                    @if(!($alreadyConverted ?? false))
                                    <input type="number" name="items[{{ $it->id }}][accepted_quantity]" class="form-control form-control-sm" step="0.01" min="0" max="{{ number_format($it->quantity_received, 2, '.', '') }}" value="{{ number_format(old('items.' . $it->id . '.accepted_quantity', ($it->accepted_quantity > 0 ? $it->accepted_quantity : $it->quantity_received)), 2, '.', '') }}">
                                    @else
                                    {{ number_format($it->accepted_quantity ?? 0, 2) }}
                                    @endif
                                    @else
                                    {{ number_format($it->accepted_quantity ?? 0, 2) }}
                                    @endcan
                                </td>
                                <td style="max-width:150px;">
                                    @can('edit purchase orders')
                                    @if(!($alreadyConverted ?? false))
                                    <select name="items[{{ $it->id }}][item_qc_status]" class="form-select form-select-sm">
                                        <option value="pending" {{ ($it->item_qc_status ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="passed" {{ ($it->item_qc_status ?? '') === 'passed' ? 'selected' : '' }}>Passed</option>
                                        <option value="failed" {{ ($it->item_qc_status ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                    @else
                                    <span class="badge bg-secondary">{{ strtoupper($it->item_qc_status ?? 'PENDING') }}</span>
                                    @endif
                                    @else
                                    <span class="badge bg-secondary">{{ strtoupper($it->item_qc_status ?? 'PENDING') }}</span>
                                    @endcan
                                </td>
                                @endif
                                <td>{{ number_format($it->unit_cost, 2) }}</td>
                                <td>{{ number_format($it->total_cost, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($isPartial && auth()->user()->can('edit purchase orders') && !($alreadyConverted ?? false))
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save QC Decisions</button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            @can('edit purchase orders')
            <a href="{{ route('purchases.grn.edit', $grn->hash_id ?? $grn->id) }}" class="btn btn-warning"><i class="bx bx-edit me-1"></i>Edit GRN</a>
            @endcan
            @can('create purchase invoices')
            @php
                $qcStatus = $grn->quality_check_status ?? 'pending';
                // Enable if parent QC is passed, OR if partial and at least one accepted item is passed
                // BUT only if not already converted
                $canConvertToInvoice = false;
                if (!($alreadyConverted ?? false)) {
                    if ($qcStatus === 'passed') {
                        $canConvertToInvoice = true;
                    } elseif ($qcStatus === 'partial') {
                        $itemsWithAcceptedAndPassed = $grn->items->filter(function($it) {
                            return ($it->accepted_quantity ?? 0) > 0 && ($it->item_qc_status ?? 'pending') === 'passed';
                        });
                        $canConvertToInvoice = $itemsWithAcceptedAndPassed->count() > 0;
                    }
                }
            @endphp
            @if($canConvertToInvoice)
                <a href="{{ route('purchases.purchase-invoices.create', ['grn_id' => $grn->hash_id ?? $grn->id]) }}" class="btn btn-success">
                    <i class="bx bx-receipt me-1"></i>Convert to Invoice
                </a>
            @else
                <button type="button" class="btn btn-success" disabled title="{{ ($alreadyConverted ?? false) ? 'This GRN has already been converted to an invoice.' : ('Quality Check must be PASSED (or all accepted items must be PASSED if partial). Current: ' . strtoupper($qcStatus)) }}">
                    <i class="bx bx-receipt me-1"></i>Convert to Invoice
                </button>
            @endif
            @endcan
            <a href="{{ route('purchases.grn.print', $grn->hash_id ?? $grn->id) }}" target="_blank" class="btn btn-outline-primary"><i class="bx bx-printer me-1"></i>Print / PDF</a>
            <a href="{{ route('purchases.grn.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i>Back</a>
            @if($grn->purchaseOrder)
            <a href="{{ route('purchases.orders.show', $grn->purchaseOrder->encoded_id) }}" class="btn btn-outline-primary"><i class="bx bx-link-external me-1"></i>View Order</a>
            @endif
        </div>
    </div>
</div>
@endsection


