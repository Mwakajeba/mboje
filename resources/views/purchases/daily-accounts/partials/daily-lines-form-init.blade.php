<script nonce="{{ $cspNonce ?? '' }}">
function initDailyLinesForm(cfg) {
    var amountField = cfg.amountField || 'kiasi';
    var amountInputType = cfg.amountInputType || 'number';
    var showLinesTotal = cfg.showLinesTotal !== false;
    var modalEl = document.getElementById(cfg.modalId);
    if (!modalEl) {
        return;
    }

    var modal = new bootstrap.Modal(modalEl);
    var lineIndex = 0;

    if ($.fn.select2) {
        $(cfg.employeeSelect).select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Tafuta mfanyakazi…',
            allowClear: true,
            dropdownParent: $('#' + cfg.modalId)
        });
    }

    function formatAmount(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateTotal() {
        if (!showLinesTotal || !cfg.linesTotal) {
            return;
        }
        var total = 0;
        $(cfg.linesBody + ' .daily-amount-input').each(function () {
            total += parseFloat($(this).val()) || 0;
        });
        $(cfg.linesTotal).text(formatAmount(total));
    }

    function addLine(maelezo, amount) {
        var idx = lineIndex++;
        var amountAttrs = amountInputType === 'text'
            ? 'type="text" class="form-control form-control-sm daily-amount-input" placeholder="Mf. 50, 2 gunia, 100kg"'
            : 'type="number" step="0.01" min="0" class="form-control form-control-sm text-end daily-amount-input"';
        var amountValue = amountInputType === 'text' ? '' : '0';
        var $row = $('<tr class="daily-line-row">'
            + '<td><input type="text" class="form-control form-control-sm daily-maelezo-input" name="lines[' + idx + '][maelezo]" placeholder="' + cfg.linePlaceholder + '" maxlength="2000" required></td>'
            + '<td><input ' + amountAttrs + ' name="lines[' + idx + '][' + amountField + ']" value="' + amountValue + '" maxlength="255" required></td>'
            + '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-line" title="Ondoa"><i class="bx bx-trash"></i></button></td>'
            + '</tr>');
        if (maelezo !== undefined) {
            $row.find('.daily-maelezo-input').val(maelezo);
        }
        if (amount !== undefined) {
            $row.find('.daily-amount-input').val(amount);
        }
        $(cfg.linesBody).append($row);
        updateTotal();
    }

    function resetForm() {
        $(cfg.errors).addClass('d-none').empty();
        $(cfg.form)[0].reset();
        $(cfg.employeeSelect).val('').trigger('change');
        $(cfg.entryDate).val(new Date().toISOString().slice(0, 10));
        lineIndex = 0;
        $(cfg.linesBody).empty();
        addLine();
        updateTotal();
    }

    $(cfg.openBtn).on('click', function () {
        resetForm();
        modal.show();
    });

    $(cfg.addLineBtn).on('click', function () {
        addLine();
    });

    $(cfg.linesBody).on('click', '.btn-remove-line', function () {
        if ($(cfg.linesBody + ' .daily-line-row').length <= 1) {
            alert('Lazima uwe na angalau mstari mmoja.');
            return;
        }
        $(this).closest('tr').remove();
        updateTotal();
    });

    if (showLinesTotal) {
        $(cfg.linesBody).on('input', '.daily-amount-input', updateTotal);
    }

    $(cfg.form).on('submit', function (e) {
        e.preventDefault();
        var $errors = $(cfg.errors);
        var $submit = $(cfg.submit);

        if (!$(cfg.employeeSelect).val()) {
            $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>Chagua mfanyakazi.</li></ul>');
            return;
        }
        if (!$(cfg.entryDate).val()) {
            $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>Chagua tarehe.</li></ul>');
            return;
        }
        if (cfg.bidhaaInput && !$.trim($(cfg.bidhaaInput).val())) {
            $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>Andika jina la bidhaa.</li></ul>');
            return;
        }

        $errors.addClass('d-none').empty();
        $submit.prop('disabled', true);

        $.ajax({
            url: cfg.storeUrl,
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        }).done(function (res) {
            modal.hide();
            var msg = (res && res.message) ? res.message : cfg.successDefault;
            var $alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">'
                + $('<div>').text(msg).html()
                + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
            $('.page-content').prepend($alert);
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
                $errors.removeClass('d-none').text('Imeshindikana kuhifadhi. Jaribu tena.');
            }
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });
}
</script>
