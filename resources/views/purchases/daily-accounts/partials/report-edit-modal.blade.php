@if(!empty($can_manage))
<div class="modal fade" id="editReportLineModal" tabindex="-1" aria-labelledby="editReportLineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editReportLineModalLabel">
                    <i class="bx bx-edit me-1"></i> Hariri rekodi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Funga"></button>
            </div>
            <form id="edit-report-line-form" novalidate>
                <div class="modal-body">
                    <div id="edit-report-line-errors" class="alert alert-danger d-none small py-2"></div>
                    <input type="hidden" id="edit_line_type" value="">
                    <input type="hidden" id="edit_line_id" value="">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="edit_employee_id" class="form-label fw-bold">Mfanyakazi <span class="text-danger">*</span></label>
                            <select id="edit_employee_id" name="employee_id" class="form-select edit-report-employee-select" required>
                                <option value=""></option>
                                @foreach($employees ?? [] as $emp)
                                    <option value="{{ $emp->id }}">
                                        {{ $emp->display_name }}@if(!empty($emp->employee_number)) ({{ $emp->employee_number }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_entry_date" class="form-label fw-bold">Tarehe <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_entry_date" name="entry_date" required>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="edit_bidhaa_wrap">
                        <label for="edit_bidhaa" class="form-label fw-bold">Bidhaa <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_bidhaa" name="bidhaa" maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="edit_maelezo" class="form-label fw-bold">Maelezo <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_maelezo" name="maelezo" rows="2" maxlength="2000" required></textarea>
                    </div>

                    <div class="mb-0">
                        <label for="edit_amount" class="form-label fw-bold" id="edit_amount_label">Kiasi <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control text-end" id="edit_amount" name="amount" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Funga</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="edit-report-line-submit">
                        <i class="bx bx-save me-1"></i> Hifadhi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
