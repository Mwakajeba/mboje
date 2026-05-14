@extends('layouts.main')

@section('title', 'Corporate Actions')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Corporate Actions', 'url' => '#', 'icon' => 'bx bx-refresh']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">CORPORATE ACTIONS</h6>
            <a href="{{ route('accounting.share-capital.corporate-actions.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> New Corporate Action
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

        <!-- Corporate Actions Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Corporate Actions</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="corporateActionsTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reference</th>
                                <th>Share Class</th>
                                <th>Action Type</th>
                                <th>Effective Date</th>
                                <th>Ratio</th>
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
        var table = $('#corporateActionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('accounting.share-capital.corporate-actions.index') }}",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'reference_link', name: 'reference_number'},
                {data: 'share_class_name', name: 'shareClass.name'},
                {data: 'action_type_badge', name: 'action_type'},
                {data: 'formatted_effective_date', name: 'effective_date'},
                {data: 'ratio_display', name: 'ratio_numerator'},
                {data: 'status_badge', name: 'status'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            order: [[4, 'desc']],
        });
    });
    
    function executeAction(encodedId) {
        if (confirm('Are you sure you want to execute this corporate action? This action cannot be undone.')) {
            // This will be handled by a modal or separate page
            window.location.href = "{{ url('accounting/share-capital/corporate-actions') }}/" + encodedId + "/execute";
        }
    }
</script>
@endpush

