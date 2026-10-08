<?php

namespace App\Http\Middleware;

use App\Services\GuardSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(private readonly GuardSessionService $sessions) {}

    /**
     * Signs out a user who was blocked while their session was still alive.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBlocked()) {
            $this->sessions->logout($request, 'web');

            return redirect()->route('login')->withErrors(['email' => 'Your account has been blocked. Please contact support.']);
        }

        return $next($request);
    }
}
