@extends('layouts.main')

@section('title','Purchase Requisition Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Purchase Requisition Report', 'url' => '#', 'icon' => 'bx bx-clipboard']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="bx bx-clipboard me-2"></i>Purchase Requisition Report
                            </h4>
                        </div>

                        <!-- Filters -->
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                                    <option value="submitted" {{ request('status')=='submitted'?'selected':'' }}>Submitted</option>
                                    <option value="pending_approval" {{ request('status')=='pending_approval'?'selected':'' }}>Pending Approval</option>
                                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                                    <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Requester</label>
                                <select name="requestor_id" class="form-select">
                                    <option value="">All Requesters</option>
                                    @foreach($requestors as $req)
                                    <option value="{{ $req->id }}" {{ request('requestor_id') == $req->id ? 'selected' : '' }}>{{ $req->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary"><i class="bx bx-filter me-1"></i>Filter</button>
                                <a href="{{ route('purchases.reports.purchase-requisition') }}" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Summary Stats -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card border border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-primary">Total Requisitions</h5>
                                        <h3 class="mb-0">{{ number_format($totalRequisitions, 0) }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Total Requisition Value</h5>
                                        <h3 class="mb-0">{{ number_format($totalRequisitionValue, 2) }} TZS</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Requisition ID</th>
                                        <th>Req Date</th>
                                        <th>Requester</th>
                                        <th>Department</th>
                                        <th>Item Code</th>
                                        <th>Item Description</th>
                                        <th class="text-end">Qty Requested</th>
                                        <th class="text-end">Estimated Unit Cost</th>
                                        <th class="text-end">Estimated Value</th>
                                        <th>Approval Status</th>
                                        <th>Approved By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData as $item)
                                    <tr>
                                        <td><strong>{{ $item['requisition_id'] }}</strong></td>
                                        <td>{{ $item['req_date'] }}</td>
                                        <td>{{ $item['requester'] }}</td>
                                        <td>{{ $item['department'] }}</td>
                                        <td>{{ $item['item_code'] }}</td>
                                        <td>{{ $item['item_description'] }}</td>
                                        <td class="text-end">{{ number_format($item['qty_requested'], 2) }}</td>
                                        <td class="text-end">{{ number_format($item['estimated_unit_cost'], 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($item['estimated_value'], 2) }}</td>
                                        <td>
                                            @if($item['approval_status'] == 'Approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($item['approval_status'] == 'Rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif($item['approval_status'] == 'Pending Approval')
                                                <span class="badge bg-warning">Pending Approval</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ $item['approved_by'] }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">No requisitions found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if($reportData->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="8" class="text-end fw-bold">Total:</td>
                                        <td class="text-end fw-bold">{{ number_format($reportData->sum('estimated_value'), 2) }} TZS</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

