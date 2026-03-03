<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $user = Auth::user();
        if ($user->is_disabled ?? false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => __('This account has been disabled.')]);
        }

        $user->update(['last_login_at' => now()]);
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Post-login redirect path. System admin → system dashboard; others → tenant dashboard.
     */
    protected function redirectPath(): string
    {
        $user = auth()->user();

        if ($user->is_system_admin) {
            return route('system.dashboard');
        }

        return route('dashboard');
    }
}
