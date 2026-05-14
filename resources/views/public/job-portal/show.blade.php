<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $job->job_title }} - {{ config('app.name') }} Career Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #102329;
            --primary-dark: #102329;
            --secondary-color: #102329;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #102329;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--gray-800);
            background: var(--gray-50);
            line-height: 1.6;
            font-size: 15px;
        }

        /* Navigation */
        .navbar {
            background: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            color: var(--gray-600) !important;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .job-hero {
            background: #102329;
            color: white;
            padding: 4rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .job-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .job-hero .container {
            position: relative;
            z-index: 1;
        }

        .job-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1.5rem;
            align-items: center;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .job-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .job-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Main Content */
        .main-content {
            margin-top: -2rem;
            position: relative;
            z-index: 2;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: none;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header-modern {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            padding: 1.5rem;
        }

        .card-header-modern h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body-modern {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-description {
            color: var(--gray-700);
            line-height: 1.8;
            font-size: 1rem;
        }

        .job-description p {
            margin-bottom: 1rem;
        }

        .requirements-list {
            list-style: none;
            padding: 0;
        }

        .requirements-list li {
            padding: 0.75rem 0;
            padding-left: 2rem;
            position: relative;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
        }

        .requirements-list li:last-child {
            border-bottom: none;
        }

        .requirements-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--success-color);
            font-weight: 700;
            font-size: 1.25rem;
        }

        /* Application Form */
        .application-form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: none;
            position: sticky;
            top: 2rem;
        }

        .form-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-100);
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .form-control, .form-select {
            border: 1.5px solid var(--gray-300);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -1px rgba(37, 99, 235, 0.4);
        }

        .info-box {
            background: var(--gray-50);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .info-box-title {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }

        .info-box-content {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Footer */
        footer {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 2rem 0;
            margin-top: 4rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .job-title {
                font-size: 1.75rem;
            }

            .job-hero {
                padding: 2.5rem 0 2rem;
            }

            .main-content {
                margin-top: -1rem;
            }

            .application-form-card {
                position: static;
                margin-top: 2rem;
            }

            .card-body-modern {
                padding: 1.5rem;
            }
        }

        /* File Upload Styling */
        .file-upload-wrapper {
            position: relative;
        }

        .file-upload-label {
            display: block;
            padding: 1rem;
            border: 2px dashed var(--gray-300);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--gray-50);
            position: relative;
        }

        .file-upload-label:hover:not(.file-uploaded) {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .file-upload-label.file-uploaded {
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
            border-style: solid;
        }

        .file-upload-label i {
            font-size: 2rem;
            color: var(--gray-400);
            margin-bottom: 0.5rem;
        }

        .file-upload-label.file-uploaded i {
            color: var(--success-color);
        }

        input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* Progress Bar */
        .upload-progress-wrapper {
            margin-top: 0.75rem;
            display: none;
        }

        .upload-progress-wrapper.show {
            display: block;
        }

        .upload-progress-bar {
            width: 100%;
            height: 8px;
            background: var(--gray-200);
            border-radius: 4px;
            overflow: hidden;
        }

        .upload-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        .upload-progress-text {
            font-size: 0.75rem;
            color: var(--gray-600);
            margin-top: 0.25rem;
            text-align: center;
        }

        /* File Uploaded State */
        .file-uploaded-info {
            display: none;
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 6px;
            border: 1px solid var(--success-color);
        }

        .file-uploaded-info.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .file-uploaded-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .file-uploaded-name i {
            color: var(--success-color);
            font-size: 1.25rem;
        }

        .file-remove-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            transition: all 0.2s;
        }

        .file-remove-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        /* Qualification Rows */
        .qualification-row {
            background: var(--gray-50);
            border: 1.5px solid var(--gray-200);
            transition: all 0.2s;
        }

        .qualification-row:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.02);
        }

        .qualification-row h6 {
            font-size: 0.9375rem;
            font-weight: 600;
        }

        .required-documents-container {
            margin-top: 1rem;
        }

        .required-documents-container .file-upload-label {
            padding: 0.75rem;
            font-size: 0.875rem;
        }

        .required-documents-container .file-upload-label i {
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="{{ route('public.job-portal.index') }}">
                <i class="bx bx-briefcase me-2"></i>{{ config('app.name') }} Careers
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('public.job-portal.index') }}">
                            <i class="bx bx-arrow-back me-1"></i>Browse All Jobs
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="job-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="job-title">{{ $job->job_title }}</h1>
                    <div class="job-meta">
                        @if($job->position)
                            <div class="job-meta-item">
                                <i class="bx bx-briefcase" style="font-size: 1.25rem;"></i>
                                <span>{{ $job->position->title }}</span>
                            </div>
                        @endif
                        @if($job->department)
                            <div class="job-meta-item">
                                <i class="bx bx-building" style="font-size: 1.25rem;"></i>
                                <span>{{ $job->department->name }}</span>
                            </div>
                        @endif
                        @if($job->company)
                            <div class="job-meta-item">
                                <i class="bx bx-buildings" style="font-size: 1.25rem;"></i>
                                <span>{{ $job->company->name }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="job-badges">
                        @if($job->budgeted_salary_min || $job->budgeted_salary_max)
                            <span class="job-badge">
                                <i class="bx bx-money"></i>
                                @if($job->budgeted_salary_min && $job->budgeted_salary_max)
                                    TZS {{ number_format($job->budgeted_salary_min) }} - {{ number_format($job->budgeted_salary_max) }}
                                @elseif($job->budgeted_salary_min)
                                    From TZS {{ number_format($job->budgeted_salary_min) }}
                                @else
                                    Up to TZS {{ number_format($job->budgeted_salary_max) }}
                                @endif
                            </span>
                        @endif
                        <span class="job-badge">
                            <i class="bx bx-group"></i>
                            {{ $job->number_of_positions }} Position(s)
                        </span>
                        @if($job->closing_date)
                            <span class="job-badge">
                                <i class="bx bx-calendar"></i>
                                Closes {{ $job->closing_date->format('M d, Y') }}
                            </span>
                        @endif
                        @if($job->recruitment_type)
                            <span class="job-badge">
                                <i class="bx bx-globe"></i>
                                {{ ucfirst($job->recruitment_type) }} Recruitment
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <button type="button" class="btn btn-outline-light btn-lg" id="share-job-btn" onclick="copyShareLink()">
                        <i class="bx bx-share-alt me-2"></i>Share Job
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Alerts -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2" style="font-size: 1.25rem;"></i>
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bx bx-error-circle me-2" style="font-size: 1.25rem;"></i>
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(!$isActive)
            <div class="alert alert-warning">
                <i class="bx bx-info-circle me-2" style="font-size: 1.25rem;"></i>
                <strong>Notice:</strong> This position is not currently accepting applications.
            </div>
        @endif

        <div class="row">
            <!-- Job Details -->
            <div class="col-lg-8">
                <!-- Job Description -->
                <div class="content-card">
                    <div class="card-header-modern">
                        <h3>
                            <i class="bx bx-file-blank" style="color: var(--primary-color);"></i>
                            Job Description
                        </h3>
                    </div>
                    <div class="card-body-modern">
                        @if($job->job_description)
                            <div class="job-description">
                                {!! nl2br(e($job->job_description)) !!}
                            </div>
                        @else
                            <p class="text-muted">No job description provided.</p>
                        @endif
                    </div>
                </div>

                <!-- Requirements -->
                @if($job->requirements)
                <div class="content-card">
                    <div class="card-header-modern">
                        <h3>
                            <i class="bx bx-list-check" style="color: var(--primary-color);"></i>
                            Requirements & Qualifications
                        </h3>
                    </div>
                    <div class="card-body-modern">
                        <ul class="requirements-list">
                            @foreach(explode("\n", $job->requirements) as $requirement)
                                @if(trim($requirement))
                                    <li>{{ trim($requirement) }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                <!-- Additional Information -->
                @if($job->opening_date || $job->closing_date || $job->contract_period_months)
                <div class="content-card">
                    <div class="card-header-modern">
                        <h3>
                            <i class="bx bx-info-circle" style="color: var(--primary-color);"></i>
                            Additional Information
                        </h3>
                    </div>
                    <div class="card-body-modern">
                        <div class="row g-3">
                            @if($job->opening_date || $job->closing_date)
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <div class="info-box-title">
                                            <i class="bx bx-calendar me-2"></i>Application Period
                                        </div>
                                        <div class="info-box-content">
                                            @if($job->opening_date)
                                                <strong>Opening:</strong> {{ $job->opening_date->format('F d, Y') }}<br>
                                            @endif
                                            @if($job->closing_date)
                                                <strong>Closing:</strong> {{ $job->closing_date->format('F d, Y') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if($job->contract_period_months)
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <div class="info-box-title">
                                            <i class="bx bx-time me-2"></i>Contract Duration
                                        </div>
                                        <div class="info-box-content">
                                            {{ $job->contract_period_months }} {{ $job->contract_period_months == 1 ? 'Month' : 'Months' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Application Form Sidebar -->
            <div class="col-lg-4">
                <div class="application-form-card">
                    <div class="card-header-modern">
                        <h3>
                            <i class="bx bx-edit" style="color: var(--primary-color);"></i>
                            Apply Now
                        </h3>
                    </div>
                    <div class="card-body-modern">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                <i class="bx bx-check-circle me-2"></i>
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="bx bx-error-circle me-2"></i>
                                <strong>Please correct the following errors:</strong>
                                <ul class="mb-0 mt-2 small">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if($isActive)
                            <form action="{{ route('public.job-portal.apply', $job->hash_id) }}" method="POST" enctype="multipart/form-data" id="applicationForm">
                                @csrf

                                <div class="form-section-title">Personal Information</div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name') }}">
                                    @error('middle_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Phone <span class="text-danger">*</span></label>
                                        <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number') }}" required>
                                        @error('phone_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                        <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                                        @error('date_of_birth')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                            <option value="">Select</option>
                                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-section-title">Professional Details</div>

                                <div class="form-section-title">Qualifications & Education</div>

                                @if(isset($qualifications) && $qualifications->count() > 0)
                                    <div id="qualifications-container">
                                        <!-- Qualification rows will be added here -->
                                    </div>

                                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add-qualification-btn">
                                        <i class="bx bx-plus me-1"></i>Add Qualification
                                    </button>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="bx bx-info-circle me-2"></i>
                                        No qualifications are currently configured. Please contact the administrator.
                                    </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label">Years of Experience</label>
                                    <input type="number" name="years_of_experience" class="form-control @error('years_of_experience') is-invalid @enderror" value="{{ old('years_of_experience', 0) }}" min="0">
                                    @error('years_of_experience')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cover Letter</label>
                                    <textarea name="cover_letter" class="form-control @error('cover_letter') is-invalid @enderror" rows="4" placeholder="Tell us why you're interested in this position...">{{ old('cover_letter') }}</textarea>
                                    @error('cover_letter')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-section-title">Documents</div>

                                <div class="mb-3">
                                    <label class="form-label">Resume/CV <small class="text-muted">(PDF, DOC, DOCX - Max 5MB)</small></label>
                                    <div class="file-upload-wrapper">
                                        <label class="file-upload-label" id="label-file-resume">
                                            <i class="bx bx-cloud-upload"></i>
                                            <div>
                                                <strong>Click to upload</strong> or drag and drop
                                            </div>
                                            <small class="text-muted">PDF, DOC, DOCX (MAX. 5MB)</small>
                                            <input type="file" name="resume" id="file-resume" class="@error('resume') is-invalid @enderror" accept=".pdf,.doc,.docx" onchange="handleFileUpload('file-resume', 'resume', 'resume')">
                                        </label>
                                    </div>
                                    <div class="upload-progress-wrapper" id="progress-file-resume">
                                        <div class="upload-progress-bar">
                                            <div class="upload-progress-fill" id="progress-fill-file-resume"></div>
                                        </div>
                                        <div class="upload-progress-text" id="progress-text-file-resume">Uploading...</div>
                                    </div>
                                    <div class="file-uploaded-info" id="uploaded-file-resume">
                                        <div class="file-uploaded-name">
                                            <i class="bx bx-check-circle"></i>
                                            <span id="uploaded-name-file-resume"></span>
                                        </div>
                                        <button type="button" class="file-remove-btn" onclick="removeFile('file-resume', 'resume', 'resume')">
                                            <i class="bx bx-trash"></i>
                                            <span>Remove</span>
                                        </button>
                                    </div>
                                    @error('resume')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Additional CV <small class="text-muted">(Optional)</small></label>
                                    <div class="file-upload-wrapper">
                                        <label class="file-upload-label" id="label-file-cv">
                                            <i class="bx bx-cloud-upload"></i>
                                            <div>
                                                <strong>Click to upload</strong> or drag and drop
                                            </div>
                                            <small class="text-muted">PDF, DOC, DOCX (MAX. 5MB)</small>
                                            <input type="file" name="cv" id="file-cv" class="@error('cv') is-invalid @enderror" accept=".pdf,.doc,.docx" onchange="handleFileUpload('file-cv', 'cv', 'cv')">
                                        </label>
                                    </div>
                                    <div class="upload-progress-wrapper" id="progress-file-cv">
                                        <div class="upload-progress-bar">
                                            <div class="upload-progress-fill" id="progress-fill-file-cv"></div>
                                        </div>
                                        <div class="upload-progress-text" id="progress-text-file-cv">Uploading...</div>
                                    </div>
                                    <div class="file-uploaded-info" id="uploaded-file-cv">
                                        <div class="file-uploaded-name">
                                            <i class="bx bx-check-circle"></i>
                                            <span id="uploaded-name-file-cv"></span>
                                        </div>
                                        <button type="button" class="file-remove-btn" onclick="removeFile('file-cv', 'cv', 'cv')">
                                            <i class="bx bx-trash"></i>
                                            <span>Remove</span>
                                        </button>
                                    </div>
                                    @error('cv')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bx bx-send me-2"></i>Submit Application
                                </button>

                                <p class="text-center text-muted small mt-3 mb-0">
                                    By submitting, you agree to our privacy policy and terms of service.
                                </p>
                            </form>
                        @else
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                Applications are not currently being accepted for this position.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script nonce="{{ $cspNonce ?? '' }}">
        // Qualifications data from server
        const qualifications = @json(isset($qualifications) && $qualifications ? $qualifications->toArray() : []);
        console.log('Qualifications loaded:', qualifications);
        let qualificationCounter = 0;

        // Add qualification row
        function addQualificationRow(qualificationId = '') {
            const container = document.getElementById('qualifications-container');
            const rowId = 'qualification-row-' + qualificationCounter++;
            
            const row = document.createElement('div');
            row.className = 'qualification-row mb-3 p-3 border rounded';
            row.id = rowId;
            row.setAttribute('data-qualification-id', qualificationId);

            // Group qualifications by level
            const groupedQuals = {};
            if (qualifications && Array.isArray(qualifications)) {
                qualifications.forEach(q => {
                    const level = q.level.charAt(0).toUpperCase() + q.level.slice(1).replace('_', ' ');
                    if (!groupedQuals[level]) {
                        groupedQuals[level] = [];
                    }
                    groupedQuals[level].push(q);
                });
            }

            let optionsHtml = '<option value="">Select Qualification</option>';
            for (const level in groupedQuals) {
                optionsHtml += `<optgroup label="${level}">`;
                groupedQuals[level].forEach(q => {
                    const selected = q.id == qualificationId ? 'selected' : '';
                    optionsHtml += `<option value="${q.id}" ${selected}>${q.name}</option>`;
                });
                optionsHtml += '</optgroup>';
            }

            row.innerHTML = `
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 text-primary">
                        <i class="bx bx-graduation me-1"></i>Qualification ${qualificationCounter}
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-qualification" onclick="removeQualificationRow('${rowId}')">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Qualification <span class="text-danger">*</span></label>
                    <select name="qualifications[${rowId}][qualification_id]" class="form-select qualification-select" required onchange="loadRequiredDocuments('${rowId}', this.value)">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="required-documents-container" id="documents-${rowId}">
                    <p class="text-muted small">Select a qualification to see required documents</p>
                </div>
            `;

            container.appendChild(row);
        }

        // Load required documents for a qualification
        function loadRequiredDocuments(rowId, qualificationId) {
            const documentsContainer = document.getElementById('documents-' + rowId);
            
            if (!qualificationId) {
                documentsContainer.innerHTML = '<p class="text-muted small">Select a qualification to see required documents</p>';
                return;
            }

            // Show loading
            documentsContainer.innerHTML = '<p class="text-muted small"><i class="bx bx-loader bx-spin me-1"></i>Loading documents...</p>';

            // Fetch documents via AJAX
            fetch(`/api/qualifications/${qualificationId}/documents`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    const documents = data.documents || [];
                    if (documents.length > 0) {
                        documentsContainer.innerHTML = documents.map((doc, index) => {
                            const fileInputId = `file-${rowId}-${doc.id}`;
                            return `
                            <div class="mb-3 file-upload-item" data-doc-id="${doc.id}">
                                <label class="form-label small">
                                    ${doc.document_name} ${doc.is_required ? '<span class="text-danger">*</span>' : ''}
                                    ${doc.description ? `<br><small class="text-muted">${doc.description}</small>` : ''}
                                </label>
                                <div class="file-upload-wrapper">
                                    <label class="file-upload-label" id="label-${fileInputId}">
                                        <i class="bx bx-cloud-upload"></i>
                                        <div>
                                            <strong>Click to upload</strong> or drag and drop
                                        </div>
                                        <small class="text-muted">PDF, DOC, DOCX (MAX. 5MB)</small>
                                        <input type="file" 
                                               id="${fileInputId}"
                                               name="qualifications[${rowId}][documents][${doc.id}]" 
                                               class="${doc.is_required ? 'required-doc' : ''}" 
                                               accept=".pdf,.doc,.docx" 
                                               ${doc.is_required ? 'required' : ''}>
                                    </label>
                                </div>
                                <div class="upload-progress-wrapper" id="progress-${fileInputId}">
                                    <div class="upload-progress-bar">
                                        <div class="upload-progress-fill" id="progress-fill-${fileInputId}"></div>
                                    </div>
                                    <div class="upload-progress-text" id="progress-text-${fileInputId}">Uploading...</div>
                                </div>
                                <div class="file-uploaded-info" id="uploaded-${fileInputId}">
                                    <div class="file-uploaded-name">
                                        <i class="bx bx-check-circle"></i>
                                        <span id="uploaded-name-${fileInputId}"></span>
                                    </div>
                                    <button type="button" class="file-remove-btn" onclick="removeFile('${fileInputId}', '${rowId}', '${doc.id}')">
                                        <i class="bx bx-trash"></i>
                                        <span>Remove</span>
                                    </button>
                                </div>
                            </div>
                        `;
                        }).join('');
                        
                        // Re-attach event listeners for newly created file inputs
                        attachFileUploadListeners();
                    } else {
                        documentsContainer.innerHTML = '<p class="text-muted small">No documents required for this qualification</p>';
                    }
                })
                .catch(error => {
                    console.error('Error loading documents:', error);
                    documentsContainer.innerHTML = '<p class="text-danger small">Error loading required documents</p>';
                });
        }

        // Remove qualification row
        function removeQualificationRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                row.remove();
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Debug: Check if qualifications are loaded
            console.log('Qualifications loaded:', qualifications);
            console.log('Qualifications count:', qualifications ? qualifications.length : 0);
            console.log('Is array?', Array.isArray(qualifications));

            // Add first qualification row if qualifications exist
            if (qualifications && Array.isArray(qualifications) && qualifications.length > 0) {
                addQualificationRow();
            } else {
                // Show message if no qualifications available
                const container = document.getElementById('qualifications-container');
                if (container) {
                    container.innerHTML = '<div class="alert alert-warning"><i class="bx bx-info-circle me-2"></i>No qualifications are currently configured. Please contact support.</div>';
                }
            }

            // Add qualification button
            const addBtn = document.getElementById('add-qualification-btn');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    if (qualifications && Array.isArray(qualifications) && qualifications.length > 0) {
                        addQualificationRow();
                    } else {
                        alert('No qualifications available to add.');
                    }
                });
            }

            // Attach file upload listeners
            attachFileUploadListeners();
        });

        // Handle file upload with progress
        function handleFileUpload(fileInputId, rowId, docId) {
            const fileInput = document.getElementById(fileInputId);
            if (!fileInput) {
                console.error('File input not found:', fileInputId);
                return;
            }

            const label = document.getElementById('label-' + fileInputId);
            const progressWrapper = document.getElementById('progress-' + fileInputId);
            const progressFill = document.getElementById('progress-fill-' + fileInputId);
            const progressText = document.getElementById('progress-text-' + fileInputId);
            const uploadedInfo = document.getElementById('uploaded-' + fileInputId);
            const uploadedName = document.getElementById('uploaded-name-' + fileInputId);

            if (!fileInput.files || !fileInput.files[0]) {
                return;
            }

            if (!label || !progressWrapper || !progressFill || !progressText || !uploadedInfo || !uploadedName) {
                console.error('Required elements not found for file upload');
                return;
            }

            const file = fileInput.files[0];
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2); // Size in MB

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds 5MB limit. Please choose a smaller file.');
                fileInput.value = '';
                return;
            }

            // Hide label, show progress
            label.style.display = 'none';
            progressWrapper.classList.add('show');
            uploadedInfo.classList.remove('show');

            // Simulate upload progress (since actual upload happens on form submit)
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    
                    // Hide progress, show uploaded state
                    setTimeout(() => {
                        // Store the file input before modifying innerHTML
                        const existingFileInput = document.getElementById(fileInputId);
                        
                        progressWrapper.classList.remove('show');
                        label.classList.add('file-uploaded');
                        label.style.display = 'block';
                        label.innerHTML = `
                            <i class="bx bx-check-circle" style="color: var(--success-color);"></i>
                            <div>
                                <strong>${fileName}</strong>
                            </div>
                            <small class="text-muted">${fileSize} MB</small>
                        `;
                        
                        // Re-add the file input to the label if it exists
                        if (existingFileInput) {
                            label.appendChild(existingFileInput);
                        }
                        
                        uploadedInfo.classList.add('show');
                        uploadedName.textContent = fileName;
                    }, 300);
                } else {
                    progressFill.style.width = progress + '%';
                    progressText.textContent = `Uploading... ${Math.round(progress)}%`;
                }
            }, 100);
        }

        // Remove file
        function removeFile(fileInputId, rowId, docId) {
            // Try to find the file input - it might be in the label or wrapper
            let fileInput = document.getElementById(fileInputId);
            
            // If not found by ID, try to find it in the file-upload-wrapper
            if (!fileInput) {
                const label = document.getElementById('label-' + fileInputId);
                if (label) {
                    const wrapper = label.closest('.file-upload-wrapper');
                    if (wrapper) {
                        fileInput = wrapper.querySelector('input[type="file"]');
                    }
                }
            }
            
            // If still not found, try to find by name attribute
            if (!fileInput) {
                const namePattern = rowId.includes('qualification-row') 
                    ? `qualifications[${rowId}][documents][${docId}]`
                    : (rowId === 'resume' ? 'resume' : 'cv');
                fileInput = document.querySelector(`input[type="file"][name="${namePattern}"]`);
            }
            
            if (!fileInput) {
                console.warn('File input not found by ID, will recreate:', fileInputId);
                // We'll recreate it below
            }

            // Get label and other elements
            const label = document.getElementById('label-' + fileInputId);
            const progressWrapper = document.getElementById('progress-' + fileInputId);
            const uploadedInfo = document.getElementById('uploaded-' + fileInputId);

            // Get the original input attributes before removing (or use defaults)
            const isRequired = fileInput ? fileInput.hasAttribute('required') : false;
            const acceptTypes = fileInput ? (fileInput.getAttribute('accept') || '.pdf,.doc,.docx') : '.pdf,.doc,.docx';
            let inputName = fileInput ? fileInput.getAttribute('name') : '';
            
            // If we don't have the name, construct it from rowId and docId
            if (!inputName) {
                if (rowId.includes('qualification-row')) {
                    inputName = `qualifications[${rowId}][documents][${docId}]`;
                } else if (rowId === 'resume') {
                    inputName = 'resume';
                } else if (rowId === 'cv') {
                    inputName = 'cv';
                }
            }
            
            const inputClass = fileInput ? (fileInput.getAttribute('class') || '') : '';

            // Create a completely new file input element
            const newFileInput = document.createElement('input');
            newFileInput.type = 'file';
            newFileInput.id = fileInputId;
            newFileInput.name = inputName;
            newFileInput.accept = acceptTypes;
            newFileInput.className = inputClass;
            if (isRequired) {
                newFileInput.setAttribute('required', 'required');
            }
            
            // Replace the old input with the new one if it exists
            if (fileInput && fileInput.parentNode) {
                fileInput.parentNode.replaceChild(newFileInput, fileInput);
            }
            
            // Reset UI
            if (label) {
                label.classList.remove('file-uploaded');
                label.style.display = 'block';
                
                // Recreate the label content
                label.innerHTML = `
                    <i class="bx bx-cloud-upload"></i>
                    <div>
                        <strong>Click to upload</strong> or drag and drop
                    </div>
                    <small class="text-muted">PDF, DOC, DOCX (MAX. 5MB)</small>
                `;
                
                // Re-add the file input to the label
                label.appendChild(newFileInput);
                
                // Reattach the change event listener
                newFileInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        handleFileUpload(fileInputId, rowId, docId);
                    }
                });
            }
            
            if (progressWrapper) {
                progressWrapper.classList.remove('show');
            }
            
            if (uploadedInfo) {
                uploadedInfo.classList.remove('show');
            }
            
            // Reset progress bar
            const progressFill = document.getElementById('progress-fill-' + fileInputId);
            const progressText = document.getElementById('progress-text-' + fileInputId);
            if (progressFill) progressFill.style.width = '0%';
            if (progressText) progressText.textContent = 'Uploading...';
        }

        // Attach file upload listeners to all file inputs
        function attachFileUploadListeners() {
            document.querySelectorAll('input[type="file"]').forEach(input => {
                // Remove existing listeners to avoid duplicates
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                
                // Add change listener
                newInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const fileInputId = this.id;
                        // Extract rowId and docId from the input name or ID
                        const matches = fileInputId.match(/file-(.+?)-(\d+)/);
                        if (matches) {
                            const rowId = matches[1];
                            const docId = matches[2];
                            handleFileUpload(fileInputId, rowId, docId);
                        } else if (fileInputId === 'file-resume' || fileInputId === 'file-cv') {
                            // Handle resume and CV files
                            handleFileUpload(fileInputId, fileInputId.replace('file-', ''), fileInputId.replace('file-', ''));
                        }
                    }
                });
            });
        }

        // Copy share link functionality
        function copyShareLink() {
            const url = window.location.href;
            
            // Fallback for non-https or older browsers
            if (!navigator.clipboard) {
                const textArea = document.createElement("textarea");
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showCopySuccess();
                } catch (err) {
                    console.error('Fallback: Oops, unable to copy', err);
                }
                document.body.removeChild(textArea);
                return;
            }

            navigator.clipboard.writeText(url).then(() => {
                showCopySuccess();
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }

        function showCopySuccess() {
            Swal.fire({
                icon: 'success',
                title: 'Link Copied!',
                text: 'The job application link has been copied to your clipboard.',
                timer: 2500,
                showConfirmButton: false,
                position: 'top-end',
                toast: true,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }
    </script>
</body>
</html>
