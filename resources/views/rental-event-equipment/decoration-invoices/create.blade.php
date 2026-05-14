@extends('layouts.main')

@section('title', 'New Decoration Service Invoice')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Rental & Event Equipment', 'url' => route('rental-event-equipment.index'), 'icon' => 'bx bx-package'],
                ['label' => 'Decoration Service Invoices', 'url' => route('rental-event-equipment.decoration-invoices.index'), 'icon' => 'bx bx-receipt'],
                ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus'],
            ]" />

            <h6 class="mb-0 text-uppercase">CREATE DECORATION SERVICE INVOICE</h6>
            <hr />

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>New Service Invoice</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('rental-event-equipment.decoration-invoices.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-5">
                                <div class="mb-3">
                                    <label for="decoration_job_id" class="form-label">Decoration Job</label>
                                    <select id="decoration_job_id" name="decoration_job_id"
                                            class="form-select select2-single @error('decoration_job_id') is-invalid @enderror">
                                        <option value="">Optional – not linked to a job</option>
                                        @foreach($jobs as $job)
                                            <option value="{{ $job->id }}"
                                                {{ old('decoration_job_id', optional($selectedJob)->id) == $job->id ? 'selected' : '' }}>
                                                {{ $job->job_number }} - {{ $job->customer->name ?? 'N/A' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('decoration_job_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select id="customer_id" name="customer_id"
                                            class="form-select select2-single @error('customer_id') is-invalid @enderror" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ old('customer_id', optional($selectedJob)->customer_id ?? null) == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} - {{ $customer->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" id="invoice_date" name="invoice_date"
                                           class="form-control @error('invoice_date') is-invalid @enderror"
                                           value="{{ old('invoice_date', now()->toDateString()) }}" required>
                                    @error('invoice_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" id="due_date" name="due_date"
                                           class="form-control @error('due_date') is-invalid @enderror"
                                           value="{{ old('due_date') }}">
                                    @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="reference" class="form-label">Reference</label>
                                    <input type="text" id="reference" name="reference"
                                           class="form-control @error('reference') is-invalid @enderror"
                                           value="{{ old('reference', optional($selectedJob)->job_number) }}"
                                           placeholder="Optional reference / PO number">
                                    @error('reference')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="service_amount" class="form-label">Service Amount (TZS) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" id="service_amount" name="service_amount"
                                           class="form-control @error('service_amount') is-invalid @enderror"
                                           value="{{ old('service_amount', optional($selectedJob)->agreed_price ?? 0) }}"
                                           required>
                                    @error('service_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="tax_amount" class="form-label">Tax Amount (TZS)</label>
                                    <input type="number" step="0.01" min="0" id="tax_amount" name="tax_amount"
                                           class="form-control @error('tax_amount') is-invalid @enderror"
                                           value="{{ old('tax_amount', 0) }}">
                                    @error('tax_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="service_description" class="form-label">Service Description</label>
                            <textarea id="service_description" name="service_description" rows="4"
                                      class="form-control @error('service_description') is-invalid @enderror"
                                      placeholder="Describe the decoration service being invoiced...">{{ old('service_description', optional($selectedJob)->service_description ?? optional($selectedJob)->package_name) }}</textarea>
                            @error('service_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Internal Notes</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Internal notes (not shown on invoice)...">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info small">
                            <i class="bx bx-info-circle me-1"></i>
                            Decoration service invoices are <strong>service-only</strong>: equipment items are not listed
                            or valued separately on the invoice. Revenue is tracked via the Decoration Service Income
                            account configured in Accounting Settings.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('rental-event-equipment.decoration-invoices.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-info">
                                <i class="bx bx-save me-1"></i>Save Invoice (Draft)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            if ($.fn.select2) {
                $('.select2-single').select2({
                    placeholder: 'Select',
                    allowClear: true,
                    width: '100%',
                    theme: 'bootstrap-5'
                });
            }

            // When job changes, optionally sync customer and reference
            $('#decoration_job_id').on('change', function () {
                const selectedOption = $(this).find('option:selected');
                const jobText = selectedOption.text() || '';
                const jobNumber = jobText.split('-')[0].trim();
                if (jobNumber && !$('#reference').val()) {
                    $('#reference').val(jobNumber);
                }
            });
        });
    </script>
@endpush

