<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsApproved
{
    /**
     * Pending, rejected and suspended shops are sent to their status on the "Become a Seller" page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->vendor?->isApproved() !== true) {
            return redirect()->route('account.seller.show');
        }

        return $next($request);
    }
}
