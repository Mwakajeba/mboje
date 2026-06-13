@php
$isEdit = isset($item);
@endphp

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bx bx-error-circle me-2"></i>
    Tafadhali rekebisha makosa yafuatayo:
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
</div>
@endif

<!-- Taarifa za Msingi -->
<div class="row">
    <div class="col-12">
        <h6 class="text-uppercase">Taarifa za Msingi</h6>
        <p class="text-muted mb-2">Chagua matawi yanayoweza kuona bidhaa hii katika hesabu na mauzo (au acha bila kuchagua kwa <strong>matawi yote</strong>). Baada ya kuhifadhi, tumia <strong>Hariri</strong> kuweka <strong>Bei kwa tawi</strong> na <strong>Bei kwa eneo</strong>.</p>
        <hr>
    </div>
</div>

@if(isset($assignableBranches) && $assignableBranches->isNotEmpty())
@php
    $selectedBranchIds = old('branch_ids', isset($item) ? $item->visibilityBranches->pluck('id')->all() : []);
    if (! is_array($selectedBranchIds)) {
        $selectedBranchIds = [];
    }
@endphp
<div class="row">
    <div class="col-12 mb-3">
        <label class="form-label">Inaonekana katika matawi</label>
        <p class="text-muted small mb-1">Usichague chochote kwa <strong>matawi yote</strong>. Vinginevyo chagua tawi moja au zaidi (kutoka matawi uliyopewa).</p>
        <select name="branch_ids[]" id="item_visibility_branches" class="form-select select2-multi @error('branch_ids') is-invalid @enderror" multiple="multiple" data-placeholder="Matawi yote">
            @foreach($assignableBranches as $branch)
            <option value="{{ $branch->id }}" {{ in_array((int) $branch->id, array_map('intval', $selectedBranchIds), true) ? 'selected' : '' }}>
                {{ $branch->name }}
            </option>
            @endforeach
        </select>
        @error('branch_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @foreach($errors->get('branch_ids.*') as $msg)
        <div class="invalid-feedback d-block">{{ $msg }}</div>
        @endforeach
    </div>
</div>
@endif

<div class="row">
    <!-- Aina ya Bidhaa -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Aina ya Bidhaa <span class="text-danger">*</span></label>
        <select name="item_type" id="item_type" class="form-select select2-single @error('item_type') is-invalid @enderror" >
            <option value="">Chagua aina ya bidhaa</option>
            <option value="product" {{ old('item_type', $item->item_type ?? '') == 'product' ? 'selected' : '' }}>Bidhaa</option>
            <option value="service" {{ old('item_type', $item->item_type ?? '') == 'service' ? 'selected' : '' }}>Huduma</option>
        </select>
        @error('item_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <!-- Nambari ya Bidhaa -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Nambari ya Bidhaa <span class="text-danger">*</span></label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
            value="{{ old('code', $item->code ?? '') }}" placeholder="Weka nambari ya bidhaa" >
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Jina la Bidhaa -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Jina la Bidhaa <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $item->name ?? '') }}" placeholder="Weka jina la bidhaa" >
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if($isEdit)
    <input type="hidden" name="description" value="{{ old('description', $item->description ?? '') }}">
    @endif
    {{-- Maelezo (Description) — imefichwa
    <div class="col-md-12 mb-3 field-product">
        <label class="form-label">Maelezo</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                  rows="3" placeholder="Weka maelezo ya bidhaa">{{ old('description', $item->description ?? '') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    --}}

    <!-- Kategoria -->
    <div class="col-md-4 mb-3">
        <label class="form-label">Kategoria</label>
        <select name="category_id" class="form-select select2-single @error('category_id') is-invalid @enderror" required>
            <option value="">Chagua kategoria</option>
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
        @php
            $selectedUnit = old('unit_of_measure', $item->unit_of_measure ?? '');
            $unitOptions = \App\Models\Inventory\Item::unitOfMeasureOptions();
        @endphp
        <label class="form-label">Kipimo<span class="text-danger">*</span></label>
        <select name="unit_of_measure" class="form-select select2-single @error('unit_of_measure') is-invalid @enderror" required>
            <option value="">Chagua kipimo</option>
            @foreach($unitOptions as $value => $label)
                <option value="{{ $value }}" {{ $selectedUnit === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
            @if($selectedUnit && ! array_key_exists($selectedUnit, $unitOptions))
                <option value="{{ $selectedUnit }}" selected>{{ $selectedUnit }}</option>
            @endif
        </select>
        @error('unit_of_measure') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3 field-product">
        <label class="form-label">Jina la Kifurushi</label>
        <input type="text" name="package_name" class="form-control @error('package_name') is-invalid @enderror"
            value="{{ old('package_name', $item->package_name ?? '') }}" placeholder="mf. karatasi, gunia, boksi">
        @error('package_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3 field-product">
        <label class="form-label">Idadi ya Kifurushi</label>
        <input type="number" step="0.01" min="0" name="package_quantity" class="form-control @error('package_quantity') is-invalid @enderror"
            value="{{ old('package_quantity', $item->package_quantity ?? '') }}" placeholder="mf. 12">
        @error('package_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@if(!$isEdit)
<input type="hidden" name="unit_price" value="0">
<input type="hidden" name="cost_price" value="0">
<input type="hidden" name="minimum_stock" value="0">
<input type="hidden" name="maximum_stock" value="0">
<input type="hidden" name="reorder_level" value="0">
<input type="hidden" name="is_active" value="1">
<input type="hidden" name="track_stock" value="1">
@endif

@if($isEdit)
<!-- Taarifa za Bei -->
<div class="row pricing-section">
    <div class="col-12">
        <h6 class="text-uppercase">Taarifa za Bei</h6>
        <p class="text-muted small mb-0">Gharama na bei ya kuuza chaguomsingi. Tumia <strong>Bei kwa tawi</strong> na <strong>Bei kwa eneo</strong> hapa chini kama chanzo cha kweli kwa kila eneo; hizi ni mbadala ikiwa bei ya tawi/eneo haijawekwa.</p>
        <hr>
    </div>
</div>

<div class="row pricing-section">
    <!-- Cost Price (default) -->
    <div class="col-md-6 mb-3 field-product">
        <label class="form-label">Gharama chaguomsingi</label>
        <input type="number" step="0.01" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror"
            value="{{ old('cost_price', $item->cost_price ?? '') }}" placeholder="0.00">
        @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Selling Price (default only) -->
    <div class="col-md-6 mb-3">
        <label class="form-label">Bei ya kuuza chaguomsingi <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror"
            value="{{ old('unit_price', $item->unit_price ?? 0) }}" placeholder="0.00" >
        <small class="text-muted">Inatumika tu ikiwa bei ya tawi/eneo haijawekwa.</small>
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
            <label class="form-check-label" for="has_wholesale">Bei ya jumla</label>
        </div>
        <small class="text-muted">Ikiwashwa, wafanyakazi wanaweza kuchagua <strong>rejareja</strong> au <strong>jumla</strong> kwa kila mstari kwenye ankara, POS, na mauzo ya taslimu.</small>
    </div>
    <div class="col-md-6 mb-3" id="wholesale_unit_price_wrap">
        <label class="form-label" for="wholesale_unit_price">Bei ya jumla chaguomsingi</label>
        <input type="number" step="0.01" min="0" name="wholesale_unit_price" id="wholesale_unit_price"
            class="form-control @error('wholesale_unit_price') is-invalid @enderror"
            value="{{ old('wholesale_unit_price', $item->wholesale_unit_price ?? '') }}"
            placeholder="Inahitajika bei ya jumla ikiwashwa">
        @error('wholesale_unit_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Badilisha kwa tawi au eneo kwenye jedwali hapa chini.</small>
    </div>
</div>

@if(isset($item) && isset($branches) && $branches->isNotEmpty())
<!-- Prices by branch (override cost & selling price per branch) -->
<div class="row mt-3">
    <div class="col-12">
        <h6 class="text-uppercase">Bei kwa tawi</h6>
        <p class="text-muted small mb-2">Weka gharama na bei tofauti kwa kila tawi. Acha tupu kutumia bei chaguomsingi hapo juu.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Tawi</th>
                        <th>Gharama</th>
                        <th>Rejareja (kuuza)</th>
                        <th>Jumla</th>
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
        <h6 class="text-uppercase mt-2">Bei kwa eneo (si lazima)</h6>
        <p class="text-muted small mb-2">Badilisha bei kwa maeneo maalum. Ikiwa haijawekwa, inatumia bei ya tawi au chaguomsingi.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Eneo</th>
                        <th>Gharama</th>
                        <th>Rejareja (kuuza)</th>
                        <th>Jumla</th>
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
@endif

@if($isEdit)
<!-- Usimamizi wa Stoo -->
<div class="row field-product">
    <div class="col-12">
        <h6 class="text-uppercase">Usimamizi wa Stoo</h6>
        <hr>
    </div>
</div>

<div class="row field-product">
    <!-- Minimum Stock -->
    <div class="col-md-4 mb-3 field-product">
        <label class="form-label">Stoo ya Chini</label>
        <input type="number" name="minimum_stock" class="form-control @error('minimum_stock') is-invalid @enderror"
            value="{{ old('minimum_stock', $item->minimum_stock ?? 0) }}" placeholder="0">
        @error('minimum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Maximum Stock -->
    <div class="col-md-4 mb-3 field-product">
        <label class="form-label">Stoo ya Juu</label>
        <input type="number" name="maximum_stock" class="form-control @error('maximum_stock') is-invalid @enderror"
            value="{{ old('maximum_stock', $item->maximum_stock ?? '') }}" placeholder="0">
        @error('maximum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <!-- Reorder Level -->
    <div class="col-md-4 mb-3 field-product">
        <label class="form-label">Kiwango cha Kuagiza</label>
        <input type="number" name="reorder_level" class="form-control @error('reorder_level') is-invalid @enderror"
            value="{{ old('reorder_level', $item->reorder_level ?? '') }}" placeholder="0">
        @error('reorder_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if(isset($item))
    <div class="col-md-12 mb-2">
        <div class="alert alert-info py-2 mb-0">
            <strong>Stoo ya Sasa:</strong> {{ number_format($item->current_stock ?? 0, 2) }} {{ $item->unit_of_measure }}
        </div>
    </div>
    @endif
</div>
@endif

<!-- Accounting Integration section removed -->

@if($isEdit)
<!-- Chaguo -->
<div class="row">
    <div class="col-12">
        <h6 class="text-uppercase">Chaguo</h6>
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
                Bidhaa Hai
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
                Fuatilia Stoo
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
                Fuatilia Tarehe ya Mwisho
            </label>
        </div>
        <small class="text-muted">Washa ufuatiliaji wa tarehe ya mwisho kwa bidhaa zinazoharibika. Siku za onyo zimewekwa kwenye Mipangilio.</small>
    </div>
</div>
@endif

@if($isEdit)
<!-- Akaunti ya Mapato ya Mauzo -->
<div class="row">
    <div class="col-12">
        <h6 class="text-uppercase">Akaunti ya Mapato ya Mauzo</h6>
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
                Tumia Akaunti Tofauti ya Mapato ya Mauzo
            </label>
        </div>
        <small class="text-muted">Ikiwashwa, bidhaa hii itatumia akaunti maalum ya mapato badala ya chaguomsingi kutoka Mipangilio ya Hesabu.</small>
    </div>

    <div class="col-md-6 mb-3" id="sales_revenue_account_field" style="display: none;">
        <label class="form-label">Akaunti ya Mapato ya Mauzo</label>
        <select name="sales_revenue_account_id" class="form-select select2-single @error('sales_revenue_account_id') is-invalid @enderror">
            <option value="">Chagua akaunti ya mapato ya mauzo</option>
            @foreach($salesAccounts ?? [] as $account)
            <option value="{{ $account->id }}" 
                    {{ old('sales_revenue_account_id', $item->sales_revenue_account_id ?? '') == $account->id ? 'selected' : '' }}>
                {{ $account->account_code }} - {{ $account->account_name }}
            </option>
            @endforeach
        </select>
        @error('sales_revenue_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Chaguomsingi: {{ \App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value') ? \App\Models\ChartAccount::find(\App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value'))->account_code . ' - ' . \App\Models\ChartAccount::find(\App\Models\SystemSetting::where('key', 'inventory_default_sales_account')->value('value'))->account_name : 'Haijawekwa' }}</small>
    </div>
</div>
@endif

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
