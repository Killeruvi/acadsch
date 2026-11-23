<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = Auth::user();
        $allClasses = AcademicClass::orderBy('grade_level')->get();

        $selectedClassId = $request->query('class_id');
        $tab = $request->query('tab', 'xp'); // 'xp', 'streak'

        $query = User::where('role', 'student')
            ->with(['batch.academicClass', 'classEnrollments.academicClass', 'badges']);

        if ($selectedClassId) {
            $query->where(function ($q) use ($selectedClassId) {
                $q->whereHas('classEnrollments', fn ($sub) => $sub->where('class_id', $selectedClassId))
                    ->orWhereHas('batch', fn ($sub) => $sub->where('class_id', $selectedClassId));
            });
        }

        if ($tab === 'streak') {
            $query->orderBy('streak_count', 'desc')->orderBy('xp_points', 'desc');
        } else {
            $query->orderBy('xp_points', 'desc')->orderBy('streak_count', 'desc');
        }

        $leaderboardUsers = $query->paginate(20)->withQueryString();

        // Top 3 Podium
        $topThree = (clone $query)->take(3)->get();

        // Find user position
        $myRank = null;
        if ($currentUser->isStudent()) {
            if ($tab === 'streak') {
                $myRank = (clone $query)->where('streak_count', '>', $currentUser->streak_count ?? 0)->count() + 1;
            } else {
                $myRank = (clone $query)->where('xp_points', '>', $currentUser->xp_points)->count() + 1;
            }
        }

        return view('leaderboard.index', compact(
            'leaderboardUsers',
            'topThree',
            'allClasses',
            'selectedClassId',
            'tab',
            'currentUser',
            'myRank'
        ));
    }
}
