<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (TransportExceptionInterface|InvalidArgumentException $exception) {
            // Do not leave a failed send throttling the user's next attempt.
            if ($user = User::where('email', $request->input('email'))->first()) {
                Password::deleteToken($user);
            }

            Log::error('Password reset email could not be sent.', [
                'mailer' => config('mail.default'), 'exception_type' => $exception::class,
            ]);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Password reset email is temporarily unavailable. Please try again later or contact the SMO.',
            ]);
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'If an account with that email exists, we have sent a password reset link.');
    }
}
