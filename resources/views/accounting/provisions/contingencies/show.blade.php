@extends('layouts.main')
@section('title', 'Contingent Item '.$contingency->contingency_number)

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <x-breadcrumbs-with-icons :links="[
                ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
                ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
                ['label' => 'Contingent Items (IAS 37)', 'url' => route('accounting.contingencies.index'), 'icon' => 'bx bx-error'],
                ['label' => $contingency->contingency_number, 'url' => '#', 'icon' => 'bx bx-file']
            ]" />
            <a href="{{ route('accounting.contingencies.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>

        <h6 class="mb-0 text-uppercase">CONTINGENT ITEM DETAILS (DISCLOSURE ONLY)</h6>
        <hr />

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error') || (isset($errors) && $errors->any()))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2"></i>
                @if(session('error'))
                    {{ session('error') }}
                @else
                    Please fix the following errors:
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-3 radius-10 border-0 shadow-sm">
                    <div class="card-header">
                        Core Details
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Reference</dt>
                            <dd class="col-sm-8">{{ $contingency->contingency_number }}</dd>

                            <dt class="col-sm-4">Type</dt>
                            <dd class="col-sm-8">{{ ucfirst($contingency->contingency_type) }}</dd>

                            <dt class="col-sm-4">Title</dt>
                            <dd class="col-sm-8">{{ $contingency->title }}</dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">{{ ucfirst($contingency->status) }}</dd>

                            <dt class="col-sm-4">Probability</dt>
                            <dd class="col-sm-8">
                                {{ ucfirst($contingency->probability) }}
                                @if($contingency->probability_percent)
                                    ({{ number_format($contingency->probability_percent, 2) }}%)
                                @endif
                            </dd>

                            <dt class="col-sm-4">Branch</dt>
                            <dd class="col-sm-8">{{ $contingency->branch->name ?? '-' }}</dd>

                            <dt class="col-sm-4">Linked Provision</dt>
                            <dd class="col-sm-8">
                                @if($contingency->provision)
                                    {{ $contingency->provision->provision_number }} – {{ $contingency->provision->title }}
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">{{ $contingency->description }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card mb-3 radius-10 border-0 shadow-sm">
                    <div class="card-header">
                        Amounts & Resolution (Disclosure Only)
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Expected Amount</dt>
                            <dd class="col-sm-8">
                                @if($contingency->expected_amount !== null)
                                    {{ number_format($contingency->expected_amount, 2) }} {{ $contingency->currency_code }}
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-4">FX Rate at Creation</dt>
                            <dd class="col-sm-8">{{ number_format($contingency->fx_rate_at_creation, 6) }}</dd>

                            <dt class="col-sm-4">Resolution Outcome</dt>
                            <dd class="col-sm-8">{{ $contingency->resolution_outcome ? ucfirst(str_replace('_', ' ', $contingency->resolution_outcome)) : '-' }}</dd>

                            <dt class="col-sm-4">Resolution Date</dt>
                            <dd class="col-sm-8">
                                {{ optional($contingency->resolution_date)->format('Y-m-d') ?? '-' }}
                            </dd>

                            <dt class="col-sm-4">Resolution Notes</dt>
                            <dd class="col-sm-8">{{ $contingency->resolution_notes ?: '-' }}</dd>
                        </dl>
                        <hr>
                        <p class="mb-0 text-muted">
                            <strong>Note:</strong> This record is for <strong>disclosure purposes only</strong>. No journal entries are posted to GL from contingent items until recognition criteria for a provision or asset are met.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


