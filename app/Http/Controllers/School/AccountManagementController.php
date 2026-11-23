<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class AccountManagementController extends Controller
{
    /**
     * Ensure only administrators can execute administrative account resets.
     */
    protected function checkAdmin(): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. Account management and security resets are restricted to administrative authority.');
        }
    }

    /**
     * Request a password reset link without generating or exposing a temporary password.
     * The account password changes only after the account holder completes the signed reset flow.
     */
    public function resetAccount(Request $request, int $id): RedirectResponse
    {
        $this->checkAdmin();

        $targetUser = User::findOrFail($id);
        if (! is_string($targetUser->email) || trim($targetUser->email) === '') {
            return redirect()->back()->withErrors([
                'account_reset' => 'This account has no email address. Add and verify an email address before requesting a password reset.',
            ]);
        }

        $status = Password::sendResetLink(['email' => $targetUser->email]);
        $sent = $status === Password::RESET_LINK_SENT;

        Log::info('Administrative password reset link requested.', [
            'actor_id' => Auth::id(),
            'target_user_id' => $targetUser->id,
            'result' => $status,
        ]);

        if (! $sent) {
            $message = $status === Password::RESET_THROTTLED
                ? 'A reset link was requested recently. Wait before requesting another one.'
                : 'The reset link could not be sent. Check mail configuration and try again.';

            return redirect()->back()->withErrors(['account_reset' => $message]);
        }

        return redirect()->back()->with('account_reset_result', [
            'email' => $targetUser->email,
            'message' => 'A password reset link was sent. No password was exposed or changed by this request.',
        ])->with('success', "Password reset instructions were sent to {$targetUser->email}.");
    }
}
