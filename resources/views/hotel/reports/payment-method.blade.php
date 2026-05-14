@extends('layouts.main')

@section('title', 'Payment Method Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Payment Method Report', 'url' => '#', 'icon' => 'bx bx-credit-card']
        ]" />

        <h6 class="mb-0 text-uppercase">PAYMENT METHOD REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.payment-method') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
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
                    <form method="POST" action="{{ route('hotel.reports.payment-method.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from', $dateFrom) }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to', $dateTo) }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4>TZS {{ number_format($cashTotal, 2) }}</h4>
                        <p class="mb-0">Cash Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4>TZS {{ number_format($mobileMoneyTotal, 2) }}</h4>
                        <p class="mb-0">Mobile Money Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>TZS {{ number_format($cardTotal, 2) }}</h4>
                        <p class="mb-0">Card Total</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4>TZS {{ number_format($grandTotal, 2) }}</h4>
                        <p class="mb-0">Grand Total</p>
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
                                <th>Date</th>
                                <th>Cash Total</th>
                                <th>Mobile Money Total</th>
                                <th>Card Total</th>
                                <th>Bank Transfer</th>
                                <th>Grand Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyData as $data)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($data['date'])->format('M d, Y') }}</td>
                                    <td class="text-end">TZS {{ number_format($data['cash'], 2) }}</td>
                                    <td class="text-end">TZS {{ number_format($data['mobile_money'], 2) }}</td>
                                    <td class="text-end">TZS {{ number_format($data['card'], 2) }}</td>
                                    <td class="text-end">TZS {{ number_format($data['bank_transfer'], 2) }}</td>
                                    <td class="text-end"><strong>TZS {{ number_format($data['total'], 2) }}</strong></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No payment data found for the selected date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-end">TOTAL:</th>
                                <th class="text-end">TZS {{ number_format($cashTotal, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($mobileMoneyTotal, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($cardTotal, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($bankTransferTotal, 2) }}</th>
                                <th class="text-end">TZS {{ number_format($grandTotal, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
