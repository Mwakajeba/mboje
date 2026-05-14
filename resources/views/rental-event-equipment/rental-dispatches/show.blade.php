@extends('layouts.main')

@section('title', 'View Rental Dispatch')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Rental Dispatches', 'url' => route('rental-event-equipment.rental-dispatches.index'), 'icon' => 'bx bx-send'],
            ['label' => 'View', 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <h6 class="mb-0 text-uppercase">RENTAL DISPATCH DETAILS</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bx-send me-1 font-22 text-info"></i></div>
                                <h5 class="mb-0 text-info">Dispatch #{{ $dispatch->dispatch_number }}</h5>
                            </div>
                            <div>
                                @if($dispatch->status === 'draft')
                                <form method="POST" action="{{ route('rental-event-equipment.rental-dispatches.confirm', $dispatch) }}" class="d-inline" id="confirm-dispatch-form">
                                    @csrf
                                    <button type="button" class="btn btn-success btn-sm" onclick="confirmDispatch()">
                                        <i class="bx bx-check me-1"></i> Confirm Dispatch
                                    </button>
                                </form>
                                @endif
                                <a href="{{ route('rental-event-equipment.rental-dispatches.index') }}" class="btn btn-secondary btn-sm">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <hr />

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Customer</label>
                                    <p class="form-control-plaintext">{{ $dispatch->customer->name ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contract</label>
                                    <p class="form-control-plaintext">
                                        <a href="{{ route('rental-event-equipment.contracts.show', $dispatch->contract) }}">
                                            {{ $dispatch->contract->contract_number }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Dispatch Date</label>
                                    <p class="form-control-plaintext">{{ $dispatch->dispatch_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            @if($dispatch->expected_return_date)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Expected Return Date</label>
                                    <p class="form-control-plaintext">{{ $dispatch->expected_return_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            @endif
                            @if($dispatch->event_date)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Date</label>
                                    <p class="form-control-plaintext">{{ $dispatch->event_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                            @endif
                            @if($dispatch->event_location)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Event Location</label>
                                    <p class="form-control-plaintext">{{ $dispatch->event_location }}</p>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Status</label>
                                    <p class="form-control-plaintext">
                                        @php
                                            $statusBadge = match($dispatch->status) {
                                                'draft' => 'secondary',
                                                'dispatched' => 'success',
                                                'returned' => 'info',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($dispatch->status) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Equipment Items -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-package me-2"></i> Dispatched Equipment</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Equipment</th>
                                                <th>Category</th>
                                                <th class="text-end">Quantity</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($dispatch->items as $item)
                                            <tr>
                                                <td>{{ $item->equipment->name ?? 'N/A' }}</td>
                                                <td>{{ $item->equipment->category->name ?? 'N/A' }}</td>
                                                <td class="text-end">{{ $item->quantity }}</td>
                                                <td>{{ $item->notes ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        @if($dispatch->notes)
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="bx bx-note me-2"></i> Notes</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">{{ $dispatch->notes }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDispatch() {
    Swal.fire({
        title: 'Confirm Dispatch?',
        html: '<div class="text-start"><p class="mb-2">This will change equipment status from <strong>Reserved</strong> to <strong>On Rent</strong>.</p><p class="mb-0 text-muted">Are you sure you want to proceed?</p></div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bx bx-check me-1"></i> Yes, Confirm',
        cancelButtonText: '<i class="bx bx-x me-1"></i> Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('confirm-dispatch-form').submit();
        }
    });
}
</script>
@endpush
