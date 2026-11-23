<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            $reason = 'Unauthorized role ['.($user->role ?? 'unknown').']. Required roles: '.implode(', ', $roles);

            // Log rejected attempt to system logger
            Log::warning('Security: Rejected unauthorized access attempt', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
                'reason' => $reason,
            ]);

            // Persist rejected attempt to security/admin audit trail
            try {
                DB::table('school_admin_audits')->insert([
                    'user_id' => $user->id,
                    'action' => 'access_rejected',
                    'description' => "Access denied to '{$request->path()}' for {$user->name} ({$user->role}). Reason: {$reason}",
                    'old_values' => null,
                    'new_values' => json_encode([
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'user_role' => $user->role,
                        'required_roles' => $roles,
                        'ip' => $request->ip(),
                    ]),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignore DB logging failure to preserve response flow
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. This action requires one of the following roles: '.implode(', ', $roles),
                ], 403);
            }
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
