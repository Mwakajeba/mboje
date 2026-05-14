@extends('layouts.main')
@section('title', 'IAS 37 Provisions Disclosure')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Provisions (IAS 37)', 'url' => route('accounting.provisions.index'), 'icon' => 'bx bx-shield-quarter'],
                ['label' => 'Disclosure Report', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div>
                <a href="{{ route('accounting.provisions.disclosure.export-json', ['period_start' => $periodStart, 'period_end' => $periodEnd]) }}" 
                   class="btn btn-outline-primary me-2" target="_blank">
                    <i class="bx bx-download"></i> Export JSON
                </a>
                <a href="{{ route('accounting.provisions.index') }}" class="btn btn-secondary">
                    Back to List
                </a>
            </div>
        </div>

        <h6 class="mb-0 text-uppercase">IAS 37 PROVISIONS DISCLOSURE</h6>
        <hr />

        <!-- Period Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('accounting.provisions.disclosure') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Period Start</label>
                        <input type="date" name="period_start" class="form-control" value="{{ $periodStart }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period End</label>
                        <input type="date" name="period_end" class="form-control" value="{{ $periodEnd }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">
                            <i class="bx bx-search"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Table -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-bar-chart me-2"></i>Total Provisions Summary</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Opening Balance</th>
                                <th>Additions</th>
                                <th>Remeasurements</th>
                                <th>Unwinding</th>
                                <th>Utilisations</th>
                                <th>Reversals</th>
                                <th>Closing Balance</th>
                                <th>Net Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-end">{{ number_format($disclosure['total_provisions']['opening_balance'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($disclosure['total_provisions']['additions'], 2) }}</td>
                                <td class="text-end text-info">{{ number_format($disclosure['total_provisions']['remeasurements'], 2) }}</td>
                                <td class="text-end text-info">{{ number_format($disclosure['total_provisions']['unwinding'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($disclosure['total_provisions']['utilisations'], 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($disclosure['total_provisions']['reversals'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($disclosure['total_provisions']['closing_balance'], 2) }}</td>
                                <td class="text-end {{ $disclosure['total_provisions']['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($disclosure['total_provisions']['net_change'], 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- By Provision Type -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Provisions by Type</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Provision Type</th>
                                <th>Count</th>
                                <th>Opening</th>
                                <th>Additions</th>
                                <th>Remeasurements</th>
                                <th>Unwinding</th>
                                <th>Utilisations</th>
                                <th>Reversals</th>
                                <th>Closing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($disclosure['provisions_by_type'] as $type => $data)
                            <tr>
                                <td><strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong></td>
                                <td class="text-center">{{ $data['count'] }}</td>
                                <td class="text-end">{{ number_format($data['opening_balance'], 2) }}</td>
                                <td class="text-end text-success">{{ number_format($data['additions'], 2) }}</td>
                                <td class="text-end text-info">{{ number_format($data['remeasurements'], 2) }}</td>
                                <td class="text-end text-info">{{ number_format($data['unwinding'], 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($data['utilisations'], 2) }}</td>
                                <td class="text-end text-warning">{{ number_format($data['reversals'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($data['closing_balance'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Contingencies -->
        @if(count($disclosure['contingencies']) > 0)
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Contingent Liabilities & Assets (Disclosure Only)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Nature</th>
                                <th>Type</th>
                                <th>Probability</th>
                                <th>Estimated Effect</th>
                                <th>Uncertainties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($disclosure['contingencies'] as $contingency)
                            <tr>
                                <td>{{ $contingency['nature'] }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($contingency['type']) }}</span></td>
                                <td><span class="badge bg-info">{{ ucfirst($contingency['probability']) }}</span></td>
                                <td class="text-end">{{ $contingency['estimated_effect'] ?? 'N/A' }}</td>
                                <td>{{ $contingency['uncertainties'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

