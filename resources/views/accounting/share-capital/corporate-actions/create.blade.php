@extends('layouts.main')

@section('title', 'Create Corporate Action')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Corporate Actions', 'url' => route('accounting.share-capital.corporate-actions.index'), 'icon' => 'bx bx-refresh'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE CORPORATE ACTION</h6>
        <hr />

        <form action="{{ route('accounting.share-capital.corporate-actions.store') }}" method="POST" id="corporateActionForm">
            @csrf
            
            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-12">
                    <div class="card border-top border-0 border-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle"></i> Corporate Action Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Share Class</label>
                                    <select name="share_class_id" id="share_class_id" class="form-select select2-single @error('share_class_id') is-invalid @enderror">
                                        <option value="">All Share Classes</option>
                                        @foreach($shareClasses as $shareClass)
                                            <option value="{{ $shareClass->id }}" {{ old('share_class_id') == $shareClass->id ? 'selected' : '' }}>
                                                {{ $shareClass->name }} ({{ $shareClass->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Select specific share class or leave blank for action on all classes</small>
                                    @error('share_class_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Action Type <span class="text-danger">*</span></label>
                                    <select name="action_type" id="action_type" class="form-select select2-single @error('action_type') is-invalid @enderror" required>
                                        <option value="split" {{ old('action_type') == 'split' ? 'selected' : '' }}>Share Split</option>
                                        <option value="reverse_split" {{ old('action_type') == 'reverse_split' ? 'selected' : '' }}>Reverse Split</option>
                                        <option value="buyback" {{ old('action_type') == 'buyback' ? 'selected' : '' }}>Share Buyback</option>
                                        <option value="conversion" {{ old('action_type') == 'conversion' ? 'selected' : '' }}>Conversion</option>
                                        <option value="bonus" {{ old('action_type') == 'bonus' ? 'selected' : '' }}>Bonus Issue</option>
                                        <option value="rights" {{ old('action_type') == 'rights' ? 'selected' : '' }}>Rights Issue</option>
                                        <option value="forfeiture" {{ old('action_type') == 'forfeiture' ? 'selected' : '' }}>Forfeiture</option>
                                        <option value="call" {{ old('action_type') == 'call' ? 'selected' : '' }}>Call</option>
                                        <option value="other" {{ old('action_type') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Type of corporate action: Split, Reverse Split, Buyback, Conversion, Bonus, Rights, Forfeiture, Call, or Other</small>
                                    @error('action_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control @error('reference_number') is-invalid @enderror" name="reference_number" value="{{ old('reference_number') }}">
                                    <small class="text-muted d-block mt-1">Optional reference number (e.g., board resolution, contract number)</small>
                                    @error('reference_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Record Date</label>
                                    <input type="date" class="form-control @error('record_date') is-invalid @enderror" name="record_date" value="{{ old('record_date') }}">
                                    <small class="text-muted d-block mt-1">Date for determining eligible shareholders</small>
                                    @error('record_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('effective_date') is-invalid @enderror" name="effective_date" value="{{ old('effective_date', date('Y-m-d')) }}" required>
                                    <small class="text-muted d-block mt-1">Date when the corporate action takes effect</small>
                                    @error('effective_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3" id="ratio_group">
                                    <label class="form-label">Ratio (e.g., 1:5 for split, 5:1 for reverse split)</label>
                                    <div class="row">
                                        <div class="col-5">
                                            <input type="number" step="0.000001" class="form-control @error('ratio_numerator') is-invalid @enderror" name="ratio_numerator" placeholder="Numerator" value="{{ old('ratio_numerator') }}">
                                        </div>
                                        <div class="col-2 text-center">
                                            <span class="form-control-plaintext">:</span>
                                        </div>
                                        <div class="col-5">
                                            <input type="number" step="0.000001" class="form-control @error('ratio_denominator') is-invalid @enderror" name="ratio_denominator" placeholder="Denominator" value="{{ old('ratio_denominator') }}">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Ratio for splits/bonus: 1:5 means 1 share becomes 5 shares (split), 5:1 means 5 shares become 1 (reverse split)</small>
                                    @error('ratio_numerator')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @error('ratio_denominator')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3" id="price_group">
                                    <label class="form-label">Price Per Share</label>
                                    <input type="number" step="0.000001" class="form-control @error('price_per_share') is-invalid @enderror" name="price_per_share" value="{{ old('price_per_share') }}">
                                    <small class="text-muted d-block mt-1">Price per share for buybacks or rights issues</small>
                                    @error('price_per_share')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3" id="buyback_shares_group" style="display: none;">
                                    <label class="form-label">Total Shares to Buyback</label>
                                    <input type="number" class="form-control @error('total_shares') is-invalid @enderror" name="total_shares" value="{{ old('total_shares') }}">
                                    <small class="text-muted d-block mt-1">Total number of shares to be repurchased from shareholders</small>
                                    @error('total_shares')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3" id="buyback_cost_group" style="display: none;">
                                    <label class="form-label">Total Cost</label>
                                    <input type="number" step="0.01" class="form-control @error('total_cost') is-invalid @enderror" name="total_cost" value="{{ old('total_cost') }}">
                                    <small class="text-muted d-block mt-1">Total cost of the share buyback transaction</small>
                                    @error('total_cost')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3">{{ old('notes') }}</textarea>
                                    <small class="text-muted d-block mt-1">Additional details and notes about this corporate action</small>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save"></i> Create Corporate Action
                    </button>
                    <a href="{{ route('accounting.share-capital.corporate-actions.index') }}" class="btn btn-secondary">
                        <i class="bx bx-x"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Show/hide fields based on action type
        function toggleFields() {
            const actionType = $('#action_type').val();
            
            // Show ratio for splits
            if (actionType === 'split' || actionType === 'reverse_split' || actionType === 'bonus' || actionType === 'rights') {
                $('#ratio_group').show();
            } else {
                $('#ratio_group').hide();
            }
            
            // Show price for buybacks and rights
            if (actionType === 'buyback' || actionType === 'rights') {
                $('#price_group').show();
            } else {
                $('#price_group').hide();
            }
            
            // Show buyback specific fields
            if (actionType === 'buyback') {
                $('#buyback_shares_group').show();
                $('#buyback_cost_group').show();
            } else {
                $('#buyback_shares_group').hide();
                $('#buyback_cost_group').hide();
            }
        }
        
        $('#action_type').on('change', toggleFields);
        toggleFields();
    });
</script>
@endpush

