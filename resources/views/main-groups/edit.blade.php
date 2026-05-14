@extends('layouts.main')
@section('title', 'Edit Main Group')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <x-breadcrumbs-with-icons :links="[
            ['label' => 'Dashboard', 'url' => route('dashboard'), 'icon' => 'bx bx-home'],
            ['label' => 'Accounting', 'url' => route('accounting.index'), 'icon' => 'bx bx-calculator'],
            ['label' => 'Main Groups', 'url' => route('accounting.main-groups.index'), 'icon' => 'bx bx-category'],
            ['label' => 'Edit Main Group', 'url' => '#', 'icon' => 'bx bx-edit']
        ]" />

            <h6 class="mb-0 text-uppercase">EDIT MAIN GROUP</h6>
            <hr />
            
            <div class="row">
                <!-- Left Column - Form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="bx bx-edit text-warning me-2"></i>Edit Main Group Information
                            </h5>
                            @include('main-groups.form')
                        </div>
                    </div>
                </div>

                <!-- Right Column - Guidelines -->
                <div class="col-lg-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-info-circle me-2"></i>Update Guidelines
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="text-warning mb-3">
                                <i class="bx bx-edit me-1"></i>Editing Main Group
                            </h6>
                            <p class="small">
                                You are updating an existing main group. Any changes will affect how accounts are organized and reported.
                            </p>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-error-circle me-1"></i>Important Considerations
                            </h6>
                            <ul class="small">
                                <li class="mb-2">
                                    <strong>Account Class:</strong> Changing the class may affect related accounts and reports
                                </li>
                                <li class="mb-2">
                                    <strong>Name Changes:</strong> Update the name to reflect current usage
                                </li>
                                <li class="mb-2">
                                    <strong>Status Changes:</strong> Setting to inactive will hide this group from selection
                                </li>
                                <li class="mb-2">
                                    <strong>Description:</strong> Keep it updated for team clarity
                                </li>
                            </ul>

                            <hr>

                            <h6 class="text-warning mb-3">
                                <i class="bx bx-shield-quarter me-1"></i>Best Practices
                            </h6>
                            <div class="small">
                                <ul>
                                    <li class="mb-2">Review all fields before saving</li>
                                    <li class="mb-2">Ensure the name is clear and descriptive</li>
                                    <li class="mb-2">Add detailed description for future reference</li>
                                    <li class="mb-2">Only deactivate if no longer needed</li>
                                </ul>
                            </div>

                            <hr>

                            <div class="alert alert-warning small mb-0">
                                <i class="bx bx-info-circle me-1"></i>
                                <strong>Note:</strong> Changes to this main group will be reflected immediately across all related accounts and reports.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
