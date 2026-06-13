@extends('layouts.main')

@section('title', 'Historia ya Mkopo')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Mikopo ya Wateja', 'url' => route('cash_collaterals.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Mteja', 'url' => route('customers.show', Hashids::encode($cashCollateral->customer_id)), 'icon' => 'bx bx-user'],
            ['label' => 'Historia ya Mkopo', 'url' => '#', 'icon' => 'bx bx-history']
        ]" />

        <!-- Header Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="text-primary mb-2">{{ $cashCollateral->type->name }} - Historia ya Mkopo</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Mteja:</strong> {{ $cashCollateral->customer->name }}</p>
                                        <p class="mb-1"><strong>Aina ya Akaunti:</strong> {{ $cashCollateral->type->name }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Salio la Sasa:</strong>
                                            <span class="badge bg-success fs-6">TSH {{ number_format($calculatedBalance ?? 0, 2) }}</span>
                                        </p>
                                        <p class="mb-1"><strong>Tawi:</strong> {{ $cashCollateral->branch->name ?? '—' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="btn-group" role="group">
                                    @can('deposit cash collateral')
                                    <a href="{{ route('cash_collaterals.deposit', Hashids::encode($cashCollateral->id)) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="bx bx-plus me-1"></i> Toa Mkopo wa Mtaji
                                    </a>
                                    @endcan

                                    @can('withdraw cash collateral')
                                    @if(($calculatedBalance ?? 0) > 0)
                                    <a href="{{ route('cash_collaterals.withdraw', Hashids::encode($cashCollateral->id)) }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="bx bx-minus me-1"></i> Lipa Mkopo kwa Taslim
                                    </a>
                                    @endif
                                    @endcan

                                    <a href="{{ route('cash_collaterals.statement-pdf', Hashids::encode($cashCollateral->id)) }}"
                                        class="btn btn-info btn-sm" target="_blank">
                                        <i class="bx bx-printer me-1"></i> Chapisha Taarifa
                                    </a>

                                    <a href="{{ route('cash_collaterals.index') }}"
                                        class="btn btn-secondary btn-sm">
                                        <i class="bx bx-arrow-back me-1"></i> Rudi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Historia ya Miamala</h5>
                            <a href="{{ route('cash_collaterals.statement-pdf', Hashids::encode($cashCollateral->id)) }}"
                               class="btn btn-primary btn-sm" target="_blank">
                                <i class="bx bx-download me-1"></i> Pakua Taarifa
                            </a>
                        </div>
                        
                        @if($transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="8%">Futa</th>
                                        <th width="12%">Tarehe</th>
                                        <th width="25%">Maelezo</th>
                                        <th width="14%">Aliyeandika</th>
                                        <th width="12%">Mkopo (Ingizo)</th>
                                        <th width="12%">Malipo (Kutoka)</th>
                                        <th width="12%">Salio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr class="{{ !($transaction['deletable'] ?? false) ? 'text-muted opacity-50' : '' }}">
                                        <td class="text-center">
                                            <span>{{ $transaction['row_number'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($transaction['deletable'] ?? false)
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger delete-transaction-btn" 
                                                        data-id="{{ $transaction['delete_id'] }}"
                                                        data-type="{{ $transaction['delete_type'] }}"
                                                        data-amount="{{ $transaction['credit'] > 0 ? $transaction['credit'] : $transaction['debit'] }}"
                                                        data-narration="{{ $transaction['narration'] }}"
                                                        title="Futa Muamala">
                                                    <i class="bx bx-trash" style="font-size: 12px;"></i>
                                                </button>
                                            @else
                                                <span class="text-muted small">Imelindwa</span>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>
                                        <td>{{ $transaction['narration'] }}</td>
                                        <td>{{ $transaction['created_by'] }}</td>
                                        <td class="text-end">
                                            @if($transaction['credit'] > 0)
                                                <span class="text-success fw-bold">
                                                    {{ number_format($transaction['credit'], 2) }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($transaction['debit'] > 0)
                                                <span class="text-danger fw-bold">
                                                    {{ number_format($transaction['debit'], 2) }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold">{{ number_format($transaction['balance'], 2) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5" class="text-end">Jumla:</th>
                                        <th class="text-end">
                                            <span class="text-success fw-bold">
                                                {{ number_format($transactions->sum('credit'), 2) }}
                                            </span>
                                        </th>
                                        <th class="text-end">
                                            <span class="text-danger fw-bold">
                                                {{ number_format($transactions->sum('debit'), 2) }}
                                            </span>
                                        </th>
                                        <th class="text-end">
                                            <span class="badge bg-primary fs-6">
                                                {{ number_format($calculatedBalance ?? 0, 2) }}
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Quick Stats -->
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="text-success">Jumla ya Mikopo wa Mtaji</h6>
                                        <h5 class="text-success mb-0">TSH {{ number_format($transactions->sum('credit'), 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h6 class="text-danger">Jumla ya Malipo</h6>
                                        <h5 class="text-danger mb-0">TSH {{ number_format($transactions->sum('debit'), 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <h6 class="text-info">Jumla ya Miamala</h6>
                                        <h5 class="text-info mb-0">{{ $transactions->count() }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary">Salio la Sasa</h6>
                                        <h5 class="text-primary mb-0">TSH {{ number_format($calculatedBalance ?? 0, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bx bx-history" style="font-size: 4rem; color: #6c757d;"></i>
                            </div>
                            <h5 class="text-muted">Hakuna Historia ya Miamala</h5>
                            <p class="text-muted">Bado hakuna mkopo wa mtaji au malipo kwenye akaunti hii.</p>
                            @can('deposit cash collateral')
                            <a href="{{ route('cash_collaterals.deposit', Hashids::encode($cashCollateral->id)) }}"
                                class="btn btn-success">
                                <i class="bx bx-plus me-1"></i> Toa Mkopo wa Mtaji wa Kwanza
                            </a>
                            @endcan
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('.delete-transaction-btn').on('click', function() {
        const transactionId = $(this).data('id');
        const transactionType = $(this).data('type');
        const amount = $(this).data('amount');
        const narration = $(this).data('narration');
        
        Swal.fire({
            title: 'Futa Muamala?',
            html: `
                <div class="text-start">
                    <p><strong>Aina:</strong> ${transactionType === 'receipt' ? 'Mkopo wa Mtaji' : 'Malipo kwa Taslim'}</p>
                    <p><strong>Kiasi:</strong> TSH ${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                    <p><strong>Maelezo:</strong> ${narration}</p>
                    <p class="text-warning mt-3"><i class="bx bx-warning"></i> Hatua hii haiwezi kutenduliwa!</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ndiyo, futa',
            cancelButtonText: 'Ghairi'
        }).then((result) => {
            if (result.isConfirmed) {
                let deleteUrl;
                if (transactionType === 'receipt') {
                    deleteUrl = `{{ url('cash_collaterals/delete-deposit') }}/${transactionId}`;
                } else if (transactionType === 'payment') {
                    deleteUrl = `{{ url('cash_collaterals/delete-withdrawal') }}/${transactionId}`;
                }
                
                Swal.fire({
                    title: 'Inafuta...',
                    text: 'Tafadhali subiri tunapofuta muamala.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Imefutwa!',
                                text: response.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Kosa!', response.message || 'Imeshindwa kufuta muamala.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Imeshindwa kufuta muamala.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire('Kosa!', errorMessage, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
