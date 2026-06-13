@extends('layouts.main')

@section('title', 'Bidhaa')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Bidhaa', 'url' => route('inventory.items.index'), 'icon' => 'bx bx-package']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Bidhaa</h4>
                            <div>
                                @can('manage inventory items')
                                <a href="{{ route('inventory.items.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Ongeza Bidhaa
                                </a>
                                @endcan
                            </div>
                        </div>

                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bx bx-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        <div class="table-responsive">
                            <table id="itemsTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Jina</th>
                                        <th>Nambari</th>
                                        <th>Kategoria</th>
                                        <th>Matawi</th>
                                        <th>Gharama</th>
                                        <th>Bei ya Kuuza</th>
                                        <th>Stoo ya Sasa</th>
                                        {{-- <th>Mwisho wa Matumizi</th> --}}
                                        <th>Hali</th>
                                        <th>Vitendo</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        $('#itemsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('inventory.items.index') }}",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            },
            columns: [
                {data: 'name', name: 'name'},
                {data: 'code', name: 'code'},
                {data: 'category_name', name: 'category.name'},
                {data: 'branches_scope', name: 'branches_scope', orderable: false, searchable: false},
                {data: 'cost_price', name: 'cost_price'},
                {data: 'unit_price', name: 'unit_price'},
                {data: 'current_stock', name: 'current_stock', orderable: false, searchable: false},
                // {data: 'expiry_tracking_badge', name: 'track_expiry', orderable: false, searchable: false},
                {data: 'status_badge', name: 'is_active', orderable: false, searchable: false},
                {data: 'actions', name: 'actions', orderable: false, searchable: false}
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Inapakia...</span></div>',
                emptyTable: '<div class="text-center p-4"><i class="bx bx-package font-24 text-muted"></i><p class="text-muted mt-2">Hakuna bidhaa zilizopatikana.</p></div>',
                search: 'Tafuta:',
                lengthMenu: 'Onyesha _MENU_ kwa ukurasa',
                info: 'Inaonyesha _START_ hadi _END_ kati ya _TOTAL_',
                infoEmpty: 'Hakuna rekodi',
                infoFiltered: '(zilichujwa kutoka _MAX_)',
                zeroRecords: 'Hakuna rekodi zinazolingana',
                paginate: {
                    first: 'Kwanza',
                    last: 'Mwisho',
                    next: 'Ifuatayo',
                    previous: 'Iliyotangulia'
                }
            }
        });

        $(document).on('click', '.delete-btn', function() {
            const itemId = $(this).data('id');
            const deleteUrl = $(this).data('url') || `/inventory/items/${itemId}`;

            if (!itemId) {
                Swal.fire('Hitilafu!', 'Kitambulisho cha bidhaa hakipo. Pakia upya ukurasa ujaribu tena.', 'error');
                return;
            }

            Swal.fire({
                title: 'Una uhakika?',
                text: 'Hatua hii haiwezi kutenduliwa!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ndiyo, futa',
                cancelButtonText: 'Ghairi'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: deleteUrl,
                        type: 'POST',
                        data: { _method: 'DELETE' },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Imefutwa!', response.message, 'success');
                                $('#itemsTable').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Hitilafu!', response.message, 'error');
                            }
                        },
                        error: function(xhr) {
                            let json = null;
                            try { json = xhr.responseJSON || JSON.parse(xhr.responseText); } catch(_) {}
                            const message = json?.message || 'Kuna tatizo limetokea!';
                            Swal.fire('Hitilafu!', message, 'error');
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
