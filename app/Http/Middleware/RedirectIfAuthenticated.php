<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Send already signed-in users to their own area: admin guard to the admin
     * dashboard, web guard to the customer or vendor dashboard by role.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect($this->redirectPath($guard));
            }
        }

        return $next($request);
    }

    private function redirectPath(?string $guard): string
    {
        return $guard === 'admin'
            ? route('admin.dashboard')
            : Auth::guard($guard)->user()->homeRoute();
    }
}
