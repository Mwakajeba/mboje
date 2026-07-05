@extends('layouts.main')

@section('title', 'Orodha ya Wafanyakazi')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Manunuzi', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Hesabu za Kila Siku', 'url' => route('purchases.daily-accounts.index'), 'icon' => 'bx bx-calendar-check'],
            ['label' => 'Orodha ya Wafanyakazi', 'url' => '#', 'icon' => 'bx bx-group']
        ]" />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0 text-uppercase">Orodha ya Wafanyakazi</h6>
            <button type="button" class="btn btn-secondary btn-sm" id="btnOpenAddWorker">
                <i class="bx bx-user-plus me-1"></i> Ongeza Mfanyakazi
            </button>
        </div>
        <hr />

        <p class="text-muted mb-4">
            Wafanyakazi wote wa kampuni — bila kuchuja kwa tawi au eneo.
        </p>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
            </div>
        @endif

        <div class="card border-secondary">
            <div class="card-body">
                @if($errors->has('employee'))
                    <div class="alert alert-danger py-2">{{ $errors->first('employee') }}</div>
                @endif

                @if($workersList->isEmpty())
                    <p class="text-muted mb-0 text-center py-4">
                        <i class="bx bx-user-x fs-3 d-block mb-2"></i>
                        Hakuna wafanyakazi waliosajiliwa.
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
                                    <th>Tawi</th>
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
                                    <td>{{ $worker->branch_name }}</td>
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
                    <p class="text-muted small mt-3 mb-0">
                        Jumla: <strong>{{ $workersList->count() }}</strong> wafanyakazi
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('purchases.daily-accounts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-arrow-back me-1"></i> Rudi kwa Hesabu za Kila Siku
            </a>
        </div>
    </div>
</div>

@include('purchases.daily-accounts.partials.employee-add-modal', ['workerRoles' => $workerRoles])
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function () {
    var workerModalEl = document.getElementById('addWorkerModal');
    if (!workerModalEl) {
        return;
    }

    var workerModal = new bootstrap.Modal(workerModalEl);

    $('#btnOpenAddWorker').on('click', function () {
        workerModal.show();
    });

    @if(session('open_worker_modal') || $errors->hasAny(['name', 'phone', 'role_id', 'employee']))
    workerModal.show();
    @endif

    $(document).on('submit', '.js-delete-worker-form', function (e) {
        var name = $(this).find('[data-worker-name]').data('worker-name') || 'mfanyakazi huyu';
        if (! confirm('Una uhakika unataka kumfuta ' + name + '?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
