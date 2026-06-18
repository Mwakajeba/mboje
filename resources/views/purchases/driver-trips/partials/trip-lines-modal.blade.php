@props([
    'modalId',
    'modalTitle',
    'modalIcon',
    'formId',
    'errorsId',
    'submitId',
    'tripIdInputId',
    'tripNameDisplayId',
    'entryDateId',
    'linesBodyId',
    'linesTotalId',
    'addLineBtnId',
    'linesLabel',
    'linePlaceholder',
    'submitBtnClass' => 'btn-success',
])

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    <i class="{{ $modalIcon }} me-1"></i> {{ $modalTitle }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <div class="modal-body">
                <form id="{{ $formId }}" novalidate>
                    <div id="{{ $errorsId }}" class="alert alert-danger d-none small py-2"></div>

                    <input type="hidden" id="{{ $tripIdInputId }}" name="driver_trip_id" value="">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Safari</label>
                            <p class="form-control-plaintext border rounded px-3 py-2 mb-0 bg-light" id="{{ $tripNameDisplayId }}">—</p>
                        </div>
                        <div class="col-md-4">
                            <label for="{{ $entryDateId }}" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="{{ $entryDateId }}" name="entry_date" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold small text-uppercase text-muted">{{ $linesLabel }}</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="{{ $addLineBtnId }}">
                            <i class="bx bx-plus me-1"></i> Ongeza mstari
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Maelezo <span class="text-danger">*</span></th>
                                    <th class="text-end" style="width: 160px">Kiasi <span class="text-danger">*</span></th>
                                    <th style="width: 48px"></th>
                                </tr>
                            </thead>
                            <tbody id="{{ $linesBodyId }}"></tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td class="text-end fw-bold">Jumla</td>
                                    <td class="text-end fw-bold" id="{{ $linesTotalId }}">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Funga</button>
                <button type="submit" form="{{ $formId }}" class="btn {{ $submitBtnClass }} btn-sm" id="{{ $submitId }}">
                    <i class="bx bx-save me-1"></i> Hifadhi
                </button>
            </div>
        </div>
    </div>
</div>
