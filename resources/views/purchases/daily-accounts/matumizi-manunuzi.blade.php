@extends('layouts.main')

@section('title', 'Ingiza Matumizi/Manunuzi')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Kila Siku', 'url' => route('purchases.daily-accounts.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Matumizi/Manunuzi', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <h6 class="mb-0 text-uppercase">Ingiza Matumizi / Manunuzi</h6>
        <hr />

        <p class="text-muted mb-4">Chagua mfanyakazi, tarehe, na andika mistari ya matumizi au manunuzi.</p>

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5 mb-4">
                <div class="card border-warning h-100" style="border-color: #f59e0b !important;">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3">
                            <i class="bx bx-receipt fs-1" style="color: #f59e0b;"></i>
                        </div>
                        <h5 class="card-title">Ingiza Matumizi</h5>
                        <p class="card-text flex-grow-1 small">Rekodi matumizi kutoka salio la malipo ya awali (si manunuzi wa cash).</p>
                        <button type="button" class="btn text-dark mt-auto" id="btnOpenIngizaMatumizi" style="background-color: #f59e0b;">
                            <i class="bx bx-plus me-1"></i> Ingiza Matumizi
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-5 mb-4">
                <div class="card border-secondary h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3">
                            <i class="bx bx-cart fs-1 text-secondary"></i>
                        </div>
                        <h5 class="card-title">Ingiza Manunuzi</h5>
                        <p class="card-text flex-grow-1 small">Rekodi manunuzi wa cash unaotumia malipo ya awali.</p>
                        <button type="button" class="btn btn-secondary mt-auto" id="btnOpenIngizaManunuzi">
                            <i class="bx bx-plus me-1"></i> Ingiza Manunuzi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('purchases.daily-accounts.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bx bx-arrow-back me-1"></i> Rudi
        </a>
    </div>
</div>

@include('purchases.daily-accounts.partials.employee-lines-modal', [
    'modalId' => 'ingizaMatumiziModal',
    'modalTitle' => 'Ingiza Matumizi',
    'modalIcon' => 'bx bx-receipt',
    'formId' => 'ingiza-matumizi-form',
    'errorsId' => 'ingiza-matumizi-form-errors',
    'submitId' => 'ingiza-matumizi-submit',
    'employeeSelectId' => 'matumizi_employee_id',
    'employeeSelectClass' => 'matumizi-employee-select',
    'entryDateId' => 'matumizi_entry_date',
    'linesBodyId' => 'matumizi-lines-body',
    'linesTotalId' => 'matumizi-lines-total',
    'addLineBtnId' => 'btnAddMatumiziLine',
    'linesLabel' => 'Mistari ya matumizi',
    'linePlaceholder' => 'Maelezo ya matumizi',
    'submitBtnClass' => 'btn-warning text-dark',
    'employees' => $employees,
])

@include('purchases.daily-accounts.partials.employee-lines-modal', [
    'modalId' => 'ingizaManunuziModal',
    'modalTitle' => 'Ingiza Manunuzi',
    'modalIcon' => 'bx bx-cart',
    'formId' => 'ingiza-manunuzi-form',
    'errorsId' => 'ingiza-manunuzi-form-errors',
    'submitId' => 'ingiza-manunuzi-submit',
    'employeeSelectId' => 'manunuzi_employee_id',
    'employeeSelectClass' => 'manunuzi-employee-select',
    'entryDateId' => 'manunuzi_entry_date',
    'linesBodyId' => 'manunuzi-lines-body',
    'linesTotalId' => 'manunuzi-lines-total',
    'addLineBtnId' => 'btnAddManunuziLine',
    'linesLabel' => 'Mistari ya manunuzi',
    'linePlaceholder' => 'Maelezo ya manunuzi',
    'submitBtnClass' => 'btn-secondary',
    'employees' => $employees,
])
@endsection

@push('scripts')
@include('purchases.daily-accounts.partials.daily-lines-form-init')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    initDailyLinesForm({
        modalId: 'ingizaMatumiziModal',
        form: '#ingiza-matumizi-form',
        errors: '#ingiza-matumizi-form-errors',
        submit: '#ingiza-matumizi-submit',
        employeeSelect: '#matumizi_employee_id',
        entryDate: '#matumizi_entry_date',
        linesBody: '#matumizi-lines-body',
        linesTotal: '#matumizi-lines-total',
        addLineBtn: '#btnAddMatumiziLine',
        openBtn: '#btnOpenIngizaMatumizi',
        storeUrl: @json(route('purchases.daily-accounts.matumizi.store')),
        linePlaceholder: 'Maelezo ya matumizi',
        amountField: 'kiasi',
        successDefault: 'Matumizi yamehifadhiwa.'
    });

    initDailyLinesForm({
        modalId: 'ingizaManunuziModal',
        form: '#ingiza-manunuzi-form',
        errors: '#ingiza-manunuzi-form-errors',
        submit: '#ingiza-manunuzi-submit',
        employeeSelect: '#manunuzi_employee_id',
        entryDate: '#manunuzi_entry_date',
        linesBody: '#manunuzi-lines-body',
        linesTotal: '#manunuzi-lines-total',
        addLineBtn: '#btnAddManunuziLine',
        openBtn: '#btnOpenIngizaManunuzi',
        storeUrl: @json(route('purchases.daily-accounts.manunuzi.store')),
        linePlaceholder: 'Maelezo ya manunuzi',
        amountField: 'kiasi',
        successDefault: 'Manunuzi yamehifadhiwa.'
    });
});
</script>
@endpush
