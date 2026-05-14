@extends('layouts.main')

@php
    use Vinkla\Hashids\Facades\Hashids;
@endphp

@section('title', 'Main Groups')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Main Groups', 'url' => '#', 'icon' => 'bx bx-category']
        ]" />

            <div class="row row-cols-1 row-cols-lg-3">
                <div class="col">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="mb-0">Total</p>
                                    <h4 class="font-weight-bold">{{ $mainGroups->count() }}</h4>
                                </div>
                                <div class="widgets-icons bg-gradient-cosmic text-white"><i class='bx bx-refresh'></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--end row-->

            <h6 class="mb-0 text-uppercase">MAIN GROUPS </h6>
            <hr />
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ $errors->first('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Chart of Accounts - Main Groups</h5>
                        <div>
                            <a href="{{ route('accounting.main-groups.create') }}" class="btn btn-primary ms-2">
                                <i class="bx bx-plus"></i> Add New Main Group
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="mainGroupsTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Account Class</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>In Use</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mainGroups as $index => $mainGroup)
                                    @php
                                        $isInUse = $mainGroup->account_class_groups_count > 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $mainGroup->accountClass->name ?? 'N/A' }}</td>
                                        <td>{{ $mainGroup->name }}</td>
                                        <td>{{ Str::limit($mainGroup->description, 50) }}</td>
                                        <td>
                                            @if($mainGroup->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($isInUse)
                                                <span class="badge bg-info" title="Used by {{ $mainGroup->account_class_groups_count }} Account Class Group(s)">
                                                    Yes ({{ $mainGroup->account_class_groups_count }})
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">No</span>
                                            @endif
                                        </td>
                                        <td>{{ $mainGroup->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('accounting.main-groups.show', Hashids::encode($mainGroup->id)) }}"
                                                class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="{{ route('accounting.main-groups.edit', Hashids::encode($mainGroup->id)) }}"
                                                class="btn btn-sm btn-outline-warning">Edit</a>

                                            @if($isInUse)
                                                <button type="button" class="btn btn-sm btn-outline-danger" disabled
                                                    title="Cannot delete: This Main Group is being used by {{ $mainGroup->account_class_groups_count }} Account Class Group(s)">
                                                    Delete
                                                </button>
                                            @else
                                                <form
                                                    action="{{ route('accounting.main-groups.destroy', Hashids::encode($mainGroup->id)) }}"
                                                    method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        data-name="{{ $mainGroup->name }}">Delete</button>
                                                </form>
                                            @endif
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
    <!--end page wrapper -->
    <!--start overlay-->
    <div class="overlay toggle-icon"></div>
    <!--end overlay-->
    <!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
    <!--End Back To Top Button-->
    <footer class="page-footer">
        <p class="mb-0">Copyright © 2021. All right reserved.</p>
    </footer>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
$(document).ready(function() {
    $('#mainGroupsTable').DataTable({
        responsive: true,
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthChange: true,
        pageLength: 10,
        language: {
            search: "",
            searchPlaceholder: "Search main groups..."
        }
    });
});
</script>
@endpush
