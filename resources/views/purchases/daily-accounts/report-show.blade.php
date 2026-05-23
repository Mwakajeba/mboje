@extends('layouts.main')

@section('title', 'Ripoti — Hesabu za Kila Siku')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3 no-print">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
                ['label' => 'Hesabu za Kila Siku', 'url' => route('purchases.daily-accounts.index'), 'icon' => 'bx bx-calendar-check'],
                ['label' => 'Ripoti', 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <div class="d-flex flex-wrap gap-2">
                @if(!empty($can_delete))
                <button type="button" class="btn btn-outline-danger btn-sm no-print" id="btnDeleteReportAll">
                    <i class="bx bx-trash me-1"></i> Futa zote (siku hii)
                </button>
                @endif
                @can('view purchases')
                <button type="button" class="btn btn-primary btn-sm" id="btnSendDailyReportSms">
                    <i class="bx bx-message-rounded-dots me-1"></i> Tuma taarifa (SMS)
                </button>
                @endcan
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bx bx-printer me-1"></i> Chapisha
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.close()">
                    <i class="bx bx-x me-1"></i> Funga
                </button>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h5 class="mb-1 text-uppercase">Ripoti ya Hesabu za Kila Siku</h5>
                    <p class="mb-0"><strong>Mfanyakazi:</strong> {{ $employee_name }}</p>
                    <p class="mb-0 text-muted"><strong>Tarehe:</strong> {{ $entry_date_formatted }}</p>
                </div>

                {{-- Mauzo --}}
                <h6 class="text-success text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-wallet me-1"></i> Mauzo
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $mauzo_lines,
                    'total' => $baki_na_mauzo,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna mauzo kwa siku hii.',
                    'showOpeningBalance' => true,
                    'openingBalance' => $opening_balance,
                    'openingBalanceLabel' => 'Salio la kufungua (baki ya tarehe '.$previous_date_formatted.')',
                    'totalLabel' => 'Jumla ya mauzo',
                    'lineType' => 'mauzo',
                    'canManage' => $can_manage ?? false,
                    'canDelete' => $can_delete ?? false,
                    'sectionDeleteLabel' => 'mauzo',
                ])

                {{-- Matumizi --}}
                <h6 class="text-warning text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-receipt me-1"></i> Matumizi
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $matumizi_lines,
                    'total' => $matumizi_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna matumizi kwa siku hii.',
                    'lineType' => 'matumizi',
                    'canManage' => $can_manage ?? false,
                    'canDelete' => $can_delete ?? false,
                    'sectionDeleteLabel' => 'matumizi',
                ])

                {{-- Manunuzi --}}
                <h6 class="text-secondary text-uppercase border-bottom pb-2 mb-3 mt-4">
                    <i class="bx bx-cart me-1"></i> Manunuzi
                </h6>
                @include('purchases.daily-accounts.partials.report-amount-section', [
                    'lines' => $manunuzi_lines,
                    'total' => $manunuzi_total,
                    'amountLabel' => 'Kiasi',
                    'emptyMessage' => 'Hakuna manunuzi kwa siku hii.',
                    'lineType' => 'manunuzi',
                    'canManage' => $can_manage ?? false,
                    'canDelete' => $can_delete ?? false,
                    'sectionDeleteLabel' => 'manunuzi',
                ])

                {{-- Baki --}}
                <div class="card bg-light border-0 mt-4 mb-4">
                    <div class="card-body py-3">
                        <div class="row g-2 small">
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Baki ya tarehe {{ $previous_date_formatted }}</span>
                                <span class="fw-semibold">{{ format_currency($opening_balance) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Mauzo ya tarehe {{ $entry_date_formatted }}</span>
                                <span class="fw-semibold">{{ format_currency($mauzo_total) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Jumla ya baki na mauzo</span>
                                <span class="fw-semibold">{{ format_currency($baki_na_mauzo) }}</span>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-between">
                                <span>Matumizi + Manunuzi</span>
                                <span class="fw-semibold">{{ format_currency($matumizi_manunuzi_total) }}</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-uppercase">Baki mpya</span>
                            <span class="fs-5 fw-bold {{ $baki_mpya >= 0 ? 'text-success' : 'text-danger' }}">{{ format_currency($baki_mpya) }}</span>
                        </div>
                    </div>
                </div>

                <div id="daily-report-sms-alert" class="d-none"></div>

                {{-- Stoo --}}
                <h6 class="text-info text-uppercase border-bottom pb-2 mb-3">
                    <i class="bx bx-package me-1"></i> Taarifa ya stoo
                </h6>

                @if(!empty($can_delete) || !empty($can_manage))
                <div class="d-flex justify-content-end mb-2 no-print">
                    @if(!empty($can_delete))
                    <button type="button"
                            class="btn btn-outline-danger btn-sm btn-delete-report-section"
                            data-type="stoo"
                            data-label="stoo">
                        <i class="bx bx-trash me-1"></i> Futa stoo zote
                    </button>
                    @endif
                </div>
                @endif

                @if(empty($stoo_groups))
                    <p class="text-muted small mb-0">Hakuna taarifa za stoo kwa siku hii.</p>
                @else
                    @foreach($stoo_groups as $group)
                        <div class="mb-3">
                            <p class="fw-bold mb-2"><i class="bx bx-box me-1"></i> {{ $group['bidhaa'] }}</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Maelezo</th>
                                            <th style="width: 160px">Thamani/Idadi</th>
                                            @if(!empty($can_delete) || !empty($can_manage))
                                                <th class="text-center no-print" style="width: {{ !empty($can_manage) && !empty($can_delete) ? '88' : '52' }}px"></th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($group['lines'] as $line)
                                            <tr>
                                                <td>{{ $line['maelezo'] }}</td>
                                                <td>{{ $line['thamani'] }}</td>
                                                @if((!empty($can_delete) || !empty($can_manage)) && !empty($line['id']))
                                                    <td class="text-center no-print text-nowrap">
                                                        @if(!empty($can_manage))
                                                        @php
                                                            $stooEditPayload = json_encode([
                                                                'maelezo' => $line['maelezo'],
                                                                'amount' => $line['thamani'],
                                                                'bidhaa' => $group['bidhaa'],
                                                                'employee_id' => $line['employee_id'] ?? null,
                                                                'entry_date' => $line['entry_date'] ?? null,
                                                            ], JSON_HEX_APOS | JSON_HEX_QUOT);
                                                        @endphp
                                                        <button type="button"
                                                                class="btn btn-outline-primary btn-sm btn-edit-report-line"
                                                                data-type="stoo"
                                                                data-line-id="{{ $line['id'] }}"
                                                                data-payload="{{ $stooEditPayload }}"
                                                                title="Hariri">
                                                            <i class="bx bx-edit"></i>
                                                        </button>
                                                        @endif
                                                        @if(!empty($can_delete))
                                                        <button type="button"
                                                                class="btn btn-outline-danger btn-sm btn-delete-report-line"
                                                                data-type="stoo"
                                                                data-line-id="{{ $line['id'] }}"
                                                                title="Futa">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                        @endif
                                                    </td>
                                                @elseif(!empty($can_delete) || !empty($can_manage))
                                                    <td class="no-print"></td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ (!empty($can_delete) || !empty($can_manage)) ? 3 : 2 }}" class="text-muted small">Hakuna mistari.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

@include('purchases.daily-accounts.partials.report-edit-modal', [
    'can_manage' => $can_manage ?? false,
    'employees' => $employees ?? collect(),
])
@endsection

@push('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .page-wrapper { padding: 0; }
}
</style>
@endpush

@push('scripts')
@include('purchases.daily-accounts.partials.report-delete-scripts')
@include('purchases.daily-accounts.partials.report-edit-scripts', [
    'can_manage' => $can_manage ?? false,
    'report_employee_id' => $employee_id ?? null,
    'report_entry_date' => $entry_date ?? null,
])
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var $btn = $('#btnSendDailyReportSms');
    if (!$btn.length) {
        return;
    }

    $btn.on('click', function () {
        if (!confirm('Tuma SMS ya hesabu kwa namba ya simu ya kampuni?')) {
            return;
        }

        var $alert = $('#daily-report-sms-alert');
        $btn.prop('disabled', true);

        $.ajax({
            url: @json(route('purchases.daily-accounts.report.notify')),
            method: 'POST',
            data: {
                employee_id: @json($employee_id),
                entry_date: @json($entry_date)
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            var ok = res && res.success;
            $alert.removeClass('d-none alert-danger alert-success')
                .addClass(ok ? 'alert alert-success' : 'alert alert-danger')
                .html($('<div>').text((res && res.message) ? res.message : 'Imekamilika.').html());
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Imeshindikana kutuma SMS.';
            $alert.removeClass('d-none alert-success')
                .addClass('alert alert-danger')
                .html($('<div>').text(msg).html());
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endpush
