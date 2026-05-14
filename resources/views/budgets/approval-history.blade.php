@extends('layouts.main')

@section('title', 'Budget Approval History')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => __('app.budgets'), 'url' => route('accounting.budgets.index'), 'icon' => 'bx bx-chart'],
            ['label' => $budget->name, 'url' => route('accounting.budgets.show', $budget), 'icon' => 'bx bx-show'],
            ['label' => 'Approval History', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />
        <h6 class="mb-0 text-uppercase">BUDGET APPROVAL HISTORY</h6>
        <hr />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Budget: {{ $budget->name }}</h5>
                            <a href="{{ route('accounting.budgets.show', $budget) }}" class="btn btn-secondary btn-sm">
                                <i class="bx bx-arrow-back me-1"></i>Back to Budget
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Approval Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h3 class="text-primary">{{ $summary['total_levels'] }}</h3>
                                        <p class="text-muted mb-0">Total Levels</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h3 class="text-success">{{ $summary['completed_levels'] }}</h3>
                                        <p class="text-muted mb-0">Completed Levels</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h3 class="text-info">{{ $summary['current_level']['name'] ?? 'N/A' }}</h3>
                                        <p class="text-muted mb-0">Current Level</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-{{ $summary['status'] === 'approved' ? 'success' : ($summary['status'] === 'rejected' ? 'danger' : 'warning') }}">
                                    <div class="card-body text-center">
                                        <h3 class="text-{{ $summary['status'] === 'approved' ? 'success' : ($summary['status'] === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst(str_replace('_', ' ', $summary['status'])) }}
                                        </h3>
                                        <p class="text-muted mb-0">Status</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Approvers -->
                        @if($currentApprovers->count() > 0)
                        <div class="alert alert-info mb-4">
                            <h6 class="fw-bold mb-2">Current Approvers:</h6>
                            <ul class="mb-0">
                                @foreach($currentApprovers as $approver)
                                <li>{{ $approver->name }} ({{ $approver->email }})</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <!-- Approval History Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date & Time</th>
                                        <th>Level</th>
                                        <th>Action</th>
                                        <th>Approver</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($history as $index => $entry)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $entry->created_at->format('M d, Y H:i:s') }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ $entry->approvalLevel->level_name ?? 'N/A' }}</span>
                                        </td>
                                        <td>{!! $entry->action_badge !!}</td>
                                        <td>{{ $entry->approver->name ?? 'N/A' }}</td>
                                        <td>{{ $entry->comments ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="bx bx-info-circle bx-lg text-muted mb-3"></i>
                                            <h6 class="text-muted">No approval history found</h6>
                                        </td>
                                    </tr>
                                    @endforelse
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

