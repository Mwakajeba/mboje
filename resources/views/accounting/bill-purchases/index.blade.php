@extends('layouts.main')

@section('title', 'Bill Purchases')

@push('styles')
<style>
    .widgets-icons-2 {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #ededed;
        font-size: 27px;
    }
    
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0a58ca) !important;
    }
    
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #146c43) !important;
    }
    
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0aa2c0) !important;
    }
    
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffb300) !important;
    }
    
    .bg-gradient-danger {
        background: linear-gradient(45deg, #dc3545, #bb2d3b) !important;
    }
    
    .bg-gradient-secondary {
        background: linear-gradient(45deg, #6c757d, #5c636a) !important;
    }
    
    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .table-light {
        background-color: #f8f9fa;
    }
    
    .radius-10 {
        border-radius: 10px;
    }
    
    .border-start {
        border-left-width: 3px !important;
    }
</style>
@endpush

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Purchases', 'url' => route('purchases.index'), 'icon' => 'bx bx-purchase-tag'],
            ['label' => 'Bill Purchases', 'url' => '#', 'icon' => 'bx bx-receipt']
        ]" />

        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-header border-bottom-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="mb-0"><i class="bx bx-receipt me-2"></i>Bill Purchases</h5>
                                <p class="mb-0 text-muted">Manage and track all bill purchases</p>
                            </div>
                            <div>
                                <a href="{{ route('accounting.bill-purchases.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i>Create Bill Purchase
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Total Bills</p>
                                <h4 class="my-1 text-primary">{{ $stats['total'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-primary"><i class="bx bx-receipt align-middle"></i> All bills</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-primary text-white ms-auto">
                                <i class="bx bx-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Paid</p>
                                <h4 class="my-1 text-success">{{ $stats['paid'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-success"><i class="bx bx-check-circle align-middle"></i> Settled</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-success text-white ms-auto">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Pending</p>
                                <h4 class="my-1 text-warning">{{ $stats['pending'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-warning"><i class="bx bx-time align-middle"></i> Awaiting</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-warning text-white ms-auto">
                                <i class="bx bx-time"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card radius-10 border-start border-0 border-3 border-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-secondary">Overdue</p>
                                <h4 class="my-1 text-danger">{{ $stats['overdue'] }}</h4>
                                <p class="mb-0 font-13">
                                    <span class="text-danger"><i class="bx bx-error-circle align-middle"></i> Past due</span>
                                </p>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-gradient-danger text-white ms-auto">
                                <i class="bx bx-error-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills Table -->
        <div class="row">
            <div class="col-12">
                <div class="card radius-10">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="example" class="table table-striped table-bordered" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Reference</th>
                                        <th>Supplier</th>
                                        <th>Date</th>
                                        <th>Due Date</th>
                                        <th>Total Amount</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                        <tbody>
                            @forelse($bills as $index => $bill)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('accounting.bill-purchases.show', $bill) }}" class="text-primary fw-bold">
                                            {{ $bill->reference }}
                                        </a>
                                    </td>
                                    <td>{{ $bill->supplier->name ?? 'N/A' }}</td>
                                    <td>{{ $bill->formatted_date }}</td>
                                    <td>{{ $bill->formatted_due_date ?? 'N/A' }}</td>
                                    <td class="text-end">TZS {{ $bill->formatted_total_amount }}</td>
                                    <td class="text-end">TZS {{ $bill->formatted_paid }}</td>
                                    <td class="text-end">TZS {{ $bill->formatted_balance }}</td>
                                    <td>{!! $bill->status_badge !!}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('accounting.bill-purchases.show', $bill) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View">
                                                <i class="bx bx-show"></i>
                                            </a>
                                            <a href="{{ route('accounting.bill-purchases.edit', $bill) }}" 
                                               class="btn btn-sm btn-outline-warning" 
                                               title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            @if(!$bill->isPaid())
                                                <a href="{{ route('accounting.bill-purchases.payment', $bill) }}" 
                                                   class="btn btn-sm btn-outline-success" 
                                                   title="Add Payment">
                                                    <i class="bx bx-money"></i>
                                                </a>
                                            @endif
                                            @if($bill->payments()->count() == 0)
                                            <form action="{{ route('accounting.bill-purchases.destroy', $bill) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this bill?')"
                                                  style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @else
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Cannot delete bill with existing payments">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bx bx-receipt fs-1"></i>
                                            <p class="mt-2">No bill purchases found</p>
                                            <a href="{{ route('accounting.bill-purchases.create') }}" class="btn btn-primary">
                                                Create Your First Bill
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    $(document).ready(function() {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#example')) {
            $('#example').DataTable().destroy();
        }
        
        // Initialize DataTable
        $('#example').DataTable({
            responsive: true,
            order: [[3, 'desc']], // Sort by date descending
            pageLength: 25,
            language: {
                search: "Search bills:",
                lengthMenu: "Show _MENU_ bills per page",
                info: "Showing _START_ to _END_ of _TOTAL_ bills",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    });
</script>
@endpush 