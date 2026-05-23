@extends('layouts.main')

@section('title', 'Ona Repoti — Hesabu za Kila Siku')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Kila Siku', 'url' => route('purchases.daily-accounts.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Ona Repoti', 'url' => '#', 'icon' => 'bx bx-file']
        ]" />

        <h6 class="mb-0 text-uppercase">Ona Repoti</h6>
        <hr />

        <div class="card radius-10">
            <div class="card-body">
                <p class="text-muted">Chagua mfanyakazi na tarehe. Ripoti inaonyesha mauzo, matumizi, manunuzi, baki, na taarifa za stoo.</p>

                <form id="daily-report-form" method="get" action="{{ route('purchases.daily-accounts.report.show') }}" target="_blank">
                    <div class="mb-3">
                        <label for="report_employee_id" class="form-label fw-bold">Mfanyakazi <span class="text-danger">*</span></label>
                        <select id="report_employee_id" name="employee_id" class="form-select report-employee-select" required>
                            <option value=""></option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->display_name }}@if(!empty($emp->employee_number)) ({{ $emp->employee_number }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="report_entry_date" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                        <input type="date" id="report_entry_date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-show me-1"></i> Angalia repoti
                    </button>
                </form>
                <a href="{{ route('purchases.daily-accounts.index') }}" class="btn btn-outline-secondary ms-2">
                    <i class="bx bx-arrow-back me-1"></i> Rudi
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    if ($.fn.select2) {
        $('.report-employee-select').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Tafuta mfanyakazi…',
            allowClear: true
        });
    }

    $('#daily-report-form').on('submit', function (e) {
        if (!$('#report_employee_id').val()) {
            e.preventDefault();
            alert('Chagua mfanyakazi.');
            return;
        }
        if (!$('#report_entry_date').val()) {
            e.preventDefault();
            alert('Chagua tarehe.');
        }
    });
});
</script>
@endpush
