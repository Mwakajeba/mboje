@extends('layouts.main')

@section('title', 'Lipa Mkopo kwa Taslim')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Mikopo ya Wateja', 'url' => route('cash_collaterals.index'), 'icon' => 'bx bx-credit-card'],
            ['label' => 'Mteja', 'url' => route('customers.show', Hashids::encode($customer->id)), 'icon' => 'bx bx-user'],
            ['label' => 'Lipa Mkopo kwa Taslim', 'url' => '#', 'icon' => 'bx bx-money-withdraw']
        ]" />
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1 fw-bold text-warning">
                            <i class="bx bx-money-withdraw me-2"></i>Lipa Mkopo kwa Taslim
                        </h4>
                        <p class="text-muted mb-0">Lipa mkopo wa mteja kwa pesa taslim kutoka akaunti ya mikopo</p>
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
                                    <div class="avatar-lg bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                                        <i class="bx bx-user fs-3 text-warning"></i>
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

        <!-- Payment Form Card -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-semibold text-warning">
                            <i class="bx bx-money-withdraw me-2"></i>Taarifa za Malipo
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('cash_collaterals.withdrawStore') }}" method="POST" id="withdrawalForm">
                            @csrf
                            <input type="hidden" name="collateral_id" value="{{ Hashids::encode($collateral->id) }}" />

                            <div class="row g-4">
                                <!-- Bank Account -->
                                <div class="col-md-6">
                                    <label for="bank_account_id" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-building me-2 text-warning"></i>Imelipwa Kutoka (Akaunti ya Benki)
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

                                <!-- Payment Date -->
                                <div class="col-md-6">
                                    <label for="withdrawal_date" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-calendar me-2 text-warning"></i>Tarehe ya Malipo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        class="form-control form-control-lg"
                                        id="withdrawal_date"
                                        name="withdrawal_date"
                                        value="{{ old('withdrawal_date', date('Y-m-d')) }}"
                                        required>
                                    @error('withdrawal_date')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <!-- Amount -->
                                <div class="col-md-6">
                                    <label for="amount" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-money me-2 text-warning"></i>Kiasi cha Malipo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="bx bx-dollar text-warning"></i> TSH
                                        </span>
                                        <input type="number"
                                            class="form-control border-start-0"
                                            id="amount"
                                            name="amount"
                                            value="{{ old('amount') }}"
                                            step="0.01"
                                            min="0.01"
                                            max="{{ $collateral->current_balance ?? 0 }}"
                                            placeholder="0.00"
                                            required>
                                    </div>
                                    <small class="text-muted mt-1">
                                        <i class="bx bx-info-circle me-1"></i>Kiwango cha juu: TSH {{ number_format($collateral->current_balance ?? 0, 2) }}
                                    </small>
                                    @error('amount')
                                    <div class="text-danger small mt-1">
                                        <i class="bx bx-error-circle me-1"></i>{{ $message }}
                                    </div>
                                    @enderror
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label for="notes" class="form-label fw-semibold mb-2">
                                        <i class="bx bx-note me-2 text-warning"></i>Maelezo
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control form-control-lg"
                                        id="notes"
                                        name="notes"
                                        rows="4"
                                        placeholder="Weka maelezo kuhusu malipo haya..."
                                        required>{{ old('notes') }}</textarea>
                                    <small class="text-muted">
                                        <i class="bx bx-info-circle me-1"></i>Eleza muamala wa malipo ya mkopo
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
                                        <button type="submit" class="btn btn-lg btn-warning" id="submitBtn">
                                            <span class="btn-text">
                                                <i class="bx bx-check me-1"></i> Lipa Mkopo kwa Taslim
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
                        <h6 class="mb-0 fw-semibold text-warning">
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
                                <span class="text-muted">Kiasi cha Malipo</span>
                                <span class="fw-bold fs-5 text-warning" id="withdrawalAmountPreview">
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

                        <div class="alert alert-warning border-0 mb-0">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-info-circle fs-5 me-2 mt-1"></i>
                                <div class="small">
                                    <strong>Kumbuka:</strong> Malipo yatachakatwa mara moja na kupunguzwa kwenye salio la akaunti ya mkopo ya mteja.
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

        const form = $('#withdrawalForm');
        const submitBtn = $('#submitBtn');
        const btnText = submitBtn.find('.btn-text');
        const spinner = submitBtn.find('.spinner-border');
        const amountInput = $('#amount');
        const withdrawalDateInput = $('#withdrawal_date');
        const currentBalance = {{ $collateral->current_balance ?? 0 }};

        if (!withdrawalDateInput.val()) {
            withdrawalDateInput.val(new Date().toISOString().split('T')[0]);
        }

        function formatAmount(value) {
            return 'TSH ' + value.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updatePreview() {
            const withdrawalAmount = parseFloat(amountInput.val()) || 0;
            const newBalance = currentBalance - withdrawalAmount;
            
            if (withdrawalAmount > currentBalance) {
                amountInput.addClass('is-invalid');
                $('#withdrawalAmountPreview').addClass('text-danger');
            } else {
                amountInput.removeClass('is-invalid');
                $('#withdrawalAmountPreview').removeClass('text-danger');
            }
            
            $('#withdrawalAmountPreview').text(formatAmount(withdrawalAmount));
            $('#newBalancePreview').text(formatAmount(Math.max(0, newBalance)));
            
            if (newBalance < 0) {
                $('#newBalancePreview').removeClass('text-success').addClass('text-danger');
            } else {
                $('#newBalancePreview').removeClass('text-danger').addClass('text-success');
            }
        }

        amountInput.on('input', updatePreview);

        amountInput.on('change', function() {
            const withdrawalAmount = parseFloat($(this).val()) || 0;
            
            if (withdrawalAmount > currentBalance) {
                alert('Kiasi cha malipo hakiwezi kuzidi salio linalopatikana la ' + formatAmount(currentBalance) + '.');
                $(this).val(currentBalance.toFixed(2));
                updatePreview();
            } else if (withdrawalAmount <= 0) {
                alert('Kiasi cha malipo lazima kiwe zaidi ya 0.');
                $(this).val('');
                updatePreview();
            }
        });

        form.on('submit', function(e) {
            const withdrawalAmount = parseFloat(amountInput.val()) || 0;
            
            if (withdrawalAmount > currentBalance) {
                e.preventDefault();
                alert('Kiasi cha malipo hakiwezi kuzidi salio linalopatikana.');
                return false;
            }
            
            if (withdrawalAmount <= 0) {
                e.preventDefault();
                alert('Tafadhali weka kiasi halali cha malipo zaidi ya 0.');
                return false;
            }
            
            if (!confirm('Una uhakika unataka kulipa mkopo kwa taslim kiasi cha ' + formatAmount(withdrawalAmount) + '?')) {
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
                btnText.html('<i class="bx bx-check me-1"></i> Lipa Mkopo kwa Taslim');
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
        border-color: #ffc107;
        box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
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

    #withdrawalAmountPreview,
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
