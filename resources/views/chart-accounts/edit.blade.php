@extends('layouts.main')
@section('title', 'Edit Chart Account')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Chart of Accounts', 'url' => route('accounting.chart-accounts.index'), 'icon' => 'bx bx-spreadsheet'],
            ['label' => 'Edit Account', 'url' => '#', 'icon' => 'bx bx-edit']
             ]" />
            <h6 class="mb-0 text-uppercase">EDIT CHART ACCOUNT</h6>
            <hr />
            
            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bx bx-edit text-warning me-2"></i>Edit Account Information
                            </h5>
                            @include('chart-accounts.form')
                        </div>
                    </div>
                </div>

                <!-- Right Column - Guidelines -->
                <div class="col-lg-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-info-circle me-2"></i>Update Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-warning mb-3">
                                <i class="bx bx-edit me-1"></i>Editing Chart Account
                            </h6>
                            <p class="small">
                                You are updating an existing chart account. Changes will affect how transactions are recorded and reported.
                            </p>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-error-circle me-1"></i>Important Considerations
                            </h6>
                            <ul class="small">
                                <li class="mb-2">
                                    <strong>FSLI Change:</strong> Changing the FSLI will affect account classification in reports
                                </li>
                                <li class="mb-2">
                                    <strong>Account Code:</strong> Must remain within the valid range for the selected class
                                </li>
                                <li class="mb-2">
                                    <strong>Account Type:</strong> 
                                    <ul class="mt-1">
                                        <li>Changing from child to parent may affect existing children</li>
                                        <li>Parent accounts with children cannot be deleted</li>
                                    </ul>
                                </li>
                                <li class="mb-2">
                                    <strong>GL Transactions:</strong> Accounts with existing transactions cannot be deleted
                                </li>
                            </ul>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-shield-quarter me-1"></i>Best Practices
                            </h6>
                            <div class="small">
                                <ul>
                                    <li class="mb-2">Review all fields before saving</li>
                                    <li class="mb-2">Ensure account code is unique</li>
                                    <li class="mb-2">Check if account has posted transactions</li>
                                    <li class="mb-2">Coordinate changes with accounting team</li>
                                    <li class="mb-2">Document reason for major changes</li>
                                </ul>
                            </div>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-sitemap me-1"></i>Current Details
                            </h6>
                            <div class="small mb-3">
                                <div class="mb-2">
                                    <strong>Account Code:</strong><br>
                                    <span class="text-muted">{{ $chartAccount->account_code }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Account Name:</strong><br>
                                    <span class="text-muted">{{ $chartAccount->account_name }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>FSLI:</strong><br>
                                    <span class="text-muted">{{ $chartAccount->accountClassGroup->name ?? 'N/A' }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>Account Type:</strong><br>
                                    <span class="badge bg-{{ $chartAccount->account_type == 'parent' ? 'primary' : 'secondary' }}">
                                        {{ ucfirst($chartAccount->account_type) }}
                                    </span>
                                </div>
                                @if($chartAccount->parent)
                                <div class="mb-2">
                                    <strong>Parent Account:</strong><br>
                                    <span class="text-muted">[{{ $chartAccount->parent->account_code }}] {{ $chartAccount->parent->account_name }}</span>
                                </div>
                                @endif
                                @if($chartAccount->children->count() > 0)
                                <div class="mb-2">
                                    <strong>Child Accounts:</strong><br>
                                    <span class="badge bg-info">{{ $chartAccount->children->count() }} children</span>
                                </div>
                                @endif
                            </div>

                            <hr>

                            <div class="alert alert-warning small mb-0">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>Note:</strong> Changes will be reflected immediately in all reports and financial statements.
                            </div>
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