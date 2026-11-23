<?php

namespace App\Providers;

use App\Models\AcademicClass;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\FeeInvoice;
use App\Policies\AcademicClassPolicy;
use App\Policies\AssignmentPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\SchoolFeePolicy;
use App\Services\SchoolBrandingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Globally share institutional school branding & Infix Appearance system
        View::composer('*', function ($view) {
            $view->with('school', SchoolBrandingService::get());
            $view->with('appearance', \App\Services\AppearanceService::get());
        });

        // Explicitly register model authorization policies
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Enrollment::class, EnrollmentPolicy::class);
        Gate::policy(AcademicClass::class, AcademicClassPolicy::class);
        Gate::policy(FeeInvoice::class, SchoolFeePolicy::class);

        // Core Institutional Gates
        Gate::define('admin-only', fn ($user) => $user->isAdmin());
        Gate::define('faculty-access', fn ($user) => $user->isAdmin() || $user->isTeacher());
        Gate::define('parent-portal', fn ($user) => $user->isParent() || $user->isAdmin());
        Gate::define('student-portal', fn ($user) => $user->isStudent());
    }
}
