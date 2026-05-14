@extends('layouts.main')

@section('title', 'Record Hotel Expense')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!-- Breadcrumb -->
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Hotel Expenses', 'url' => route('hotel.expenses.index'), 'icon' => 'bx bx-wallet'],
                ['label' => 'Record Expense', 'url' => '#', 'icon' => 'bx bx-plus']
            ]" />
            <h6 class="mb-0 text-uppercase">RECORD HOTEL EXPENSE</h6>
            <hr />
            <div class="row">
                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-header bg-secondary text-white">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h5 class="mb-0 text-white">
                                        <i class="bx bx-wallet me-2"></i>New Hotel Expense
                                    </h5>
                                    <p class="mb-0 opacity-75">Record a hotel expense (general or room-specific)</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="hotelExpenseForm" action="{{ route('hotel.expenses.store') }}"
                                method="POST">
                                @csrf

                                <!-- Expense Details -->
                                <div class="row mb-4">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label for="expense_date" class="form-label fw-bold">
                                                <i class="bx bx-calendar me-1"></i>Expense Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control form-control-lg @error('expense_date') is-invalid @enderror"
                                                id="expense_date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                                            @error('expense_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bx bx-money me-1"></i>Total Amount
                                            </label>
                                            <input type="text" class="form-control form-control-lg" id="total_amount" value="0.00" disabled>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Account -->
                                <div class="row mb-4">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="bx bx-wallet me-1"></i>Bank Account <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select form-select-lg select2-single mt-2 @error('bank_account_id') is-invalid @enderror"
                                                id="bank_account_id" name="bank_account_id" required>
                                                <option value="">-- Select Bank Account --</option>
                                                @foreach($bankAccounts as $bankAccount)
                                                    <option value="{{ $bankAccount->id }}" {{ old('bank_account_id') == $bankAccount->id ? 'selected' : '' }}>
                                                        {{ $bankAccount->name }} - {{ $bankAccount->account_number }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('bank_account_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Scope (Property or Room) -->
                                <div class="row mb-4">
                                    <div class="col-lg-12">
                                        <div class="card border-primary">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold">
                                                    <i class="bx bx-map-pin me-2"></i>Scope (Property or Room)
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-lg-6">
                                                        <label for="property_id" class="form-label fw-bold">Property (General Expense)</label>
                                                        <select class="form-select form-select-lg select2-single" id="property_id" name="property_id">
                                                            <option value="">-- Select Property --</option>
                                                            @foreach($properties as $property)
                                                                <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                                                    {{ $property->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-lg-6">
                                                        <label for="room_id" class="form-label fw-bold">Room (Specific Expense)</label>
                                                        <select class="form-select form-select-lg select2-single" id="room_id" name="room_id">
                                                            <option value="">-- Select Room --</option>
                                                            @foreach($rooms as $room)
                                                                <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                                                    {{ $room->room_number }}{{ $room->room_name ? ' - ' . $room->room_name : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="row mb-4">
                                    <div class="col-12 mb-3">
                                        <label for="description" class="form-label fw-bold">
                                            <i class="bx bx-message-square-detail me-1"></i>Description <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control form-control-lg @error('description') is-invalid @enderror"
                                            id="description" name="description" rows="3" required
                                            placeholder="Describe this expense...">{{ old('description') }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Line Items -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-primary">
                                            <div class="card-header bg-light">
                                                <h6 class="mb-0 fw-bold"><i class="bx bx-list-ul me-2"></i>Line Items</h6>
                                            </div>
                                            <div class="card-body">
                                                <div id="lineItemsContainer"></div>
                                                <div class="text-left mt-3">
                                                    <button type="button" class="btn btn-success" id="addLineBtn">
                                                        <i class="bx bx-plus me-2"></i>Add Line
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-start">
                                            <a href="{{ route('hotel.expenses.index') }}"
                                                class="btn btn-secondary me-2">
                                                <i class="bx bx-arrow-back me-2"></i>Cancel
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <button type="submit" class="btn btn-success" id="saveBtn">
                                                <i class="bx bx-save me-2"></i>Save Expense
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

@push('styles')
    <style>
        .form-control-lg,
        .form-select-lg {
            font-size: 1.1rem;
            padding: 0.75rem 1rem;
        }

        .btn-lg {
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
        }

        .line-item-row {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .line-item-row:hover {
            background: #e9ecef;
            border-color: #adb5bd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .line-item-row .form-label {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .line-item-row .form-select,
        .line-item-row .form-control {
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .line-item-row {
                padding: 15px;
            }

            .line-item-row .col-md-4,
            .line-item-row .col-md-3 {
                margin-bottom: 15px;
            }

            .line-item-row .col-md-1 {
                margin-bottom: 15px;
                text-align: center;
            }
        }
    </style>
@endpush

@push('scripts')
    <script nonce="{{ $cspNonce ?? '' }}">
        $(document).ready(function () {
            // Initialize select2
            $('.select2-single').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Amount formatting with comma separators (keeps numeric value on submit)
            const amountInputs = () => $('.line-amount-input');
            function formatAmount(val){
                if (val === '' || val === null || val === undefined) return '';
                // Strip non-numeric except dot and comma, normalize to dot
                let raw = (''+val).replace(/[^\d.,]/g,'').replace(/,/g,'');
                if(raw === '') return '';
                const num = parseFloat(raw);
                if (isNaN(num)) return '';
                return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function formatLive(val){
                // live format without forcing 2 decimals while typing
                let raw = (''+val).replace(/[^\d.]/g,'');
                if(raw === '') return '';
                // Split integer and decimals if any
                const parts = raw.split('.');
                const intPart = parts[0].replace(/^0+(?=\d)/,'');
                const decPart = parts[1] ?? '';
                const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                return decPart.length ? withCommas + '.' + decPart : withCommas;
            }
            function bindAmountFormatting(el){
                const $el = $(el);
                $el.on('blur', function(){ $(this).val(formatAmount($(this).val())); calculateTotal(); });
                $el.on('focus', function(){ const raw = (''+$(this).val()).replace(/[^\d.,]/g,'').replace(/,/g,''); $(this).val(raw); });
                $el.on('input', function(){
                    const node = this; const before = node.value; const formatted = formatLive(before);
                    if (formatted !== before) { node.value = formatted; setTimeout(()=>{ node.selectionStart = node.selectionEnd = node.value.length; }, 0); }
                    calculateTotal();
                });
            }
            // On submit normalize to raw numeric
            $('#hotelExpenseForm').on('submit', function(){
                amountInputs().each(function(){
                    const normalized = (''+this.value).replace(/[^\d.,]/g,'').replace(/,/g,'');
                    this.value = normalized;
                });
            });
            // Basic required checks before submit
            $('#hotelExpenseForm').on('submit', function (e) {
                const propertyId = $('#property_id').val();
                const roomId = $('#room_id').val();
                if (!propertyId && !roomId) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Select a Property for general expense or a specific Room.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }
                if ($('#lineItemsContainer .line-item-row').length === 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Add at least one line item.',
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }
            });

            // Line items
            let lineIdx = 0;
            function addLine(){
                lineIdx++;
                const html = `
                <div class="line-item-row mb-3 p-3 border rounded" data-index="${lineIdx}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Account <span class="text-danger">*</span></label>
                            <select class="form-select select2-single" name="line_items[${lineIdx}][chart_account_id]" required>
                                <option value="">-- Select Account --</option>
                                @foreach($chartAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->account_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Description</label>
                            <input type="text" class="form-control" name="line_items[${lineIdx}][description]" placeholder="Optional">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                            <input type="text" class="form-control line-amount-input" name="line_items[${lineIdx}][amount]" placeholder="0.00" required>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-line"><i class="bx bx-trash"></i></button>
                        </div>
                    </div>
                </div>`;
                $('#lineItemsContainer').append(html);
                const $row = $('#lineItemsContainer .line-item-row').last();
                $row.find('.select2-single').select2({ theme: 'bootstrap-5', width: '100%' });
                bindAmountFormatting($row.find('.line-amount-input'));
            }

            function calculateTotal(){
                let total = 0;
                amountInputs().each(function(){
                    const raw = (''+this.value).replace(/[^\d.,]/g,'').replace(/,/g,'');
                    const num = parseFloat(raw) || 0;
                    total += num;
                });
                $('#total_amount').val(total.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
            }

            $('#addLineBtn').on('click', addLine);
            $(document).on('click', '.remove-line', function(){
                $(this).closest('.line-item-row').remove();
                calculateTotal();
            });

            // start with one line
            addLine();
        });
    </script>
@endpush 