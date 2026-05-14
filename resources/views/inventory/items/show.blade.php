@extends('layouts.main')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Items', 'url' => route('inventory.items.index'), 'icon' => 'bx bx-box'],
            ['label' => $item->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-0">Item Details - {{ $item->name }}</h5>
                            </div>
                            <div class="d-flex gap-2">
                                @can('update', $item)
                                <a href="{{ route('inventory.items.edit', $item->hash_id) }}" class="btn btn-primary btn-sm">
                                    <i class="bx bx-edit me-1"></i> Edit
                                </a>
                                @endcan
                                <a href="{{ route('inventory.items.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Item Code:</strong></div>
                                            <div class="col-sm-8">{{ $item->code }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Name:</strong></div>
                                            <div class="col-sm-8">{{ $item->name }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Description:</strong></div>
                                            <div class="col-sm-8">{{ $item->description ?: 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Category:</strong></div>
                                            <div class="col-sm-8">{{ $item->category_name }}</div>
                                        </div>
                                        <!-- <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Location:</strong></div>
                                            <div class="col-sm-8">{{ $item->location_name }}</div>
                                        </div> -->
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Type:</strong></div>
                                            <div class="col-sm-8">
                                                <span class="badge bg-{{ $item->item_type === 'product' ? 'primary' : 'info' }}">
                                                    {{ ucfirst($item->item_type) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Unit:</strong></div>
                                            <div class="col-sm-8">{{ $item->unit_of_measure ?: 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-0">
                                            <div class="col-sm-4"><strong>Status:</strong></div>
                                            <div class="col-sm-8">{!! $item->status_badge !!}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Information -->
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Stock Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Current Stock:</strong></div>
                                            <div class="col-sm-8">
                                                <span class="badge bg-{{ $item->stock_status_class }}">
                                                    {{ number_format($item->current_stock, 2) }} {{ $item->unit_of_measure }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Stock Status:</strong></div>
                                            <div class="col-sm-8">
                                                <span class="badge bg-{{ $item->stock_status_class }}">
                                                    {{ $item->stock_status }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Minimum Stock:</strong></div>
                                            <div class="col-sm-8">{{ $item->minimum_stock ?: 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Maximum Stock:</strong></div>
                                            <div class="col-sm-8">{{ $item->maximum_stock ?: 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Reorder Level:</strong></div>
                                            <div class="col-sm-8">{{ $item->reorder_level ?: 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-0">
                                            <div class="col-sm-4"><strong>Stock Value:</strong></div>
                                            <div class="col-sm-8">TZS {{ number_format($inventoryValue['total_value'] ?? 0, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <!-- Pricing Information -->
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Pricing Information</h6>
                                    </div>
                                    <div class="card-body">
                                        @php
                                            $resolvedCost = $item->getCostPriceForBranchOrLocation(session('branch_id'), session('location_id'));
                                            $resolvedPrice = $item->getUnitPriceForBranchOrLocation(session('branch_id'), session('location_id'));
                                        @endphp
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Cost Price:</strong></div>
                                            <div class="col-sm-8">TZS {{ number_format($resolvedCost, 2) }}</div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-sm-4"><strong>Selling Price:</strong></div>
                                            <div class="col-sm-8">TZS {{ number_format($resolvedPrice, 2) }}</div>
                                        </div>
                                        <div class="row mb-0">
                                            <div class="col-sm-4"><strong>Profit Margin:</strong></div>
                                            <div class="col-sm-8">
                                                @if($resolvedCost > 0)
                                                    {{ number_format((($resolvedPrice - $resolvedCost) / $resolvedCost) * 100, 2) }}%
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code Section -->
                            <div class="col-md-6">
                                <div class="card border">
                                    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                                        <h6 class="card-title mb-0">
                                            <i class="bx bx-qr me-2"></i>QR Code
                                        </h6>
                                        <button class="btn btn-sm btn-outline-light" id="btn-print-qr" title="Print QR Code">
                                            <i class="bx bx-printer me-1"></i> Print
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center">
                                            <div id="qr-preview" class="d-inline-block p-3 border rounded bg-white"></div>
                                            <div class="mt-3">
                                                <div class="fw-bold">{{ $item->code }}</div>
                                                <div class="small text-muted">{{ Str::limit($item->name, 50) }}</div>
                                                @if($item->category)
                                                    <div class="small text-muted mt-1">{{ $item->category->name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Information -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="card-title mb-0">
                                            <i class="bx bx-calculator me-2"></i>Cost Information
                                            <small class="ms-2 opacity-75">({{ ucfirst(str_replace('_', ' ', \App\Models\SystemSetting::where('key', 'inventory_cost_method')->value('value') ?? 'FIFO')) }} Method)</small>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="row mb-2">
                                                    <div class="col-sm-6"><strong>Total Inventory Value:</strong></div>
                                                    <div class="col-sm-6">{{ number_format($inventoryValue['total_value'], 2) }}</div>
                                                </div>
                                                <div class="row mb-2">
                                                    <div class="col-sm-6"><strong>Available Quantity:</strong></div>
                                                    <div class="col-sm-6">{{ number_format($inventoryValue['total_quantity'], 2) }}</div>
                                                </div>
                                                <div class="row mb-0">
                                                    <div class="col-sm-6"><strong>Average Cost:</strong></div>
                                                    <div class="col-sm-6">{{ number_format($inventoryValue['average_cost'], 2) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                @if($costLayers->count() > 0)
                                                    <h6 class="mb-3">Active Cost Layers:</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <th>Qty</th>
                                                                    <th>Unit Cost</th>
                                                                    <th>Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($costLayers as $layer)
                                                                <tr>
                                                                    <td>{{ $layer->transaction_date->format('M d, Y') }}</td>
                                                                    <td>{{ number_format($layer->remaining_quantity, 2) }}</td>
                                                                    <td>{{ number_format($layer->unit_cost, 2) }}</td>
                                                                    <td>{{ number_format($layer->remaining_quantity * $layer->unit_cost, 2) }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <p class="text-muted">No active cost layers found.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Movements -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">Stock Movements</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="movementsTable" class="table table-striped table-bordered" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Type</th>
                                                        <th>Reference</th>
                                                        <th>Quantity</th>
                                                        <th>Balance After</th>
                                                        <th>Notes</th>
                                                        <th>User</th>
                                                    </tr>
                                                </thead>
                                            </table>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Generate QR Code
    function generateQRCode() {
        const data = {
            t: 'inventory_item',
            id: @json($item->id),
            hid: @json($item->hash_id),
            c: @json($item->code),
            n: @json($item->name),
            cat: @json(optional($item->category)->name),
            p: @json($item->getUnitPriceForBranchOrLocation(session('branch_id'), session('location_id'))),
            s: @json($currentStock),
            vt: @json($vatType),
            vr: @json($vatRate),
            it: @json($item->item_type),
            ts: @json($item->track_stock),
            url: @json(route('inventory.items.show', $item->hash_id))
        };

        const qrContainer = document.getElementById('qr-preview');
        if (!qrContainer) return;

        if (typeof QRCode === 'undefined') {
            // Wait for library to load
            setTimeout(generateQRCode, 100);
            return;
        }

        try {
            qrContainer.innerHTML = '';
            const qrText = JSON.stringify(data);
            
            new QRCode(qrContainer, {
                text: qrText,
                width: 200,
                height: 200,
                correctLevel: QRCode.CorrectLevel.M,
                colorDark: '#000000',
                colorLight: '#ffffff'
            });
        } catch (e) {
            console.error('QR Code generation error:', e);
            qrContainer.innerHTML = '<div class="text-danger small p-2">Failed to generate QR code. Please refresh the page.</div>';
        }
    }

    // Generate QR code on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', generateQRCode);
    } else {
        generateQRCode();
    }

    // Print QR Code handler
    $('#btn-print-qr').on('click', function() {
        const printWindow = window.open('', '_blank');
        const qrData = {
            t: 'inventory_item',
            c: @json($item->code),
            n: @json($item->name),
            cat: @json(optional($item->category)->name),
            id: @json($item->hash_id),
            url: @json(route('inventory.items.show', $item->hash_id))
        };
        
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>QR Code - {{ $item->code }}</title>
                <style>
                    @page { size: A4; margin: 20mm; }
                    body { 
                        font-family: Arial, sans-serif; 
                        display: flex; 
                        flex-direction: column; 
                        align-items: center; 
                        justify-content: center; 
                        min-height: 100vh; 
                        margin: 0; 
                        padding: 20px;
                    }
                    .qr-container { 
                        text-align: center; 
                        padding: 20px; 
                        border: 2px solid #000; 
                        border-radius: 8px;
                        background: white;
                    }
                    .qr-code { margin: 20px 0; }
                    .item-info { margin-top: 15px; }
                    .item-code { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
                    .item-name { font-size: 14px; color: #666; }
                    .item-category { font-size: 12px; color: #999; margin-top: 5px; }
                </style>
            </head>
            <body>
                <div class="qr-container">
                    <div class="qr-code" id="qr-print"></div>
                    <div class="item-info">
                        <div class="item-code">${qrData.c}</div>
                        <div class="item-name">${qrData.n}</div>
                        ${qrData.cat ? '<div class="item-category">' + qrData.cat + '</div>' : ''}
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script>
                <script nonce="{{ $cspNonce ?? '' }}">
                    new QRCode(document.getElementById('qr-print'), {
                        text: ${JSON.stringify(JSON.stringify(qrData))},
                        width: 300,
                        height: 300,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                    window.onload = function() { window.print(); };
                <\/script>
            </body>
            </html>
        `;
        
        printWindow.document.write(printContent);
        printWindow.document.close();
    });

    $('#movementsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inventory.items.movements', $item->hash_id) }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        },
        columns: [
            { data: 'movement_date_formatted', name: 'movement_date' },
            { data: 'movement_type_badge', name: 'movement_type', orderable: false },
            { data: 'reference_display', name: 'reference' },
            { data: 'quantity_formatted', name: 'quantity' },
            { data: 'balance_after_formatted', name: 'balance_after' },
            { data: 'notes_display', name: 'notes' },
            { data: 'user_name', name: 'user.name' }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            processing: "Loading movements...",
            emptyTable: "No stock movements recorded yet.",
            zeroRecords: "No matching movements found."
        }
    });
});
</script>
@endpush
@endsection
