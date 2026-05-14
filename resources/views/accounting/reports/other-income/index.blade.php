@extends('layouts.main')

@section('title', 'Other Income Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting Reports', 'url' => route('reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Other Income Report', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-money me-2"></i>Other Income Report</h5>
                                <small class="text-muted">Generate other income report excluding sales revenue</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" onclick="generateReport()">
                                    <i class="bx bx-refresh me-1"></i> Generate Report
                                </button>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bx bx-download me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">
                                            <i class="bx bx-file-pdf me-2"></i> Export PDF
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">
                                            <i class="bx bx-file me-2"></i> Export Excel
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters Section -->
                        <form id="otherIncomeForm" method="GET" action="{{ route('accounting.reports.other-income') }}">
                            <div class="row">
                                <!-- Start Date -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="{{ $startDate ?? '' }}" required>
                                </div>

                                <!-- End Date -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="{{ $endDate ?? '' }}" required>
                                </div>

                                <!-- Reporting Type -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="reporting_type" class="form-label">Reporting Type</label>
                                    <select class="form-select" id="reporting_type" name="reporting_type" required>
                                        <option value="accrual" {{ ($reportingType ?? 'accrual') === 'accrual' ? 'selected' : '' }}>Accrual Basis</option>
                                        <option value="cash" {{ ($reportingType ?? '') === 'cash' ? 'selected' : '' }}>Cash Basis</option>
                                    </select>
                                </div>

                                <!-- Branch (Assigned) -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <label for="branch_id" class="form-label">Branch</label>
                                    <select class="form-select" id="branch_id" name="branch_id">
                                        @if(($branches->count() ?? 0) > 1)
                                            <option value="all" {{ ($branchId ?? 'all') === 'all' ? 'selected' : '' }}>All Branches</option>
                                        @endif
                                        @foreach($branches ?? [] as $branch)
                                            <option value="{{ $branch->id }}" {{ ($branchId ?? '') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </form>

                        @if(isset($otherIncomeData))
                        <!-- Results -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">OTHER INCOME REPORT</h6>
                                                <small class="text-muted">
                                                    @if($startDate === $endDate)
                                                        As at: {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }} | 
                                                    @else
                                                        Period: {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }} | 
                                                    @endif
                                                    Basis: {{ ucfirst($reportingType) }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if(isset($otherIncomeData) && count($otherIncomeData) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Account Code</th>
                                                            <th>Account Name</th>
                                                            <th>Account Group</th>
                                                            <th class="text-end">Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $totalIncome = 0;
                                                        @endphp
                                                        @foreach($otherIncomeData as $account)
                                                            @php
                                                                $totalIncome += $account['sum'];
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $account['account_code'] }}</td>
                                                                <td>{{ $account['account'] }}</td>
                                                                <td>{{ $account['group_name'] }}</td>
                                                                <td class="text-end">{{ number_format($account['sum'], 2) }}</td>
                                                            </tr>
                                                        @endforeach
                                                        <tr class="table-success">
                                                            <td colspan="3"><strong>Total Other Income</strong></td>
                                                            <td class="text-end"><strong>{{ number_format($totalIncome, 2) }}</strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <i class="bx bx-info-circle fs-1 text-muted"></i>
                                                <p class="mt-2 text-muted">No other income data found for the selected criteria.</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function generateReport() {
    document.getElementById('otherIncomeForm').submit();
}

function exportReport(type) {
    const form = document.getElementById('otherIncomeForm');
    const formData = new FormData(form);
    formData.append('export_type', type);
    
    const url = '{{ route("accounting.reports.other-income.export") }}?' + new URLSearchParams(formData);
    
    // Show loading state
    Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait while we prepare your ' + type.toUpperCase() + ' report.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Download the file
    window.location.href = url;

    // close the loading state after a short delay
    setTimeout(() => {
        Swal.close();
    }, 2000);
}
</script>
@endsection
