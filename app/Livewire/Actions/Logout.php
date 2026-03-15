<?php

declare(strict_types=1);

namespace App\Livewire\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;

class Logout
{
    public function __invoke(?StatefulGuard $guard = null): void
    {
        ($guard ?? Auth::guard('web'))->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }
}
