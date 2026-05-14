@extends('layouts.main')
@section('title', 'Import FX Rates')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'FX Rates', 'url' => route('accounting.fx-rates.index'), 'icon' => 'bx bx-dollar'],
            ['label' => 'Import Rates', 'url' => '#', 'icon' => 'bx bx-import']
        ]" />
        <h6 class="mb-0 text-uppercase">IMPORT FX RATES</h6>
        <hr />

        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-import me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Bulk Import FX Rates</h5>
                                </div>
                                <p class="mb-0 text-muted">Import multiple exchange rates from CSV or Excel file</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('accounting.fx-rates.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to Rates
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Instructions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle me-2"></i>Import Instructions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">File Format Requirements:</h6>
                                <ul>
                                    <li>File format: CSV or Excel (.csv, .xlsx, .xls)</li>
                                    <li>Maximum file size: 10MB</li>
                                    <li>Required columns: <code>rate_date</code>, <code>from_currency</code>, <code>to_currency</code>, <code>spot_rate</code></li>
                                    <li>Optional columns: <code>month_end_rate</code>, <code>average_rate</code>, <code>source</code></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">CSV Format Example:</h6>
                                <pre class="bg-light p-3 rounded"><code>rate_date,from_currency,to_currency,spot_rate,month_end_rate,average_rate,source
2025-12-01,USD,TZS,2500.000000,2500.000000,2500.000000,manual
2025-12-01,EUR,TZS,2700.000000,2700.000000,2700.000000,api
2025-12-01,GBP,TZS,3150.000000,3150.000000,3150.000000,import</code></pre>
                            </div>
                        </div>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Note:</strong> Duplicate rates (same date, from_currency, to_currency) will be updated if they exist, or created if they don't exist.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import Form -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-upload me-2"></i>Upload File</h6>
                    </div>
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        @if(session('import_errors'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <strong>Import Errors:</strong>
                            <ul class="mb-0 mt-2" style="max-height: 300px; overflow-y: auto;">
                                @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <form action="{{ route('accounting.fx-rates.process-import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">Select File <span class="text-danger">*</span></label>
                                        <a href="{{ route('accounting.fx-rates.download-sample') }}" class="btn btn-outline-primary btn-sm">
                                            <i class="bx bx-download me-1"></i> Download Sample CSV
                                        </a>
                                    </div>
                                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                           accept=".csv,.xlsx,.xls" required>
                                    <small class="text-muted">Accepted formats: CSV, Excel (.xlsx, .xls). Max size: 10MB</small>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('accounting.fx-rates.index') }}" class="btn btn-outline-secondary">
                                            <i class="bx bx-x me-1"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="importBtn">
                                            <i class="bx bx-upload me-1"></i> Import Rates
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
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
        $('#importForm').on('submit', function(e) {
            const fileInput = $('input[name="file"]')[0];
            
            if (!fileInput.files || !fileInput.files[0]) {
                e.preventDefault();
                alert('Please select a file to import.');
                return false;
            }

            const file = fileInput.files[0];
            const maxSize = 10 * 1024 * 1024; // 10MB in bytes
            
            if (file.size > maxSize) {
                e.preventDefault();
                alert('File size exceeds 10MB limit. Please select a smaller file.');
                return false;
            }

            // Show loading state
            $('#importBtn').prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i> Importing...');
        });
    });
</script>
@endpush

