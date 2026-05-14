@extends('layouts.main')

@section('title', 'Reconciliation Summary Movement')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Bank Reconciliation Reports', 'url' => route('accounting.reports.bank-reconciliation-report.reports-index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Reconciliation Summary Movement', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        
        <h6 class="mb-0 text-uppercase">RECONCILIATION SUMMARY MOVEMENT</h6>
        <hr />

        <!-- Filters -->
        <div class="card radius-10 mb-4">
            <div class="card-header">
                <h6 class="mb-0">Filters</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.reports.bank-reconciliation-report.summary-movement') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Bank Account</label>
                            <select name="bank_account_id" id="bank_account_id" class="form-select select2-single">
                                <option value="">All Bank Accounts</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ $bankAccountId == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }} - {{ $account->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Month</label>
                            <input type="month" name="start_month" class="form-control" value="{{ $startMonth }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Month</label>
                            <input type="month" name="end_month" class="form-control" value="{{ $endMonth }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Button -->
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('accounting.reports.bank-reconciliation-report.summary-movement', array_merge(request()->all(), ['export' => 'pdf'])) }}" class="btn btn-danger">
                <i class="bx bx-download me-2"></i>Export PDF
            </a>
        </div>

        <!-- Report Table -->
        <div class="card radius-10">
            <div class="card-header">
                <h6 class="mb-0">Reconciliation Summary Movement</h6>
                <small class="text-muted">Tracks how uncleared items change month-to-month</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th rowspan="2">Month</th>
                                <th colspan="4" class="text-center bg-success text-white">DNC (Deposits Not Credited)</th>
                                <th colspan="4" class="text-center bg-danger text-white">UPC (Unpresented Cheques)</th>
                            </tr>
                            <tr>
                                <th class="bg-success text-white">Opening Outstanding</th>
                                <th class="bg-success text-white">Cleared This Month</th>
                                <th class="bg-success text-white">New Uncleared</th>
                                <th class="bg-success text-white">Closing Outstanding</th>
                                <th class="bg-danger text-white">Opening Outstanding</th>
                                <th class="bg-danger text-white">Cleared This Month</th>
                                <th class="bg-danger text-white">New Uncleared</th>
                                <th class="bg-danger text-white">Closing Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlyData as $month => $data)
                            <tr>
                                <td class="fw-bold">{{ $data['month'] }}</td>
                                <td class="text-end">{{ number_format($data['dnc']['opening'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($data['dnc']['cleared'], 2) }}</td>
                                <td class="text-end">{{ number_format($data['dnc']['new'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($data['dnc']['closing'], 2) }}</td>
                                <td class="text-end">{{ number_format($data['upc']['opening'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($data['upc']['cleared'], 2) }}</td>
                                <td class="text-end">{{ number_format($data['upc']['new'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($data['upc']['closing'], 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bx bx-info-circle fs-1 mb-2"></i>
                                    <p>No reconciliation data found for the selected period</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($monthlyData) > 0)
                        <tfoot>
                            <tr class="table-secondary">
                                <td class="fw-bold">Totals</td>
                                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('dnc.opening'), 2) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format(collect($monthlyData)->sum('dnc.cleared'), 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('dnc.new'), 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('dnc.closing'), 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('upc.opening'), 2) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format(collect($monthlyData)->sum('upc.cleared'), 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('upc.new'), 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format(collect($monthlyData)->sum('upc.closing'), 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Purpose Note -->
                <div class="alert alert-info mt-3 mb-0">
                    <strong>Purpose:</strong> Monthly control, board reporting, fraud detection
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Initialize Select2 for bank account
    $('#bank_account_id').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Bank Account',
        allowClear: true
    });
});
</script>
@endpush

