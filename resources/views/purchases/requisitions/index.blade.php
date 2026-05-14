@extends('layouts.main')

@section('title', 'Purchase Requisitions')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }

    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }

    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }

    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }

    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }

    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .table-light {
        background-color: #f8f9fa;
    }

    .radius-10 {
        border-radius: 10px;
    }

    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Purchase Requisitions', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        {{-- Header card --}}
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mb-3">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0">
                                    <i class="bx bx-file me-2"></i>Purchase Requisitions
                                </h5>
                                <p class="mb-0 text-muted">Manage and track all internal purchase requests</p>
                            </div>
                            <div>
                                <a href="{{ route('purchases.requisitions.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Requisition
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dashboard stats similar to quotations --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Requisitions</p>
                                <h4 class="my-1 text-primary">{{ $stats['total'] ?? 0 }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-file align-middle"></i> All time</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-file"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Draft</p>
                                <h4 class="my-1 text-secondary">{{ $stats['draft'] ?? 0 }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-secondary"><i class="bx bx-edit align-middle"></i> Pending submission</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-secondary text-white ms-auto">
                                <i class="bx bx-edit"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">In Approval</p>
                                <h4 class="my-1 text-info">
                                    {{ $stats['in_approval'] ?? 0 }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-time-five align-middle"></i> Awaiting approval</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-time-five"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Approved</p>
                                <h4 class="my-1 text-success">
                                    {{ $stats['approved'] ?? 0 }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check align-middle"></i> Ready for PO</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dashboard: Pending Approvals Summary --}}
        @if(isset($pendingApprovals) && ($pendingApprovals['total_pending'] ?? 0) > 0)
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-time-five me-2"></i>Pending Approvals Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Document</th>
                                        <th>Requestor/Supplier</th>
                                        <th>Department</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Pending Since</th>
                                        <th>Hours Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingApprovals['requisitions'] ?? [] as $pr)
                                    <tr>
                                        <td>
                                            <strong>{{ $pr['pr_no'] }}</strong>
                                            <br><small class="text-muted">Purchase Requisition</small>
                                        </td>
                                        <td>{{ $pr['requestor'] }}</td>
                                        <td>{{ $pr['department'] }}</td>
                                        <td class="text-end">TZS {{ number_format($pr['total_amount'], 2) }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $pr['status'])) }}</span>
                                            @if($pr['current_level'])
                                                <br><small class="text-muted">Level {{ $pr['current_level'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $pr['submitted_at'] ?? 'N/A' }}</td>
                                        <td>
                                            @if($pr['pending_hours'] > 48)
                                                <span class="badge bg-danger">{{ $pr['pending_hours'] }}h</span>
                                            @elseif($pr['pending_hours'] > 24)
                                                <span class="badge bg-warning">{{ $pr['pending_hours'] }}h</span>
                                            @else
                                                <span class="badge bg-info">{{ $pr['pending_hours'] }}h</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                    @foreach($pendingApprovals['orders'] ?? [] as $po)
                                    <tr>
                                        <td>
                                            <strong>{{ $po['order_number'] }}</strong>
                                            <br><small class="text-muted">Purchase Order</small>
                                        </td>
                                        <td>{{ $po['supplier'] }}</td>
                                        <td>-</td>
                                        <td class="text-end">TZS {{ number_format($po['total_amount'], 2) }}</td>
                                        <td><span class="badge bg-warning">Pending Approval</span></td>
                                        <td>{{ $po['created_at'] }}</td>
                                        <td>
                                            @if($po['pending_hours'] > 48)
                                                <span class="badge bg-danger">{{ $po['pending_hours'] }}h</span>
                                            @elseif($po['pending_hours'] > 24)
                                                <span class="badge bg-warning">{{ $po['pending_hours'] }}h</span>
                                            @else
                                                <span class="badge bg-info">{{ $po['pending_hours'] }}h</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Dashboard: Procurement KPIs --}}
        @if(isset($kpis))
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-bar-chart-alt-2 me-2"></i>Procurement KPIs
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <label class="mb-0 text-muted small">Month:</label>
                            <select id="kpi-month-selector" class="form-select form-select-sm" style="width: auto; min-width: 150px;">
                                @php
                                    $currentMonth = $selectedMonth ?? now()->format('Y-m');
                                    $months = [];
                                    for ($i = 11; $i >= 0; $i--) {
                                        $date = now()->subMonths($i);
                                        $months[] = [
                                            'value' => $date->format('Y-m'),
                                            'label' => $date->format('F Y')
                                        ];
                                    }
                                @endphp
                                @foreach($months as $month)
                                    <option value="{{ $month['value'] }}" @selected($month['value'] == $currentMonth)>
                                        {{ $month['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Avg Approval Time</h6>
                                        <h4 class="text-primary mb-0">{{ number_format($kpis['cycle_times']['pr_to_approval_avg_hours'] ?? 0, 1) }}h</h4>
                                        <small class="text-muted">PR to Approval</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Avg PR to PO Time</h6>
                                        <h4 class="text-success mb-0">{{ number_format($kpis['cycle_times']['pr_to_po_avg_hours'] ?? 0, 1) }}h</h4>
                                        <small class="text-muted">Approval to PO</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Delays (>48h)</h6>
                                        <h4 class="text-info mb-0">{{ ($kpis['delays']['requisitions_pending_over_48h'] ?? 0) + ($kpis['delays']['orders_pending_over_48h'] ?? 0) }}</h4>
                                        <small class="text-muted">Pending Items</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Total Volume</h6>
                                        <h4 class="text-warning mb-0">{{ $kpis['volumes']['total_requisitions'] ?? 0 }}</h4>
                                        <small class="text-muted">Requisitions</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Dashboard: Budget Utilization Chart --}}
        @if(isset($budgetUtilization) && !isset($budgetUtilization['error']))
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-pie-chart-alt-2 me-2"></i>Budget Utilization - {{ $budgetUtilization['budget']['name'] ?? 'Budget ' . ($budgetUtilization['budget']['year'] ?? date('Y')) }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <canvas id="budgetUtilizationChart" height="300"></canvas>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <h6>Summary</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Total Budgeted:</td>
                                            <td class="text-end"><strong>TZS {{ number_format($budgetUtilization['summary']['total_budgeted'] ?? 0, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Used:</td>
                                            <td class="text-end text-danger">TZS {{ number_format($budgetUtilization['summary']['total_used'] ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Committed:</td>
                                            <td class="text-end text-warning">TZS {{ number_format($budgetUtilization['summary']['total_committed'] ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Available:</td>
                                            <td class="text-end text-success"><strong>TZS {{ number_format($budgetUtilization['summary']['total_available'] ?? 0, 2) }}</strong></td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td><strong>Utilization:</strong></td>
                                            <td class="text-end"><strong>{{ number_format($budgetUtilization['summary']['overall_utilization_percent'] ?? 0, 1) }}%</strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Dashboard: PR to PO Cycle Metrics --}}
        @if(isset($cycleMetrics))
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-trending-up me-2"></i>PR to PO Cycle Metrics
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <label class="mb-0 text-muted small">Month:</label>
                            <select id="cycle-metrics-month-selector" class="form-select form-select-sm" style="width: auto; min-width: 150px;">
                                @php
                                    $currentMonth = $selectedMonth ?? now()->format('Y-m');
                                    $months = [];
                                    for ($i = 11; $i >= 0; $i--) {
                                        $date = now()->subMonths($i);
                                        $months[] = [
                                            'value' => $date->format('Y-m'),
                                            'label' => $date->format('F Y')
                                        ];
                                    }
                                @endphp
                                @foreach($months as $month)
                                    <option value="{{ $month['value'] }}" @selected($month['value'] == $currentMonth)>
                                        {{ $month['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <div class="text-center p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">Total PRs</h6>
                                    <h4 class="mb-0">{{ $cycleMetrics['total_requisitions'] ?? 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">Approved</h6>
                                    <h4 class="mb-0 text-success">{{ $cycleMetrics['approved_requisitions'] ?? 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-center p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">PO Created</h6>
                                    <h4 class="mb-0 text-primary">{{ $cycleMetrics['po_created'] ?? 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">Avg Approval Time</h6>
                                    <h4 class="mb-0">{{ number_format($cycleMetrics['average_approval_time_hours'] ?? 0, 1) }}h</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <h6 class="text-muted mb-1">Avg PR to PO Time</h6>
                                    <h4 class="mb-0">{{ number_format($cycleMetrics['average_pr_to_po_time_hours'] ?? 0, 1) }}h</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Requisitions table --}}
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bx bx-list-ul me-2"></i>All Purchase Requisitions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="requisitions-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>PR No</th>
                                        <th>Department</th>
                                        <th>Requestor</th>
                                        <th>Required Date</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    // Month selector change handlers
    $('#kpi-month-selector, #cycle-metrics-month-selector').on('change', function() {
        const selectedMonth = $(this).val();
        // Update both selectors to keep them in sync
        $('#kpi-month-selector, #cycle-metrics-month-selector').val(selectedMonth);
        // Reload page with selected month parameter
        const url = new URL(window.location.href);
        url.searchParams.set('month', selectedMonth);
        window.location.href = url.toString();
    });
    // Budget Utilization Chart
    @if(isset($budgetUtilization) && !isset($budgetUtilization['error']))
    const budgetCtx = document.getElementById('budgetUtilizationChart');
    if (budgetCtx && typeof Chart !== 'undefined') {
        const budgetData = @json($budgetUtilization['utilization'] ?? []);
        const labels = budgetData.map(item => item.account_name).slice(0, 10); // Top 10 accounts
        const used = budgetData.map(item => item.used).slice(0, 10);
        const committed = budgetData.map(item => item.committed).slice(0, 10);
        const available = budgetData.map(item => item.available).slice(0, 10);

        new Chart(budgetCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Used',
                        data: used,
                        backgroundColor: 'rgba(231, 76, 60, 0.8)',
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Committed',
                        data: committed,
                        backgroundColor: 'rgba(241, 196, 15, 0.8)',
                        borderColor: 'rgba(241, 196, 15, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Available',
                        data: available,
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Budget Utilization by Account (Top 10)'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': TZS ' + 
                                    new Intl.NumberFormat('en-US').format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'TZS ' + new Intl.NumberFormat('en-US').format(value);
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    }
    @endif

    // Requisitions DataTable
    $('#requisitions-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: '{{ route('purchases.requisitions.data') }}',
        order: [[0, 'desc']],
        columns: [
            { data: 'pr_no', name: 'pr_no',
              render: function (data, type, row) {
                  return '<strong>' + data + '</strong>';
              }
            },
            { data: 'department', name: 'department' },
            { data: 'requestor', name: 'requestor' },
            { data: 'required_date', name: 'required_date',
              render: function (data) {
                  return data || '';
              }
            },
            { data: 'status', name: 'status',
              render: function (data, type, row) {
                  const map = {
                      draft: 'bg-secondary',
                      submitted: 'bg-info',
                      pending_approval: 'bg-info',
                      in_review: 'bg-info',
                      approved: 'bg-success',
                      po_created: 'bg-success',
                      rejected: 'bg-danger',
                      cancelled: 'bg-warning',
                  };
                  const cls = map[data] || 'bg-secondary';
                  const label = row.status_label || data;
                  return '<span class="badge ' + cls + '">' + label + '</span>';
              }
            },
            { data: 'total_amount', name: 'total_amount',
              className: 'text-end',
              render: function (data) {
                  return 'TZS ' + data;
              }
            },
            { data: null, orderable: false, searchable: false, className: 'text-center',
              render: function (data, type, row) {
                  const canDelete = row.status === 'draft';
                  let actions = `
                    <div class="btn-group" role="group">
                        <a href="${row.show_url}" class="btn btn-sm btn-outline-primary" title="View">
                            <i class="bx bx-show"></i>
                        </a>
                  `;
                  if (canDelete) {
                      actions += `
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-delete-pr"
                                data-id="${row.hash_id}"
                                title="Delete Draft">
                            <i class="bx bx-trash"></i>
                        </button>
                      `;
                  }
                  actions += `</div>`;
                  return actions;
              }
            }
        ],
        language: {
            search: "Search requisitions:",
            lengthMenu: "Show _MENU_ requisitions per page",
            info: "Showing _START_ to _END_ of _TOTAL_ requisitions",
            infoEmpty: "Showing 0 to 0 of 0 requisitions",
            infoFiltered: "(filtered from _MAX_ total requisitions)"
        }
    });

    // Delete draft requisition (SweetAlert)
    $(document).on('click', '.btn-delete-pr', function () {
        const id = $(this).data('id');
        if (!id) return;

        Swal.fire({
            title: 'Delete draft requisition?',
            text: 'Are you sure you want to delete this draft requisition? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '{{ url("purchases/requisitions") }}/' + id,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    _method: 'DELETE'
                },
                success: function (response) {
                    $('#requisitions-table').DataTable().ajax.reload(null, false);
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: response.message || 'Draft requisition deleted successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Failed to delete requisition.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });
                }
            });
        });
    });
});
</script>
@endpush


