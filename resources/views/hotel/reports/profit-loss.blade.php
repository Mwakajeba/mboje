@extends('layouts.main')

@section('title', 'Profit & Loss Summary')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Profit & Loss Summary', 'url' => '#', 'icon' => 'bx bx-calculator']
        ]" />

        <h6 class="mb-0 text-uppercase">PROFIT & LOSS SUMMARY</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.profit-loss') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Period</label>
                        <select name="period" class="form-select">
                            <option value="month" {{ $period == 'month' ? 'selected' : '' }}>Month</option>
                            <option value="year" {{ $period == 'year' ? 'selected' : '' }}>Year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            @for($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    @if($period == 'month')
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <select name="month" class="form-select">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('hotel.reports.profit-loss.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="period" value="{{ request('period', $period) }}">
                        <input type="hidden" name="year" value="{{ request('year', $year) }}">
                        <input type="hidden" name="month" value="{{ request('month', $month) }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>TZS {{ number_format($totalRevenue, 2) }}</h3>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>TZS {{ number_format($operatingExpenses, 2) }}</h3>
                        <p class="mb-0">Operating Expenses</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-{{ $netProfit >= 0 ? 'primary' : 'warning' }} text-white">
                    <div class="card-body text-center">
                        <h3>TZS {{ number_format($netProfit, 2) }}</h3>
                        <p class="mb-0">Net Profit</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Total Revenue</th>
                                <th>Operating Expenses</th>
                                <th>Net Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>{{ $periodLabel }}</strong></td>
                                <td class="text-end"><strong>TZS {{ number_format($totalRevenue, 2) }}</strong></td>
                                <td class="text-end"><strong>TZS {{ number_format($operatingExpenses, 2) }}</strong></td>
                                <td class="text-end">
                                    <strong class="text-{{ $netProfit >= 0 ? 'success' : 'danger' }}">
                                        TZS {{ number_format($netProfit, 2) }}
                                    </strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
