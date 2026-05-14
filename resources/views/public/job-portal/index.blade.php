<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Career Opportunities - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
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
            --gray-900: #0f172a;
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

        .nav-link:hover, .nav-link.active {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5rem 0 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .stats-row {
            display: flex;
            gap: 3rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        /* Job Cards */
        .job-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--gray-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: var(--primary-color);
        }

        .job-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-100);
        }

        .job-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .job-card-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .job-meta-item i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .job-card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .job-description-preview {
            color: var(--gray-600);
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-badges-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .job-badge {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            border: 1px solid var(--gray-200);
        }

        .job-badge.primary {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border-color: rgba(37, 99, 235, 0.2);
        }

        .job-badge.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .job-badge.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border-color: rgba(245, 158, 11, 0.2);
        }

        .job-card-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .job-closing-date {
            font-size: 0.8125rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .job-closing-date i {
            color: var(--warning-color);
        }

        .btn-view-job {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-view-job:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state-icon {
            font-size: 5rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }

        .empty-state-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.75rem;
        }

        .empty-state-text {
            color: var(--gray-600);
            font-size: 1rem;
        }

        /* Pagination */
        .pagination-wrapper {
            margin-top: 3rem;
            display: flex;
            justify-content: center;
        }

        .pagination .page-link {
            border-radius: 8px;
            margin: 0 0.25rem;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
            padding: 0.5rem 1rem;
        }

        .pagination .page-link:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
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

        /* Footer */
        footer {
            background: white;
            border-top: 1px solid var(--gray-200);
            padding: 2rem 0;
            margin-top: 4rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .hero-section {
                padding: 3rem 0 2.5rem;
            }

            .stats-row {
                gap: 2rem;
            }

            .job-card {
                margin-bottom: 1.5rem;
            }
        }

        /* Loading Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .job-card {
            animation: fadeInUp 0.5s ease-out;
        }

        .job-card:nth-child(1) { animation-delay: 0.1s; }
        .job-card:nth-child(2) { animation-delay: 0.2s; }
        .job-card:nth-child(3) { animation-delay: 0.3s; }
        .job-card:nth-child(4) { animation-delay: 0.4s; }
        .job-card:nth-child(5) { animation-delay: 0.5s; }
        .job-card:nth-child(6) { animation-delay: 0.6s; }
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
                        <a class="nav-link active" href="{{ route('public.job-portal.index') }}">
                            <i class="bx bx-list-ul me-1"></i>All Jobs
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="hero-title">Find Your Next Opportunity</h1>
                    <p class="hero-subtitle">
                        Join our team and make a difference. Explore exciting career opportunities that match your skills and aspirations.
                    </p>
                    <div class="stats-row">
                        <div class="stat-item">
                            <span class="stat-number">{{ $jobs->total() }}</span>
                            <span class="stat-label">Open Positions</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ $jobs->where('recruitment_type', 'external')->count() + $jobs->where('recruitment_type', 'both')->count() }}</span>
                            <span class="stat-label">External Opportunities</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">{{ $jobs->where('recruitment_type', 'internal')->count() + $jobs->where('recruitment_type', 'both')->count() }}</span>
                            <span class="stat-label">Internal Positions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container" style="margin-top: -2rem; position: relative; z-index: 2;">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-2" style="font-size: 1.25rem;"></i>
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($jobs->count() > 0)
            <div class="row g-4">
                @foreach($jobs as $job)
                    <div class="col-md-6 col-lg-4">
                        <div class="job-card">
                            <div class="job-card-header">
                                <h3 class="job-card-title">{{ $job->job_title }}</h3>
                                <div class="job-card-meta">
                                    @if($job->position)
                                        <div class="job-meta-item">
                                            <i class="bx bx-briefcase"></i>
                                            <span>{{ $job->position->title }}</span>
                                        </div>
                                    @endif
                                    @if($job->department)
                                        <div class="job-meta-item">
                                            <i class="bx bx-building"></i>
                                            <span>{{ $job->department->name }}</span>
                                        </div>
                                    @endif
                                    @if($job->company)
                                        <div class="job-meta-item">
                                            <i class="bx bx-buildings"></i>
                                            <span>{{ $job->company->name }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="job-card-body">
                                @if($job->job_description)
                                    <p class="job-description-preview">
                                        {{ Str::limit(strip_tags($job->job_description), 120) }}
                                    </p>
                                @else
                                    <p class="job-description-preview text-muted">
                                        No description available.
                                    </p>
                                @endif

                                <div class="job-badges-container">
                                    @if($job->budgeted_salary_min || $job->budgeted_salary_max)
                                        <span class="job-badge primary">
                                            <i class="bx bx-money"></i>
                                            @if($job->budgeted_salary_min && $job->budgeted_salary_max)
                                                TZS {{ number_format($job->budgeted_salary_min / 1000) }}K - {{ number_format($job->budgeted_salary_max / 1000) }}K
                                            @elseif($job->budgeted_salary_min)
                                                From TZS {{ number_format($job->budgeted_salary_min / 1000) }}K
                                            @else
                                                Up to TZS {{ number_format($job->budgeted_salary_max / 1000) }}K
                                            @endif
                                        </span>
                                    @endif
                                    <span class="job-badge success">
                                        <i class="bx bx-group"></i>
                                        {{ $job->number_of_positions }} Position{{ $job->number_of_positions > 1 ? 's' : '' }}
                                    </span>
                                    @if($job->recruitment_type)
                                        <span class="job-badge warning">
                                            <i class="bx bx-globe"></i>
                                            {{ ucfirst($job->recruitment_type) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="job-card-footer">
                                @if($job->closing_date)
                                    <div class="job-closing-date">
                                        <i class="bx bx-calendar"></i>
                                        <span>Closes on {{ $job->closing_date->format('M d, Y') }}</span>
                                    </div>
                                @endif
                                <a href="{{ route('public.job-portal.show', $job->hash_id) }}" class="btn-view-job">
                                    <span>View Details & Apply</span>
                                    <i class="bx bx-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($jobs->hasPages())
                <div class="pagination-wrapper">
                    {{ $jobs->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bx bx-briefcase-alt-2"></i>
                </div>
                <h2 class="empty-state-title">No Job Openings Available</h2>
                <p class="empty-state-text">
                    We don't have any open positions at the moment. Please check back later for new opportunities.
                </p>
            </div>
        @endif
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
</body>
</html>
