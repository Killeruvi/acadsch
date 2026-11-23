<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of users, optionally filtered by role.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('search')) {
            $search = strtolower($request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $users = $query->select(['id', 'name', 'email', 'role', 'phone', 'bio', 'created_at'])
                       ->orderBy('name')
                       ->paginate($request->integer('per_page', 20));

        return response()->json($users);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'taughtCourses',
            'enrollments.course',
        ])->findOrFail($id);

        return response()->json([
            'user' => $user,
        ]);
    }
}
