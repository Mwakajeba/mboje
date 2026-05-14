@extends('layouts.main')
@section('title', 'Create Chart Account')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Chart of Accounts', 'url' => route('accounting.chart-accounts.index'), 'icon' => 'bx bx-spreadsheet'],
            ['label' => 'Create Account', 'url' => '#', 'icon' => 'bx bx-plus']
             ]" />
            <h6 class="mb-0 text-uppercase">CREATE NEW CHART ACCOUNT</h6>
            <hr />
            
            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bx bx-plus-circle text-primary me-2"></i>Account Information
                            </h5>
                            @include('chart-accounts.form')
                        </div>
                    </div>
                </div>

                <!-- Right Column - Guidelines -->
                <div class="col-lg-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-info-circle me-2"></i>Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-info mb-3">
                                <i class="bx bx-question-mark me-1"></i>What is a Chart Account?
                            </h6>
                            <p class="small">
                                Chart of Accounts are the individual accounts used to record financial transactions. They are organized under FSLI (Financial Statement Line Items).
                            </p>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-sitemap me-1"></i>Account Hierarchy
                            </h6>
                            <div class="small mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-primary me-2">1</span>
                                    <strong>Account Class</strong>
                                </div>
                                <div class="d-flex align-items-center mb-2 ms-3">
                                    <i class="bx bx-subdirectory-right me-2"></i>
                                    <span class="badge bg-purple me-2">2</span>
                                    <strong>Main Group</strong>
                                </div>
                                <div class="d-flex align-items-center mb-2 ms-4">
                                    <i class="bx bx-subdirectory-right me-2"></i>
                                    <span class="badge bg-success me-2">3</span>
                                    <strong>FSLI</strong>
                                </div>
                                <div class="d-flex align-items-center mb-2 ms-5">
                                    <i class="bx bx-subdirectory-right me-2"></i>
                                    <span class="badge bg-warning me-2">4</span>
                                    <strong>Chart Account</strong>
                                </div>
                                <div class="d-flex align-items-center ms-5 ps-3">
                                    <i class="bx bx-right-arrow-alt me-2 text-muted"></i>
                                    <span class="badge bg-secondary me-2">Parent</span>
                                    <span class="text-muted small">or</span>
                                    <span class="badge bg-secondary ms-2">Child</span>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-list-ul me-1"></i>How to Create
                            </h6>
                            <ol class="small">
                                <li class="mb-2">
                                    <strong>Select FSLI:</strong> Choose the financial statement line item
                                </li>
                                <li class="mb-2">
                                    <strong>Account Code:</strong> Enter code within the displayed range
                                </li>
                                <li class="mb-2">
                                    <strong>Account Name:</strong> Descriptive name for the account
                                </li>
                                <li class="mb-2">
                                    <strong>Account Type:</strong> 
                                    <ul class="mt-1">
                                        <li><strong>Parent:</strong> Can have child accounts</li>
                                        <li><strong>Child:</strong> Must select a parent account</li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <strong>Optional:</strong> Check cash flow or equity impact if applicable
                                </li>
                            </ol>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-bulb me-1"></i>Examples
                            </h6>
                            <div class="small">
                                <div class="mb-3">
                                    <strong class="text-primary">Parent Account:</strong>
                                    <div class="ms-3">
                                        [1000] Cash and Bank
                                        <ul class="mt-1 mb-0">
                                            <li>[1001] Petty Cash (child)</li>
                                            <li>[1002] Bank - CRDB (child)</li>
                                            <li>[1003] Bank - NMB (child)</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <strong class="text-primary">Standalone Account:</strong>
                                    <div class="ms-3">
                                        [2000] Accounts Payable<br>
                                        <small class="text-muted">(No children)</small>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-info mb-3">
                                <i class="bx bx-info-square me-1"></i>Cash Flow & Equity
                            </h6>
                            <div class="small">
                                <p><strong>Cash Flow Impact:</strong> Check if transactions in this account affect cash flow statements</p>
                                <p><strong>Equity Impact:</strong> Check if transactions affect equity statements</p>
                            </div>

                            <hr>

                            <h6 class="text-info mb-2">
                                <i class="bx bx-error me-1"></i>Important Notes
                            </h6>
                            <ul class="small text-muted mb-0">
                                <li>Account code must be unique</li>
                                <li>Code must be within class range</li>
                                <li>Parent accounts organize child accounts</li>
                                <li>Child accounts must have a parent</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('styles')
<style>
    .text-purple { color: #6f42c1 !important; }
    .bg-purple { background-color: #6f42c1 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    // Build mapping of group_id => class_id
    const groupToClass = {};
    @foreach($accountClassGroups as $group)
        groupToClass[{{ $group->id }}] = {{ $group->class_id }};
    @endforeach
    // Build mapping of class_id => {from, to}
    const classRanges = @json($classRanges);

    // Function to update account code range hint
    function updateRangeHint() {
        const groupSelect = $('select[name="account_class_group_id"]');
        const selectedGroupId = groupSelect.val();
        const classId = groupToClass[selectedGroupId];
        const rangeHint = $('#range_hint');
        const rangeHintDisplay = $('#range_hint_display');
        
        if (classRanges[classId] && classRanges[classId].from !== null && classRanges[classId].from !== undefined &&
            classRanges[classId].to !== null && classRanges[classId].to !== undefined) {
            const rangeText = `Range: ${classRanges[classId].from} - ${classRanges[classId].to}`;
            rangeHint.text(rangeText);
            rangeHintDisplay.text(rangeText);
        } else {
            rangeHint.text('');
            rangeHintDisplay.text('');
        }
    }

    // Initialize Select2 for all select elements with select2 class
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select an option...',
        allowClear: true
    });

    // Add change event for account class group select
    $('select[name="account_class_group_id"]').on('select2:select', function() {
        updateRangeHint();
    });

    // Handle account type change - show/hide parent account selection
    $('#account_type').change(function() {
        const accountType = $(this).val();
        const parentAccountDiv = $('#parent_account_div');
        const parentSelect = $('#parent_id');
        
        if (accountType === 'child') {
            parentAccountDiv.show();
            parentSelect.attr('required', true);
            // Reinitialize Select2 for parent account dropdown
            if (!parentSelect.hasClass('select2-hidden-accessible')) {
                parentSelect.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Select Parent Account...',
                    allowClear: true
                });
            }
        } else {
            parentAccountDiv.hide();
            parentSelect.attr('required', false);
            parentSelect.val('').trigger('change');
        }
    });

    // Trigger on page load to set initial state
    $('#account_type').trigger('change');

    // Re-initialize Select2 when dynamic content is shown
    $('#has_cash_flow').change(function() {
        if ($(this).is(':checked')) {
            $('#cash_flow_category_div').show();
            $('#cash_flow_category_div .select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Cash Flow Category...',
                allowClear: true
            });
        } else {
            $('#cash_flow_category_div').hide();
        }
    });

    $('#has_equity').change(function() {
        if ($(this).is(':checked')) {
            $('#equity_category_div').show();
            $('#equity_category_div .select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select Equity Category...',
                allowClear: true
            });
        } else {
            $('#equity_category_div').hide();
        }
    });

    // Initialize range hint on page load
    updateRangeHint();
});
</script>
@endpush

@endsection