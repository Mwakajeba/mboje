@extends('layouts.main')

@section('title', 'Makundi')

@push('styles')
<style>
    /* Ensure DataTable controls are visible */
    .dataTables_wrapper .dataTables_filter {
        display: block !important;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_length {
        display: block !important;
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_info {
        display: block !important;
        margin-top: 10px;
    }
    
    .dataTables_wrapper .dataTables_paginate {
        display: block !important;
        margin-top: 10px;
    }
    
    /* Improve search box styling */
    .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 6px 12px;
        margin-left: 8px;
    }
    
    /* Improve length filter styling */
    .dataTables_length select {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 4px 8px;
        margin: 0 4px;
    }
    
    /* Ensure action column is visible and properly styled */
    #categoriesTable th:last-child,
    #categoriesTable td:last-child {
        min-width: 120px;
        text-align: center;
    }
    
    /* Style action buttons */
    #categoriesTable .btn-group {
        display: flex;
        gap: 2px;
        justify-content: center;
    }
    
    #categoriesTable .btn-group .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    /* Ensure table is responsive */
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Fix DataTable responsive issues */
    .dataTables_wrapper .dataTables_scroll {
        overflow-x: auto;
    }
    
    /* Style badges consistently */
    .badge {
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Usimamizi wa Hesabu', 'url' => route('inventory.index'), 'icon' => 'bx bx-package'],
            ['label' => 'Makundi', 'url' => route('inventory.categories.index'), 'icon' => 'bx bx-category']
        ]" />

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h6 class="mb-0 text-uppercase">MAKUNDI</h6>
                <p class="mb-0 text-muted">Simamia makundi ya bidhaa</p>
            </div>
            @can('manage inventory categories')
            <a href="{{ route('inventory.categories.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i>Ongeza Kategoria
            </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Funga"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="categoriesTable" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Nambari</th>
                                <th>Jina</th>
                                <th>Maelezo</th>
                                <th>Idadi ya Bidhaa</th>
                                <th>Hali</th>
                                <th>Tarehe ya Kusajili</th>
                                <th>Vitendo</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('inventory.categories.index') }}",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            error: function(xhr, error, code) {
                console.log('DataTables Ajax Error:', error, code);
                console.log('Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'code', name: 'code', width: '10%' },
            { 
                data: 'name', 
                name: 'name', 
                width: '20%',
                render: function(data, type, row) {
                    if (typeof data === 'string') {
                        return data.replace(/\w\S*/g, function(txt){
                            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                        });
                    }
                    return data;
                }
            },
            { data: 'description', name: 'description', orderable: false, width: '25%' },
            { data: 'items_count', name: 'items_count', orderable: false, width: '10%' },
            { data: 'status_badge', name: 'is_active', orderable: false, width: '10%' },
            { data: 'created_at', name: 'created_at', width: '15%' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '10%' }
        ],
        order: [[5, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Inapakia...</span></div>',
            emptyTable: '<div class="text-center p-4"><i class="bx bx-folder-open font-24 text-muted"></i><p class="text-muted mt-2">Hakuna makundi yaliyopatikana.</p></div>',
            search: "",
            searchPlaceholder: "Tafuta makundi...",
            lengthMenu: "Onyesha _MENU_ kwa ukurasa",
            info: "Inaonyesha _START_ hadi _END_ kati ya _TOTAL_",
            infoEmpty: "Inaonyesha 0 hadi 0 kati ya 0",
            infoFiltered: "(kuchujwa kutoka _MAX_ jumla)",
            zeroRecords: "Hakuna makundi yanayolingana",
            paginate: {
                first: "Kwanza",
                last: "Mwisho",
                next: "Ijayo",
                previous: "Iliyotangulia"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Handle delete with SweetAlert
    $(document).on('click', '.delete-category', function(e) {
        e.preventDefault();
        const form = $(this).closest('form');
        const categoryName = $(this).data('name');
        
        Swal.fire({
            title: 'Una uhakika?',
            text: `Unakaribia kufuta kategoria "${categoryName}". Hatua hii haiwezi kutenduliwa!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ndiyo, futa',
            cancelButtonText: 'Ghairi'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection
