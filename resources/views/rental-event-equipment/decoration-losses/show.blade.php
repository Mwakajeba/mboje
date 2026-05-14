@extends('layouts.main')

@section('title', 'Decoration Equipment Loss Details')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Loss Handling', 'url' => route('rental-event-equipment.decoration-losses.index'), 'icon' => 'bx bx-error'],
                ['label' => $loss->loss_number, 'url' => '#', 'icon' => 'bx bx-show'],
            ]" />

            <h6 class="mb-0 text-uppercase">DECORATION EQUIPMENT LOSS DETAILS</h6>
            <hr />

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1 text-danger">
                                <i class="bx bx-error me-2"></i>Loss {{ $loss->loss_number }}
                            </h5>
                            <p class="mb-1 small text-muted">
                                Job: <strong>{{ optional($loss->job)->job_number ?? '-' }}</strong>
                                @if(optional($loss->job)->customer)
                                    – {{ $loss->job->customer->name }}
                                @endif
                            </p>
                            <p class="mb-0 small text-muted">
                                Loss Date:
                                <strong>{{ optional($loss->loss_date)->format('M d, Y') ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="text-end">
                            @php
                                $typeBadge = $loss->loss_type === 'employee' ? 'warning' : 'danger';
                                $typeLabel = $loss->loss_type === 'employee' ? 'Employee Liability' : 'Business Expense';
                                $statusBadge = match($loss->status) {
                                    'draft' => 'secondary',
                                    'confirmed' => 'success',
                                    'cancelled' => 'dark',
                                    default => 'secondary',
                                };
                            @endphp
                            <div class="mb-1">
                                <span class="badge bg-{{ $typeBadge }}">{{ $typeLabel }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-{{ $statusBadge }}">{{ ucfirst($loss->status) }}</span>
                            </div>

                            @if($loss->status === 'draft')
                                <form method="POST"
                                      action="{{ route('rental-event-equipment.decoration-losses.confirm', $loss->getRouteKey()) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Confirm this loss classification? This will mark it as {{ $typeLabel }}.')">
                                        <i class="bx bx-check-circle me-1"></i>Confirm Loss
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <hr />

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-2">Responsibility</h6>
                            <p class="mb-1 small">
                                Loss Type:
                                <strong>{{ $typeLabel }}</strong>
                            </p>
                            <p class="mb-0 small">
                                Responsible Employee:
                                <strong>{{ optional($loss->responsibleEmployee)->name ?? 'N/A' }}</strong>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">Summary</h6>
                            <p class="mb-0 small">
                                Total Quantity Lost:
                                <strong>{{ $loss->quantity_lost }}</strong>
                            </p>
                        </div>
                    </div>

                    @if($loss->reason)
                        <hr />
                        <p class="mb-0 small">
                            <strong>Reason / Explanation:</strong><br>
                            {{ $loss->reason }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Loss Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Equipment</th>
                                <th style="width: 15%;">Code</th>
                                <th style="width: 15%;" class="text-end">Quantity Lost</th>
                                <th style="width: 30%;">Notes</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $items = $loss->items ?? collect();
                            @endphp
                            @if($items->count() > 0)
                                @foreach($items as $item)
                                    <tr>
                                        <td>{{ $item->equipment->name ?? 'N/A' }}</td>
                                        <td class="text-muted small">{{ $item->equipment->equipment_code ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($item->quantity_lost, 0) }}</td>
                                        <td>{{ $item->notes }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>{{ optional($loss->equipment)->name ?? 'N/A' }}</td>
                                    <td class="text-muted small">{{ optional($loss->equipment)->equipment_code }}</td>
                                    <td class="text-end">{{ number_format($loss->quantity_lost, 0) }}</td>
                                    <td></td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

