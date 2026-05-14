@extends('layouts.main')
@section('title', 'Create FX Revaluation')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb -->
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'FX Revaluation', 'url' => route('accounting.fx-revaluation.index'), 'icon' => 'bx bx-refresh'],
            ['label' => 'Create Revaluation', 'url' => '#', 'icon' => 'bx bx-plus']
        ]" />
        <h6 class="mb-0 text-uppercase">CREATE FX REVALUATION</h6>
        <hr />

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-plus-circle me-1 font-22 text-primary"></i></div>
                                    <h5 class="mb-0 text-primary">Create New FX Revaluation</h5>
                                </div>
                                <p class="mb-0 text-muted">Revalue foreign currency monetary items at month-end closing rates</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('accounting.fx-revaluation.index') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back to History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Revaluation Form -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bx bx-edit me-2"></i>Revaluation Details</h6>
                    </div>
                    <div class="card-body">
                        <form id="revaluationForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Revaluation Date <span class="text-danger">*</span></label>
                                    <input type="date" name="revaluation_date" id="revaluation_date" 
                                           class="form-control" 
                                           value="{{ old('revaluation_date', now()->endOfMonth()->toDateString()) }}" 
                                           required>
                                    <small class="text-muted">Typically the last day of the month</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Branch</label>
                                    <select name="branch_id" id="branch_id" class="form-select select2-single">
                                        <option value="">All Branches</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Leave blank to revalue all branches</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Functional Currency</label>
                                    <input type="text" class="form-control" value="{{ $functionalCurrency }}" readonly>
                                    <small class="text-muted">Base currency for revaluation</small>
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <button type="button" id="previewBtn" class="btn btn-primary">
                                        <i class="bx bx-search me-1"></i> Generate Preview
                                    </button>
                                    <button type="button" id="postBtn" class="btn btn-success" style="display: none;">
                                        <i class="bx bx-check me-1"></i> Post Revaluation
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div id="previewSection" style="display: none;">
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card radius-10 border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h6 class="mb-0"><i class="bx bx-list-ul me-2"></i>Revaluation Preview</h6>
                        </div>
                        <div class="card-body">
                            <!-- Summary -->
                            <div id="previewSummary" class="row mb-4"></div>

                            <!-- Filters and Actions -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                                        <input type="text" id="itemSearch" class="form-control" placeholder="Search items...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select id="itemTypeFilter" class="form-select">
                                        <option value="">All Item Types</option>
                                        <option value="AR">Accounts Receivable</option>
                                        <option value="AP">Accounts Payable</option>
                                        <option value="Bank">Bank Account</option>
                                        <option value="Loan">Loan</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" id="downloadReconciliationBtn" class="btn btn-outline-primary w-100">
                                        <i class="bx bx-download me-1"></i> Download Reconciliation
                                    </button>
                                </div>
                            </div>

                            <!-- Items Table with Grouping -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="previewTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30px;">
                                                <input type="checkbox" id="selectAllItems" title="Select All">
                                            </th>
                                            <th>Item Type <i class="bx bx-sort text-muted"></i></th>
                                            <th>Reference <i class="bx bx-sort text-muted"></i></th>
                                            <th>Date <i class="bx bx-sort text-muted"></i></th>
                                            <th>Currency</th>
                                            <th class="text-end">FCY Amount <i class="bx bx-sort text-muted"></i></th>
                                            <th class="text-end">Original Rate</th>
                                            <th class="text-end">Closing Rate</th>
                                            <th class="text-end">Original LCY <i class="bx bx-sort text-muted"></i></th>
                                            <th class="text-end">New LCY <i class="bx bx-sort text-muted"></i></th>
                                            <th class="text-end">Gain/Loss <i class="bx bx-sort text-muted"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewTableBody">
                                        <!-- Preview items will be loaded here -->
                                    </tbody>
                                    <tfoot id="previewTableFooter" class="table-light">
                                        <!-- Group totals will be displayed here -->
                                    </tfoot>
                                </table>
                            </div>

                            <div id="noItemsMessage" class="text-center py-4" style="display: none;">
                                <i class="bx bx-info-circle font-48 text-muted mb-3"></i>
                                <h6 class="text-muted">No items found for revaluation</h6>
                                <p class="text-muted">There are no foreign currency monetary items to revalue for the selected date and branch.</p>
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
        // Initialize Select2
        $('.select2-single').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });

        let previewData = null;

        // Generate Preview
        $('#previewBtn').on('click', function() {
            const revaluationDate = $('#revaluation_date').val();
            const branchId = $('#branch_id').val();

            if (!revaluationDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a revaluation date.',
                });
                return;
            }

            const btn = $(this);
            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i> Generating Preview...');

            $.ajax({
                url: '{{ route("accounting.fx-revaluation.preview") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    revaluation_date: revaluationDate,
                    branch_id: branchId
                },
                success: function(response) {
                    if (response.success) {
                        previewData = response.preview;
                        displayPreview(response.preview);
                        $('#previewSection').show();
                        $('#postBtn').show();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Preview Generated',
                            text: `Found ${response.preview.summary.total_items} items for revaluation`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to generate preview',
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to generate preview';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                    });
                },
                complete: function() {
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Display Preview
        function displayPreview(preview) {
            // Display Summary
            const summary = preview.summary;
            const summaryHtml = `
                <div class="col-md-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total Items</h6>
                            <h3 class="mb-0">${summary.total_items}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total Gain</h6>
                            <h3 class="mb-0 text-success">${formatCurrency(summary.total_gain)}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Total Loss</h6>
                            <h3 class="mb-0 text-danger">${formatCurrency(summary.total_loss)}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-${summary.net_gain_loss >= 0 ? 'success' : 'danger'}">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-1">Net Gain/Loss</h6>
                            <h3 class="mb-0 text-${summary.net_gain_loss >= 0 ? 'success' : 'danger'}">${formatCurrency(summary.net_gain_loss)}</h3>
                        </div>
                    </div>
                </div>
            `;
            $('#previewSummary').html(summaryHtml);

            // Display Items
            const tbody = $('#previewTableBody');
            tbody.empty();

            if (preview.items.length === 0) {
                $('#noItemsMessage').show();
                $('#previewTable').hide();
            } else {
                $('#noItemsMessage').hide();
                $('#previewTable').show();

                // Group items by type
                const groupedItems = {};
                preview.items.forEach(function(item) {
                    if (!groupedItems[item.item_type]) {
                        groupedItems[item.item_type] = [];
                    }
                    groupedItems[item.item_type].push(item);
                });
                
                // Display grouped items
                Object.keys(groupedItems).sort().forEach(function(itemType) {
                    const items = groupedItems[itemType];
                    let groupTotalFCY = 0;
                    let groupTotalGainLoss = 0;
                    
                    items.forEach(function(item) {
                        groupTotalFCY += parseFloat(item.fcy_amount || 0);
                        groupTotalGainLoss += parseFloat(item.gain_loss || 0);
                        
                        const row = `
                            <tr data-item-type="${item.item_type}" class="item-row">
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="${item.item_ref}" data-item-type="${item.item_type}">
                                </td>
                                <td><span class="badge bg-${getItemTypeColor(item.item_type)}">${item.item_type}</span></td>
                                <td>${item.item_ref}</td>
                                <td>${item.item_date}</td>
                                <td>${item.currency}</td>
                                <td class="text-end">${formatCurrency(item.fcy_amount)}</td>
                                <td class="text-end">${formatNumber(item.original_rate, 6)}</td>
                                <td class="text-end">${formatNumber(item.closing_rate, 6)}</td>
                                <td class="text-end">${formatCurrency(item.original_lcy_amount)}</td>
                                <td class="text-end">${formatCurrency(item.new_lcy_amount)}</td>
                                <td class="text-end">
                                    <span class="fw-bold ${item.gain_loss >= 0 ? 'text-success' : 'text-danger'}">
                                        ${formatCurrency(item.gain_loss)}
                                    </span>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                    
                    // Add group total row
                    // Table has 11 columns: checkbox(1) + Item Type(1) + Reference(1) + Date(1) + Currency(1) + FCY Amount(1) + Original Rate(1) + Closing Rate(1) + Original LCY(1) + New LCY(1) + Gain/Loss(1)
                    // Use 11 actual <td> elements instead of colspan to avoid DataTables column count issues
                    const groupTotalRow = `
                        <tr class="table-info group-total-row" data-item-type="${itemType}">
                            <td></td>
                            <td class="fw-bold text-end">
                                <strong>${itemType} Total:</strong>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-end fw-bold">${formatCurrency(groupTotalFCY)}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-end fw-bold ${groupTotalGainLoss >= 0 ? 'text-success' : 'text-danger'}">
                                ${formatCurrency(groupTotalGainLoss)}
                            </td>
                        </tr>
                    `;
                    tbody.append(groupTotalRow);
                });
                
                // Initialize DataTables for sorting and filtering
                initializePreviewTable();
            }
        }

        // Post Revaluation
        $('#postBtn').on('click', function() {
            if (!previewData) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Preview',
                    text: 'Please generate a preview first.',
                });
                return;
            }

            Swal.fire({
                title: 'Post Revaluation?',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Are you sure you want to post this revaluation?</p>
                        <div class="alert alert-info mb-0">
                            <strong>Date:</strong> ${$('#revaluation_date').val()}<br>
                            <strong>Items:</strong> ${previewData.summary.total_items}<br>
                            <strong>Net Gain/Loss:</strong> ${formatCurrency(previewData.summary.net_gain_loss)}<br>
                            <strong>Warning:</strong> This will create journal entries. This action cannot be undone.
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="bx bx-check me-1"></i> Yes, Post It',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Posting Revaluation...',
                        html: 'Please wait while the revaluation is being posted.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const form = $('<form>', {
                        'method': 'POST',
                        'action': '{{ route("accounting.fx-revaluation.store") }}'
                    });

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': '_token',
                        'value': '{{ csrf_token() }}'
                    }));

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'revaluation_date',
                        'value': $('#revaluation_date').val()
                    }));

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'branch_id',
                        'value': $('#branch_id').val()
                    }));

                    // Ensure previewData exists and is valid
                    if (!previewData || !previewData.items || previewData.items.length === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Preview Data',
                            text: 'Please generate a preview first before posting.',
                        });
                        return;
                    }

                    form.append($('<input>', {
                        'type': 'hidden',
                        'name': 'preview_data',
                        'value': JSON.stringify(previewData)
                    }));

                    $('body').append(form);
                    
                    // Submit form
                    try {
                        form.submit();
                    } catch (error) {
                        console.error('Form submission error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Error',
                            text: 'An error occurred while submitting the form: ' + error.message,
                        });
                    }
                }
            });
        });

        // Helper Functions
        function formatCurrency(amount) {
            const num = parseFloat(amount) || 0;
            return num.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatNumber(num, decimals = 2) {
            return parseFloat(num || 0).toFixed(decimals);
        }

        function getItemTypeColor(type) {
            const colors = {
                'AR': 'info',
                'AP': 'warning',
                'Bank': 'success',
                'Loan': 'secondary'
            };
            return colors[type] || 'secondary';
        }
        
        // Initialize DataTables for preview table
        let previewDataTable = null;
        function initializePreviewTable() {
            if ($.fn.DataTable.isDataTable('#previewTable')) {
                previewDataTable.destroy();
            }
            
            previewDataTable = $('#previewTable').DataTable({
                paging: true,
                pageLength: 25,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false,
                order: [[1, 'asc']], // Sort by item type
                columnDefs: [
                    { orderable: false, targets: 0 }, // Checkbox column
                    { type: 'num', targets: [5, 7, 8, 9, 10] }, // Numeric columns
                    { orderable: false, targets: [0, 2, 3, 4, 6, 7, 8, 9] } // Disable sorting on empty columns in group total rows
                ],
                footerCallback: function(row, data, start, end, display) {
                    // Footer totals can be added here if needed
                }
            });
            
            // Search functionality
            $('#itemSearch').on('keyup', function() {
                previewDataTable.search(this.value).draw();
            });
            
            // Filter by item type
            $('#itemTypeFilter').on('change', function() {
                const filterValue = this.value;
                if (filterValue) {
                    previewDataTable.column(1).search('^' + filterValue + '$', true, false).draw();
                } else {
                    previewDataTable.column(1).search('').draw();
                }
            });
        }
        
        // Select All functionality
        $('#selectAllItems').on('change', function() {
            $('.item-checkbox').prop('checked', this.checked);
        });
        
        // Download Reconciliation
        $('#downloadReconciliationBtn').on('click', function() {
            if (!previewData) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Preview',
                    text: 'Please generate a preview first.',
                });
                return;
            }
            
            // Create CSV content
            let csvContent = 'Item Type,Reference,Date,Currency,FCY Amount,Original Rate,Closing Rate,Original LCY,New LCY,Gain/Loss\n';
            
            previewData.items.forEach(function(item) {
                csvContent += `${item.item_type},${item.item_ref},${item.item_date},${item.currency},${item.fcy_amount},${item.original_rate},${item.closing_rate},${item.original_lcy_amount},${item.new_lcy_amount},${item.gain_loss}\n`;
            });
            
            // Download CSV
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `fx_revaluation_${$('#revaluation_date').val()}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            Swal.fire({
                icon: 'success',
                title: 'Downloaded',
                text: 'Reconciliation file downloaded successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        });
    });
</script>
@endpush

