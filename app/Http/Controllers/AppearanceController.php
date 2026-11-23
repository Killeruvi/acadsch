<?php

namespace App\Http\Controllers;

use App\Services\AppearanceService;
use App\Services\SchoolBrandingService;
use Illuminate\Http\Request;

class AppearanceController extends Controller
{
    /**
     * Display Appearance & Themes configuration hub.
     */
    public function index()
    {
        $appearance = AppearanceService::get();
        $availableThemes = AppearanceService::availableThemes();
        $school = SchoolBrandingService::get();

        return view('settings.appearance', compact('appearance', 'availableThemes', 'school'));
    }

    /**
     * Activate a theme.
     */
    public function activate(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|string',
        ]);

        $success = AppearanceService::setActiveTheme($request->input('theme_id'));

        if ($success) {
            return back()->with('success', 'Theme activated successfully! The system appearance and colors have been updated.');
        }

        return back()->with('error', 'Invalid theme selected.');
    }

    /**
     * Customize color palette.
     */
    public function customize(Request $request)
    {
        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'nullable|string',
            'gradient' => 'nullable|string',
            'bg_color' => 'nullable|string',
            'footer_bg' => 'nullable|string',
            'footer_text' => 'nullable|string',
        ]);

        // Auto-generate a beautiful Infix gradient if not explicitly customized
        if (empty($validated['gradient'])) {
            $validated['gradient'] = "linear-gradient(135deg, {$validated['primary_color']} 0%, ".($validated['accent_color'] ?: $validated['primary_color']).' 100%)';
        }

        AppearanceService::saveCustomColors($validated);

        return back()->with('success', 'Custom appearance palette updated successfully!');
    }

    /**
     * Update Google Fonts configuration.
     */
    public function updateFonts(Request $request)
    {
        $request->validate([
            'family1' => 'required|string|max:50',
            'family2' => 'required|string|max:50',
        ]);

        AppearanceService::saveFonts($request->input('family1'), $request->input('family2'));

        return back()->with('success', 'Typography fonts updated successfully!');
    }

    /**
     * Toggle & configure preloader.
     */
    public function updatePreloader(Request $request)
    {
        $status = $request->has('preloader_status') && $request->input('preloader_status') == '1';
        $style = $request->input('preloader_style', 'rocket');

        AppearanceService::savePreloader($status, $style);

        return back()->with('success', 'Preloader settings updated successfully!');
    }
}
