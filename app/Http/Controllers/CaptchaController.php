<?php

namespace App\Http\Controllers;

use App\Services\CaptchaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    /**
     * Generate and stream a fresh dynamic SVG captcha challenge.
     */
    public function image(): Response
    {
        $svg = CaptchaService::generateSvg();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Return fresh inline SVG data URI and encrypted token via JSON.
     */
    public function refresh(): JsonResponse
    {
        $phrase = CaptchaService::generatePhrase();
        $image = CaptchaService::getInlineSvgDataUri($phrase);
        $token = CaptchaService::generateToken($phrase);

        return response()->json([
            'success' => true,
            'image' => $image,
            'token' => $token,
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
