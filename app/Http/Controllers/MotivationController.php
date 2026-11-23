<?php

namespace App\Http\Controllers;

use App\Services\MotivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MotivationController extends Controller
{
    /**
     * Return fresh dynamic intellectual spark: quote, mind-blowing academic fact, and streak fire status.
     */
    public function spark(Request $request): JsonResponse
    {
        $streak = Auth::check() ? (Auth::user()->streak_count ?? 1) : 1;
        $payload = MotivationService::getSparkPayload($streak);

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }
}