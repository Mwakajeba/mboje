@extends('layouts.main')

@section('title', 'Item Ledger Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Item Ledger', 'url' => '#', 'icon' => 'bx bx-book']
        ]" />
        
        <h6 class="mb-0 text-uppercase">ITEM LEDGER REPORT</h6>
        <hr />

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('inventory.reports.item-ledger') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="item_id" class="form-label">Item *</label>
                                <select class="form-select select2-single" id="item_id" name="item_id" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} ({{ $item->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Generate
                                    </button>
                                    <a href="{{ route('inventory.reports.item-ledger') }}" class="btn btn-secondary">
                                        <i class="bx bx-refresh me-1"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(isset($item))
    <!-- Export Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <a href="{{ route('inventory.reports.item-ledger.export.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bx bx-file me-1"></i> Export Excel
                        </a>
                        <a href="{{ route('inventory.reports.item-ledger.export.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bx bx-file-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($item))
    {{-- <!-- Item Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body">
                    <h5 class="card-title">Item Information</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Item ID:</strong> {{ $item->id }}
                        </div>
                        <div class="col-md-3">
                            <strong>Item Code:</strong> {{ $item->code }}
                        </div>
                        <div class="col-md-3">
                            <strong>Item Name:</strong> {{ $item->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>Category:</strong> {{ $item->category->name ?? 'N/A' }}
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <strong>Current Stock:</strong> {{ number_format($item->current_stock, 2) }} {{ $item->unit_of_measure }}
                        </div>
                        <div class="col-md-3">
                            <strong>Request Item ID:</strong> {{ request('item_id') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Form Item ID:</strong> <span id="debug-item-id">{{ request('item_id') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Report Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Item Ledger (Kardex)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th class="text-end">In Qty</th>
                                    <th class="text-end">Out Qty</th>
                                    <th class="text-end">Unit Cost</th>
                                    <th class="text-end">Running Qty</th>
                                    <th class="text-end">Running Value</th>
                                    <th class="text-end">Avg Unit Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgerEntries as $entry)
                                    @php
                                        $movement = $entry['movement'];
                                        $isIn = in_array($movement->movement_type, [
                                            'in', 'adjustment_in', 'purchased', 'opening_balance', 'return_in', 'transfer_in'
                                        ]);
                                    @endphp
                                    <tr>
                                        <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $movement->reference_type }} #{{ $movement->reference_id }}</td>
                                        <td>
                                            <span class="badge bg-{{ $isIn ? 'success' : 'danger' }}">
                                                {{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ $isIn ? number_format($movement->quantity, 2) : '-' }}</td>
                                        <td class="text-end">{{ !$isIn ? number_format($movement->quantity, 2) : '-' }}</td>
                                        <td class="text-end">{{ number_format($entry['unit_cost'] ?? $movement->unit_cost, 2) }} TZS</td>
                                        <td class="text-end"><strong>{{ number_format($entry['running_qty'], 2) }}</strong></td>
                                        <td class="text-end"><strong>{{ number_format($entry['running_value'], 2) }} TZS</strong></td>
                                        <td class="text-end">{{ number_format($entry['avg_unit_cost'] ?? ($entry['running_qty'] > 0 ? $entry['running_value'] / $entry['running_qty'] : 0), 2) }} TZS</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No movements found for this item</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 (same pattern as sales invoice)
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
});
</script>
@endpush
