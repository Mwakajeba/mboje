<form 
    action="{{ isset($journal) ? route('accounting.journals.update', $journal) : route('accounting.journals.store') }}" 
    method="POST" 
    enctype="multipart/form-data"
    id="journalForm"
>
    @csrf
    @if(isset($journal))
        @method('PUT')
    @endif

    <!-- Basic Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="form-group">
                <label for="date" class="form-label fw-bold">
                    <i class="bx bx-calendar me-1"></i>Entry Date
                </label>
                <input type="date" 
                       name="date" 
                       id="date"
                       class="form-control transaction-date @error('date') is-invalid @enderror" 
                       value="{{ old('date', isset($journal) ? $journal->date->format('Y-m-d') : now()->format('Y-m-d')) }}"
                       required>
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="attachment" class="form-label fw-bold">
                    <i class="bx bx-paperclip me-1"></i>Attachment
                </label>
                <input type="file" 
                       name="attachment" 
                       id="attachment"
                       class="form-control @error('attachment') is-invalid @enderror"
                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                @error('attachment')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            @if(isset($journal) && $journal->attachment)
                <div class="mt-2">
                        <a href="{{ asset('storage/' . $journal->attachment) }}" 
                           target="_blank" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-download me-1"></i>View Current Attachment
                        </a>
                </div>
            @endif
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="form-group mb-4">
        <label for="description" class="form-label fw-bold">
            <i class="bx bx-message-square-detail me-1"></i>Description
        </label>
        <textarea name="description" 
                  id="description"
                  class="form-control @error('description') is-invalid @enderror" 
                  rows="3"
                  placeholder="Enter a detailed description of this journal entry..."
                  required>{{ old('description', $journal->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Journal Items Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="bx bx-list-ul me-2"></i>Journal Entries (Debit / Credit)
                </h6>
                <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                    <i class="bx bx-plus me-1"></i>Add Entry
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="items-table">
                    <thead class="table-light">
            <tr>
                            <th style="width: 35%;">Account</th>
                            <th style="width: 15%;">Nature</th>
                            <th style="width: 20%;">Amount</th>
                            <th style="width: 25%;">Description</th>
                            <th style="width: 5%;">Action</th>
            </tr>
        </thead>
        <tbody>
                        @php 
                            $items = old('items', []);
                            if (isset($journal) && $journal->items->count() > 0) {
                                $items = $journal->items->map(function($item) {
                                    return [
                                        'account_id' => $item->chart_account_id,
                                        'amount' => $item->amount,
                                        'nature' => $item->nature,
                                        'description' => $item->description,
                                    ];
                                })->toArray();
                            }
                        @endphp
                        @if(count($items) > 0)
            @foreach($items as $index => $item)
                                <tr class="journal-item">
                    <td>
                                        <select name="items[{{ $index }}][account_id]" 
                                                class="form-select chart-account-select select2-single @error('items.'.$index.'.account_id') is-invalid @enderror"
                                                required>
                            <option value="">-- Select Account --</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}"
                                                    {{ isset($item['account_id']) && $item['account_id'] == $account->id ? 'selected' : '' }}>
                                                    {{ $account->account_name }} ({{ $account->account_code }})
                                </option>
                            @endforeach
                        </select>
                                        @error('items.'.$index.'.account_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                    </td>
                    <td>
                                        <select name="items[{{ $index }}][nature]" 
                                                class="form-select nature-select @error('items.'.$index.'.nature') is-invalid @enderror"
                                                required>
                                            <option value="debit" {{ isset($item['nature']) && $item['nature'] == 'debit' ? 'selected' : '' }}>Debit</option>
                                            <option value="credit" {{ isset($item['nature']) && $item['nature'] == 'credit' ? 'selected' : '' }}>Credit</option>
                        </select>
                                        @error('items.'.$index.'.nature')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                    </td>
                    <td>
                                        <input type="number" 
                                               step="0.01" 
                                               name="items[{{ $index }}][amount]" 
                                               class="form-control amount-input @error('items.'.$index.'.amount') is-invalid @enderror"
                                               value="{{ $item['amount'] ?? '' }}"
                                               placeholder="0.00"
                                               required>
                                        @error('items.'.$index.'.amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                    </td>
                    <td>
                                        <input type="text" 
                                               name="items[{{ $index }}][description]" 
                                               class="form-control @error('items.'.$index.'.description') is-invalid @enderror"
                                               value="{{ $item['description'] ?? '' }}"
                                               placeholder="Optional description">
                                        @error('items.'.$index.'.description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                                            <i class="bx bx-trash"></i>
                                        </button>
                    </td>
                </tr>
            @endforeach
                        @else
                            <tr id="no-items-row">
                                <td colspan="5" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bx bx-list-ul font-48 text-muted mb-3"></i>
                                        <h6 class="text-muted">No Journal Entries Added</h6>
                                        <p class="text-muted mb-0">Click "Add Entry" to start adding journal items</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
        </tbody>
    </table>
            </div>
        </div>
    </div>

    <!-- Totals Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Debit</h6>
                    <h4 class="mb-0 text-success" id="total-debit">TZS 0.00</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Total Credit</h6>
                    <h4 class="mb-0 text-danger" id="total-credit">TZS 0.00</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-1">Balance</h6>
                    <h4 class="mb-0" id="balance">TZS 0.00</h4>
                    <div id="balance-status" class="mt-2" style="display: none;">
                        <span class="badge bg-success" id="balanced-badge">
                            <i class="bx bx-check-circle me-1"></i>Entry Balanced
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <a href="{{ route('accounting.journals.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Cancel
            </a>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="validateAndSubmit()">
                <i class="bx bx-check me-1"></i>Validate Entry
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;" data-processing-text="Processing...">
                <i class="bx bx-save me-1"></i>{{ isset($journal) ? 'Update' : 'Create' }} Journal Entry
        </button>
        </div>
    </div>
</form>

<!-- Include Select2 CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script nonce="{{ $cspNonce ?? '' }}">
// Wait for jQuery to be available
function waitForJQuery(callback) {
    if (typeof jQuery !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            waitForJQuery(callback);
        }, 50);
    }
}

// Initialize form when jQuery is ready
waitForJQuery(function() {
    // Load Select2 after jQuery is available
    if (typeof $.fn.select2 === 'undefined') {
        $.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', function() {
            initializeForm();
        });
    } else {
        initializeForm();
    }
});

// Global variable for item index
let itemIndex = {{ count($items) }};

function initializeForm() {
    console.log('Document ready - initializing form...');
    console.log('Initial item count:', itemIndex);
    
    // Initialize Select2 for all select2-single dropdowns (matching sales invoice style)
    $('.select2-single').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
    
    // Initialize Select2 for account dropdowns specifically
    $('.chart-account-select').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Calculate totals on page load with delay to ensure DOM is ready
    // Use multiple delays to ensure totals are calculated after all initialization
    setTimeout(function() {
        console.log('Calculating initial totals (first attempt)...');
        calculateTotals();
    }, 300);
    
    // Additional calculation after Select2 is fully initialized
    setTimeout(function() {
        console.log('Calculating initial totals (second attempt)...');
        calculateTotals();
    }, 800);
    
    // Final calculation to ensure all values are loaded
    setTimeout(function() {
        console.log('Calculating initial totals (final attempt)...');
        calculateTotals();
    }, 1500);
    
    // Also calculate when window is fully loaded (important for edit mode)
    $(window).on('load', function() {
        setTimeout(function() {
            console.log('Window loaded - calculating totals for edit mode...');
            calculateTotals();
        }, 500);
    });

    // Add event listeners for amount changes
    $(document).on('input', '.amount-input', function() {
        console.log('Amount changed:', $(this).val());
        calculateTotals();
    });

    // Add event listeners for nature changes
    $(document).on('change', '.nature-select', function() {
        console.log('Nature changed:', $(this).val());
        calculateTotals();
    });
}

    function addItem() {
    const tbody = $('#items-table tbody');
    const newRow = `
        <tr class="journal-item">
            <td>
                <select name="items[${itemIndex}][account_id]" class="form-select chart-account-select select2-single" required>
                    <option value="">-- Select Account --</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }} ({{ $account->account_code }})</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="items[${itemIndex}][nature]" class="form-select nature-select" required>
                    <option value="debit">Debit</option>
                    <option value="credit">Credit</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${itemIndex}][amount]" 
                       class="form-control amount-input" placeholder="0.00" required>
            </td>
            <td>
                <input type="text" name="items[${itemIndex}][description]" 
                       class="form-control" placeholder="Optional description">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)">
                    <i class="bx bx-trash"></i>
                </button>
            </td>
        </tr>
        `;
    
    // Remove the "no items" row if it exists
    $('#no-items-row').remove();
    
    tbody.append(newRow);
    
    // Initialize Select2 for the new row (matching sales invoice style)
    setTimeout(function() {
        const newSelect = tbody.find('.chart-account-select').last();
        newSelect.select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }, 100);
    
    // Add event listeners to the new row
    const newRowElement = tbody.find('tr').last();
    newRowElement.find('.amount-input').on('input', function() {
        console.log('New amount input changed:', $(this).val());
        calculateTotals();
    });
    
    newRowElement.find('.nature-select').on('change', function() {
        console.log('New nature select changed:', $(this).val());
        calculateTotals();
    });
    
        itemIndex++;
    console.log('Added new item, total items:', $('.journal-item').length);
}

function removeItem(button) {
    $(button).closest('tr').remove();
    calculateTotals();
    
    // If no items left, show the "no items" message
    if ($('.journal-item').length === 0) {
        const tbody = $('#items-table tbody');
        tbody.append(`
            <tr id="no-items-row">
                <td colspan="5" class="text-center py-4">
                    <div class="d-flex flex-column align-items-center">
                        <i class="bx bx-list-ul font-48 text-muted mb-3"></i>
                        <h6 class="text-muted">No Journal Entries Added</h6>
                        <p class="text-muted mb-0">Click "Add Entry" to start adding journal items</p>
                    </div>
                </td>
            </tr>
        `);
    }
}

function calculateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;
    
    console.log('Calculating totals...');
    console.log('Found journal items:', $('.journal-item').length);
    
    $('.journal-item').each(function(index) {
        const amountInput = $(this).find('.amount-input');
        const natureSelect = $(this).find('.nature-select');
        
        console.log(`Item ${index + 1}:`);
        console.log('  - Amount input found:', amountInput.length > 0);
        console.log('  - Nature select found:', natureSelect.length > 0);
        
        // Get amount value - ensure we get the actual value
        let amountValue = amountInput.val();
        if (!amountValue || amountValue === '') {
            amountValue = amountInput.attr('value') || '0';
        }
        const amount = parseFloat(amountValue) || 0;
        
        // Get nature value - handle both regular select and Select2
        let nature = natureSelect.val();
        if (!nature || nature === '') {
            // Try to get from selected option
            nature = natureSelect.find('option:selected').val() || '';
        }
        
        console.log(`  - Amount value: ${amountValue}, parsed: ${amount}`);
        console.log(`  - Nature value: ${nature}`);
        
        if (nature === 'debit') {
            totalDebit += amount;
            console.log(`  - Added to debit: ${amount}`);
        } else if (nature === 'credit') {
            totalCredit += amount;
            console.log(`  - Added to credit: ${amount}`);
        } else {
            console.log(`  - Warning: Unknown nature "${nature}" for amount ${amount}`);
        }
    });
    
    const balance = totalDebit - totalCredit;
    
    console.log(`Final Totals: Debit = ${totalDebit}, Credit = ${totalCredit}, Balance = ${balance}`);
    
    // Ensure all values are formatted to 2 decimal places with comma separators
    $('#total-debit').text('TZS ' + Number(totalDebit).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#total-credit').text('TZS ' + Number(totalCredit).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#balance').text('TZS ' + Number(balance).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    
    // Update balance color and show/hide submit button
    // Use Math.abs() to handle floating point precision issues
    if (Math.abs(balance) < 0.01 && $('.journal-item').length > 0) {
        $('#balance').removeClass('text-warning text-danger').addClass('text-success');
        $('#submitBtn').show();
        $('#balance-status').show();
        console.log('Entry is balanced - showing submit button');
    } else {
        if (balance > 0) {
            $('#balance').removeClass('text-success text-danger').addClass('text-warning');
        } else {
            $('#balance').removeClass('text-success text-warning').addClass('text-danger');
        }
        $('#submitBtn').hide();
        $('#balance-status').hide();
        console.log('Entry is not balanced - hiding submit button');
    }
    }

function validateAndSubmit() {
    // Remove TZS prefix and comma separators before parsing
    const balanceText = $('#balance').text().replace('TZS ', '').replace(/,/g, '');
    const balance = parseFloat(balanceText) || 0;
    
    if (Math.abs(balance) >= 0.01) {
        Swal.fire({
            title: 'Unbalanced Entry',
            text: 'The journal entry is not balanced. Debit and Credit totals must be equal.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
    } else {
        Swal.fire({
            title: 'Entry Validated',
            text: 'The journal entry is balanced and ready to save.',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
}

    // Calculate totals on page load
    $(document).ready(function() {
        // Wait a bit longer to ensure all elements are properly loaded
        setTimeout(function() {
            console.log('Document ready - calculating totals...');
            calculateTotals();
        }, 500);
    });
    
    // Also calculate totals when window is fully loaded
    $(window).on('load', function() {
        setTimeout(function() {
            console.log('Window loaded - calculating totals...');
            calculateTotals();
        }, 100);
    });

// Period lock check helper
function checkPeriodLock(date, onResult) {
    if (!date) return;
    $.ajax({
        url: '{{ route('settings.period-closing.check-date') }}',
        method: 'GET',
        data: { date: date },
        success: function(response) {
            if (typeof onResult === 'function') onResult(response);
        },
        error: function() {
            console.error('Failed to check period lock status.');
        }
    });
}

// Warn when user selects a locked period date
$('.transaction-date').on('change', function() {
    const date = $(this).val();
    checkPeriodLock(date, function(response) {
        if (response.locked) {
            Swal.fire({
                title: 'Locked Period',
                text: response.message || 'The selected period is locked. Please choose another date.',
                icon: 'warning'
            });
        }
    });
});

// Form validation + period lock enforcement
$('#journalForm').on('submit', function(e) {
    // Remove TZS prefix and comma separators before parsing
    const balanceText = $('#balance').text().replace('TZS ', '').replace(/,/g, '');
    const balance = parseFloat(balanceText) || 0;
    
    if (Math.abs(balance) >= 0.01) {
        e.preventDefault();
        Swal.fire({
            title: 'Cannot Save',
            text: 'The journal entry must be balanced before saving. Debit and Credit totals must be equal.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return false;
    }
    
    if ($('.journal-item').length === 0) {
        e.preventDefault();
        Swal.fire({
            title: 'No Entries',
            text: 'Please add at least one journal entry before saving.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return false;
    }

    const form = this;
    const date = $('.transaction-date').val();
    if (!date) {
        return true;
    }

    // If we already passed the check once, allow submit
    if ($(form).data('period-checked') === true) {
        return true;
    }

    e.preventDefault();

    checkPeriodLock(date, function(response) {
        if (response.locked) {
            Swal.fire({
                title: 'Locked Period',
                text: response.message || 'The selected period is locked. Please choose another date.',
                icon: 'error'
            });
        } else {
            $(form).data('period-checked', true);
            form.submit();
        }
    });
});
</script>
