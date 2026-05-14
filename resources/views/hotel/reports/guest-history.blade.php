@extends('layouts.main')

@section('title', 'Guest History Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Guest History Report', 'url' => '#', 'icon' => 'bx bx-user']
        ]" />

        <h6 class="mb-0 text-uppercase">GUEST HISTORY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.guest-history') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="bx bx-search me-1"></i> Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-2">
                    <form method="POST" action="{{ route('hotel.reports.guest-history.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report Data -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Guest Name</th>
                                <th>Phone / Email</th>
                                <th>Visits Count</th>
                                <th>Total Spent</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($guests as $guestData)
                                <tr>
                                    <td>{{ $guestData['guest']->first_name }} {{ $guestData['guest']->last_name }}</td>
                                    <td>
                                        {{ $guestData['guest']->phone ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $guestData['guest']->email ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-end">{{ $guestData['visits_count'] }}</td>
                                    <td class="text-end"><strong>TZS {{ number_format($guestData['total_spent'], 2) }}</strong></td>
                                    <td>{{ $guestData['last_visit'] ? \Carbon\Carbon::parse($guestData['last_visit'])->format('M d, Y') : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No guest history found.</td>
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
