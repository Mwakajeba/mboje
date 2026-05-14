@extends('layouts.main')

@section('title', 'Create Share Class')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Share Classes', 'url' => route('accounting.share-capital.share-classes.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE SHARE CLASS</h6>
        <hr />

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body p-5">
                <form action="{{ route('accounting.share-capital.share-classes.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}" required>
                            <small class="text-muted d-block mt-1">Unique identifier code for this share class (e.g., ORD, PREF)</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                            <small class="text-muted d-block mt-1">Full descriptive name of the share class</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                            <small class="text-muted d-block mt-1">Additional details about this share class</small>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Share Type <span class="text-danger">*</span></label>
                            <select class="form-select select2-single @error('share_type') is-invalid @enderror" name="share_type" required>
                                <option value="ordinary" {{ old('share_type') == 'ordinary' ? 'selected' : '' }}>Ordinary</option>
                                <option value="preference" {{ old('share_type') == 'preference' ? 'selected' : '' }}>Preference</option>
                                <option value="other" {{ old('share_type') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <small class="text-muted d-block mt-1">Type of shares: Ordinary (common), Preference (preferred), or Other</small>
                            @error('share_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Voting Rights <span class="text-danger">*</span></label>
                            <select class="form-select select2-single @error('voting_rights') is-invalid @enderror" name="voting_rights" required>
                                <option value="full" {{ old('voting_rights') == 'full' ? 'selected' : '' }}>Full</option>
                                <option value="limited" {{ old('voting_rights') == 'limited' ? 'selected' : '' }}>Limited</option>
                                <option value="none" {{ old('voting_rights') == 'none' ? 'selected' : '' }}>None</option>
                            </select>
                            <small class="text-muted d-block mt-1">Voting rights attached to these shares: Full, Limited, or None</small>
                            @error('voting_rights')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dividend Policy <span class="text-danger">*</span></label>
                            <select class="form-select select2-single @error('dividend_policy') is-invalid @enderror" name="dividend_policy" required>
                                <option value="discretionary" {{ old('dividend_policy') == 'discretionary' ? 'selected' : '' }}>Discretionary</option>
                                <option value="fixed" {{ old('dividend_policy') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                <option value="participating" {{ old('dividend_policy') == 'participating' ? 'selected' : '' }}>Participating</option>
                                <option value="none" {{ old('dividend_policy') == 'none' ? 'selected' : '' }}>None</option>
                            </select>
                            <small class="text-muted d-block mt-1">Dividend payment policy: Discretionary (board decides), Fixed (fixed rate), Participating, or None</small>
                            @error('dividend_policy')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="has_par_value" id="has_par_value" value="1" {{ old('has_par_value', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_par_value">Has Par Value</label>
                            </div>
                            <small class="text-muted d-block mt-1">Check if shares have a nominal/par value</small>
                        </div>
                        
                        <div class="col-md-3 mb-3" id="par_value_group">
                            <label class="form-label">Par Value</label>
                            <input type="number" step="0.000001" class="form-control @error('par_value') is-invalid @enderror" name="par_value" value="{{ old('par_value', 0) }}">
                            <small class="text-muted d-block mt-1">Nominal value per share (e.g., 0.01, 1.00)</small>
                            @error('par_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Currency Code</label>
                            <input type="text" class="form-control @error('currency_code') is-invalid @enderror" name="currency_code" value="{{ old('currency_code', 'USD') }}" maxlength="3">
                            <small class="text-muted d-block mt-1">ISO currency code (e.g., USD, EUR, TZS)</small>
                            @error('currency_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Classification <span class="text-danger">*</span></label>
                            <select class="form-select select2-single @error('classification') is-invalid @enderror" name="classification" required>
                                <option value="equity" {{ old('classification') == 'equity' ? 'selected' : '' }}>Equity</option>
                                <option value="liability" {{ old('classification') == 'liability' ? 'selected' : '' }}>Liability</option>
                                <option value="compound" {{ old('classification') == 'compound' ? 'selected' : '' }}>Compound</option>
                            </select>
                            <small class="text-muted d-block mt-1">IFRS classification: Equity, Liability, or Compound (both)</small>
                            @error('classification')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Authorized Shares</label>
                            <input type="number" class="form-control @error('authorized_shares') is-invalid @enderror" name="authorized_shares" value="{{ old('authorized_shares') }}">
                            <small class="text-muted d-block mt-1">Maximum number of shares authorized to be issued for this class</small>
                            @error('authorized_shares')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Authorized Value</label>
                            <input type="number" step="0.01" class="form-control @error('authorized_value') is-invalid @enderror" name="authorized_value" value="{{ old('authorized_value') }}">
                            <small class="text-muted d-block mt-1">Total authorized capital value for this share class</small>
                            @error('authorized_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Share Features</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="redeemable" id="redeemable" value="1" {{ old('redeemable') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="redeemable">Redeemable</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Can be redeemed/bought back by company</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="convertible" id="convertible" value="1" {{ old('convertible') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="convertible">Convertible</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Can be converted to another share class</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="cumulative" id="cumulative" value="1" {{ old('cumulative') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="cumulative">Cumulative</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Unpaid dividends accumulate</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="participating" id="participating" value="1" {{ old('participating') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="participating">Participating</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">Participates in additional dividends</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Create Share Class
                            </button>
                            <a href="{{ route('accounting.share-capital.share-classes.index') }}" class="btn btn-secondary">
                                <i class="bx bx-x"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#has_par_value').on('change', function() {
            if ($(this).is(':checked')) {
                $('#par_value_group').show();
            } else {
                $('#par_value_group').hide();
            }
        }).trigger('change');
    });
</script>
@endpush
@endsection

