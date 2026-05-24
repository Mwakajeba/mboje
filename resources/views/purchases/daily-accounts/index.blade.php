@extends('layouts.main')

@section('title', 'Hesabu za Kila Siku (Wafanyakazi)')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Kila Siku (Wafanyakazi)', 'url' => '#', 'icon' => 'bx bx-calendar-check']
        ]" />

        <h6 class="mb-0 text-uppercase">Hesabu za Kila Siku (Wafanyakazi)</h6>
        <hr />

        <p class="text-muted mb-4">Chagua kitendo cha kufanya kwa mfanyakazi wa leo.</p>

        <div class="row">
            @can('record purchase payment')
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card border-success h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3">
                            <i class="bx bx-wallet fs-1 text-success"></i>
                        </div>
                        <h5 class="card-title">Ingiza Mauzo/Mapato</h5>
                        <p class="card-text flex-grow-1 small">Rekodi mauzo/mapato ya mfanyakazi kwa siku.</p>
                        <button type="button" class="btn btn-success mt-auto" id="btnOpenIngizaMauzo">
                            <i class="bx bx-plus me-1"></i> Ingiza Mauzo/Mapato
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card border-warning h-100" style="border-color: #f59e0b !important;">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3">
                            <i class="bx bx-receipt fs-1" style="color: #f59e0b;"></i>
                        </div>
                        <h5 class="card-title">Ingiza Matumizi/Manunuzi</h5>
                        <p class="card-text flex-grow-1 small">Weka matumizi kutoka salio la awali au manunuzi wa cash.</p>
                        <a href="{{ route('purchases.daily-accounts.matumizi-manunuzi') }}" class="btn text-dark mt-auto" style="background-color: #f59e0b;">
                            <i class="bx bx-right-arrow-alt me-1"></i> Endelea
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card border-info h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3">
                            <i class="bx bx-package fs-1 text-info"></i>
                        </div>
                        <h5 class="card-title">Ingiza Taarifa za stoo</h5>
                        <p class="card-text flex-grow-1 small">Weka mistari ya stoo: maelezo na thamani/idadi kwa mfanyakazi.</p>
                        <button type="button" class="btn btn-info text-white mt-auto" id="btnOpenIngizaStoo">
                            <i class="bx bx-plus me-1"></i> Weka stoo
                        </button>
                    </div>
                </div>
            </div>
            @endcan

            @can('view purchases')
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="card border-primary h-100">
                    <div class="card-body text-center d-flex flex-column">
                        <div class="mb-3">
                            <i class="bx bx-file fs-1 text-primary"></i>
                        </div>
                        <h5 class="card-title">Ona Repoti</h5>
                        <p class="card-text flex-grow-1 small">Angalia hesabu za fedha: malipo, matumizi, manunuzi, na stoo.</p>
                        <a href="{{ route('purchases.daily-accounts.report') }}" class="btn btn-primary mt-auto">
                            <i class="bx bx-show me-1"></i> Ona Repoti
                        </a>
                    </div>
                </div>
            </div>
            @endcan
        </div>

        <div class="mt-2">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i> Rudi kwa Manunuzi
            </a>
        </div>
    </div>
</div>

@can('record purchase payment')
@include('purchases.daily-accounts.partials.employee-lines-modal', [
    'modalId' => 'ingizaMauzoModal',
    'modalTitle' => 'Ingiza Mauzo/Mapato',
    'modalIcon' => 'bx bx-wallet',
    'formId' => 'ingiza-mauzo-form',
    'errorsId' => 'ingiza-mauzo-form-errors',
    'submitId' => 'ingiza-mauzo-submit',
    'employeeSelectId' => 'mauzo_employee_id',
    'employeeSelectClass' => 'mauzo-employee-select',
    'entryDateId' => 'mauzo_entry_date',
    'linesBodyId' => 'mauzo-lines-body',
    'linesTotalId' => 'mauzo-lines-total',
    'addLineBtnId' => 'btnAddMauzoLine',
    'linesLabel' => 'Mistari ya mauzo/mapato',
    'linePlaceholder' => 'Maelezo ya alichouza / mapato',
    'amountField' => 'kiasi',
    'amountLabel' => 'Kiasi',
    'submitBtnClass' => 'btn-success',
    'employees' => $employees,
])

@include('purchases.daily-accounts.partials.employee-lines-modal', [
    'modalId' => 'ingizaStooModal',
    'modalTitle' => 'Ingiza Taarifa za stoo',
    'modalIcon' => 'bx bx-package',
    'formId' => 'ingiza-stoo-form',
    'errorsId' => 'ingiza-stoo-form-errors',
    'submitId' => 'ingiza-stoo-submit',
    'employeeSelectId' => 'stoo_employee_id',
    'employeeSelectClass' => 'stoo-employee-select',
    'entryDateId' => 'stoo_entry_date',
    'linesBodyId' => 'stoo-lines-body',
    'linesTotalId' => 'stoo-lines-total',
    'addLineBtnId' => 'btnAddStooLine',
    'linesLabel' => 'Mistari ya stoo',
    'linePlaceholder' => 'Maelezo (mf. zilizouzwa, zizonunuliwa, baki…)',
    'showBidhaa' => true,
    'bidhaaInputId' => 'stoo_bidhaa',
    'amountField' => 'thamani',
    'amountLabel' => 'Thamani/Idadi',
    'showLinesTotal' => false,
    'submitBtnClass' => 'btn-info text-white',
    'employees' => $employees,
])
@endcan
@endsection

@push('scripts')
@include('purchases.daily-accounts.partials.daily-lines-form-init')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    initDailyLinesForm({
        modalId: 'ingizaMauzoModal',
        form: '#ingiza-mauzo-form',
        errors: '#ingiza-mauzo-form-errors',
        submit: '#ingiza-mauzo-submit',
        employeeSelect: '#mauzo_employee_id',
        entryDate: '#mauzo_entry_date',
        linesBody: '#mauzo-lines-body',
        linesTotal: '#mauzo-lines-total',
        addLineBtn: '#btnAddMauzoLine',
        openBtn: '#btnOpenIngizaMauzo',
        storeUrl: @json(route('purchases.daily-accounts.mauzo.store')),
        linePlaceholder: 'Maelezo ya alichouza / mapato',
        amountField: 'kiasi',
        successDefault: 'Mauzo/Mapato yamehifadhiwa.'
    });

    initDailyLinesForm({
        modalId: 'ingizaStooModal',
        form: '#ingiza-stoo-form',
        errors: '#ingiza-stoo-form-errors',
        submit: '#ingiza-stoo-submit',
        employeeSelect: '#stoo_employee_id',
        entryDate: '#stoo_entry_date',
        linesBody: '#stoo-lines-body',
        linesTotal: '#stoo-lines-total',
        addLineBtn: '#btnAddStooLine',
        openBtn: '#btnOpenIngizaStoo',
        storeUrl: @json(route('purchases.daily-accounts.stoo.store')),
        linePlaceholder: 'Maelezo (mf. zilizouzwa, zizonunuliwa, baki…)',
        amountField: 'thamani',
        amountInputType: 'text',
        showLinesTotal: false,
        bidhaaInput: '#stoo_bidhaa',
        successDefault: 'Taarifa za stoo zimehifadhiwa.'
    });
});
</script>
@endpush
