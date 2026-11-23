<?php

namespace App\Http\Middleware;

use App\Models\AcademySetting;
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
        // Skip API routes, health check, dev tools, and static asset streaming
        if ($request->is('api/*') || $request->is('up') || $request->is('_boost/*') || $request->is('storage/*')) {
            return $next($request);
        }

        // Exempt routes required during onboarding, template downloads, or authentication
        if ($request->routeIs('school.onboarding.*') ||
            $request->routeIs('school.bulk.*') ||
            $request->routeIs('login') ||
            $request->routeIs('logout') ||
            $request->routeIs('password.*')) {
            return $next($request);
        }

        $isOnboarded = AcademySetting::get('onboarding_completed') === 'true';

        if (! $isOnboarded) {
            // System is not onboarded: always redirect to installation / onboarding wizard
            return redirect()->route('school.onboarding.index');
        }

        return $next($request);
    }
}
