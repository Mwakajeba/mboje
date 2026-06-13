@extends('layouts.main')

@section('title', 'Wateja')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashibodi', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Mauzo', 'url' => route('sales.index'), 'icon' => 'bx bx-store'],
            ['label' => 'Wateja', 'url' => '#', 'icon' => 'bx bx-group']
             ]" />
        <h6 class="mb-0 text-uppercase">ORODHA YA WATEJA</h6>
        <hr />

        <!-- Dashboard Stats -->
        <div class="row row-cols-1 row-cols-lg-4">
            <!-- Total Registered Customers -->
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Jumla ya Wateja Waliosajiliwa</p>
                            <h2 class="mb-0 fw-bold">{{ number_format($totalRegisteredCustomers ?? 0) }}</h2>
                            <p class="text-muted mb-0 small mt-1">Hai + Haifanyi kazi</p>
                        </div>
                        <div class="widgets-icons bg-gradient-burning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class='bx bx-group fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Active Customers -->
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm border-start border-4 border-success">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Wateja Hai</p>
                            <h2 class="mb-0 fw-bold text-success">{{ number_format($activeCustomers ?? 0) }}</h2>
                            <p class="text-success mb-0 small mt-1">
                                <i class='bx bx-check-circle'></i> Wenye miamala
                            </p>
                        </div>
                        <div class="widgets-icons bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class='bx bx-user-check fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dormant Customers -->
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm border-start border-4 border-danger">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Wateja Waliosinzia</p>
                            <h2 class="mb-0 fw-bold text-danger">{{ number_format($dormantCustomers ?? 0) }}</h2>
                            <p class="text-danger mb-0 small mt-1">
                                <i class='bx bx-time-five'></i> Hakuna shughuli (miezi 3-6)
                            </p>
                        </div>
                        <div class="widgets-icons bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class='bx bx-user-x fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- New Customers This Month -->
            <div class="col mb-4">
                <div class="card radius-10 border-0 shadow-sm border-start border-4 border-info">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small">Wateja Wapya (Mwezi Huu)</p>
                            <h2 class="mb-0 fw-bold text-info">{{ number_format($newCustomersThisMonth ?? 0) }}</h2>
                            <p class="mb-0 small mt-1">
                                @if(isset($newCustomersIncrease) && $newCustomersIncrease > 0)
                                    <span class="text-success">
                                        <i class='bx bx-trending-up'></i> 
                                        Ongezeko la {{ number_format(abs($newCustomersIncrease), 1) }}%
                                    </span>
                                @elseif(isset($newCustomersIncrease) && $newCustomersIncrease < 0)
                                    <span class="text-danger">
                                        <i class='bx bx-trending-down'></i> 
                                        Upungufu wa {{ number_format(abs($newCustomersIncrease), 1) }}%
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class='bx bx-minus'></i> Hakuna mabadiliko
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div class="widgets-icons bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class='bx bx-user-plus fs-4'></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="card-title mb-0">Orodha ya Wateja</h6>
                            <div>
                                @can('create customer')
                                <a href="{{ route('customers.bulk-upload') }}" class="btn btn-success me-2">
                                    <i class="bx bx-upload"></i> Pakia Wingi
                                </a>
                                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus"></i> Ongeza Mteja
                                </a>
                                @endcan
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped nowrap" id="customers-table">
                                <thead>
                                    <tr>
                                        <th>Nambari</th>
                                        <th>Jina</th>
                                        <th>Simu</th>
                                        <th>Barua pepe</th>
                                        <th>Tawi</th>
                                        <th>Kikomo cha Mkopo</th>
                                        <th>Hali</th>
                                        <th>Tarehe ya Kusajili</th>
                                        <th class="text-center">Vitendo</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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
    const table = $('#customers-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("customers.index") }}',
            type: 'GET'
        },
        columns: [
            { data: 'customerNo', name: 'customerNo' },
            { data: 'customer_avatar', name: 'name', orderable: false },
            { data: 'formatted_phone', name: 'phone' },
            { data: 'email', name: 'email' },
            { data: 'branch.name', name: 'branch.name' },
            { data: 'formatted_credit_limit', name: 'credit_limit' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { 
                data: 'actions',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Zote"]],
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Inapakia...</span></div>',
            search: "",
            searchPlaceholder: "Tafuta wateja...",
            lengthMenu: "Onyesha _MENU_ kwa ukurasa",
            info: "Inaonyesha _START_ hadi _END_ kati ya _TOTAL_",
            infoEmpty: "Inaonyesha 0 hadi 0 kati ya 0",
            infoFiltered: "(kuchujwa kutoka _MAX_ jumla)",
            zeroRecords: "Hakuna wateja waliofanana",
            paginate: {
                first: "Kwanza",
                last: "Mwisho",
                next: "Ijayo",
                previous: "Iliyotangulia"
            },
            emptyTable: `
                <div class="text-center text-muted py-4">
                    <i class="bx bx-group fs-1 d-block mb-2"></i>
                    <h6>Hakuna Wateja</h6>
                    <p class="mb-0">Anza kwa kusajili mteja wako wa kwanza</p>
                    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm mt-2">
                        <i class="bx bx-plus me-1"></i> Ongeza Mteja
                    </a>
                </div>
            `
        }
    });

    $('#search-input').on('keyup', function() {
        table.search(this.value).draw();
    });

    function refreshTable() {
        table.ajax.reload(null, false);
    }

    $('#refresh-table').on('click', function() {
        refreshTable();
    });

    window.refreshCustomersTable = refreshTable;
    
    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const customerName = form.find('button').data('name');
        
        Swal.fire({
            title: 'Una uhakika?',
            text: `Unakaribia kufuta mteja "${customerName}". Hatua hii haiwezi kutenduliwa!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ndiyo, futa',
            cancelButtonText: 'Ghairi'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Imefutwa!',
                                text: response.message || 'Mteja amefutwa kikamilifu.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire({
                                title: 'Kosa!',
                                text: response.message || 'Imeshindwa kufuta mteja.',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Imeshindwa kufuta mteja.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            errorMessage = xhr.responseText;
                        }
                        
                        Swal.fire({
                            title: 'Kosa!',
                            text: errorMessage,
                            icon: 'error'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush
