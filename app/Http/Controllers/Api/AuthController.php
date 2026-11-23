<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and issue API token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            try {
                DB::table('school_admin_audits')->insert([
                    'user_id' => $user?->id,
                    'action' => 'api_login_failed',
                    'description' => "Failed API authentication for email {$validated['email']}",
                    'old_values' => null,
                    'new_values' => json_encode(['email' => $validated['email'], 'ip' => $request->ip()]),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Match the web guard: inactive student/faculty accounts must not obtain API tokens.
        if (($user->isTeacher() || $user->isStudent()) && ! $user->is_approved) {
            try {
                DB::table('school_admin_audits')->insert([
                    'user_id' => $user->id,
                    'action' => 'api_login_unapproved',
                    'description' => "Unapproved {$user->role} attempted API token generation: {$user->email}",
                    'old_values' => null,
                    'new_values' => json_encode(['role' => $user->role, 'ip' => $request->ip()]),
                    'ip_address' => $request->ip(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
            }

            return response()->json([
                'message' => 'This account is inactive or awaiting administrative approval.',
            ], 403);
        }

        $deviceName = $validated['device_name'] ?? 'api-token';
        $token = $user->createToken($deviceName)->plainTextToken;

        try {
            DB::table('school_admin_audits')->insert([
                'user_id' => $user->id,
                'action' => 'api_login_success',
                'description' => "API token issued for {$user->name} ({$user->role})",
                'old_values' => null,
                'new_values' => json_encode(['role' => $user->role, 'device' => $deviceName, 'ip' => $request->ip()]),
                'ip_address' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'bio' => $user->bio,
            ],
        ]);
    }

    /**
     * Register a new user (defaults to student unless specified or authenticated admin).
     */
    public function register(Request $request): JsonResponse
    {
        // Public API registration must never allow callers to choose privileged roles
        // or assign themselves to a school class. School placement is an admin workflow.
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'bio' => 'nullable|string|max:1000',
            'role' => 'prohibited',
            'class_id' => 'prohibited',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'bio' => $user->bio,
            ],
        ], 201);
    }

    /**
     * Get details of currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Revoke current API token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        // Stateful Sanctum requests may expose a TransientToken, which is not
        // a persisted personal access token and therefore has no delete method.
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
