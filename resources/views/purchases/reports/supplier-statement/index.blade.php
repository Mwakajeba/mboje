@extends('layouts.main')

@section('title', 'Supplier Statement Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchase Reports', 'url' => route('purchases.reports.index'), 'icon' => 'bx bx-bar-chart'],
            ['label' => 'Supplier Statement', 'url' => '#', 'icon' => 'bx bx-file-text']
        ]" />
        
        <h6 class="mb-0 text-uppercase">SUPPLIER STATEMENT REPORT</h6>
        <hr />

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bx bx-file-text me-2"></i>Generate Supplier Statement</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('purchases.reports.supplier-statement.generate') }}">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select select2-single" required>
                                <option value="">Select supplier</option>
                                @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Choose the supplier for the statement.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From <span class="text-danger">*</span></label>
                            <input type="date" name="date_from" class="form-control" value="{{ date('Y-m-01') }}" required>
                            <small class="text-muted">Start date for the statement.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To <span class="text-danger">*</span></label>
                            <input type="date" name="date_to" class="form-control" value="{{ date('Y-m-d') }}" required>
                            <small class="text-muted">End date for the statement.</small>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('purchases.reports.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Reports
                        </a>
                        <button type="submit" class="btn btn-primary" data-processing-text="Generating...">
                            <i class="bx bx-file-text me-1"></i>Generate Statement
                        </button>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Quick Export Options:</h6>
                        <p class="text-muted">You can also export directly from the form below:</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-danger" onclick="exportPdf()">
                                <i class="bx bx-file-pdf me-1"></i>Export PDF
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="exportExcel()">
                                <i class="bx bx-file me-1"></i>Export Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">About Supplier Statement</h6>
                <p class="text-muted">
                    The Supplier Statement report shows all transactions for a selected supplier within a specified date range, 
                    including purchase invoices, cash purchases, payments, and opening balances. It provides a running balance 
                    to track the supplier's account activity and outstanding amounts.
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Report Includes:</h6>
                        <ul class="list-unstyled">
                            <li><i class="bx bx-check text-success me-2"></i>Opening Balances</li>
                            <li><i class="bx bx-check text-success me-2"></i>Purchase Invoices</li>
                            <li><i class="bx bx-check text-success me-2"></i>Cash Purchases</li>
                            <li><i class="bx bx-check text-success me-2"></i>Payments Made</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Features:</h6>
                        <ul class="list-unstyled">
                            <li><i class="bx bx-check text-success me-2"></i>Running Balance Calculation</li>
                            <li><i class="bx bx-check text-success me-2"></i>Transaction Details</li>
                            <li><i class="bx bx-check text-success me-2"></i>Summary Totals</li>
                            <li><i class="bx bx-check text-success me-2"></i>Date Range Filtering</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        padding-left: 12px;
    }
</style>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function exportPdf() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    // Create a new form for PDF export
    const exportForm = document.createElement('form');
    exportForm.method = 'POST';
    exportForm.action = '{{ route("purchases.reports.supplier-statement.export-pdf") }}';
    exportForm.style.display = 'none';
    
    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    exportForm.appendChild(csrfToken);
    
    // Add form data
    for (let [key, value] of formData.entries()) {
        if (key !== '_token') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            exportForm.appendChild(input);
        }
    }
    
    document.body.appendChild(exportForm);
    exportForm.submit();
    document.body.removeChild(exportForm);
}

function exportExcel() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    // Create a new form for Excel export
    const exportForm = document.createElement('form');
    exportForm.method = 'POST';
    exportForm.action = '{{ route("purchases.reports.supplier-statement.export-excel") }}';
    exportForm.style.display = 'none';
    
    // Add CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    exportForm.appendChild(csrfToken);
    
    // Add form data
    for (let [key, value] of formData.entries()) {
        if (key !== '_token') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            exportForm.appendChild(input);
        }
    }
    
    document.body.appendChild(exportForm);
    exportForm.submit();
    document.body.removeChild(exportForm);
}
</script>
@endpush
