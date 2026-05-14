@extends('layouts.main')
@section('title', 'Provision '.$provision->provision_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Provisions (IAS 37)', 'url' => route('accounting.provisions.index'), 'icon' => 'bx bx-shield-quarter'],
                ['label' => $provision->provision_number, 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div>
                @if($provision->canBeEdited())
                    <a href="{{ route('accounting.provisions.edit', $provision->encoded_id) }}" class="btn btn-primary me-2">
                        <i class="bx bx-edit"></i> Edit Provision
                    </a>
                @endif
                <a href="{{ route('accounting.provisions.index') }}" class="btn btn-secondary">
                    Back to List
                </a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 text-uppercase">PROVISION DETAILS (IAS 37)</h6>
            <div>
                @php
                    $status = $provision->status;
                    $badgeClass = match($status) {
                        'draft' => 'bg-secondary',
                        'pending_approval' => 'bg-info',
                        'approved' => 'bg-primary',
                        'active' => 'bg-success',
                        'settled' => 'bg-dark',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
            </div>
        </div>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error') || (isset($errors) && $errors->any()))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                @if(session('error'))
                    {{ session('error') }}
                @else
                    Please fix the following errors:
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mb-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
            @if(in_array($provision->status, ['draft', 'rejected']))
                <form method="POST" action="{{ route('accounting.provisions.submit', $provision->encoded_id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bx bx-send"></i> Submit for Approval
                    </button>
                </form>
            @elseif($provision->status === 'pending_approval')
                <form method="POST" action="{{ route('accounting.provisions.approve', $provision->encoded_id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bx bx-check"></i> Approve
                    </button>
                </form>
                <button class="btn btn-sm btn-danger" type="button" data-bs-toggle="collapse" data-bs-target="#rejectReasonCollapse">
                    <i class="bx bx-x"></i> Reject
                </button>
                <div class="collapse mt-2" id="rejectReasonCollapse">
                    <form method="POST" action="{{ route('accounting.provisions.reject', $provision->encoded_id) }}">
                        @csrf
                        <div class="input-group input-group-sm">
                            <input type="text" name="reason" class="form-control" placeholder="Rejection reason" required>
                            <button type="submit" class="btn btn-danger">
                                Confirm Reject
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if(isset($approvalSummary) && $approvalSummary['current_level'])
                <div class="card border-info mb-0 flex-grow-1" style="max-width: 420px;">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-info">
                                <i class="bx bx-check-shield me-1"></i>
                                Approval – Level {{ $approvalSummary['current_level']['level'] }} ({{ $approvalSummary['current_level']['name'] }})
                            </strong>
                        </div>
                        @if($approvalSummary['approvers']->isNotEmpty())
                            <small class="text-muted d-block">
                                Current approvers:
                                {{ $approvalSummary['approvers']->pluck('name')->join(', ') }}
                            </small>
                        @else
                            <small class="text-muted d-block">
                                No approvers assigned for this level. Check Provision Approval Settings.
                            </small>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">
                    Core Details
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Title</dt>
                        <dd class="col-sm-8">{{ $provision->title }}</dd>

                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $provision->provision_type)) }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8"><span class="badge bg-secondary">{{ ucfirst($provision->status) }}</span></dd>

                        <dt class="col-sm-4">Probability</dt>
                        <dd class="col-sm-8">
                            {{ ucfirst($provision->probability) }}
                            @if($provision->probability_percent)
                                ({{ number_format($provision->probability_percent, 2) }}%)
                            @endif
                        </dd>

                        <dt class="col-sm-4">Present Obligation</dt>
                        <dd class="col-sm-8">
                            {{ $provision->has_present_obligation ? 'Yes' : 'No' }}
                            @if($provision->present_obligation_type)
                                ({{ ucfirst($provision->present_obligation_type) }})
                            @endif
                        </dd>

                        <dt class="col-sm-4">Estimate Method</dt>
                        <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $provision->estimate_method)) }}</dd>

                        <dt class="col-sm-4">Expected Settlement</dt>
                        <dd class="col-sm-8">
                            {{ optional($provision->expected_settlement_date)->format('M d, Y') ?? 'N/A' }}
                        </dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8">{{ $provision->description }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header">
                    Measurement & Accounts
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Original Estimate</dt>
                        <dd class="col-sm-7">
                            {{ number_format($provision->original_estimate, 2) }} {{ $provision->currency_code }}
                        </dd>

                        <dt class="col-sm-5">Current Balance</dt>
                        <dd class="col-sm-7">
                            {{ number_format($provision->current_balance, 2) }} {{ $provision->currency_code }}
                        </dd>

                        <dt class="col-sm-5">Utilised</dt>
                        <dd class="col-sm-7">
                            {{ number_format($provision->utilised_amount, 2) }} {{ $provision->currency_code }}
                        </dd>

                        <dt class="col-sm-5">Reversed</dt>
                        <dd class="col-sm-7">
                            {{ number_format($provision->reversed_amount, 2) }} {{ $provision->currency_code }}
                        </dd>

                        <dt class="col-sm-5">Discounting</dt>
                        <dd class="col-sm-7">
                            @if($provision->is_discounted)
                                Yes ({{ number_format($provision->discount_rate, 2) }}%)
                            @else
                                No
                            @endif
                        </dd>

                        <dt class="col-sm-5">Expense Account</dt>
                        <dd class="col-sm-7">
                            {{ optional($provision->expenseAccount)->account_code }}
                            - {{ optional($provision->expenseAccount)->account_name }}
                        </dd>

                        <dt class="col-sm-5">Provision Account</dt>
                        <dd class="col-sm-7">
                            {{ optional($provision->provisionAccount)->account_code }}
                            - {{ optional($provision->provisionAccount)->account_name }}
                        </dd>

                        <dt class="col-sm-5">Unwinding Account</dt>
                        <dd class="col-sm-7">
                            @if($provision->unwindingAccount)
                                {{ $provision->unwindingAccount->account_code }} - {{ $provision->unwindingAccount->account_name }}
                            @else
                                N/A
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header">
                    Remeasurement & Discount Unwinding
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="mb-1">Remeasure Provision (New Best Estimate)</h6>
                        <small class="text-muted d-block mb-2">
                            Use this when the best estimate changes at a reporting date. The system will post Dr/Cr between expense and the provision account as required by IAS 37 and record a movement.
                        </small>
                        <form action="{{ route('accounting.provisions.remeasure', $provision->encoded_id) }}" method="POST" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">New Estimate (Home)</label>
                                <input type="number" step="0.01" min="0" name="new_home_estimate" class="form-control" value="{{ $provision->current_balance }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Movement Date</label>
                                <input type="date" name="movement_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Narration</label>
                                <input type="text" name="description" class="form-control" placeholder="e.g. Remeasurement at year-end" value="Remeasurement at reporting date">
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-refresh"></i> Post Remeasurement
                                </button>
                            </div>
                        </form>
                    </div>
                    @if($provision->is_discounted && $provision->unwinding_account_id)
                    <hr>
                    <div class="mb-0">
                        <h6 class="mb-1">Unwind Discount (Finance Cost)</h6>
                        <small class="text-muted d-block mb-2">
                            Use this to recognise the unwinding of discount: Dr Finance Cost / Cr Provision. Typically done at each reporting date for discounted provisions.
                        </small>
                        <form action="{{ route('accounting.provisions.unwind', $provision->encoded_id) }}" method="POST" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">Unwind Amount (Home)</label>
                                <input type="number" step="0.01" min="0.01" name="unwind_amount" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Movement Date</label>
                                <input type="date" name="movement_date" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Narration</label>
                                <input type="text" name="description" class="form-control" placeholder="e.g. Unwinding of discount for period">
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bx bx-time"></i> Post Unwinding
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            </div>
        </div>

        <div class="card radius-10 border-0 shadow-sm">
        <div class="card-header">
            Movements (Recognition, Remeasurement, Utilisation, Unwinding)
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Home Amount</th>
                        <th class="text-end">Balance After</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($provision->movements()->orderBy('movement_date')->get() as $movement)
                        <tr>
                            <td>{{ $movement->movement_date->format('Y-m-d') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $movement->movement_type)) }}</td>
                            <td>{{ $movement->description }}</td>
                            <td class="text-end">{{ number_format($movement->home_amount, 2) }} {{ $movement->currency_code }}</td>
                            <td class="text-end">{{ number_format($movement->balance_after_movement, 2) }} {{ $movement->currency_code }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No movements recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

