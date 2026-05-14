@extends('layouts.main')

@section('title', 'Share Issues')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Share Issues', 'url' => '#', 'icon' => 'bx bx-trending-up']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">SHARE ISSUES</h6>
            <a href="{{ route('accounting.share-capital.share-issues.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> New Share Issue
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

        <!-- Share Issues Table -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Share Issues</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="shareIssuesTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reference</th>
                                <th>Share Class</th>
                                <th>Type</th>
                                <th>Issue Date</th>
                                <th>Shares</th>
                                <th>Amount</th>
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

<!-- Post to GL Modal -->
<div class="modal fade" id="postToGlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="postToGlForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Post Share Issue to GL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                        <select name="bank_account_id" class="form-select" required>
                            <option value="">Select Bank Account</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Share Capital Account <span class="text-danger">*</span></label>
                        <select name="share_capital_account_id" class="form-select" required>
                            <option value="">Select Account</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Share Premium Account</label>
                        <select name="share_premium_account_id" class="form-select">
                            <option value="">Select Account</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Issue Costs</label>
                        <input type="number" step="0.01" name="issue_costs" class="form-control" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Post to GL</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        var table = $('#shareIssuesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('accounting.share-capital.share-issues.index') }}",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'reference_link', name: 'reference_number'},
                {data: 'share_class_name', name: 'shareClass.name'},
                {data: 'issue_type_badge', name: 'issue_type'},
                {data: 'formatted_issue_date', name: 'issue_date'},
                {data: 'formatted_total_shares', name: 'total_shares'},
                {data: 'formatted_total_amount', name: 'total_amount'},
                {data: 'status_badge', name: 'status'},
                {data: 'actions', name: 'actions', orderable: false, searchable: false},
            ],
            order: [[4, 'desc']],
        });
    });
    
    function postIssue(encodedId) {
        $('#postToGlForm').attr('action', "{{ url('accounting/share-capital/share-issues') }}/" + encodedId + "/post-to-gl");
        $('#postToGlModal').modal('show');
    }
</script>
@endpush

