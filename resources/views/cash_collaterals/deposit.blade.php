@extends('layouts.main')

@section('title', 'Toa Mkopo')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Mikopo ya Wateja', 'url' => route('cash_collaterals.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Mteja', 'url' => route('customers.show', Hashids::encode($customer->id)), 'icon' => 'bx bx-user'],
            ['label' => 'Toa Mkopo', 'url' => '#', 'icon' => 'bx bx-money']
        ]" />
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold text-primary">
                            <i class="bx bx-money me-2"></i>Toa Mkopo
                        </h4>
                        <p class="text-muted mb-0">Toa mkopo kwa mteja na kuongeza salio la akaunti ya mikopo</p>
                    </div>
                    <a href="{{ route('customers.show', Hashids::encode($customer->id)) }}" class="btn btn-outline-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Rudi kwa Mteja
                    </a>
                </div>
            </div>
        </div>

        <!-- Customer Information Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-lg bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                        <i class="bx bx-user fs-3 text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 fw-bold">{{ $customer->name }}</h5>
                                        <p class="text-muted mb-0 small">
                                            <i class="bx bx-id-card me-1"></i>Mteja #{{ $customer->customerNo }}
                                            @if($customer->phone)
                                                <span class="ms-3"><i class="bx bx-phone me-1"></i>{{ $customer->phone }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <div class="bg-light rounded p-3 d-inline-block">
                                    <small class="text-muted d-block mb-1">Salio la Sasa</small>
                                    <h4 class="mb-0 fw-bold text-success">
                                        TSH {{ number_format($collateral->current_balance ?? 0, 2) }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deposit Form Card -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-semibold text-primary">
                            <i class="bx bx-credit-card me-2"></i>Taarifa za Mkopo
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('cash_collaterals.depositStore') }}" method="POST" id="depositForm">
                            @csrf
                            <input type="hidden" name="collateral_id" value="{{ Hashids::encode($collateral->id) }}" />

                            <div class="row g-4">
                                <!-- Bank Account -->
                                <div class="col-md-6">
                                    <label for="bank_account_id" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-building me-2 text-primary"></i>Imepokelewa Kwenye (Akaunti ya Benki)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="bank_account_id" id="bank_account_id" class="form-select form-select-lg select2-single" required>
                                        <option value="">Chagua akaunti ya benki</option>
                                        @foreach($bankAccounts as $bankAccount)
                                        <option value="{{ $bankAccount->id }}"
                                            {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                            {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('bank_account_id')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <!-- Deposit Date -->
                                <div class="col-md-6">
                                    <label for="deposit_date" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-calendar me-2 text-primary"></i>Tarehe ya Mkopo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        class="form-control form-control-lg"
                                        id="deposit_date"
                                        name="deposit_date"
                                        value="{{ old('deposit_date', date('Y-m-d')) }}"
                                        required>
                                    @error('deposit_date')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <!-- Loan Type -->
                                <div class="col-md-6">
                                    <label for="loan_type" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-list-check me-2 text-primary"></i>Aina ya Mkopo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="loan_type" id="loan_type" class="form-select form-select-lg" required>
                                        <option value="">Chagua aina ya mkopo</option>
                                        @foreach(\App\Models\CashCollateral::loanTypeOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('loan_type') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('loan_type')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <!-- Amount -->
                                <div class="col-md-6">
                                    <label for="amount" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-money me-2 text-primary"></i>Kiasi cha Mkopo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bx bx-dollar text-primary"></i> TSH
                                        </span>
                                        <input type="number"
                                            class="form-control border-start-0"
                                            id="amount"
                                            name="amount"
                                            value="{{ old('amount') }}"
                                            step="0.01"
                                            min="0.01"
                                            placeholder="0.00"
                                            required>
                                    </div>
                                    @error('amount')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-note me-2 text-primary"></i>Maelezo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control form-control-lg"
                                        id="notes"
                                        name="notes"
                                        rows="4"
                                        placeholder="Weka maelezo kuhusu mkopo..."
                                        required>{{ old('notes') }}</textarea>
                                    <small class="text-muted">
                                        <i class="bx bx-info-circle me-1"></i>Eleza muamala wa kutoa mkopo
                                    </small>
                                    @error('notes')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="row mt-4 pt-3 border-top">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('customers.show', Hashids::encode($customer->id)) }}" class="btn btn-lg btn-outline-secondary">
                                            <i class="bx bx-x me-1"></i> Ghairi
                                        </a>
                                        <button type="submit" class="btn btn-lg btn-primary" id="submitBtn">
                                            <span class="btn-text">
                                                <i class="bx bx-check me-1"></i> Toa Mkopo
                                            </span>
                                            <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-semibold text-primary">
                            <i class="bx bx-info-circle me-2"></i>Muhtasari wa Muamala
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Salio la Sasa</span>
                                <span class="fw-bold fs-5 text-success">
                                    TSH {{ number_format($collateral->current_balance ?? 0, 2) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Kiasi cha Mkopo</span>
                                <span class="fw-bold fs-5 text-primary" id="depositAmountPreview">
                                    TSH 0.00
                                </span>
                            </div>
                            <hr class="my-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Salio Jipya</span>
                                <span class="fw-bold fs-4 text-success" id="newBalancePreview">
                                    TSH {{ number_format($collateral->current_balance ?? 0, 2) }}
                                </span>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 mb-0">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-5 me-2 mt-1"></i>
                                <div class="small">
                                    <strong>Kumbuka:</strong> Mkopo utachakatwa mara moja na kuongezwa kwenye salio la akaunti ya mteja.
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
        $('#bank_account_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Chagua akaunti ya benki',
            allowClear: false
        });

        const form = $('#depositForm');
        const submitBtn = $('#submitBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        const amountInput = $('#amount');
        const depositDateInput = $('#deposit_date');
        const currentBalance = {{ $collateral->current_balance ?? 0 }};

        if (!depositDateInput.val()) {
            depositDateInput.val(new Date().toISOString().split('T')[0]);
        }

        function formatAmount(value) {
            return 'TSH ' + value.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updatePreview() {
            const depositAmount = parseFloat(amountInput.val()) || 0;
            const newBalance = currentBalance + depositAmount;
            
            $('#depositAmountPreview').text(formatAmount(depositAmount));
            $('#newBalancePreview').text(formatAmount(newBalance));
        }

        amountInput.on('input', updatePreview);

        form.on('submit', function(e) {
            const depositAmount = parseFloat(amountInput.val()) || 0;

            if (depositAmount <= 0) {
                e.preventDefault();
                alert('Tafadhali weka kiasi halali cha mkopo zaidi ya 0.');
                return false;
            }

            if (!confirm('Una uhakika unataka kutoa mkopo kiasi cha ' + formatAmount(depositAmount) + '?')) {
                e.preventDefault();
                return false;
            }

            submitBtn.prop('disabled', true);
            btnText.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Inachakata...');
            spinner.removeClass('d-none');
            submitBtn.addClass('loading');
        });

        setTimeout(function() {
            if (submitBtn.prop('disabled')) {
                submitBtn.prop('disabled', false);
                btnText.html('<i class="bx bx-check me-1"></i> Toa Mkopo');
                spinner.addClass('d-none');
                submitBtn.removeClass('loading');
            }
        }, 5000);
    });
</script>
@endpush

@push('styles')
<style>
    .card {
        border-radius: 0.75rem;
        transition: box-shadow 0.15s ease-in-out;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    .card-header {
        border-radius: 0.75rem 0.75rem 0 0 !important;
        border-bottom: 2px solid rgba(0, 0, 0, 0.08);
        font-weight: 600;
        background-color: #f8f9fa !important;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-label {
        margin-bottom: 0.5rem;
        color: #495057;
        font-weight: 500;
    }

    .form-control, .form-select {
        border-radius: 0.5rem;
        border: 1px solid #ced4da;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    .form-control-lg, .form-select-lg {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }

    .input-group-text {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        color: #495057;
    }

    .input-group-lg .input-group-text {
        padding: 0.75rem 1rem;
    }

    .btn {
        border-radius: 0.5rem;
        font-weight: 500;
        transition: all 0.15s ease-in-out;
        padding: 0.75rem 1.5rem;
    }

    .btn-lg {
        padding: 0.875rem 1.75rem;
        font-size: 1rem;
    }

    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }

    .btn.loading {
        position: relative;
        pointer-events: none;
        opacity: 0.8;
    }
    
    .btn .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    .avatar-lg {
        width: 60px;
        height: 60px;
    }

    .alert {
        border-radius: 0.5rem;
    }

    #depositAmountPreview,
    #newBalancePreview {
        font-family: 'Courier New', monospace;
    }

    @media (max-width: 768px) {
        .card-body {
            padding: 1rem;
        }
        
        .btn-lg {
            padding: 0.75rem 1.25rem;
            font-size: 0.95rem;
        }
    }
</style>
@endpush
