@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp

@extends('layouts.main')

@section('title', 'Supplier Management')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-shopping-bag'],
            ['label' => 'Suppliers', 'url' => '#', 'icon' => 'bx bx-store']
        ]" />

            <h6 class="mb-0 text-uppercase">SUPPLIER MANAGEMENT</h6>
            <hr />

            <!-- Dashboard Stats -->
            <div class="row row-cols-1 row-cols-lg-4">
                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Suppliers</p>
                                <h4 class="mb-0">{{ $stats['total'] }}</h4>
                            </div>
                            <div class="ms-3">
                                <div
                                    class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bx bx-store font-size-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Active Suppliers</p>
                                <h4 class="mb-0 text-success">{{ $stats['active'] }}</h4>
                            </div>
                            <div class="ms-3">
                                <div
                                    class="avatar-sm bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bx bx-check-circle font-size-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Inactive Suppliers</p>
                                <h4 class="mb-0 text-warning">{{ $stats['inactive'] }}</h4>
                            </div>
                            <div class="ms-3">
                                <div
                                    class="avatar-sm bg-warning text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bx bx-pause-circle font-size-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col mb-4">
                    <div class="card radius-10">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Blacklisted</p>
                                <h4 class="mb-0 text-danger">{{ $stats['blacklisted'] }}</h4>
                            </div>
                            <div class="ms-3">
                                <div
                                    class="avatar-sm bg-danger text-white rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bx bx-block font-size-24"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Suppliers Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="card-title mb-0">Suppliers List</h4>
                                <div>
                                    <a href="{{ route('accounting.suppliers.create') }}" class="btn btn-primary">
                                        Add Supplier
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive nowrap" id="suppliersTable">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Contact Info</th>
                                            <th>Location</th>
                                            <th>Business Details</th>
                                            <th>Status</th>
                                            <th>Branch</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($suppliers as $supplier)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div
                                                            class="avatar-sm bg-light-primary text-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                            <i class="bx bx-store font-size-18"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $supplier->name }}</h6>
                                                            @if($supplier->company_registration_name)
                                                                <small
                                                                    class="text-muted">{{ $supplier->company_registration_name }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($supplier->email)
                                                        <div><i class="bx bx-envelope me-1"></i> {{ $supplier->email }}</div>
                                                    @endif
                                                    @if($supplier->phone)
                                                        <div><i class="bx bx-phone me-1"></i> {{ $supplier->phone }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($supplier->address || $supplier->region)
                                                        <div class="text-truncate" style="max-width: 200px;"
                                                            title="{{ $supplier->full_address }}">
                                                            <i class="bx bx-map-pin me-1"></i> {{ $supplier->full_address }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No address</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($supplier->tin_number)
                                                        <div><small class="text-muted">TIN:</small> {{ $supplier->tin_number }}
                                                        </div>
                                                    @endif
                                                    @if($supplier->vat_number)
                                                        <div><small class="text-muted">VAT:</small> {{ $supplier->vat_number }}
                                                        </div>
                                                    @endif
                                                    @if($supplier->products_or_services)
                                                        <div class="text-truncate" style="max-width: 150px;"
                                                            title="{{ $supplier->products_or_services }}">
                                                            <small class="text-muted">Services:</small>
                                                            {{ Str::limit($supplier->products_or_services, 30) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {!! $supplier->status_badge !!}
                                                </td>
                                                <td>
                                                    {{ optional($supplier->branch)->name ?? 'N/A' }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('accounting.suppliers.show', Hashids::encode($supplier->id)) }}"
                                                            class="btn btn-sm btn-outline-primary" title="View Details">
                                                            View
                                                        </a>
                                                        <a href="{{ route('accounting.suppliers.edit', Hashids::encode($supplier->id)) }}"
                                                            class="btn btn-sm btn-outline-warning" title="Edit">
                                                            Edit
                                                        </a>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger delete-supplier-btn"
                                                            title="Delete"
                                                            data-supplier-id="{{ Hashids::encode($supplier->id) }}"
                                                            data-supplier-name="{{ $supplier->name }}">
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
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
        $(document).ready(function () {
            $('#suppliersTable').DataTable({
                responsive: true,
                order: [[0, 'asc']],
                pageLength: 10,
                language: {
                    search: "",
                    searchPlaceholder: "Search suppliers..."
                },
                columnDefs: [
                    { targets: -1, responsivePriority: 1, orderable: false, searchable: false },
                    { targets: [0, 1, 2], responsivePriority: 2 }
                ]
            });

            // SweetAlert delete confirmation
            $('.delete-supplier-btn').on('click', function () {
                const supplierId = $(this).data('supplier-id');
                const supplierName = $(this).data('supplier-name');

                Swal.fire({
                    title: 'Are you sure?',
                    text: `Do you want to delete supplier "${supplierName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create a form and submit it
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': `/accounting/suppliers/${supplierId}`
                        });

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }));

                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }));

                        $('body').append(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush