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
        <p class="text-muted mb-2">Chagua matawi yanayoweza kuona bidhaa hii katika hesabu na mauzo (au acha bila kuchagua kwa <strong>matawi yote</strong>).</p>
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
@else
@php
    $preserveIsActive = old('is_active') !== null ? (bool) old('is_active') : (bool) ($item->is_active ?? true);
    $preserveTrackStock = old('track_stock') !== null ? (bool) old('track_stock') : (bool) ($item->track_stock ?? true);
    $preserveTrackExpiry = old('track_expiry') !== null ? (bool) old('track_expiry') : (bool) ($item->track_expiry ?? false);
    $preserveHasWholesale = old('has_wholesale') !== null ? (bool) old('has_wholesale') : (bool) ($item->has_wholesale ?? false);
    $preserveHasSalesAccount = old('has_different_sales_revenue_account') !== null
        ? (bool) old('has_different_sales_revenue_account')
        : (bool) ($item->has_different_sales_revenue_account ?? false);
@endphp
<input type="hidden" name="unit_price" value="{{ old('unit_price', $item->unit_price ?? 0) }}">
<input type="hidden" name="cost_price" value="{{ old('cost_price', $item->cost_price ?? 0) }}">
<input type="hidden" name="minimum_stock" value="{{ old('minimum_stock', $item->minimum_stock ?? 0) }}">
<input type="hidden" name="maximum_stock" value="{{ old('maximum_stock', $item->maximum_stock ?? 0) }}">
<input type="hidden" name="reorder_level" value="{{ old('reorder_level', $item->reorder_level ?? 0) }}">
@if($preserveIsActive)<input type="hidden" name="is_active" value="1">@endif
@if($preserveTrackStock)<input type="hidden" name="track_stock" value="1">@endif
@if($preserveTrackExpiry)<input type="hidden" name="track_expiry" value="1">@endif
@if($preserveHasWholesale)
<input type="hidden" name="has_wholesale" value="1">
<input type="hidden" name="wholesale_unit_price" value="{{ old('wholesale_unit_price', $item->wholesale_unit_price ?? 0) }}">
@endif
@if($preserveHasSalesAccount)
<input type="hidden" name="has_different_sales_revenue_account" value="1">
<input type="hidden" name="sales_revenue_account_id" value="{{ old('sales_revenue_account_id', $item->sales_revenue_account_id ?? '') }}">
@endif
@endif

<script nonce="{{ $cspNonce ?? '' }}">
document.addEventListener('DOMContentLoaded', function() {
    const itemTypeSelect = document.getElementById('item_type');
    const productFields = document.querySelectorAll('.field-product');

    function toggleFields() {
        const isService = itemTypeSelect.value === 'service';

        productFields.forEach((field) => {
            field.style.display = isService ? 'none' : '';
            field.style.visibility = isService ? 'hidden' : 'visible';
        });

        if (isService) {
            ['cost_price', 'minimum_stock', 'maximum_stock', 'reorder_level'].forEach((name) => {
                const input = document.querySelector(`input[name="${name}"]:not([type="hidden"])`);
                if (input) {
                    input.value = '';
                    input.removeAttribute('required');
                }
            });
        }
    }

    toggleFields();

    itemTypeSelect.addEventListener('change', toggleFields);

    if (typeof $ !== 'undefined' && $.fn.select2) {
        $(itemTypeSelect).on('change', toggleFields);
    }
});
</script>
