@if(!empty($can_manage))
@php
    $lineUpdateUrlTemplate = str_replace(
        ['mauzo', '/0'],
        ['__TYPE__', '__LINE__'],
        route('purchases.daily-accounts.report.line.update', ['type' => 'mauzo', 'line' => 0])
    );
@endphp
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var lineUpdateUrl = @json($lineUpdateUrlTemplate);
    var reportEmployeeId = @json($report_employee_id ?? null);
    var reportEntryDate = @json($report_entry_date ?? null);
    var modalEl = document.getElementById('editReportLineModal');
    if (!modalEl) {
        return;
    }

    var editModal = new bootstrap.Modal(modalEl);
    var currentAmountField = 'kiasi';
    var $employeeSelect = $('#edit_employee_id');

    if ($.fn.select2 && $employeeSelect.length) {
        $employeeSelect.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Tafuta mfanyakazi…',
            allowClear: true,
            dropdownParent: $('#editReportLineModal')
        });
    }

    $(document).on('click', '.btn-edit-report-line', function () {
        var $btn = $(this);
        var type = $btn.data('type');
        currentAmountField = type === 'stoo' ? 'thamani' : 'kiasi';

        var payload = {};
        try {
            payload = JSON.parse($btn.attr('data-payload') || '{}');
        } catch (e) {
            payload = {};
        }

        $('#edit_line_type').val(type);
        $('#edit_line_id').val($btn.data('line-id'));
        $employeeSelect.val(payload.employee_id || reportEmployeeId || '').trigger('change');
        $('#edit_entry_date').val(payload.entry_date || reportEntryDate || '');
        $('#edit_maelezo').val(payload.maelezo || '');
        $('#edit_amount').val(payload.amount ?? (type === 'stoo' ? '' : 0));
        $('#edit-report-line-errors').addClass('d-none').empty();

        if (type === 'stoo') {
            $('#edit_bidhaa_wrap').removeClass('d-none');
            $('#edit_bidhaa').val(payload.bidhaa || '').prop('required', true);
            $('#edit_amount_label').html('Thamani/Idadi <span class="text-danger">*</span>');
            $('#edit_amount').attr('type', 'text').removeAttr('step min').removeClass('text-end').attr('maxlength', '255');
        } else {
            $('#edit_bidhaa_wrap').addClass('d-none');
            $('#edit_bidhaa').prop('required', false);
            $('#edit_amount_label').html('Kiasi <span class="text-danger">*</span>');
            $('#edit_amount').attr({ type: 'number', step: '0.01', min: '0' }).addClass('text-end').removeAttr('maxlength');
        }

        editModal.show();
    });

    $('#edit-report-line-form').on('submit', function (e) {
        e.preventDefault();

        var type = $('#edit_line_type').val();
        var lineId = $('#edit_line_id').val();
        var $errors = $('#edit-report-line-errors');
        var $submit = $('#edit-report-line-submit');

        var payload = {
            employee_id: $employeeSelect.val(),
            entry_date: $('#edit_entry_date').val(),
            maelezo: $('#edit_maelezo').val(),
        };
        payload[currentAmountField] = $('#edit_amount').val();

        if (type === 'stoo') {
            payload.bidhaa = $('#edit_bidhaa').val();
        }

        $errors.addClass('d-none').empty();
        $submit.prop('disabled', true);

        var url = lineUpdateUrl.replace('__TYPE__', type).replace('__LINE__', lineId);

        $.ajax({
            url: url,
            method: 'PATCH',
            data: payload,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            editModal.hide();
            if (res && res.redirect) {
                window.location.href = res.redirect;
            } else {
                window.location.reload();
            }
        }).fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                var list = [];
                $.each(xhr.responseJSON.errors, function (_, msgs) {
                    list = list.concat(msgs);
                });
                $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>' + list.map(function (m) {
                    return $('<div>').text(m).html();
                }).join('</li><li>') + '</li></ul>');
            } else {
                var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Imeshindikana kuhifadhi.';
                $errors.removeClass('d-none').text(msg);
            }
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });
});
</script>
@endif
