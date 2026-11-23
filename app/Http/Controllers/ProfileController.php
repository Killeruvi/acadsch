<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();

        return view('profile.index', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'min:7', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:30',
            'guardian_email' => 'nullable|email|max:255',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8|confirmed',
        ], [
            'phone.required' => 'A permanent phone number is mandatory and cannot be omitted.',
            'phone.regex' => 'Please provide a valid permanent phone number format.',
        ]);

        if (! empty($validated['new_password'])) {
            if (empty($validated['current_password']) || ! Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'The provided current password does not match.']);
            }
            $user->password = Hash::make($validated['new_password']);
            // Revoke existing API credentials after a credential change.
            $user->tokens()->delete();
        }

        $user->name = $validated['name'];
        $user->phone = $validated['phone'] ?? null;
        $user->bio = $validated['bio'] ?? null;
        $user->date_of_birth = $validated['date_of_birth'] ?? null;
        $user->address = $validated['address'] ?? null;
        $user->blood_group = $validated['blood_group'] ?? null;

        if ($user->isStudent()) {
            $user->guardian_name = $validated['guardian_name'] ?? null;
            $user->guardian_phone = $validated['guardian_phone'] ?? null;
            $user->guardian_email = $validated['guardian_email'] ?? null;
        }

        $user->save();

        if (! empty($validated['new_password'])) {
            // Rotate the current browser session after changing credentials.
            $request->session()->regenerate();
            Cache::put('user_active_session_'.$user->id, $request->session()->getId(), now()->addDay());
        }

        return back()->with('success', 'Profile information updated successfully!');
    }
}
