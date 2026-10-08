<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuardSessionService
{
    /**
     * Log one guard out without disturbing the other.
     *
     * The admin and customer guards share one session cookie, so blindly invalidating the
     * session on logout would also sign the other guard out. The session is invalidated only
     * when no other guard is still logged in; otherwise it is regenerated.
     */
    public function logout(Request $request, string $guard): void
    {
        Auth::guard($guard)->logout();

        $stillLoggedIn = collect(['web', 'admin'])
            ->reject(fn (string $other) => $other === $guard)
            ->contains(fn (string $other) => Auth::guard($other)->check());

        if ($stillLoggedIn) {
            $request->session()->regenerate();
        } else {
            $request->session()->invalidate();
        }

        $request->session()->regenerateToken();
    }
}
