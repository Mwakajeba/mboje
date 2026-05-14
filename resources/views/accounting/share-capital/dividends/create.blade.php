@extends('layouts.main')

@section('title', 'Declare Dividend')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Dividends', 'url' => route('accounting.share-capital.dividends.index'), 'icon' => 'bx bx-money'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">DECLARE DIVIDEND</h6>
        <hr />

        <form action="{{ route('accounting.share-capital.dividends.store') }}" method="POST" id="dividendForm">
            @csrf
            
            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-12">
                    <div class="card border-top border-0 border-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle"></i> Dividend Information</h6>
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
                                    <small class="text-muted d-block mt-1">Select specific share class or leave blank for dividend on all classes</small>
                                    @error('share_class_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Dividend Type <span class="text-danger">*</span></label>
                                    <select name="dividend_type" id="dividend_type" class="form-select select2-single @error('dividend_type') is-invalid @enderror" required>
                                        <option value="cash" {{ old('dividend_type') == 'cash' ? 'selected' : '' }}>Cash Dividend</option>
                                        <option value="bonus" {{ old('dividend_type') == 'bonus' ? 'selected' : '' }}>Bonus/Share Dividend</option>
                                        <option value="scrip" {{ old('dividend_type') == 'scrip' ? 'selected' : '' }}>Scrip Dividend</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Type of dividend: Cash (paid in money), Bonus (paid in shares), or Scrip (deferred cash)</small>
                                    @error('dividend_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Declaration Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('declaration_date') is-invalid @enderror" name="declaration_date" value="{{ old('declaration_date', date('Y-m-d')) }}" required>
                                    <small class="text-muted d-block mt-1">Date when dividend is declared by the board</small>
                                    @error('declaration_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Record Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('record_date') is-invalid @enderror" name="record_date" value="{{ old('record_date', date('Y-m-d')) }}" required>
                                    <small class="text-muted d-block mt-1">Shareholders on this date are eligible to receive the dividend</small>
                                    @error('record_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Ex-Dividend Date</label>
                                    <input type="date" class="form-control @error('ex_date') is-invalid @enderror" name="ex_date" value="{{ old('ex_date') }}">
                                    <small class="text-muted d-block mt-1">Date when shares trade without dividend entitlement</small>
                                    @error('ex_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3" id="payment_date_group">
                                    <label class="form-label">Payment Date</label>
                                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror" name="payment_date" value="{{ old('payment_date') }}">
                                    <small class="text-muted d-block mt-1">Date when dividend payments will be made to shareholders</small>
                                    @error('payment_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Per Share Amount</label>
                                    <input type="number" step="0.000001" class="form-control @error('per_share_amount') is-invalid @enderror" name="per_share_amount" id="per_share_amount" value="{{ old('per_share_amount') }}">
                                    <small class="text-muted d-block mt-1">Dividend amount per share (e.g., 0.50 per share)</small>
                                    @error('per_share_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3" id="withholding_tax_group">
                                    <label class="form-label">Withholding Tax Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control @error('withholding_tax_rate') is-invalid @enderror" name="withholding_tax_rate" value="{{ old('withholding_tax_rate', 0) }}">
                                    <small class="text-muted d-block mt-1">Tax rate to withhold from dividend payments (e.g., 10.00 for 10%)</small>
                                    @error('withholding_tax_rate')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                                    <small class="text-muted d-block mt-1">Additional notes about this dividend declaration</small>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <div class="alert alert-info">
                                        <i class="bx bx-info-circle"></i> 
                                        <strong>Note:</strong> Eligible shareholders will be automatically calculated based on holdings as of the record date.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save"></i> Create Dividend
                    </button>
                    <a href="{{ route('accounting.share-capital.dividends.index') }}" class="btn btn-secondary">
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
        // Show/hide fields based on dividend type
        function toggleFields() {
            const dividendType = $('#dividend_type').val();
            
            if (dividendType === 'cash') {
                $('#payment_date_group').show();
                $('#withholding_tax_group').show();
            } else {
                $('#payment_date_group').hide();
                $('#withholding_tax_group').hide();
            }
        }
        
        $('#dividend_type').on('change', toggleFields);
        toggleFields();
    });
</script>
@endpush

