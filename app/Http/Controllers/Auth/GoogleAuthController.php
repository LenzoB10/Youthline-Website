<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $user = null;

        try {
            $googleUser = Socialite::driver('google')->user();

            // Google sign-in is only for people who already have a Youthline account
            // (created by the Admin). It never creates a new account.
            $user = User::query()->where('email', $googleUser->getEmail())->first();
            if (! $user) {
                return redirect()->route('login')->with([
                    'error' => 'There is no Youthline account for this Google email. Ask the administrator to create one.',
                ]);
            }

            // Signing in with Google proves the person controls this email address.
            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            activity()
                ->causedBy($user)
                ->event('Login')
                ->log('User logged in with Google');

            if ($user->status === 'inactive') {
                Auth::logout();

                throw ValidationException::withMessages([
                    'email' => 'You account was banned.',
                ]);
            }

            Auth::login($user);

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            Log::error('Google Auth Error: '.$e->getMessage());

            if ($user?->status === 'Banned') {
                return redirect()->route('login')->with([
                    'error' => 'Unable to login. Your account was Banned',
                ]);
            }

            return redirect()->route('login')->with([
                'error' => 'Unable to login using Google. Please try again.',
            ]);
        }
    }
}
