<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Hr\LeaveType::class => \App\Policies\Leave\LeaveTypePolicy::class,
        \App\Models\Hr\LeaveRequest::class => \App\Policies\Leave\LeaveRequestPolicy::class,
        \App\Models\Hr\LeaveBalance::class => \App\Policies\Leave\LeaveBalancePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

