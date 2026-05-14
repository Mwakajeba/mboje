@extends('layouts.main')
@section('title', 'Contingent Liabilities & Assets (IAS 37)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Contingent Items (IAS 37)', 'url' => '#', 'icon' => 'bx bx-error']
        ]" />

        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bx bx-error me-2"></i>Contingent Liabilities & Assets (IAS 37)</h5>
                    <p class="mb-0 text-muted">Disclosure-only items that must not post any journal entries.</p>
                </div>
                <div>
                    <a href="{{ route('accounting.contingencies.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> New Contingent Item
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error') || (isset($errors) && $errors->any()))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                @if(session('error'))
                    {{ session('error') }}
                @else
                    Please fix the following errors:
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Dashboard-style stats cards -->
        <div class="row mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Contingent Items</p>
                                <h4 class="my-1 text-primary">
                                    {{ number_format($totalContingencies ?? 0) }}
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-error"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Liabilities (Expected)</p>
                                <h4 class="my-1 text-warning">
                                    {{ number_format($totalLiabilities ?? 0, 2) }}
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-minus-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Assets (Expected)</p>
                                <h4 class="my-1 text-success">
                                    {{ number_format($totalAssets ?? 0, 2) }}
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-plus-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Open Items</p>
                                <h4 class="my-1 text-info">
                                    {{ number_format($openItems ?? 0) }}
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-body table-responsive">
                <table id="contingencies-table" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Probability</th>
                            <th>Expected Amount</th>
                            <th>Status</th>
                            <th>Resolution Outcome</th>
                            <th>Resolution Date</th>
                            <th>Branch</th>
                            <th>Linked Provision</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }

    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }

    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }

    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function () {
        $('#contingencies-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("accounting.contingencies.index") }}',
                type: 'GET'
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'contingency_number', name: 'contingency_number' },
                { data: 'contingency_type_label', name: 'contingency_type' },
                { data: 'title', name: 'title' },
                { data: 'probability', name: 'probability' },
                { data: 'formatted_expected_amount', name: 'expected_amount' },
                { data: 'status_badge', name: 'status' },
                { data: 'resolution_outcome', name: 'resolution_outcome' },
                { data: 'formatted_resolution_date', name: 'resolution_date' },
                { data: 'branch_name', name: 'branch.name' },
                { data: 'linked_provision', name: 'provision.provision_number' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: 'No contingent items found',
                zeroRecords: 'No matching contingent items found'
            }
        });
    });
</script>
@endpush


