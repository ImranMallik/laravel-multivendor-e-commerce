<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasShop
{
    /**
     * The vendor area belongs to users that own a shop record (any status); everyone else is
     * sent to the "Become a Seller" page. Whether the shop is approved is checked by vendor.approved.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasShop()) {
            return redirect()->route('account.seller.show');
        }

        return $next($request);
    }
}
