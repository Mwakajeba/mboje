@props([
    'modalId',
    'modalTitle',
    'modalIcon',
    'formId',
    'errorsId',
    'submitId',
    'employeeSelectId',
    'employeeSelectClass',
    'entryDateId',
    'linesBodyId',
    'linesTotalId',
    'addLineBtnId',
    'linesLabel',
    'linePlaceholder',
    'amountField' => 'kiasi',
    'amountLabel' => 'Kiasi',
    'showBidhaa' => false,
    'bidhaaInputId' => 'bidhaa',
    'submitBtnClass' => 'btn-success',
    'employees',
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

                    <div class="row mb-3">
                        <div class="{{ $showBidhaa ? 'col-md-6' : 'col-md-8' }}">
                            <label for="{{ $employeeSelectId }}" class="form-label fw-bold">Mfanyakazi <span class="text-danger">*</span></label>
                            <select id="{{ $employeeSelectId }}" name="employee_id" class="form-select {{ $employeeSelectClass }}" required>
                                <option value=""></option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">
                                        {{ $emp->display_name }}@if(!empty($emp->employee_number)) ({{ $emp->employee_number }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @if($employees->isEmpty())
                                <p class="text-warning small mb-0 mt-1">Hakuna wafanyakazi hai. Ongeza wafanyakazi kwenye HR kwanza.</p>
                            @endif
                        </div>
                        <div class="{{ $showBidhaa ? 'col-md-3' : 'col-md-4' }}">
                            <label for="{{ $entryDateId }}" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="{{ $entryDateId }}" name="entry_date" required>
                        </div>
                        @if($showBidhaa)
                        <div class="col-md-3">
                            <label for="{{ $bidhaaInputId }}" class="form-label fw-bold">Bidhaa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="{{ $bidhaaInputId }}" name="bidhaa" placeholder="Jina la bidhaa" maxlength="255" required>
                        </div>
                        @endif
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
                                    <th class="text-end" style="width: 160px">{{ $amountLabel }} <span class="text-danger">*</span></th>
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
