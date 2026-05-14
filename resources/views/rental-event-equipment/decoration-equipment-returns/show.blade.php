@extends('layouts.main')

@section('title', 'Decoration Equipment Return Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Equipment Returns', 'url' => route('rental-event-equipment.decoration-equipment-returns.index'), 'icon' => 'bx bx-undo'],
                ['label' => $return->return_number, 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION EQUIPMENT RETURN DETAILS</h6>
            <hr />

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 text-primary">
                                <i class="bx bx-undo me-2"></i>Return {{ $return->return_number }}
                            </h5>
                            <p class="mb-1 small text-muted">
                                Issue: <strong>{{ optional($return->issue)->issue_number }}</strong> |
                                Job: <strong>{{ optional($return->job)->job_number }}</strong> -
                                {{ optional(optional($return->job)->customer)->name }}
                            </p>
                            <p class="mb-0 small text-muted">
                                Return Date: <strong>{{ optional($return->return_date)->format('M d, Y') }}</strong>
                            </p>
                        </div>
                        <div class="text-end">
                            @php
                                $badgeClass = match ($return->status) {
                                    'draft' => 'secondary',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($return->status) }}</span>
                        </div>
                    </div>

                    @if($return->notes)
                        <hr />
                        <p class="mb-0 small"><strong>Notes:</strong> {{ $return->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Returned Equipment</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Equipment</th>
                                <th>Code</th>
                                <th class="text-end">Quantity</th>
                                <th>Condition</th>
                                <th>Notes</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($return->items as $idx => $item)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ optional($item->equipment)->name ?? 'N/A' }}</td>
                                    <td class="text-muted small">{{ optional($item->equipment)->equipment_code }}</td>
                                    <td class="text-end">{{ $item->quantity_returned }}</td>
                                    <td>
                                        @php
                                            $cond = $item->condition;
                                            $condBadge = match($cond) {
                                                'good' => 'success',
                                                'damaged' => 'warning',
                                                'lost' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $condBadge }}">{{ ucfirst($cond) }}</span>
                                    </td>
                                    <td>{{ $item->condition_notes }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

