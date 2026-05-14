@extends('layouts.main')

@section('title', 'Year-end Stock Valuation Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory Reports', 'url' => route('inventory.reports.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Year-end Stock Valuation', 'url' => '#', 'icon' => 'bx bx-file-blank']
        ]" />
        
        <h6 class="mb-0 text-uppercase">YEAR-END STOCK VALUATION REPORT (IPSAS/IFRS COMPLIANT)</h6>
        <hr />

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="{{ route('inventory.reports.year-end-stock-valuation') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="year" class="form-label">Year</label>
                                    <input type="number" class="form-control" id="year" name="year" value="{{ $year }}" min="2020" max="2099">
                                </div>
                                <div class="col-md-3">
                                    <label for="as_of_date" class="form-label">As Of Date</label>
                                    <input type="date" class="form-control" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-search me-1"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ number_format($summary['total_items']) }}</h3>
                        <p class="mb-0">Total Items</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ number_format($summary['total_quantity'], 2) }}</h3>
                        <p class="mb-0">Total Quantity</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h3 class="text-info">{{ number_format($summary['total_value'], 2) }}</h3>
                        <p class="mb-0">Total Value (TZS)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning">{{ number_format($summary['total_variances']) }}</h3>
                        <p class="mb-0">Total Variances</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Stock Valuation as of {{ \Carbon\Carbon::parse($asOfDate)->format('December 31, Y') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($valuation->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item Code</th>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th>Total Quantity</th>
                                            <th>Unit Cost</th>
                                            <th>Total Value (TZS)</th>
                                            <th>Locations</th>
                                            <th>Variance Count</th>
                                            <th>Variance Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($valuation as $item)
                                            <tr>
                                                <td>{{ $item['item']->item_code ?? 'N/A' }}</td>
                                                <td>{{ $item['item']->name ?? 'N/A' }}</td>
                                                <td>{{ $item['item']->category->name ?? 'N/A' }}</td>
                                                <td>{{ number_format($item['total_quantity'], 2) }}</td>
                                                <td>{{ number_format($item['unit_cost'], 2) }}</td>
                                                <td><strong>{{ number_format($item['total_value'], 2) }}</strong></td>
                                                <td>{{ count($item['location_breakdown']) }} location(s)</td>
                                                <td>{{ $item['variance_count'] }}</td>
                                                <td>{{ number_format($item['variance_value'], 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <th colspan="4" class="text-end">TOTAL:</th>
                                            <th>{{ number_format($summary['total_quantity'], 2) }}</th>
                                            <th colspan="2"><strong>{{ number_format($summary['total_value'], 2) }} TZS</strong></th>
                                            <th>{{ number_format($summary['total_variances']) }}</th>
                                            <th>{{ number_format($summary['total_variance_value'], 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>No inventory items found for valuation.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

