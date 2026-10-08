<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StatusController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $vendor = auth('web')->user()->vendor;

        abort_unless($vendor, 403);

        // Approved vendors have nothing to wait for.
        if ($vendor->isApproved()) {
            return redirect()->route('vendor.dashboard');
        }

        return view('vendor.status', ['vendor' => $vendor]);
    }
}
