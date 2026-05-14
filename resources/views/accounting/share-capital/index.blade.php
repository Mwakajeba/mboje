@extends('layouts.main')

@section('title', 'Share Capital Management')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital Management', 'url' => '#', 'icon' => 'bx bx-group']
        ]" />
        <h6 class="mb-0 text-uppercase">SHARE CAPITAL MANAGEMENT</h6>
        <hr />

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Share Classes</h6>
                                <h4 class="mb-0">{{ $totalShareClasses ?? 0 }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary">
                                <i class="bx bx-category"></i>
                            </div>
                        </div>
                        <a href="{{ route('accounting.share-capital.share-classes.index') }}" class="btn btn-sm btn-primary mt-2">
                            <i class="bx bx-list-ul"></i> Manage Classes
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Shareholders</h6>
                                <h4 class="mb-0">{{ $totalShareholders ?? 0 }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success">
                                <i class="bx bx-group"></i>
                            </div>
                        </div>
                        <a href="{{ route('accounting.share-capital.shareholders.index') }}" class="btn btn-sm btn-success mt-2">
                            <i class="bx bx-list-ul"></i> Manage Shareholders
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Share Issues</h6>
                                <h4 class="mb-0">{{ $totalIssues ?? 0 }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info">
                                <i class="bx bx-trending-up"></i>
                            </div>
                        </div>
                        <a href="{{ route('accounting.share-capital.share-issues.index') }}" class="btn btn-sm btn-info mt-2">
                            <i class="bx bx-plus"></i> New Issue
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Dividends</h6>
                                <h4 class="mb-0">{{ $totalDividends ?? 0 }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning">
                                <i class="bx bx-money"></i>
                            </div>
                        </div>
                        <a href="{{ route('accounting.share-capital.dividends.index') }}" class="btn btn-sm btn-warning mt-2">
                            <i class="bx bx-plus"></i> Declare Dividend
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-list-ul"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('accounting.share-capital.share-classes.create') }}" class="btn btn-outline-primary">
                                <i class="bx bx-plus"></i> Create Share Class
                            </a>
                            <a href="{{ route('accounting.share-capital.shareholders.create') }}" class="btn btn-outline-success">
                                <i class="bx bx-user-plus"></i> Add Shareholder
                            </a>
                            <a href="{{ route('accounting.share-capital.share-issues.create') }}" class="btn btn-outline-info">
                                <i class="bx bx-trending-up"></i> Issue New Shares
                            </a>
                            <a href="{{ route('accounting.share-capital.dividends.create') }}" class="btn btn-outline-warning">
                                <i class="bx bx-money"></i> Declare Dividend
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-time"></i> Recent Activities</h6>
                    </div>
                    <div class="card-body">
                        @if(isset($recentIssues) && $recentIssues->count() > 0)
                            <h6 class="text-muted mb-2">Recent Share Issues</h6>
                            <ul class="list-unstyled">
                                @foreach($recentIssues as $issue)
                                    <li class="mb-2">
                                        <small class="text-muted">{{ $issue->issue_date->format('M d, Y') }}</small><br>
                                        <strong>{{ $issue->shareClass->name ?? 'N/A' }}</strong> - 
                                        {{ number_format($issue->total_shares) }} shares
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No recent share issues.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


