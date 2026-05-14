@extends('layouts.main')
@section('title', 'New Contingent Item (IAS 37)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Contingent Items (IAS 37)', 'url' => route('accounting.contingencies.index'), 'icon' => 'bx bx-error'],
                ['label' => 'New Contingent Item', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />
            <a href="{{ route('accounting.contingencies.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>

        <h6 class="mb-0 text-uppercase">NEW CONTINGENT ITEM (DISCLOSURE ONLY)</h6>
        <hr />

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

        <div class="card radius-10 border-0 shadow-sm">
            <form method="POST" action="{{ route('accounting.contingencies.store') }}">
                @csrf
                <div class="card-body">
                    <h5 class="mb-3">Classification & Link (IAS 37)</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="contingency_type" class="form-select select2-single" required data-placeholder="Select type">
                                <option value="liability">Contingent Liability</option>
                                <option value="asset">Contingent Asset</option>
                            </select>
                            <small class="text-muted">Liability: possible obligation/outflow. Asset: possible inflow (do not recognise until virtually certain).</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select select2-single" data-placeholder="Use current branch">
                                <option value="">Use current branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Optional branch for allocating the disclosure to a specific location.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Linked Provision (optional)</label>
                            <select name="provision_id" class="form-select select2-single" data-placeholder="No linked provision">
                                <option value="">No linked provision</option>
                                @foreach($provisions as $provision)
                                    <option value="{{ $provision->id }}">{{ $provision->provision_number }} - {{ $provision->title }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Link to an existing provision if this contingent item may become recognised later.</small>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Nature, Probability & Amount</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                            <small class="text-muted">Short label for the contingent case (e.g. “Legal dispute – Supplier X”).</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Probability of Outcome</label>
                            <select name="probability" class="form-select select2-single" required data-placeholder="Select probability">
                                <option value="remote">Remote</option>
                                <option value="possible">Possible</option>
                                <option value="probable">Probable (&gt; 50%)</option>
                                <option value="virtually_certain">Virtually Certain</option>
                            </select>
                            <small class="text-muted">Used for note disclosure and for judging when a provision or asset recognition becomes appropriate.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                        <small class="text-muted">Summarise the nature of the contingency, key uncertainties, and major assumptions.</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Expected Amount</label>
                            <input type="number" step="0.01" min="0" name="expected_amount" class="form-control">
                            <small class="text-muted">Best estimate for disclosure purposes only (no posting).</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency_code" value="TZS" maxlength="3" class="form-control text-uppercase" required>
                            <small class="text-muted">Three-letter ISO code for the disclosed amount.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">FX Rate at Initial Assessment</label>
                            <input type="number" step="0.000001" min="0.000001" name="fx_rate_at_creation" value="1" class="form-control">
                            <small class="text-muted">FX rate used when the estimate was first prepared (for audit trail).</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Probability (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="probability_percent" class="form-control">
                            <small class="text-muted">Optional numeric probability to enrich note disclosures.</small>
                        </div>
                    </div>

                    <hr>

                    <h5 class="mb-3">Status & Resolution (No GL Impact)</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select select2-single" required>
                                <option value="open">Open</option>
                                <option value="resolved">Resolved</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <small class="text-muted">Track whether the contingency is still outstanding, resolved, or no longer relevant.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Resolution Outcome</label>
                            <select name="resolution_outcome" class="form-select select2-single">
                                <option value="">Not yet resolved / N/A</option>
                                <option value="no_outflow">No outflow/inflow</option>
                                <option value="outflow">Outflow (expense / payment)</option>
                                <option value="inflow">Inflow (gain / recovery)</option>
                                <option value="other">Other</option>
                            </select>
                            <small class="text-muted">For resolved items, indicate the actual outcome for disclosure.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Resolution Date</label>
                            <input type="date" name="resolution_date" class="form-control">
                            <small class="text-muted">Date the contingency was resolved / settled (if applicable).</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Resolution Notes</label>
                        <textarea name="resolution_notes" class="form-control" rows="2"></textarea>
                        <small class="text-muted">High-level summary of how the case was resolved. Still disclosure-only – no GL impact.</small>
                    </div>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save"></i> Save Contingent Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function () {
        if ($.fn.select2) {
            $('.select2-single').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        allowClear: !$(this).prop('required'),
                        placeholder: $(this).data('placeholder') || 'Select...'
                    });
                }
            });
        }
    });
</script>
@endpush


