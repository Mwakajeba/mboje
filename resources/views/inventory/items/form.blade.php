@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-2"></i>
    Please fix the following errors:
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Basic Information -->
<div class="row">
    <div class="col-12">
        <h6 class="text-uppercase">Basic Information</h6>
        <p class="text-muted mb-2">Items are company-wide and can be stocked at any location.</p>
        <hr>
    </div>
</div>

<div class="row">
    <!-- Product Type -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Product Type <span class="text-danger">*</span></label>
        <select name="item_type" id="item_type" class="form-select select2-single @error('item_type') is-invalid @enderror" >
            <option value="">Select Product Type</option>
            <option value="product" {{ old('item_type', $item->item_type ?? '') == 'product' ? 'selected' : '' }}>Product</option>
            <option value="service" {{ old('item_type', $item->item_type ?? '') == 'service' ? 'selected' : '' }}>Service</option>
        </select>
        @error('item_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <!-- Item Code -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Item Code <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
            value="{{ old('code', $item->code ?? '') }}" placeholder="Enter item code" >
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Item Name -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Item Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $item->name ?? '') }}" placeholder="Enter item name" >
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Description -->
    <div class="col-md-12 mb-3 field-product">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                  rows="3" placeholder="Enter item description">{{ old('description', $item->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Category -->
    <div class="col-md-4 mb-3">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-select select2-single @error('category_id') is-invalid @enderror" required>
            <option value="">Select Category</option>
            @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ old('category_id', $item->category_id ?? ($prefillCategoryId ?? '')) == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>


    <!-- Unit of Measure -->
    <div class="col-md-4 mb-3">
        <label class="form-label">Unit of Measure<span class="text-danger">*</span></label>
        <input type="text" name="unit_of_measure" class="form-control @error('unit_of_measure') is-invalid @enderror"
            value="{{ old('unit_of_measure', $item->unit_of_measure ?? '') }}" 
            placeholder="e.g., pieces, kg, liters" required>
        @error('unit_of_measure') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<!-- Pricing Information -->
<div class="row pricing-section">
    <div class="col-12">
        <h6 class="text-uppercase">Pricing Information</h6>
        <p class="text-muted small mb-0">Default cost and selling price. Use <strong>Prices by branch</strong> and <strong>Prices by location</strong> below as the source of truth per location; these are fallbacks when no branch/location price is set.</p>
        <hr>
    </div>
</div>

<div class="row pricing-section">
    <!-- Cost Price (default) -->
    <div class="col-md-6 mb-3 field-product">
        <label class="form-label">Default cost price</label>
        <input type="number" step="0.01" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror"
            value="{{ old('cost_price', $item->cost_price ?? '') }}" placeholder="0.00">
        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Selling Price (default only) -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Default selling price <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror"
            value="{{ old('unit_price', $item->unit_price ?? '') }}" placeholder="0.00" >
        <small class="text-muted">Used only when no branch/location price is set.</small>
        @error('unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@php
    $wholesaleChecked = old('has_wholesale') !== null
        ? (bool) old('has_wholesale')
        : (bool) ($item->has_wholesale ?? false);
@endphp
<div class="row pricing-section">
    <div class="col-md-6 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="has_wholesale" value="1" id="has_wholesale"
                {{ $wholesaleChecked ? 'checked' : '' }}>
            <label class="form-check-label" for="has_wholesale">Wholesale pricing</label>
        </div>
        <small class="text-muted">When enabled, staff can choose <strong>retail</strong> or <strong>wholesale</strong> per line on invoices, POS, and cash sales. Retail stays the default selling price above.</small>
    </div>
    <div class="col-md-6 mb-3" id="wholesale_unit_price_wrap">
        <label class="form-label" for="wholesale_unit_price">Default wholesale unit price</label>
        <input type="number" step="0.01" min="0" name="wholesale_unit_price" id="wholesale_unit_price"
            class="form-control @error('wholesale_unit_price') is-invalid @enderror"
            value="{{ old('wholesale_unit_price', $item->wholesale_unit_price ?? '') }}"
            placeholder="Required when wholesale is on">
        @error('wholesale_unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Override per branch or location in the tables below when set.</small>
    </div>
</div>

@if(isset($item) && isset($branches) && $branches->isNotEmpty())
<!-- Prices by branch (override cost & selling price per branch) -->
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-uppercase">Prices by branch</h6>
        <p class="text-muted small mb-2">Set different cost and selling prices per branch. Leave blank to use the default prices above.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>Cost price</th>
                        <th>Retail (selling)</th>
                        <th>Wholesale</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $branch)
                    @php $bp = $branchPricesByBranch[$branch->id] ?? null; @endphp
                    <tr>
                        <td>{{ $branch->name }}</td>
                        <td>
                            <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}][cost_price]" class="form-control form-control-sm"
                                value="{{ old("branch_prices.{$branch->id}.cost_price", $bp ? $bp->cost_price : '') }}" placeholder="{{ $item->cost_price }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}][unit_price]" class="form-control form-control-sm"
                                value="{{ old("branch_prices.{$branch->id}.unit_price", $bp ? $bp->unit_price : '') }}" placeholder="{{ $item->unit_price }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}][wholesale_unit_price]" class="form-control form-control-sm"
                                value="{{ old("branch_prices.{$branch->id}.wholesale_unit_price", $bp && $bp->wholesale_unit_price !== null ? $bp->wholesale_unit_price : '') }}" placeholder="{{ $item->wholesale_unit_price ?? '—' }}">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(isset($locations) && $locations->isNotEmpty())
<div class="row mt-2">
    <div class="col-12">
        <h6 class="text-uppercase mt-2">Prices by location (optional)</h6>
        <p class="text-muted small mb-2">Override prices for specific locations. Falls back to branch price or default if not set.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Cost price</th>
                        <th>Retail (selling)</th>
                        <th>Wholesale</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $loc)
                    @php $lp = $locationPricesByLocation[$loc->id] ?? null; @endphp
                    <tr>
                        <td>{{ $loc->name }} @if($loc->branch) <small class="text-muted">({{ $loc->branch->name }})</small> @endif</td>
                        <td>
                            <input type="number" step="0.01" name="location_prices[{{ $loc->id }}][cost_price]" class="form-control form-control-sm"
                                value="{{ old("location_prices.{$loc->id}.cost_price", $lp ? $lp->cost_price : '') }}" placeholder="">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="location_prices[{{ $loc->id }}][unit_price]" class="form-control form-control-sm"
                                value="{{ old("location_prices.{$loc->id}.unit_price", $lp ? $lp->unit_price : '') }}" placeholder="">
                        </td>
                        <td>
                            <input type="number" step="0.01" name="location_prices[{{ $loc->id }}][wholesale_unit_price]" class="form-control form-control-sm"
                                value="{{ old("location_prices.{$loc->id}.wholesale_unit_price", $lp && $lp->wholesale_unit_price !== null ? $lp->wholesale_unit_price : '') }}" placeholder="">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endif

<!-- Stock Management -->
<div class="row field-product">
    <div class="col-12">
        <h6 class="text-uppercase">Stock Management</h6>
        <hr>
    </div>
</div>

<div class="row field-product">
    <!-- Minimum Stock -->
    <div class="col-md-4 mb-3 field-product">
        <label class="form-label">Minimum Stock</label>
        <input type="number" name="minimum_stock" class="form-control @error('minimum_stock') is-invalid @enderror"
            value="{{ old('minimum_stock', $item->minimum_stock ?? 0) }}" placeholder="0">
        @error('minimum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Maximum Stock -->
    <div class="col-md-4 mb-3 field-product">
        <label class="form-label">Maximum Stock</label>
        <input type="number" name="maximum_stock" class="form-control @error('maximum_stock') is-invalid @enderror"
            value="{{ old('maximum_stock', $item->maximum_stock ?? '') }}" placeholder="0">
        @error('maximum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Reorder Level -->
    <div class="col-md-4 mb-3 field-product">
        <label class="form-label">Reorder Level</label>
        <input type="number" name="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror"
            value="{{ old('reorder_level', $item->reorder_level ?? '') }}" placeholder="0">
        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if(isset($item))
    <div class="col-md-12 mb-2">
        <div class="alert alert-info py-2 mb-0">
            <strong>Current Stock:</strong> {{ number_format($item->current_stock ?? 0, 2) }} {{ $item->unit_of_measure }}
        </div>
    </div>
    @endif
</div>

<!-- Accounting Integration section removed -->

<!-- Options -->
<div class="row">
    <div class="col-12">
        <h6 class="text-uppercase">Options</h6>
        <hr>
    </div>
</div>

<div class="row">
    <!-- Is Active -->
    <div class="col-md-6 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                   {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }} 
                   id="is_active">
            <label class="form-check-label" for="is_active">
                Active Item
            </label>
        </div>
    </div>

    <!-- Track Stock -->
    <div class="col-md-6 mb-3 field-product">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="track_stock" value="1" 
                   {{ old('track_stock', $item->track_stock ?? true) ? 'checked' : '' }} 
                   id="track_stock">
            <label class="form-check-label" for="track_stock">
                Track Stock
            </label>
        </div>
    </div>

    <!-- Track Expiry -->
    <div class="col-md-6 mb-3 field-product">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="track_expiry" value="1" 
                   {{ old('track_expiry', $item->track_expiry ?? false) ? 'checked' : '' }} 
                   id="track_expiry">
            <label class="form-check-label" for="track_expiry">
                Track Expiry Dates
            </label>
        </div>
        <small class="text-muted">Enable expiry date tracking for perishable items. Warning days are configured globally in Settings.</small>
    </div>
</div>

<!-- Sales Revenue Account -->
<div class="row">
    <div class="col-12">
        <h6 class="text-uppercase">Sales Revenue Account</h6>
        <hr>
    </div>
</div>

<div class="row">
    <!-- Has Different Sales Revenue Account -->
    <div class="col-md-12 mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="has_different_sales_revenue_account" value="1" 
                   {{ old('has_different_sales_revenue_account', $item->has_different_sales_revenue_account ?? false) ? 'checked' : '' }} 
                   id="has_different_sales_revenue_account">
            <label class="form-check-label" for="has_different_sales_revenue_account">
                Use Different Sales Revenue Account
            </label>
        </div>
        <small class="text-muted">If enabled, this item will use a specific sales revenue account instead of the default from Inventory Settings.</small>
    </div>

    <!-- Sales Revenue Account -->
    <div class="col-md-6 mb-3" id="sales_revenue_account_field" style="display: none;">
        <label class="form-label">Sales Revenue Account</label>
        <select name="sales_revenue_account_id" class="form-select select2-single @error('sales_revenue_account_id') is-invalid @enderror">
            <option value="">Select Sales Revenue Account</option>
            @foreach($salesAccounts ?? [] as $account)
            <option value="{{ $account->id }}" 
                    {{ old('sales_revenue_account_id', $item->sales_revenue_account_id ?? '') == $account->id ? 'selected' : '' }}>
                {{ $account->account_code }} - {{ $account->account_name }}
            </option>
            @endforeach
        </select>
        @error('sales_revenue_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Default: {{ \App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value') ? \App\Models\ChartAccount::find(\App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value'))->account_code . ' - ' . \App\Models\ChartAccount::find(\App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value'))->account_name : 'Not set' }}</small>
    </div>
</div>

    

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const itemTypeSelect = document.getElementById('item_type');
    const productFields = document.querySelectorAll('.field-product');
    const pricingSections = document.querySelectorAll('.pricing-section');
    const costPriceInput = document.querySelector('input[name="cost_price"]');

    function toggleFields() {
        const isService = itemTypeSelect.value === 'service';
        
        if (isService) {
            // Hide product-only fields
            productFields.forEach((field) => {
                field.style.display = 'none';
                field.style.visibility = 'hidden';
            });
            
            // Clear and disable product-specific fields for services
            const costPriceInput = document.querySelector('input[name="cost_price"]');
            const minimumStockInput = document.querySelector('input[name="minimum_stock"]');
            const maximumStockInput = document.querySelector('input[name="maximum_stock"]');
            const reorderLevelInput = document.querySelector('input[name="reorder_level"]');
            const trackStockInput = document.querySelector('input[name="track_stock"]');
            
            if (costPriceInput) {
                costPriceInput.value = '';
                costPriceInput.removeAttribute('required');
            }
            if (minimumStockInput) {
                minimumStockInput.value = '';
                minimumStockInput.removeAttribute('required');
            }
            if (maximumStockInput) {
                maximumStockInput.value = '';
            }
            if (reorderLevelInput) {
                reorderLevelInput.value = '';
            }
            if (trackStockInput) {
                trackStockInput.checked = false;
            }
            
        } else {
            // Show all fields for non-service items
            productFields.forEach((field) => {
                field.style.display = '';
                field.style.visibility = 'visible';
            });
            
            // Re-enable product-specific fields
            const trackStockInput = document.querySelector('input[name="track_stock"]');
            
            if (trackStockInput) {
                trackStockInput.checked = true;
            }
        }
    }
    
    function calculateOpeningBalanceValue() { /* opening balance UI deprecated */ }
    
    function toggleOpeningBalanceFields() { /* opening balance UI deprecated */ }
    
    // If editing and item already has opening balance, keep quantity readonly and do not recalc
    const alreadyHasOpening = false;

    function toggleWholesaleFields() {
        const cb = document.getElementById('has_wholesale');
        const wrap = document.getElementById('wholesale_unit_price_wrap');
        const input = document.getElementById('wholesale_unit_price');
        if (!cb || !wrap) return;
        if (cb.checked) {
            wrap.style.display = '';
            if (input) input.removeAttribute('disabled');
        } else {
            wrap.style.display = 'none';
            if (input) {
                input.value = '';
                input.setAttribute('disabled', 'disabled');
            }
        }
    }

    const hasWholesaleCb = document.getElementById('has_wholesale');
    if (hasWholesaleCb) {
        hasWholesaleCb.addEventListener('change', toggleWholesaleFields);
        toggleWholesaleFields();
    }

    // Initial toggle on page load
    toggleFields();
    toggleOpeningBalanceFields();
    
    // Toggle fields when item type changes (handle both regular and Select2)
    itemTypeSelect.addEventListener('change', function() {
        toggleFields();
        toggleOpeningBalanceFields();
    });
    
    // Handle Select2 change event if Select2 is being used
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(itemTypeSelect).on('change', function() {
            toggleFields();
            toggleOpeningBalanceFields();
        });
    }
    
    // Toggle opening balance fields when checkbox changes
    // opening balance checkbox removed
    
    // Calculate opening balance value when cost price or quantity changes
    if (costPriceInput && !alreadyHasOpening) {
        costPriceInput.addEventListener('input', calculateOpeningBalanceValue);
    }
    
    // opening balance inputs removed
    
    // Toggle sales revenue account field
    const hasDifferentSalesRevenueAccountCheckbox = document.getElementById('has_different_sales_revenue_account');
    const salesRevenueAccountField = document.getElementById('sales_revenue_account_field');
    
    function toggleSalesRevenueAccountField() {
        if (hasDifferentSalesRevenueAccountCheckbox && salesRevenueAccountField) {
            if (hasDifferentSalesRevenueAccountCheckbox.checked) {
                salesRevenueAccountField.style.display = 'block';
            } else {
                salesRevenueAccountField.style.display = 'none';
                // Clear the value when unchecked
                const salesRevenueAccountSelect = document.querySelector('select[name="sales_revenue_account_id"]');
                if (salesRevenueAccountSelect) {
                    salesRevenueAccountSelect.value = '';
                }
            }
        }
    }
    
    // Initial toggle
    toggleSalesRevenueAccountField();
    
    // Toggle on checkbox change
    if (hasDifferentSalesRevenueAccountCheckbox) {
        hasDifferentSalesRevenueAccountCheckbox.addEventListener('change', toggleSalesRevenueAccountField);
    }
});
</script>
