<script nonce="{{ $cspNonce ?? '' }}">
function initTripLinesForm(cfg) {
    var modalEl = document.getElementById(cfg.modalId);
    if (!modalEl) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var lineIndex = 0;

    function formatAmount(n) {
        return Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function updateTotal() {
        var total = 0;
        $(cfg.linesBody + ' .trip-amount-input').each(function () {
            total += parseFloat($(this).val()) || 0;
        });
        $(cfg.linesTotal).text(formatAmount(total));
    }

    function addLine(maelezo, amount) {
        var idx = lineIndex++;
        var $row = $('<tr class="trip-line-row">'
            + '<td><input type="text" class="form-control form-control-sm trip-maelezo-input" name="lines[' + idx + '][maelezo]" placeholder="' + cfg.linePlaceholder + '" maxlength="2000" required></td>'
            + '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm text-end trip-amount-input" name="lines[' + idx + '][kiasi]" value="0" required></td>'
            + '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm btn-remove-line" title="Ondoa"><i class="bx bx-trash"></i></button></td>'
            + '</tr>');
        if (maelezo !== undefined) {
            $row.find('.trip-maelezo-input').val(maelezo);
        }
        if (amount !== undefined) {
            $row.find('.trip-amount-input').val(amount);
        }
        $(cfg.linesBody).append($row);
        updateTotal();
    }

    function resetForm(tripId, tripName, tripDate) {
        $(cfg.errors).addClass('d-none').empty();
        $(cfg.tripIdInput).val(tripId || '');
        $(cfg.tripNameDisplay).text(tripName || '—');
        $(cfg.entryDate).val(tripDate || new Date().toISOString().slice(0, 10));
        lineIndex = 0;
        $(cfg.linesBody).empty();
        addLine();
        updateTotal();
    }

    $(document).on('click', cfg.openBtnSelector, function () {
        var $btn = $(this);
        resetForm($btn.data('trip-id'), $btn.data('trip-name'), $btn.data('trip-date'));
        modal.show();
    });

    $(cfg.addLineBtn).on('click', function () {
        addLine();
    });

    $(cfg.linesBody).on('click', '.btn-remove-line', function () {
        if ($(cfg.linesBody + ' .trip-line-row').length <= 1) {
            alert('Lazima uwe na angalau mstari mmoja.');
            return;
        }
        $(this).closest('tr').remove();
        updateTotal();
    });

    $(cfg.linesBody).on('input', '.trip-amount-input', updateTotal);

    $(cfg.form).on('submit', function (e) {
        e.preventDefault();
        var $errors = $(cfg.errors);
        var $submit = $(cfg.submit);

        if (!$(cfg.tripIdInput).val()) {
            $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>Safari haijachaguliwa.</li></ul>');
            return;
        }
        if (!$(cfg.entryDate).val()) {
            $errors.removeClass('d-none').html('<ul class="mb-0 ps-3"><li>Chagua tarehe.</li></ul>');
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
            if (cfg.reloadTable && window.driverTripsTable) {
                window.driverTripsTable.ajax.reload(null, false);
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
                $errors.removeClass('d-none').text('Imeshindikana kuhifadhi. Jaribu tena.');
            }
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });
}
</script>
