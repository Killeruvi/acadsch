<?php

namespace App\Http\Middleware;

use App\Models\SchoolSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolOnboarded
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Skip API routes, health check, dev tools, and static asset streaming
        if ($request->is('api/*') || 
            $request->is('up') || 
            $request->is('_boost/*') || 
            $request->is('storage/*') || 
            $request->is('uploads/*') || 
            $request->is('assets/*')) {
            return $next($request);
        }

        $installedFile = storage_path('installed');
        $isInstalled = file_exists($installedFile);

        // 2. Fresh uninstalled system: Redirect everything to the Web Installer wizard
        if (! $isInstalled) {
            if ($request->is('install*')) {
                return $next($request);
            }

            return redirect()->route('install.index');
        }

        // 3. System is already installed: Prevent re-running installer
        if ($request->is('install*')) {
            return redirect()->route('login')->with('info', 'Application is already installed and secured.');
        }

        // 4. Exempt routes required during authentication or onboarding
        if ($request->routeIs('school.onboarding.*') ||
            $request->routeIs('school.bulk.*') ||
            $request->routeIs('login') ||
            $request->routeIs('logout') ||
            $request->routeIs('password.*')) {
            return $next($request);
        }

        // 5. Safely verify onboarding flag
        try {
            $isOnboarded = SchoolSetting::get('onboarding_completed') === 'true';
            if (! $isOnboarded) {
                return redirect()->route('school.onboarding.index');
            }
        } catch (\Throwable $e) {
            // Gracefully ignore database issues if not yet ready
        }

        return $next($request);
    }
}
