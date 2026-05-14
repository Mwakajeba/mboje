@extends('layouts.main')

@section('title', 'Guest Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel & Property Management', 'url' => route('hotel.management.index'), 'icon' => 'bx bx-building-house'],
            ['label' => 'Guest Management', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />

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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title">Guest Management</h4>
                                <p class="card-subtitle text-muted">Manage hotel guests and customer information</p>
                            </div>
                            <div>
                                <a href="{{ route('guests.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add Guest
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($guests->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Guest #</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Nationality</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($guests as $guest)
                                            <tr>
                                                <td>{{ $guest->guest_number }}</td>
                                                <td>
                                                    <strong>{{ $guest->first_name }} {{ $guest->last_name }}</strong>
                                                    @if($guest->date_of_birth)
                                                        <br><small class="text-muted">Age: {{ $guest->date_of_birth->age }} years</small>
                                                    @endif
                                                </td>
                                                <td>{{ $guest->email ?: 'Not provided' }}</td>
                                                <td>{{ $guest->phone ?: 'Not provided' }}</td>
                                                <td>{{ $guest->nationality ?: 'Not specified' }}</td>
                                                <td>
                                                    @switch($guest->status)
                                                        @case('active')
                                                            <span class="badge bg-success">Active</span>
                                                            @break
                                                        @case('inactive')
                                                            <span class="badge bg-secondary">Inactive</span>
                                                            @break
                                                        @case('blacklisted')
                                                            <span class="badge bg-danger">Blacklisted</span>
                                                            @break
                                                    @endswitch
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('guests.show', $guest) }}" class="btn btn-sm btn-outline-primary">
                                                            <i class="bx bx-show"></i>
                                                        </a>
                                                        <a href="{{ route('guests.edit', $guest) }}" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bx bx-edit"></i>
                                                        </a>
                                                        <form method="POST" action="{{ route('guests.destroy', $guest) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this guest?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="d-flex justify-content-center">
                                {{ $guests->links() }}
                            </div>
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="bx bx-user font-size-48 mb-3 d-block"></i>
                                <h5>No guests found</h5>
                                <p>Start by adding your first guest to begin managing customer information.</p>
                                <a href="{{ route('guests.create') }}" class="btn btn-primary">
                                    <i class="bx bx-plus me-1"></i> Add First Guest
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
