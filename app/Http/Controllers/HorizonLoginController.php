<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HorizonLoginController extends Controller
{
    /**
     * Show the Horizon login page.
     */
    public function showLoginForm(Request $request)
    {
        // If user is already authenticated via web session, redirect to Horizon
        if (Auth::guard('web')->check()) {
            return redirect()->intended(config('horizon.path', 'horizon'));
        }

        return view('horizon.login');
    }

    /**
     * Handle Horizon login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->withInput($request->only('email'));
        }

        if ($user->status !== \App\Enums\UserStatus::Inactive && $user->status !== \App\Enums\UserStatus::Active) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->withInput($request->only('email'));
        }

        // Update user status if needed
        if ($user->type === \App\Enums\UserType::Unknown) {
            $user->type = \App\Enums\UserType::General;
        }
        if ($user->status === \App\Enums\UserStatus::Inactive) {
            $user->status = \App\Enums\UserStatus::Active;
        }
        $user->logged_at = now();
        $user->save();

        // Create session for web authentication (Sanctum will use this session)
        Auth::guard('web')->login($user, $request->boolean('remember'));

        // Log session
        \Illuminate\Support\Facades\DB::table('user_sessions')->insert([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => 'ورود',
            'type' => 1,
            'last_activity' => now(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(config('horizon.path', 'horizon'));
    }
}
