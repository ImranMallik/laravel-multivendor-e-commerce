<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsApproved
{
    /**
     * Vendors that are pending, rejected or suspended may only see the status page.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = $request->user()?->vendor;

        abort_unless($vendor, 403);

        if (! $vendor->isApproved()) {
            return redirect()->route('vendor.status');
        }

        return $next($request);
    }
}
