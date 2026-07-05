@if(!empty($canManageWorkers))
<div class="row mt-3" id="orodha-wafanyakazi">
    <div class="col-12">
        <div class="card border-secondary">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">
                    <i class="bx bx-group me-2"></i>Orodha ya Wafanyakazi
                </h5>
                <button type="button" class="btn btn-sm btn-secondary" id="btnOpenAddWorkerFromList">
                    <i class="bx bx-user-plus me-1"></i> Ongeza Mfanyakazi
                </button>
            </div>
            <div class="card-body">
                @if($errors->has('employee'))
                    <div class="alert alert-danger py-2">{{ $errors->first('employee') }}</div>
                @endif

                @if(($workersList ?? collect())->isEmpty())
                    <p class="text-muted mb-0 text-center py-3">
                        Hakuna wafanyakazi waliosajiliwa kwa tawi hili.
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Jina</th>
                                    <th>Simu</th>
                                    <th>Jukumu</th>
                                    <th>Nambari</th>
                                    <th class="text-center">Vitendo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workersList as $index => $worker)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><strong>{{ $worker->display_name }}</strong></td>
                                    <td>{{ $worker->phone }}</td>
                                    <td>{{ ucwords($worker->role_name) }}</td>
                                    <td>{{ $worker->employee_number ?? '—' }}</td>
                                    <td class="text-center">
                                        <form method="POST"
                                              action="{{ route('purchases.daily-accounts.employees.destroy', $worker->id) }}"
                                              class="d-inline js-delete-worker-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Futa mfanyakazi"
                                                    data-worker-name="{{ $worker->display_name }}">
                                                <i class="bx bx-trash"></i> Futa
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
