@extends('layouts.main')

@section('title', 'Import Opening Balances')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Sales</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('sales.opening-balances.index') }}">Opening Balances</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Import</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Import Opening Balances</h4>
                        <p class="card-subtitle">Upload a CSV file to import multiple opening balances at once</p>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">
                                <i class="bx bx-error"></i>
                                {{ session('error') }}
                            </div>
                        @endif

                        @if(session('import_errors'))
                            <div class="alert alert-warning">
                                <h6><i class="bx bx-error"></i> Import Errors:</h6>
                                <ul class="mb-0">
                                    @foreach(session('import_errors') as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-8">
                                <form action="{{ route('sales.opening-balances.process-import') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    
                                    <div class="mb-3">
                                        <label for="import_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control @error('import_file') is-invalid @enderror" 
                                               id="import_file" name="import_file" accept=".csv,.txt" required>
                                        @error('import_file')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">Maximum file size: 2MB. Supported formats: CSV, TXT</small>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bx bx-upload"></i> Import Opening Balances
                                        </button>
                                        <a href="{{ route('sales.opening-balances.index') }}" class="btn btn-secondary">
                                            <i class="bx bx-arrow-back"></i> Back to List
                                        </a>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bx bx-download"></i> Download Template
                                        </h6>
                                        <p class="card-text">Download the CSV template with sample data to get started.</p>
                                        <a href="{{ route('sales.opening-balances.download-template') }}" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bx bx-download"></i> Download Template
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row">
                            <div class="col-12">
                                <h5><i class="bx bx-info-circle"></i> Import Instructions</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Required Fields:</h6>
                                        <ul>
                                            <li><strong>customer_name</strong> - Exact customer name (must exist in system)</li>
                                            <li><strong>opening_date</strong> - Date in YYYY-MM-DD format</li>
                                            <li><strong>amount</strong> - Opening balance amount (must be greater than 0)</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h6>Optional Fields:</h6>
                                        <ul>
                                            <li><strong>currency</strong> - Currency code (default: TZS)</li>
                                            <li><strong>exchange_rate</strong> - Exchange rate (default: 1.00)</li>
                                            <li><strong>reference</strong> - Reference number</li>
                                            <li><strong>notes</strong> - Additional notes</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3">
                                    <h6><i class="bx bx-bulb"></i> Tips:</h6>
                                    <ul class="mb-0">
                                        <li>Make sure customer names in the CSV exactly match existing customers in the system</li>
                                        <li>Use YYYY-MM-DD format for dates (e.g., 2024-01-01)</li>
                                        <li>Amounts should be positive numbers (e.g., 50000.00)</li>
                                        <li>Empty rows will be skipped automatically</li>
                                        <li>If a customer is not found, that row will be skipped with an error message</li>
                                    </ul>
                                </div>

                                <h6 class="mt-4">CSV Template Features:</h6>
                                <div class="alert alert-info">
                                    <h6><i class="bx bx-info-circle"></i> Template Includes All Customers:</h6>
                                    <ul class="mb-0">
                                        <li>The downloaded template includes <strong>all your existing customers</strong> with sample data</li>
                                        <li>You can <strong>delete rows</strong> for customers you don't want to create opening balances for</li>
                                        <li>You can <strong>modify the amounts, dates, and other details</strong> as needed</li>
                                        <li>You can <strong>add new customers</strong> by adding new rows (make sure customer names match exactly)</li>
                                    </ul>
                                </div>

                                <h6>CSV Format Example:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>customer_name</th>
                                                <th>opening_date</th>
                                                <th>amount</th>
                                                <th>currency</th>
                                                <th>exchange_rate</th>
                                                <th>reference</th>
                                                <th>notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>John Doe</td>
                                                <td>2024-01-01</td>
                                                <td>50000.00</td>
                                                <td>TZS</td>
                                                <td>1.00</td>
                                                <td>OB-2024-001</td>
                                                <td>Opening balance - John Doe</td>
                                            </tr>
                                            <tr>
                                                <td>Jane Smith</td>
                                                <td>2024-01-15</td>
                                                <td>75000.00</td>
                                                <td>TZS</td>
                                                <td>1.00</td>
                                                <td>OB-2024-002</td>
                                                <td>Initial customer balance - Jane Smith</td>
                                            </tr>
                                            <tr>
                                                <td>Anselim Balthazar</td>
                                                <td>2024-02-01</td>
                                                <td>100000.00</td>
                                                <td>TZS</td>
                                                <td>1.00</td>
                                                <td>OB-2024-003</td>
                                                <td>Starting balance - Anselim Balthazar</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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
$(document).ready(function() {
    // File input validation
    $('#import_file').change(function() {
        const file = this.files[0];
        if (file) {
            const fileSize = file.size / 1024 / 1024; // Convert to MB
            if (fileSize > 2) {
                alert('File size must be less than 2MB');
                this.value = '';
                return;
            }
            
            const fileName = file.name.toLowerCase();
            if (!fileName.endsWith('.csv') && !fileName.endsWith('.txt')) {
                alert('Please select a CSV or TXT file');
                this.value = '';
                return;
            }
        }
    });

    // Form submission with loading state
    $('form').submit(function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Importing...');
    });
});
</script>
@endpush
