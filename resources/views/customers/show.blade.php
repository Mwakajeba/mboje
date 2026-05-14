@extends('layouts.main')

@section('title', 'Customer Profile')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Customers', 'url' => route('customers.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Customer', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">Customer Profile</h6>
        </div>
        <div class="row">
            <!-- Cash Deposit Balance -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Cash Deposit Balance</p>
                                <h4 class="my-1">
                                    {{ number_format($correctCashDepositBalance ?? $customer->cash_deposit_balance, 2) }}
                                </h4>
                                <p class="mb-0 font-13 text-success">
                                    <i class="bx bxs-wallet align-middle"></i> Available
                                </p>
                            </div>
                            <div class="widgets-icons bg-light-success text-success ms-auto">
                                <i class="bx bxs-wallet"></i>
                            </div>
                        </div>
                        {{-- Cash deposit management buttons temporarily disabled until CashDepositController is implemented
                        @if($customer->cash_deposit_balance > 0)
                        <div class="mt-3 d-flex gap-2">
                            @can('deposit cash deposit')
                            <a href="{{ route('cash_deposits.create') }}?customer_id={{ Hashids::encode($customer->id) }}" class="btn btn-sm btn-primary flex-fill">
                                <i class="bx bx-plus"></i> Deposit
                            </a>
                            @endcan
                            @can('withdraw cash deposit')
                            <a href="{{ route('cash_deposits.withdraw', Hashids::encode($customer->id)) }}" class="btn btn-sm btn-success flex-fill">
                                <i class="bx bx-minus"></i> Withdraw
                            </a>
                            @endcan
                        </div>
                        @endif
                        --}}
                    </div>
                </div>
            </div>

            <!-- Account Balance -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Account Balance</p>
                                <h4 class="my-1">
                                    {{ number_format($customer->account_balance, 2) }}
                                </h4>
                                <p class="mb-0 font-13 {{ $customer->account_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                    <i class="bx {{ $customer->account_balance >= 0 ? 'bxs-up-arrow' : 'bxs-down-arrow' }} align-middle"></i> 
                                    {{ $customer->account_balance >= 0 ? 'Credit' : 'Debit' }}
                                </p>
                            </div>
                            <div class="widgets-icons {{ $customer->account_balance >= 0 ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }} ms-auto">
                                <i class="bx bxs-bank"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Limit -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Credit Limit</p>
                                <h4 class="my-1">
                                    {{ number_format($customer->credit_limit ?? 0, 2) }}
                                </h4>
                                <p class="mb-0 font-13 text-info">
                                    <i class="bx bxs-credit-card align-middle"></i> Available
                                </p>
                            </div>
                            <div class="widgets-icons bg-light-info text-info ms-auto">
                                <i class="bx bxs-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Due Invoices -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Total Due Invoices</p>
                                <h4 class="my-1">
                                    {{ number_format($customer->total_due_invoices, 2) }}
                                </h4>
                                <p class="mb-0 font-13 text-warning">
                                    <i class="bx bxs-time-five align-middle"></i> Outstanding
                                </p>
                            </div>
                            <div class="widgets-icons bg-light-warning text-warning ms-auto">
                                <i class="bx bxs-file-doc"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <!-- Total Orders -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Total Orders</p>
                                <h4 class="my-1">{{ $customer->total_orders }}</h4>
                                <p class="mb-0 font-13 text-primary">
                                    <i class="bx bxs-shopping-bag align-middle"></i> Sales Orders
                                </p>
                            </div>
                            <div class="widgets-icons bg-light-primary text-primary ms-auto">
                                <i class="bx bxs-shopping-bag"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Proformas -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Total Proformas</p>
                                <h4 class="my-1">{{ $customer->total_proformas }}</h4>
                                <p class="mb-0 font-13 text-secondary">
                                    <i class="bx bxs-file align-middle"></i> Quotations
                                </p>
                            </div>
                            <div class="widgets-icons bg-light-secondary text-secondary ms-auto">
                                <i class="bx bxs-file"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="col-md-3">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div>
                                <p class="mb-0 text-secondary">Total Invoices</p>
                                <h4 class="my-1">{{ $customer->total_invoices }}</h4>
                                <p class="mb-0 font-13 text-info">
                                    <i class="bx bxs-receipt align-middle"></i> Generated
                                </p>
                            </div>
                            <div class="widgets-icons bg-light-info text-info ms-auto">
                                <i class="bx bxs-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <div class="row">

            <!-- Profile and Company Information - Left Side -->
            <div class="col-xl-4">
                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="text-center">
                            <div class="avatar-lg mx-auto mb-4">
                                <img
                                    src="{{ $customer->photo ? asset('storage/' . $customer->photo) : asset('assets/images/avatars/default.png') }}"
                                    alt="{{ $customer->name }}"
                                    class="rounded-circle p-1 bg-primary"
                                    width="110" />
                            </div>
                            <h5 class="font-size-16 mb-1 text-truncate">{{ $customer->name }}</h5>
                            <p class="text-muted text-truncate mb-3">{{ $customer->phone ?? 'No phone' }}</p>
                        </div>

                        <hr class="my-4">

                        <div class="text-muted">
                            <div class="table-responsive">
                                <table class="table table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Customer ID :</th>
                                            <td>{{ $customer->customerNo }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Phone :</th>
                                            <td>{{ $customer->phone }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Email :</th>
                                            <td>{{ $customer->email ?: 'No email provided' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Credit Limit :</th>
                                            <td>{{ $customer->credit_limit ? number_format($customer->credit_limit, 2) : 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Branch :</th>
                                            <td>{{ $customer->branch->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Company :</th>
                                            <td>{{ $customer->company->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Description :</th>
                                            <td>{{ $customer->description ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Registrar :</th>
                                            <td>{{ $customer->user->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Joined :</th>
                                            <td>{{ $customer->created_at->format('M d, Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Last Updated :</th>
                                            <td>{{ $customer->updated_at->format('M d, Y') }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 d-flex flex-wrap gap-2">
                            <a href="{{ route('customers.edit', Hashids::encode($customer->id)) }}" class="btn btn-sm btn-warning flex-fill">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                            <a href="{{ route('sales.invoices.create', ['customer_id' => Hashids::encode($customer->id)]) }}" class="btn btn-sm btn-primary flex-fill">
                                <i class="bx bx-plus"></i> Create Invoice
                            </a>
                            <button type="button" class="btn btn-sm btn-info flex-fill" data-bs-toggle="modal" data-bs-target="#sendCustomerSmsModal">
                                <i class="bx bx-envelope"></i> Send SMS
                            </button>
                            <form action="{{ route('customers.destroy', Hashids::encode($customer->id)) }}" method="POST" class="flex-fill delete-form" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger w-100" data-name="{{ $customer->name }}">
                                    <i class="bx bx-trash"></i> Delete
                                </button>
                            </form>
                        </div>

                    </div>
                </div>

                <!-- Company Information -->
                @if($customer->company_name || $customer->company_registration_number || $customer->tin_number || $customer->vat_number)
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Company Information</h5>
                        <hr class="my-4">

                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    @if($customer->company_name)
                                    <tr>
                                        <th scope="row">Company Name :</th>
                                        <td>{{ $customer->company_name }}</td>
                                    </tr>
                                    @endif
                                    @if($customer->company_registration_number)
                                    <tr>
                                        <th scope="row">Registration Number :</th>
                                        <td>{{ $customer->company_registration_number }}</td>
                                    </tr>
                                    @endif
                                    @if($customer->tin_number)
                                    <tr>
                                        <th scope="row">TIN Number :</th>
                                        <td>{{ $customer->tin_number }}</td>
                                    </tr>
                                    @endif
                                    @if($customer->vat_number)
                                    <tr>
                                        <th scope="row">VAT Number :</th>
                                        <td>{{ $customer->vat_number }}</td>
                                    </tr>
                                    @endif

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Cash Deposits and Unpaid Invoices - Right Side -->
            <div class="col-xl-8">
                <!-- Cash Deposits Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Cash Deposits</h5>
                            <div class="btn-group">
                                @can('create cash deposit')
                                <a href="{{ route('cash_collaterals.create') }}?customer_id={{ Hashids::encode($customer->id) }}" class="btn btn-sm btn-primary">
                                    <i class="bx bx-plus"></i> New Deposit Account
                                </a>
                                @endcan
                            </div>
                        </div>
                        <hr class="my-4">

                        <div class="table-responsive">
                            <table class="table table-bordered dt-responsive nowrap table-striped" id="cashDepositsTable">
                                <thead>
                                    <tr>
                                        <th>Deposit Type</th>
                                        <th>Current Balance</th>
                                        <th>Date Created</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Customer Unpaid Invoices Card -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-size-16 text-truncate mb-0">Customer Unpaid Invoices</h5>
                            <a href="{{ route('sales.invoices.create', ['customer_id' => Hashids::encode($customer->id)]) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-plus"></i> Create New Invoice
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="unpaidInvoicesTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Balance Due</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Send Customer SMS Modal (inline to ensure presence in DOM) -->
        <div class="modal fade" id="sendCustomerSmsModal" tabindex="-1" aria-labelledby="sendCustomerSmsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendCustomerSmsModalLabel">Send SMS to {{ $customer->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="sendCustomerSmsForm" action="{{ route('customers.send-sms', Hashids::encode($customer->id)) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="customer_message_title" class="form-label">Message Title</label>
                                <select class="form-select" id="customer_message_title" name="message_title" required>
                                    <option value="">Select a title...</option>
                                    <option value="Payment Reminder">Payment Reminder</option>
                                    <option value="Custom">Custom Title</option>
                                </select>
                            </div>
                            <div class="mb-3" id="customer_message_content_wrapper">
                                <label for="customer_message_content" class="form-label">Message Content</label>
                                <textarea class="form-control" id="customer_message_content" name="bulk_message_content" rows="4" maxlength="500"></textarea>
                                <div class="form-text"><span id="customer_character_count">0</span>/500 characters</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="sendCustomerSmsBtn">
                                <i class="bx bx-send me-1"></i> Send SMS
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->
        <footer class="page-footer">
            <p class="mb-0">Copyright © {{ date('Y') }}. All right reserved. -- By SAFCO FINTECH</p>
        </footer>

        @endsection

        @push('scripts')
        <script nonce="{{ $cspNonce ?? '' }}">
            // Server-side DataTables Initialization
            $(document).ready(function() {
                // Cash Deposits Table
                $('#cashDepositsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("customers.deposits.datatable", Hashids::encode($customer->id)) }}',
                        type: 'GET'
                    },
                    columns: [
                        {data: 'type_name', name: 'type_name'},
                        {data: 'formatted_amount', name: 'amount'},
                        {data: 'formatted_date', name: 'created_at'},
                        {data: 'actions', name: 'actions', orderable: false, searchable: false}
                    ],
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                    order: [[2, 'desc']], // Sort by date descending
                    language: {
                        search: "Search deposit accounts:",
                        lengthMenu: "Show _MENU_ accounts per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ deposit accounts",
                        infoEmpty: "Showing 0 to 0 of 0 deposit accounts",
                        infoFiltered: "(filtered from _MAX_ total accounts)",
                        zeroRecords: "No deposit accounts found",
                        processing: "Loading deposit accounts..."
                    }
                });

                // Unpaid Invoices Table
                $('#unpaidInvoicesTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("customers.invoices.datatable", Hashids::encode($customer->id)) }}',
                        type: 'GET'
                    },
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'invoice_number', name: 'invoice_number'},
                        {data: 'formatted_date', name: 'invoice_date'},
                        {data: 'formatted_total', name: 'total_amount', className: 'text-end'},
                        {data: 'formatted_balance', name: 'balance_due', className: 'text-end'},
                        {data: 'status_badge', name: 'status'},
                        {data: 'actions', name: 'actions', orderable: false, searchable: false}
                    ],
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
                    order: [[2, 'desc']], // Sort by date descending
                    language: {
                        search: "Search invoices:",
                        lengthMenu: "Show _MENU_ invoices per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ invoices",
                        infoEmpty: "Showing 0 to 0 of 0 invoices",
                        infoFiltered: "(filtered from _MAX_ total invoices)",
                        zeroRecords: "No invoices found",
                        processing: "Loading invoices..."
                    }
                });

                // Delete confirmation
                $('.delete-form').on('submit', function(e) {
                    e.preventDefault();
                    const form = $(this);
                    const customerName = form.find('button').data('name');
                    
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Are you sure you want to delete customer "${customerName}"? This action cannot be undone.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
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
                                            title: 'Deleted!',
                                            text: response.message || 'Customer has been deleted successfully.',
                                            icon: 'success',
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            window.location.href = '{{ route("customers.index") }}';
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.message || 'Failed to delete customer.',
                                            icon: 'error'
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    let errorMessage = 'Failed to delete customer.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        errorMessage = xhr.responseText;
                                    }
                                    
                                    Swal.fire({
                                        title: 'Error!',
                                        text: errorMessage,
                                        icon: 'error'
                                    });
                                }
                            });
                        }
                    });
                });
            });
        (function(){
            const titleEl = document.getElementById('customer_message_title');
            const contentEl = document.getElementById('customer_message_content');
            const wrapperEl = document.getElementById('customer_message_content_wrapper');
            const countEl = document.getElementById('customer_character_count');

            function updateCount(){
                countEl.textContent = (contentEl.value || '').length;
            }
            function toggleContent(){
                if (titleEl.value === 'Payment Reminder') {
                    // Prefill a template; hide the content box
                    if (!contentEl.value || contentEl.getAttribute('data-autofilled') !== 'yes') {
                        contentEl.value = 'Dear Customer, this is a friendly reminder to clear your outstanding balance. Please make your payment at your earliest convenience. Thank you.';
                        contentEl.setAttribute('data-autofilled','yes');
                    }
                    wrapperEl.style.display = 'none';
                } else {
                    wrapperEl.style.display = '';
                }
                updateCount();
            }
            titleEl.addEventListener('change', toggleContent);
            contentEl.addEventListener('input', updateCount);
            toggleContent();

            const form = document.getElementById('sendCustomerSmsForm');
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const btn = document.getElementById('sendCustomerSmsBtn');
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Sending...';
                btn.disabled = true;
                const data = new FormData(form);
                fetch(form.action, { method:'POST', body:data, headers:{ 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                    .then(r => r.json())
                    .then(resp => {
                        if (resp.success) {
                            Swal.fire({ icon:'success', title:'SMS Sent', timer:2000, showConfirmButton:false });
                            const m = bootstrap.Modal.getInstance(document.getElementById('sendCustomerSmsModal'));
                            m && m.hide();
                        } else {
                            Swal.fire({ icon:'error', title:'Failed', text: resp.message || 'Failed to send SMS' });
                        }
                    })
                    .catch(() => Swal.fire({ icon:'error', title:'Network Error', text:'Please try again.' }))
                    .finally(() => { btn.innerHTML = original; btn.disabled = false; });
            });
        })();
        </script>
        @endpush