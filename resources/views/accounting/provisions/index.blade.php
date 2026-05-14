@extends('layouts.main')
@section('title', 'Provisions (IAS 37)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Provisions (IAS 37)', 'url' => '#', 'icon' => 'bx bx-shield-quarter']
        ]" />

        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="bx bx-shield-quarter me-2"></i>Provisions (IAS 37)</h5>
                    <p class="mb-0 text-muted">Manage provisions and contingencies in line with IAS 37.</p>
                </div>
                <div>
                    <a href="{{ route('accounting.provisions.disclosure') }}" class="btn btn-outline-info me-2">
                        <i class="bx bx-file"></i> Disclosure Report
                    </a>
                    <a href="{{ route('accounting.provisions.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> New Provision
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
                                <p class="mb-0 text-secondary">Total Provisions</p>
                                <h4 class="my-1 text-primary" id="total-provisions">
                                    {{ number_format($totalProvisions ?? 0) }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-shield-quarter align-middle"></i> All provisions</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-shield-quarter"></i>
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
                                <p class="mb-0 text-secondary">Current Balance</p>
                                <h4 class="my-1 text-success" id="total-current-balance">
                                    {{ number_format($totalCurrentBalance ?? 0, 2) }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-calculator align-middle"></i> Closing liability</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-calculator"></i>
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
                                <p class="mb-0 text-secondary">Utilised</p>
                                <h4 class="my-1 text-info" id="total-utilised">
                                    {{ number_format($totalUtilised ?? 0, 2) }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-info"><i class="bx bx-check-circle align-middle"></i> Applied to settlements</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-info text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
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
                                <p class="mb-0 text-secondary">Reversed</p>
                                <h4 class="my-1 text-warning" id="total-reversed">
                                    {{ number_format($totalReversed ?? 0, 2) }}
                                </h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-undo align-middle"></i> Over-provisions reversed</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-undo"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <div class="card radius-10 border-0 shadow-sm">
            <div class="card-body table-responsive">
                <table id="provisions-table" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                    <thead class="table-light">
                        <tr>
                            <th>Provision #</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Probability</th>
                            <th>Branch</th>
                            <th>Current Balance</th>
                            <th>Utilised</th>
                            <th>Reversed</th>
                            <th>Expected Settlement</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables will populate via AJAX -->
                    </tbody>
                </table>
            </div>
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
        $('#provisions-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("accounting.provisions.index") }}',
                type: 'GET'
            },
            columns: [
                { data: 'provision_number', name: 'provision_number' },
                { data: 'title', name: 'title' },
                { data: 'provision_type_label', name: 'provision_type' },
                { data: 'status_badge', name: 'status' },
                { data: 'probability', name: 'probability' },
                { data: 'branch_name', name: 'branch.name' },
                { data: 'formatted_current_balance', name: 'current_balance' },
                { data: 'formatted_utilised', name: 'utilised_amount' },
                { data: 'formatted_reversed', name: 'reversed_amount' },
                { data: 'formatted_expected_settlement', name: 'expected_settlement_date' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' },
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                emptyTable: 'No provisions found',
                zeroRecords: 'No matching provisions found'
            }
        });
    });
</script>
@endpush

