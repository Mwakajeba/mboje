@extends('layouts.main')

@section('title', 'Delivery Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Sales', 'url' => route('sales.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Deliveries', 'url' => route('sales.deliveries.index'), 'icon' => 'bx bx-truck'],
            ['label' => $delivery->delivery_number, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">DELIVERY DETAILS</h6>
            <div class="d-flex gap-2">
                @if($delivery->status === 'draft')
                    @can('edit delivery')
                    <a href="{{ route('sales.deliveries.edit', Hashids::encode($delivery->id)) }}" class="btn btn-warning">
                        <i class="bx bx-edit me-1"></i> Edit Delivery
                    </a>
                    @endcan
                @endif
                
                @if($delivery->status === 'draft')
                    @can('manage delivery workflow')
                    <button type="button" class="btn btn-info" onclick="startPicking('{{ Hashids::encode($delivery->id) }}')">
                        <i class="bx bx-package me-1"></i> Start Picking
                    </button>
                    @endcan
                @endif
                
                @if($delivery->status === 'picking')
                    @can('manage delivery workflow')
                    <button type="button" class="btn btn-warning" onclick="completePicking('{{ Hashids::encode($delivery->id) }}')">
                        <i class="bx bx-check me-1"></i> Complete Picking
                    </button>
                    @endcan
                @endif
                
                @if($delivery->status === 'packed')
                    @can('manage delivery workflow')
                    <button type="button" class="btn btn-primary" onclick="startDelivery('{{ Hashids::encode($delivery->id) }}')">
                        <i class="bx bx-truck me-1"></i> Start Delivery
                    </button>
                    @endcan
                @endif
                
                @if($delivery->status === 'in_transit')
                    @can('manage delivery workflow')
                    <button type="button" class="btn btn-success" onclick="completeDelivery('{{ Hashids::encode($delivery->id) }}')">
                        <i class="bx bx-check-circle me-1"></i> Complete Delivery
                    </button>
                    @endcan
                @endif
                
                <button type="button" class="btn btn-outline-primary" onclick="printDeliveryNote('{{ Hashids::encode($delivery->id) }}')">
                    <i class="bx bx-printer me-1"></i> Delivery Note
                </button>
                <a href="{{ route('sales.deliveries.note.pdf', Hashids::encode($delivery->id)) }}" class="btn btn-primary" target="_blank">
                    <i class="bx bx-download me-1"></i> Generate PDF
                </a>
                
                @if($delivery->status === 'converted_to_invoice')
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Invoice Created:</strong> This delivery has been automatically converted to an invoice.
                    </div>
                @endif
            </div>
        </div>
        <hr />

        <!-- Delivery Information -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Delivery Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Delivery Number:</strong></td>
                                        <td>{{ $delivery->delivery_number }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>{!! $delivery->status_badge !!}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Delivery Date:</strong></td>
                                        <td>{{ $delivery->formatted_delivery_date_time }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Delivery Type:</strong></td>
                                        <td>{{ $delivery->delivery_type_text }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Quantity:</strong></td>
                                        <td>{{ number_format($delivery->total_quantity, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Weight:</strong></td>
                                        <td>{{ number_format($delivery->total_weight, 2) }} {{ $delivery->weight_unit }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Created By:</strong></td>
                                        <td>{{ $delivery->createdBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created Date:</strong></td>
                                        <td>{{ $delivery->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    @if($delivery->picked_by)
                                    <tr>
                                        <td><strong>Picked By:</strong></td>
                                        <td>{{ $delivery->pickedBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Picked At:</strong></td>
                                        <td>{{ $delivery->picked_at ? $delivery->picked_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    @if($delivery->packed_by)
                                    <tr>
                                        <td><strong>Packed By:</strong></td>
                                        <td>{{ $delivery->packedBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Packed At:</strong></td>
                                        <td>{{ $delivery->packed_at ? $delivery->packed_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    @if($delivery->delivered_by)
                                    <tr>
                                        <td><strong>Delivered By:</strong></td>
                                        <td>{{ $delivery->deliveredBy->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Delivered At:</strong></td>
                                        <td>{{ $delivery->delivered_at ? $delivery->delivered_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    @if($delivery->received_by)
                                    <tr>
                                        <td><strong>Received By:</strong></td>
                                        <td>{{ $delivery->received_by_name ?? ($delivery->receivedBy->name ?? 'N/A') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Received At:</strong></td>
                                        <td>{{ $delivery->received_at ? $delivery->received_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Customer Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-user me-2"></i>Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <h6>{{ $delivery->customer->name }}</h6>
                        <p class="mb-1"><strong>Phone:</strong> {{ $delivery->customer->phone ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $delivery->customer->email ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Address:</strong> {{ $delivery->customer->address ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Delivery Details -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-truck me-2"></i>Delivery Details</h6>
                    </div>
                    <div class="card-body">
                        @if($delivery->delivery_address)
                        <p class="mb-1"><strong>Address:</strong> {{ $delivery->delivery_address }}</p>
                        @endif
                        @if($delivery->contact_person)
                        <p class="mb-1"><strong>Contact:</strong> {{ $delivery->contact_person }}</p>
                        @endif
                        @if($delivery->contact_phone)
                        <p class="mb-1"><strong>Phone:</strong> {{ $delivery->contact_phone }}</p>
                        @endif
                        @if($delivery->vehicle_number)
                        <p class="mb-1"><strong>Vehicle:</strong> {{ $delivery->vehicle_number }}</p>
                        @endif
                        @if($delivery->driver_name)
                        <p class="mb-1"><strong>Driver:</strong> {{ $delivery->driver_name }}</p>
                        @endif
                        @if($delivery->driver_phone)
                        <p class="mb-0"><strong>Driver Phone:</strong> {{ $delivery->driver_phone }}</p>
                        @endif
                    </div>
                </div>

                <!-- Progress -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bx bx-bar-chart me-2"></i>Delivery Process</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar" role="progressbar" style="width: {{ $delivery->progress_percentage }}%" 
                                 aria-valuenow="{{ $delivery->progress_percentage }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $delivery->progress_percentage }}%
                            </div>
                        </div>
                        
                        <!-- Process Steps -->
                        <div class="row text-center mb-3">
                            <div class="col-3">
                                <div class="p-2 {{ $delivery->status === 'draft' ? 'bg-primary text-white rounded' : ($delivery->status === 'picking' || in_array($delivery->status, ['packed', 'in_transit', 'delivered']) ? 'bg-success text-white rounded' : 'bg-light') }}">
                                    <i class="bx bx-edit d-block mb-1"></i>
                                    <small>Draft</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 {{ $delivery->status === 'picking' ? 'bg-warning text-white rounded' : (in_array($delivery->status, ['packed', 'in_transit', 'delivered']) ? 'bg-success text-white rounded' : 'bg-light') }}">
                                    <i class="bx bx-package d-block mb-1"></i>
                                    <small>Picking</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 {{ $delivery->status === 'packed' ? 'bg-info text-white rounded' : ($delivery->status === 'in_transit' || $delivery->status === 'delivered' ? 'bg-success text-white rounded' : 'bg-light') }}">
                                    <i class="bx bx-box d-block mb-1"></i>
                                    <small>Packed</small>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="p-2 {{ $delivery->status === 'in_transit' ? 'bg-warning text-white rounded' : ($delivery->status === 'delivered' ? 'bg-success text-white rounded' : 'bg-light') }}">
                                    <i class="bx bx-truck d-block mb-1"></i>
                                    <small>Delivered</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <small class="text-muted">Current Status: <strong>{{ ucfirst($delivery->status) }}</strong></small>
                        </div>
                        
                        <!-- Quick Actions -->
                        @if($delivery->status === 'picking')
                        <div class="text-center mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="pickAll('{{ Hashids::encode($delivery->id) }}')">
                                <i class="bx bx-check-double"></i> Pick All Remaining
                            </button>
                        </div>
                        @elseif(in_array($delivery->status, ['packed','in_transit']))
                        <div class="text-center mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="packAll('{{ Hashids::encode($delivery->id) }}')">
                                <i class="bx bx-package"></i> Pack All Remaining
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success ms-1" onclick="deliverAll('{{ Hashids::encode($delivery->id) }}')">
                                <i class="bx bx-send"></i> Deliver All Remaining
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-package me-2"></i>Delivery Items</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>VAT Type</th>
                                <th>VAT Rate</th>
                                <th>VAT Amount</th>
                                <th>Line Total</th>
                                <th>Picking Status</th>
                                <th>Packing Status</th>
                                <th>Delivery Status</th>
                                @if($delivery->status === 'picking')
                                <th>Action</th>
                                @endif
                                @if(in_array($delivery->status, ['packed','in_transit']))
                                <th>Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($delivery->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->item_name }}</strong><br>
                                    <small class="text-muted">{{ $item->item_code }}</small>
                                </td>
                                <td>{{ number_format($item->quantity, 2) }} {{ $item->unit_of_measure }}</td>
                                <td>TZS {{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ ucfirst($item->vat_type) }}</td>
                                <td>{{ number_format($item->vat_rate, 1) }}%</td>
                                <td>TZS {{ number_format($item->vat_amount, 2) }}</td>
                                <td>TZS {{ number_format($item->line_total, 2) }}</td>
                                <td>{!! $item->picking_status_badge !!}</td>
                                <td>{!! $item->packing_status_badge !!}</td>
                                <td>{!! $item->delivery_status_badge !!}</td>
                                @if($delivery->status === 'picking')
                                <td>
                                    @if(!$item->picked)
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="pickItem('{{ Hashids::encode($item->id) }}')">
                                        <i class="bx bx-check"></i> Pick
                                    </button>
                                    @else
                                    <span class="text-success">Picked</span>
                                    @endif
                                </td>
                                @endif
                                @if(in_array($delivery->status, ['packed','in_transit']))
                                <td>
                                    @if(!$item->packed)
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="packItem('{{ Hashids::encode($item->id) }}')">
                                        <i class="bx bx-package"></i> Pack
                                    </button>
                                    @elseif(!$item->delivered)
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="deliverItem('{{ Hashids::encode($item->id) }}')">
                                        <i class="bx bx-send"></i> Deliver
                                    </button>
                                    @else
                                    <span class="text-success">Delivered</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ in_array($delivery->status, ['packed','in_transit']) ? 12 : ($delivery->status === 'picking' ? 11 : 10) }}" class="text-center">No items found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delivery Summary -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-calculator me-2"></i>Delivery Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <div class="mb-2">
                                <i class="bx bx-package fs-4 text-primary"></i>
                            </div>
                            <h6 class="text-muted mb-1">Items Total</h6>
                            <h5 class="mb-0 text-primary">TZS {{ number_format($delivery->items->sum('line_total'), 2) }}</h5>
                        </div>
                    </div>
                    
                    @if($delivery->has_transport_cost && $delivery->transport_cost > 0)
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <div class="mb-2">
                                <i class="bx bx-truck fs-4 text-info"></i>
                            </div>
                            <h6 class="text-muted mb-1">Transport Cost</h6>
                            <h5 class="mb-0 text-info">TZS {{ number_format($delivery->transport_cost, 2) }}</h5>
                        </div>
                    </div>
                    @endif
                    
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-light">
                            <div class="mb-2">
                                <i class="bx bx-receipt fs-4 text-warning"></i>
                            </div>
                            <h6 class="text-muted mb-1">Total VAT</h6>
                            <h5 class="mb-0 text-warning">TZS {{ number_format($delivery->items->sum('vat_amount'), 2) }}</h5>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="text-center p-3 border rounded bg-success text-white">
                            <div class="mb-2">
                                <i class="bx bx-money fs-4"></i>
                            </div>
                            <h6 class="mb-1">Grand Total</h6>
                            <h4 class="mb-0">{{ number_format($delivery->items->sum('line_total') + $delivery->transport_cost, 2) }}</h4>
                        </div>
                    </div>
                </div>
                
                @if($delivery->has_transport_cost && $delivery->transport_cost > 0)
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Transport Cost Included:</strong> This delivery includes transport charges of TZS {{ number_format($delivery->transport_cost, 2) }}.
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if($delivery->notes || $delivery->delivery_instructions)
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-note me-2"></i>Notes & Instructions</h6>
            </div>
            <div class="card-body">
                @if($delivery->delivery_instructions)
                <div class="mb-3">
                    <h6>Delivery Instructions:</h6>
                    <p class="mb-0">{{ $delivery->delivery_instructions }}</p>
                </div>
                @endif
                @if($delivery->notes)
                <div>
                    <h6>Notes:</h6>
                    <p class="mb-0">{{ $delivery->notes }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Related Sales Order -->
        @if($delivery->salesOrder)
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-cart me-2"></i>Related Sales Order</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Order Number:</strong> {{ $delivery->salesOrder->order_number }}</p>
                        <p><strong>Order Date:</strong> {{ $delivery->salesOrder->order_date->format('M d, Y') }}</p>
                        @if($delivery->salesOrder->proforma)
                        <p class="mb-0"><strong>From Proforma:</strong>
                            <a href="{{ route('sales.proformas.show', \Vinkla\Hashids\Facades\Hashids::encode($delivery->salesOrder->proforma->id)) }}" class="text-primary fw-semibold">
                                {{ $delivery->salesOrder->proforma->proforma_number }}
                            </a>
                        </p>
                        @endif
                        <p><strong>Expected Delivery:</strong> {{ $delivery->salesOrder->expected_delivery_date->format('M d, Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Order Status:</strong> {!! $delivery->salesOrder->status_badge !!}</p>
                        <p><strong>Total Amount:</strong> TZS {{ number_format($delivery->salesOrder->total_amount, 2) }}</p>
                        <a href="{{ route('sales.orders.show', Hashids::encode($delivery->salesOrder->id)) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-show me-1"></i>View Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
// Start Picking
function startPicking(deliveryId) {
    Swal.fire({
        title: 'Start Picking?',
        text: 'This will start the picking process for this delivery.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, start picking!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("sales.deliveries.start-picking", ":id") }}'.replace(':id', deliveryId),
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'An error occurred while starting picking.', 'error');
                }
            });
        }
    });
}

// Complete Picking
function completePicking(deliveryId) {
    Swal.fire({
        title: 'Complete Picking?',
        text: 'This will mark all items as picked and move to packing.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, complete picking!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("sales.deliveries.complete-picking", ":id") }}'.replace(':id', deliveryId),
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    // Mark as handled to prevent global error handler
                    xhr.errorHandled = true;
                    Swal.fire('Error', 'An error occurred while completing picking.', 'error');
                }
            });
        }
    });
}

// Pick all remaining items
function pickAll(deliveryId) {
    $.ajax({
        url: '{{ route("sales.deliveries.pick-all", ":id") }}'.replace(':id', deliveryId),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) { 
            xhr.errorHandled = true;
            Swal.fire('Error', 'An error occurred while picking all items.', 'error'); 
        }
    });
}

// Pack all remaining items
function packAll(deliveryId) {
    $.ajax({
        url: '{{ route("sales.deliveries.pack-all", ":id") }}'.replace(':id', deliveryId),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) { 
            xhr.errorHandled = true;
            Swal.fire('Error', 'An error occurred while packing all items.', 'error'); 
        }
    });
}

// Pack a single item
function packItem(itemId) {
    $.ajax({
        url: '{{ route("sales.deliveries.items.pack", ":id") }}'.replace(':id', itemId),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() { Swal.fire('Error', 'An error occurred while packing the item.', 'error'); }
    });
}

// Deliver all remaining items
function deliverAll(deliveryId) {
    $.ajax({
        url: '{{ route("sales.deliveries.deliver-all", ":id") }}'.replace(':id', deliveryId),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() { Swal.fire('Error', 'An error occurred while delivering all items.', 'error'); }
    });
}

// Deliver a single item
function deliverItem(itemId) {
    $.ajax({
        url: '{{ route("sales.deliveries.items.deliver", ":id") }}'.replace(':id', itemId),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() { Swal.fire('Error', 'An error occurred while delivering the item.', 'error'); }
    });
}
// Pick a single item
function pickItem(itemId) {
    $.ajax({
        url: '{{ route("sales.deliveries.items.pick", ":id") }}'.replace(':id', itemId),
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                Swal.fire('Success', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'An error occurred while picking the item.', 'error');
        }
    });
}

// Start Delivery
function startDelivery(deliveryId) {
    Swal.fire({
        title: 'Start Delivery?',
        text: 'This will start the delivery process.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, start delivery!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("sales.deliveries.start-delivery", ":id") }}'.replace(':id', deliveryId),
                type: 'PATCH',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'An error occurred while starting delivery.', 'error');
                }
            });
        }
    });
}

// Complete Delivery
function completeDelivery(deliveryId) {
    Swal.fire({
        title: 'Complete Delivery?',
        text: 'This will mark the delivery as completed and update stock.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, complete delivery!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Received By',
                input: 'text',
                inputLabel: 'Name of person who received the delivery',
                inputPlaceholder: 'Enter recipient name',
                showCancelButton: true,
                confirmButtonText: 'Complete Delivery',
                showLoaderOnConfirm: true,
                preConfirm: (receivedByName) => {
                    return $.ajax({
                        url: '{{ route("sales.deliveries.complete-delivery", ":id") }}'.replace(':id', deliveryId),
                        type: 'PATCH',
                        data: {
                            _token: '{{ csrf_token() }}',
                            received_by_name: receivedByName
                        }
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value.success) {
                        let message = result.value.message;
                        if (result.value.invoice_number) {
                            message += '\n\nInvoice Number: ' + result.value.invoice_number;
                            message += '\nThe invoice has been created automatically.';
                        }
                        Swal.fire('Success', message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', result.value.message, 'error');
                    }
                }
            });
        }
    });
}

// Generate Delivery Note
function generateDeliveryNote(deliveryId) {
    $.ajax({
        url: '{{ route("sales.deliveries.generate-note", ":id") }}'.replace(':id', deliveryId),
        type: 'GET',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Delivery Note',
                    html: '<pre style="text-align: left; white-space: pre-wrap;">' + response.delivery_note + '</pre>',
                    width: '800px',
                    confirmButtonText: 'Close'
                });
            } else {
                Swal.fire('Error', 'Failed to generate delivery note.', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'An error occurred while generating the delivery note.', 'error');
        }
    });
}

// Print Delivery Note
function printDeliveryNote(deliveryId) {
    // Open the delivery note in a new window
    const printWindow = window.open('{{ route("sales.deliveries.note", ":id") }}'.replace(':id', deliveryId), '_blank', 'width=800,height=600');
    
    // Wait for the window to load, then trigger print
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.print();
            // Close the window after printing (optional)
            // printWindow.close();
        }, 500);
    };
}

</script>
@endpush
@endsection 