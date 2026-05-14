@extends('layouts.main')

@section('title', 'Supplier Opening Balances')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Opening Balances', 'url' => '#', 'icon' => 'bx bx-book']
        ]" />
        <h6 class="mb-0 text-uppercase">SUPPLIER OPENING BALANCES</h6>
        <hr />

        <div class="d-flex justify-content-between mb-3">
            <div></div>
            <a href="{{ route('purchases.opening-balances.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Add Opening Balance</a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($balances as $index => $b)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $b->supplier->name ?? 'N/A' }}</td>
                                    <td>{{ $b->opening_date->format('M d, Y') }}</td>
                                    <td class="text-end">{{ $b->currency ?? 'TZS' }} {{ number_format($b->amount, 2) }}</td>
                                    <td class="text-end text-success">{{ $b->currency ?? 'TZS' }} {{ number_format($b->paid_amount, 2) }}</td>
                                    <td class="text-end text-primary">{{ $b->currency ?? 'TZS' }} {{ number_format($b->balance_due, 2) }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($b->status) }}</span></td>
                                    <td>
                                        @php $hashid = Vinkla\Hashids\Facades\Hashids::encode($b->id); @endphp
                                        <a href="{{ route('purchases.opening-balances.show', $hashid) }}" class="btn btn-sm btn-outline-info"><i class="bx bx-show"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">No opening balances found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
