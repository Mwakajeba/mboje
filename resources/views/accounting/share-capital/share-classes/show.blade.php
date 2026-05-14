@extends('layouts.main')

@section('title', 'Share Class Details')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Share Capital', 'url' => route('accounting.share-capital.index'), 'icon' => 'bx bx-group'],
            ['label' => 'Share Classes', 'url' => route('accounting.share-capital.share-classes.index'), 'icon' => 'bx bx-category'],
            ['label' => $shareClass->name, 'url' => '#', 'icon' => 'bx bx-show']
        ]" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-uppercase">SHARE CLASS DETAILS</h6>
            <div>
                <a href="{{ route('accounting.share-capital.share-classes.edit', $shareClass->encoded_id) }}" class="btn btn-primary me-2">
                    <i class="bx bx-edit"></i> Edit
                </a>
                <a href="{{ route('accounting.share-capital.share-classes.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back"></i> Back
                </a>
            </div>
        </div>
        <hr />
        <h6 class="mb-0 text-uppercase">SHARE CLASS DETAILS</h6>
        <hr />

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Total Issued</h6>
                                <h4 class="mb-0">{{ number_format($totalIssued ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-primary text-primary">
                                <i class="bx bx-trending-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Outstanding</h6>
                                <h4 class="mb-0">{{ number_format($totalOutstanding ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-success text-success">
                                <i class="bx bx-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Holdings</h6>
                                <h4 class="mb-0">{{ number_format($totalHoldings ?? 0) }}</h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-info text-info">
                                <i class="bx bx-group"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-top border-0 border-4 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 text-muted">Status</h6>
                                <h4 class="mb-0">
                                    @if($shareClass->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </h4>
                            </div>
                            <div class="widgets-icons-2 rounded-circle bg-light-warning text-warning">
                                <i class="bx bx-info-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Class Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bx bx-info-circle"></i> Share Class Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="30%">Code:</th>
                                <td><strong>{{ $shareClass->code }}</strong></td>
                            </tr>
                            <tr>
                                <th>Name:</th>
                                <td>{{ $shareClass->name }}</td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td>{{ $shareClass->description ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Share Type:</th>
                                <td><span class="badge bg-info">{{ ucfirst($shareClass->share_type) }}</span></td>
                            </tr>
                            <tr>
                                <th>Classification:</th>
                                <td><span class="badge bg-primary">{{ ucfirst($shareClass->classification) }}</span></td>
                            </tr>
                            <tr>
                                <th>Par Value:</th>
                                <td>
                                    @if($shareClass->has_par_value)
                                        {{ number_format($shareClass->par_value, 6) }} {{ $shareClass->currency_code ?? '' }}
                                    @else
                                        No Par Value
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Authorized Shares:</th>
                                <td>{{ $shareClass->authorized_shares ? number_format($shareClass->authorized_shares) : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Authorized Value:</th>
                                <td>{{ $shareClass->authorized_value ? number_format($shareClass->authorized_value, 2) : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Voting Rights:</th>
                                <td><span class="badge bg-secondary">{{ ucfirst($shareClass->voting_rights) }}</span></td>
                            </tr>
                            <tr>
                                <th>Dividend Policy:</th>
                                <td><span class="badge bg-secondary">{{ ucfirst($shareClass->dividend_policy) }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Attributes -->
                <div class="card mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bx bx-list-check"></i> Share Attributes</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="bx {{ $shareClass->redeemable ? 'bx-check text-success' : 'bx-x text-danger' }}"></i> Redeemable</li>
                                    <li><i class="bx {{ $shareClass->convertible ? 'bx-check text-success' : 'bx-x text-danger' }}"></i> Convertible</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="bx {{ $shareClass->cumulative ? 'bx-check text-success' : 'bx-x text-danger' }}"></i> Cumulative</li>
                                    <li><i class="bx {{ $shareClass->participating ? 'bx-check text-success' : 'bx-x text-danger' }}"></i> Participating</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bx bx-time"></i> Audit Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th>Created:</th>
                                <td>{{ $shareClass->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $shareClass->creator->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td>{{ $shareClass->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Updated By:</th>
                                <td>{{ $shareClass->updater->name ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

