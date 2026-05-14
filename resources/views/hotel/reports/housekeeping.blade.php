@extends('layouts.main')

@section('title', 'Housekeeping Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Housekeeping Report', 'url' => '#', 'icon' => 'bx bx-broom']
        ]" />

        <h6 class="mb-0 text-uppercase">HOUSEKEEPING REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.housekeeping') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
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
                    <form method="POST" action="{{ route('hotel.reports.housekeeping.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date_from" value="{{ request('date_from', $dateFrom) }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to', $dateTo) }}">
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
                                <th>Room No</th>
                                <th>Cleaning Status</th>
                                <th>Cleaned By</th>
                                <th>Cleaning Date & Time</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($housekeepingData as $data)
                                <tr>
                                    <td>{{ $data['room_no'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $data['cleaning_status'] == 'Cleaned' ? 'success' : 'warning' }}">
                                            {{ $data['cleaning_status'] }}
                                        </span>
                                    </td>
                                    <td>{{ $data['cleaned_by'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($data['cleaning_date'])->format('M d, Y H:i') }}</td>
                                    <td>{{ $data['remarks'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">No housekeeping data found for the selected date range.</td>
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
