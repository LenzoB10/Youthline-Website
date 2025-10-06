<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]
            );

            if ($user->wasRecentlyCreated) {
                activity()
                    ->causedBy($user)
                    ->event('Register')
                    ->log('User registered with Google');
            } else {
                activity()
                    ->causedBy($user)
                    ->event('Login')
                    ->log('User logged in with Google');
            }

            if ($user->status === 'inactive') {
                Auth::logout();

                throw ValidationException::withMessages([
                    'email' => 'You account was banned.'
                ]);
            }

            Auth::login($user);

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());

            if($user->status == "Banned"){
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
