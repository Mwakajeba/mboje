@extends('layouts.main')

@section('title', 'Opening Balances')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Inventory', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Opening Balances', 'url' => '#', 'icon' => 'bx bx-layer-plus']
        ]" />

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Opening Balances</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#obImportModal">
                            <i class="bx bx-upload me-1"></i> Import CSV
                        </button>
                        <a href="{{ route('inventory.opening-balances.create') }}" class="btn btn-warning">
                            <i class="bx bx-plus-circle me-1"></i> New Opening Balance
                        </a>
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table id="openingBalancesTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Code</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Total Cost</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <!-- Import Modal (inline to ensure it's in DOM) -->
                <div class="modal fade" id="obImportModal" tabindex="-1" aria-labelledby="obImportModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="obImportModalLabel"><i class="bx bx-upload me-2"></i>Import Opening Balance (CSV)</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="obImportForm" action="{{ route('inventory.opening-balances.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">CSV File <span class="text-danger">*</span></label>
                                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                                            <small class="text-muted">Required columns: item_code, quantity, unit_cost</small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Movement Date</label>
                                            <input type="date" name="movement_date" class="form-control" value="{{ date('Y-m-d') }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Reference</label>
                                            <input type="text" name="reference" class="form-control" placeholder="e.g. OB-{{ date('Ymd') }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer d-flex justify-content-between align-items-center w-100">
                                    <a href="{{ route('inventory.opening-balances.template') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-download me-1"></i> Download Sample
                                    </a>
                                    <div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary ob-import-submit">
                                                <span class="ob-import-default"><i class="bx bx-upload me-1"></i> Import</span>
                                                <span class="ob-import-processing d-none"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...</span>
                                            </button>
                                        </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(function() {
        $('#openingBalancesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('inventory.opening-balances.index') }}",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            },
            columns: [{
                    data: 'opened_at',
                    name: 'opened_at'
                },
                {
                    data: 'item_name',
                    name: 'item.name',
                    orderable: false
                },
                {
                    data: 'item_code',
                    name: 'item.code',
                    orderable: false
                },
                {
                    data: 'category',
                    name: 'item.category.name',
                    orderable: false
                },
                {
                    data: 'location_name',
                    name: 'location.name',
                    orderable: false
                },
                {
                    data: 'quantity',
                    name: 'quantity'
                },
                {
                    data: 'unit_cost',
                    name: 'unit_cost'
                },
                {
                    data: 'total_cost',
                    name: 'total_cost'
                }
            ],
            order: [
                [0, 'desc']
            ],
            pageLength: 25
        });

        // Import form processing state
        const $form = $('#obImportForm');
        $form.on('submit', function() {
            // Disable inputs except CSRF token so it is submitted
            const $btns = $form.find('.ob-import-submit');
            $btns.prop('disabled', true);
            // Keep file input enabled so the browser includes it in the multipart request
            $form.find('input:not([name="_token"]):not([type="file"]),select,button').prop('disabled', true);
            $btns.find('.ob-import-default').addClass('d-none');
            $btns.find('.ob-import-processing').removeClass('d-none');
        });
    });
</script>
@endpush
