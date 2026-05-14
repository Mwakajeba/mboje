@extends('layouts.main')

@section('title', 'Check-In & Check-Out Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Check-In & Check-Out Report', 'url' => '#', 'icon' => 'bx bx-log-in']
        ]" />

        <h6 class="mb-0 text-uppercase">CHECK-IN & CHECK-OUT REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.check-in-out') }}" class="row g-3">
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
                    <form method="POST" action="{{ route('hotel.reports.check-in-out.export-pdf') }}" style="display: inline;">
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
                                <th>Guest Name</th>
                                <th>Room No</th>
                                <th>Check-in Time</th>
                                <th>Check-out Time</th>
                                <th>Stay Duration</th>
                                <th>Processed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                <tr>
                                    <td>{{ $movement['guest_name'] }}</td>
                                    <td>{{ $movement['room_no'] }}</td>
                                    <td>
                                        @if($movement['type'] == 'Check-In')
                                            {{ \Carbon\Carbon::parse($movement['time'])->format('M d, Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($movement['type'] == 'Check-Out')
                                            {{ \Carbon\Carbon::parse($movement['time'])->format('M d, Y H:i') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $movement['stay_duration'] }}</td>
                                    <td>{{ $movement['processed_by'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No check-in/check-out data found for the selected date range.</td>
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
