@if(!empty($can_delete))
@php
    $lineDestroyUrlTemplate = str_replace(
        ['mauzo', '/0'],
        ['__TYPE__', '__LINE__'],
        route('purchases.daily-accounts.report.line.destroy', ['type' => 'mauzo', 'line' => 0])
    );
    $sectionDestroyUrlTemplate = str_replace(
        'mauzo',
        '__TYPE__',
        route('purchases.daily-accounts.report.section.destroy', ['type' => 'mauzo'])
    );
@endphp
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var reportScope = {
        employee_id: @json($employee_id),
        entry_date: @json($entry_date)
    };
    var lineDestroyUrl = @json($lineDestroyUrlTemplate);
    var sectionDestroyUrl = @json($sectionDestroyUrlTemplate);
    var allDestroyUrl = @json(route('purchases.daily-accounts.report.all.destroy'));

    function reloadReport() {
        window.location.reload();
    }

    function deleteRequest(url, confirmMsg) {
        if (!confirm(confirmMsg)) {
            return;
        }

        $.ajax({
            url: url,
            method: 'DELETE',
            data: reportScope,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            if (res && res.success) {
                reloadReport();
            } else {
                alert((res && res.message) ? res.message : 'Imeshindikana kufuta.');
            }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Imeshindikana kufuta.';
            alert(msg);
        });
    }

    $(document).on('click', '.btn-delete-report-line', function () {
        var $btn = $(this);
        var type = $btn.data('type');
        var lineId = $btn.data('line-id');
        var url = lineDestroyUrl.replace('__TYPE__', type).replace('__LINE__', lineId);
        deleteRequest(url, 'Futa mstari huu?');
    });

    $(document).on('click', '.btn-delete-report-section', function () {
        var $btn = $(this);
        var type = $btn.data('type');
        var label = $btn.data('label') || 'rekodi';
        var url = sectionDestroyUrl.replace('__TYPE__', type);
        deleteRequest(url, 'Futa ' + label + ' zote za siku hii?');
    });

    $('#btnDeleteReportAll').on('click', function () {
        deleteRequest(allDestroyUrl, 'Futa rekodi ZOTE za siku hii (mauzo, matumizi, manunuzi, stoo)?');
    });
});
</script>
@endif
