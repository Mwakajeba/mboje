@extends('layouts.main')

@section('title', 'Create Share Issue')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Share Issues', 'url' => route('accounting.share-capital.share-issues.index'), 'icon' => 'bx bx-trending-up'],
            ['label' => 'Create', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE SHARE ISSUE</h6>
        <hr />

        <form action="{{ route('accounting.share-capital.share-issues.store') }}" method="POST" id="shareIssueForm">
            @csrf
            
            <div class="row">
                <!-- Basic Information -->
                <div class="col-md-12">
                    <div class="card border-top border-0 border-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bx bx-info-circle"></i> Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Share Class <span class="text-danger">*</span></label>
                                    <select name="share_class_id" id="share_class_id" class="form-select select2-single @error('share_class_id') is-invalid @enderror" required>
                                        <option value="">Select Share Class</option>
                                        @foreach($shareClasses as $shareClass)
                                            <option value="{{ $shareClass->id }}" data-par-value="{{ $shareClass->par_value }}" {{ old('share_class_id') == $shareClass->id ? 'selected' : '' }}>
                                                {{ $shareClass->name }} ({{ $shareClass->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Select the share class for this issue</small>
                                    @error('share_class_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Issue Type <span class="text-danger">*</span></label>
                                    <select name="issue_type" class="form-select select2-single @error('issue_type') is-invalid @enderror" required>
                                        <option value="initial" {{ old('issue_type') == 'initial' ? 'selected' : '' }}>Initial</option>
                                        <option value="private_placement" {{ old('issue_type') == 'private_placement' ? 'selected' : '' }}>Private Placement</option>
                                        <option value="public_offering" {{ old('issue_type') == 'public_offering' ? 'selected' : '' }}>Public Offering</option>
                                        <option value="rights" {{ old('issue_type') == 'rights' ? 'selected' : '' }}>Rights Issue</option>
                                        <option value="bonus" {{ old('issue_type') == 'bonus' ? 'selected' : '' }}>Bonus Issue</option>
                                        <option value="conversion" {{ old('issue_type') == 'conversion' ? 'selected' : '' }}>Conversion</option>
                                        <option value="other" {{ old('issue_type') == 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Type of share issue: Initial, Private Placement, Public Offering, Rights, Bonus, Conversion, or Other</small>
                                    @error('issue_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control @error('reference_number') is-invalid @enderror" name="reference_number" value="{{ old('reference_number') }}">
                                    <small class="text-muted d-block mt-1">Optional reference number for tracking (e.g., contract number, board resolution)</small>
                                    @error('reference_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                                    <small class="text-muted d-block mt-1">Date when shares are issued to shareholders</small>
                                    @error('issue_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Record Date</label>
                                    <input type="date" class="form-control @error('record_date') is-invalid @enderror" name="record_date" value="{{ old('record_date') }}">
                                    <small class="text-muted d-block mt-1">Date for determining eligible shareholders (if applicable)</small>
                                    @error('record_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price Per Share <span class="text-danger">*</span></label>
                                    <input type="number" step="0.000001" class="form-control @error('price_per_share') is-invalid @enderror" name="price_per_share" id="price_per_share" value="{{ old('price_per_share') }}" required>
                                    <small class="text-muted d-block mt-1">Issue price per share (may differ from par value)</small>
                                    @error('price_per_share')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Total Shares <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_shares') is-invalid @enderror" name="total_shares" id="total_shares" value="{{ old('total_shares') }}" required>
                                    <small class="text-muted d-block mt-1">Total number of shares being issued in this transaction</small>
                                    @error('total_shares')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Total Amount</label>
                                    <input type="text" class="form-control" id="total_amount" readonly>
                                    <small class="text-muted d-block mt-1">Automatically calculated: Price × Shares</small>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                                    <small class="text-muted d-block mt-1">Additional notes or details about this share issue</small>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Shareholders Allocation -->
                <div class="col-md-12 mt-3">
                    <div class="card border-top border-0 border-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bx bx-group"></i> Shareholders Allocation</h6>
                        </div>
                        <div class="card-body">
                            <div id="shareholdersContainer">
                                <div class="row mb-2">
                                    <div class="col-md-5"><strong>Shareholder</strong></div>
                                    <div class="col-md-3"><strong>Shares</strong></div>
                                    <div class="col-md-3"><strong>Amount</strong></div>
                                    <div class="col-md-1"></div>
                                </div>
                                <div class="shareholder-row mb-2">
                                    <div class="row">
                                        <div class="col-md-5 mb-2">
                                            <select name="shareholders[0][shareholder_id]" class="form-select select2-single shareholder-select" required>
                                                <option value="">Select Shareholder</option>
                                                @foreach($shareholders as $shareholder)
                                                    <option value="{{ $shareholder->getKey() }}" {{ (isset($selectedShareholderId) && $selectedShareholderId == $shareholder->getKey() ? 'selected' : '') }}>
                                                        {{ $shareholder->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <input type="number" name="shareholders[0][shares]" class="form-control shares-input" min="1" required>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <input type="text" class="form-control amount-display" readonly>
                                        </div>
                                        <div class="col-md-1 mb-2">
                                            <button type="button" class="btn btn-danger btn-sm remove-shareholder" style="display: none;">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addShareholder">
                                <i class="bx bx-plus"></i> Add Shareholder
                            </button>
                            <div class="mt-3">
                                <strong>Total Allocated: <span id="total_allocated">0</span> shares</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- GL Account Mapping -->
                <div class="col-md-12 mt-3">
                    <div class="card border-top border-0 border-4 border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bx bx-calculator"></i> GL Account Mapping</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bank Account <span class="text-danger">*</span></label>
                                    <select name="bank_account_id" class="form-select select2-single @error('bank_account_id') is-invalid @enderror" required>
                                        <option value="">Select Bank Account</option>
                                        @foreach($bankAccounts as $bankAccount)
                                            <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                {{ $bankAccount->name }} ({{ $bankAccount->account_number ?? 'N/A' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Bank account where proceeds from share issue will be received</small>
                                    @error('bank_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Share Capital Account <span class="text-danger">*</span></label>
                                    <select name="share_capital_account_id" class="form-select select2-single @error('share_capital_account_id') is-invalid @enderror" required>
                                        <option value="">Select Account</option>
                                        @foreach($equityAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('share_capital_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">GL equity account for share capital (par value portion)</small>
                                    @error('share_capital_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Share Premium Account</label>
                                    <select name="share_premium_account_id" class="form-select select2-single @error('share_premium_account_id') is-invalid @enderror">
                                        <option value="">Select Account</option>
                                        @foreach($equityAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('share_premium_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->account_code }} - {{ $account->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">GL equity account for share premium (amount above par value)</small>
                                    @error('share_premium_account_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Issue Costs</label>
                                    <input type="number" step="0.01" class="form-control @error('issue_costs') is-invalid @enderror" name="issue_costs" value="{{ old('issue_costs', 0) }}">
                                    <small class="text-muted d-block mt-1">Total costs incurred in issuing shares (legal, underwriting, etc.)</small>
                                    @error('issue_costs')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="auto_approve" id="auto_approve" value="1">
                                        <label class="form-check-label" for="auto_approve">
                                            Auto-approve and post to GL immediately
                                        </label>
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
                        <i class="bx bx-save"></i> Create Share Issue
                    </button>
                    <a href="{{ route('accounting.share-capital.share-issues.index') }}" class="btn btn-secondary">
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
    let shareholderIndex = 1;
    
    $(document).ready(function() {
        // Calculate total amount
        function calculateTotal() {
            const price = parseFloat($('#price_per_share').val()) || 0;
            const shares = parseInt($('#total_shares').val()) || 0;
            const total = price * shares;
            $('#total_amount').val(total.toFixed(2));
        }
        
        $('#price_per_share, #total_shares').on('input', calculateTotal);
        
        // Calculate shareholder amounts
        function calculateShareholderAmounts() {
            const price = parseFloat($('#price_per_share').val()) || 0;
            let totalAllocated = 0;
            
            $('.shares-input').each(function() {
                const shares = parseInt($(this).val()) || 0;
                const amount = price * shares;
                $(this).closest('.row').find('.amount-display').val(amount.toFixed(2));
                totalAllocated += shares;
            });
            
            $('#total_allocated').text(totalAllocated);
        }
        
        $('#price_per_share').on('input', calculateShareholderAmounts);
        $(document).on('input', '.shares-input', calculateShareholderAmounts);
        
        // Add shareholder row
        $('#addShareholder').on('click', function() {
            const newRow = `
                <div class="shareholder-row mb-2">
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <select name="shareholders[${shareholderIndex}][shareholder_id]" class="form-select select2-single shareholder-select" required>
                                <option value="">Select Shareholder</option>
                                @foreach($shareholders as $shareholder)
                                    <option value="{{ $shareholder->getKey() }}">{{ $shareholder->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="number" name="shareholders[${shareholderIndex}][shares]" class="form-control shares-input" min="1" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" class="form-control amount-display" readonly>
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="button" class="btn btn-danger btn-sm remove-shareholder">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#shareholdersContainer').append(newRow);
            // Initialize select2 for the newly added select element
            $('#shareholdersContainer .shareholder-select').last().select2({
                placeholder: 'Select Shareholder',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });
            shareholderIndex++;
            updateRemoveButtons();
        });
        
        // Remove shareholder row
        $(document).on('click', '.remove-shareholder', function() {
            $(this).closest('.shareholder-row').remove();
            calculateShareholderAmounts();
            updateRemoveButtons();
        });
        
        function updateRemoveButtons() {
            const rows = $('.shareholder-row').length;
            $('.remove-shareholder').toggle(rows > 1);
        }
        
        updateRemoveButtons();
        
        // Form validation
        $('#shareIssueForm').on('submit', function(e) {
            const totalShares = parseInt($('#total_shares').val()) || 0;
            const totalAllocated = parseInt($('#total_allocated').text()) || 0;
            
            if (totalShares !== totalAllocated) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    html: `
                        <div class="text-center">
                            <i class="bx bx-error-circle text-danger" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p class="mb-2"><strong>Total shares allocated must equal total shares issued.</strong></p>
                            <div class="mt-3">
                                <p class="mb-1"><strong>Total Shares Issued:</strong> <span class="text-primary">${totalShares.toLocaleString()}</span></p>
                                <p class="mb-0"><strong>Total Shares Allocated:</strong> <span class="text-danger">${totalAllocated.toLocaleString()}</span></p>
                                <p class="mt-2 text-muted"><small>Difference: ${Math.abs(totalShares - totalAllocated).toLocaleString()} shares</small></p>
                            </div>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: '<i class="bx bx-check me-1"></i>OK',
                    buttonsStyling: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
                return false;
            }
        });
    });
</script>
@endpush

