@extends('layouts.main')

@section('title', 'Dividends')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Dividends', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">DIVIDENDS</h6>
            <a href="{{ route('accounting.share-capital.dividends.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Declare Dividend
            </a>
        </div>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Dividends Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Dividends</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="dividendsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reference</th>
                                <th>Share Class</th>
                                <th>Type</th>
                                <th>Declaration Date</th>
                                <th>Per Share</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        var table = $('#dividendsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('accounting.share-capital.dividends.index') }}",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'reference_link', name: 'id'},
                {data: 'share_class_name', name: 'shareClass.name'},
                {data: 'dividend_type_badge', name: 'dividend_type'},
                {data: 'formatted_declaration_date', name: 'declaration_date'},
                {data: 'formatted_per_share_amount', name: 'per_share_amount'},
                {data: 'formatted_total_amount', name: 'total_amount'},
                {data: 'status_badge', name: 'status'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            order: [[4, 'desc']],
        });
    });
    
    function declareDividend(encodedId) {
        // This will be handled by a modal or separate page
        window.location.href = "{{ url('accounting/share-capital/dividends') }}/" + encodedId + "/declare";
    }
    
    function processPayment(encodedId) {
        // This will be handled by a modal or separate page
        window.location.href = "{{ url('accounting/share-capital/dividends') }}/" + encodedId + "/process-payment";
    }
</script>
@endpush

