<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreConfirmPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfirmPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.confirm-password');
    }

    public function store(StoreConfirmPasswordRequest $request): RedirectResponse
    {

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('dashboard'));
    }
}
