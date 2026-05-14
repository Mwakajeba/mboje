@extends('layouts.main')

@section('title', 'Daily Occupancy Report')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Hotel Reports', 'url' => route('hotel.reports.index'), 'icon' => 'bx bx-file'],
            ['label' => 'Daily Occupancy Report', 'url' => '#', 'icon' => 'bx bx-bed']
        ]" />

        <h6 class="mb-0 text-uppercase">DAILY OCCUPANCY REPORT</h6>
        <hr />

        <!-- Filters -->
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('hotel.reports.daily-occupancy') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $date }}">
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
                    <form method="POST" action="{{ route('hotel.reports.daily-occupancy.export-pdf') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="date" value="{{ request('date', $date) }}">
                        <button type="submit" class="btn btn-danger">
                            <i class="bx bx-file-blank me-1"></i> Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h3>{{ number_format($totalRooms) }}</h3>
                        <p class="mb-0">Total Rooms</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3>{{ number_format($availableRooms) }}</h3>
                        <p class="mb-0">Available Rooms</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3>{{ number_format($occupiedRooms) }}</h3>
                        <p class="mb-0">Occupied Rooms</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h3>{{ number_format($occupancyRate, 1) }}%</h3>
                        <p class="mb-0">Occupancy Rate</p>
                    </div>
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
                                <th>Date</th>
                                <th>Total Rooms</th>
                                <th>Occupied Rooms</th>
                                <th>Available Rooms</th>
                                <th>Reserved Rooms</th>
                                <th>Occupancy Rate (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                <td class="text-end">{{ number_format($totalRooms) }}</td>
                                <td class="text-end">{{ number_format($occupiedRooms) }}</td>
                                <td class="text-end">{{ number_format($availableRooms) }}</td>
                                <td class="text-end">{{ number_format($reservedRooms) }}</td>
                                <td class="text-end"><strong>{{ number_format($occupancyRate, 2) }}%</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
